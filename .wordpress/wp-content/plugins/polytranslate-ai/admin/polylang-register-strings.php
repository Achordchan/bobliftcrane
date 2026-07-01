<?php
if (!defined('ABSPATH')) exit;

  class PTAI_Register_Strings
  {
    private $strings = [];
    private $map     = [];

    public function __construct()
    {
      add_action('init',       [$this, 'load'], 1);
      add_action('admin_init', [$this, 'register_strings']);
      add_action('update_option_polytranslate_ai_strings', [$this, 'clear_cache']);

      // Clear map cache when Polylang string translations are saved
      add_action('pll_save_strings_translations', [$this, 'clear_map_cache']);

      // Fallback: Polylang stores translations in this option
      add_action('update_option_pll_string_translations', [$this, 'clear_map_cache']);
    }

    // ── Cache management ──────────────────────────────────────────────────────────

    // Clears everything — called when your own plugin option changes
    public function clear_cache()
    {
      delete_transient('ptai_strings');
      $this->clear_map_cache();
    }

    // Clears only per-language translation maps — called when Polylang translations change
    public function clear_map_cache()
    {
      if (function_exists('pll_languages_list')) {
        foreach (pll_languages_list() as $lang) {
          delete_transient('ptai_map_' . $lang);
        }
      }
    }

    // ── Boot ──────────────────────────────────────────────────────────────────────

    public function load()
    {
      if (!function_exists('pll__')) return;

      // Skip default language entirely — nothing to replace
      if (
        function_exists('pll_current_language') &&
        function_exists('pll_default_language') &&
        pll_current_language() === pll_default_language()
      ) return;

      // Skip wp-admin (but NOT ajax — we handle that smartly in replace())
      if (is_admin() && !wp_doing_ajax()) return;

      // Skip REST API — JSON responses should never be string-replaced
      if (defined('REST_REQUEST') && REST_REQUEST) return;

      // ── Strings: transient-cached ───────────────────────────────────────────
      $this->strings = get_transient('ptai_strings');

      if ($this->strings === false) {
        $option = get_option('polytranslate_ai_strings', []);
        $raw    = array_values(
          array_filter(array_map('trim', (array)($option['translatable_strings'] ?? [])))
        );

        // Sort longest-first to prevent partial replacements
        // e.g. "Read" must not replace inside "Read more"
        usort($raw, static fn($a, $b) => strlen($b) - strlen($a));

        $this->strings = $raw;
        set_transient('ptai_strings', $this->strings, HOUR_IN_SECONDS);
      }

      if (empty($this->strings)) return;

      // ── Translation map: transient-cached per language ──────────────────────
      $lang      = pll_current_language();
      $cache_key = 'ptai_map_' . $lang;
      $this->map = get_transient($cache_key);

      if ($this->map === false) {
        $this->map = [];
        foreach ($this->strings as $s) {
          $t = pll__($s);
          if ($t !== $s) $this->map[$s] = $t;
        }
        set_transient($cache_key, $this->map, HOUR_IN_SECONDS);
      }

      if (empty($this->map)) return;

      // ── Start buffer ────────────────────────────────────────────────────────
      // Works for both full page loads AND AJAX requests (Elementor popups,
      // WooCommerce cart, infinite scroll, etc.)
      // The replace() method detects JSON AJAX responses and skips them safely.
      add_action('template_redirect', [$this, 'start_buffer'], 0);
      add_action('wp_ajax_nopriv_heartbeat', '__return_false', 0); // never buffer heartbeat
      if (wp_doing_ajax()) {
        $this->start_buffer(); // AJAX fires before template_redirect, start immediately
      }
    }

    // ── Register strings in Polylang UI ──────────────────────────────────────────

    public function register_strings()
    {
      if (!function_exists('pll_register_string')) return;

      // Re-read strings directly here since admin_init fires before load()
      $option  = get_option('polytranslate_ai_strings', []);
      $strings = array_values(
        array_filter(array_map('trim', (array)($option['translatable_strings'] ?? [])))
      );

      foreach ($strings as $s) {
        pll_register_string($s, $s, 'polytranslate-ai');
      }
    }

    // ── Output buffer ─────────────────────────────────────────────────────────────

    public function start_buffer()
    {
      // Prevent stacking on top of ob_gzhandler — would corrupt compressed output
      $handlers = ob_list_handlers();
      if (in_array('ob_gzhandler', $handlers, true)) {
        ob_end_clean(); // close gzip buffer, we'll re-apply after ours
        ob_start([$this, 'replace']);
        ob_start('ob_gzhandler');
      } else {
        ob_start([$this, 'replace']);
      }
    }

    public function replace($html)
    {
      if (empty($html)) return $html;

      // ── AJAX safety: skip pure JSON responses ─────────────────────────────────
      // Page builders send HTML fragments via AJAX (Elementor popups, WC cart).
      // Pure JSON API responses must never be string-replaced.
      // Fastest check: JSON always starts with { or [ after optional whitespace.
      if (wp_doing_ajax()) {
        $trimmed = ltrim($html);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
          return $html; // It's JSON — bail immediately
        }
      }

      // ── Memory guard ─────────────────────────────────────────────────────────
      // Skip processing if page is extremely large (>2MB) to avoid memory spikes.
      // Adjust limit based on your server's memory_limit if needed.
      if (strlen($html) > 2097152) return $html;

      // ── Quick bail: check if any string exists in output ─────────────────────
      $found = false;
      foreach ($this->map as $original => $_) {
        if (str_contains($html, $original)) {
          $found = true;
          break;
        }
      }
      if (!$found) return $html;

      // ── Protect <script> and <style> blocks ──────────────────────────────────
      // Skips regex on small outputs (<10KB) where false positives are unlikely
      $placeholders = [];

      if (strlen($html) > 10240) {
        $i    = 0;
        $html = preg_replace_callback(
          '#<(script|style)[^>]*>.*?</\1>#si',
          static function ($m) use (&$placeholders, &$i) {
            $key              = "<!--PTAI_{$i}-->";
            $placeholders[$key] = $m[0];
            $i++;
            return $key;
          },
          $html
        );
      }

      // ── Single-pass replacement ───────────────────────────────────────────────
      // Keys are pre-sorted longest-first so "Read more" is replaced before "Read"
      $html = str_replace(array_keys($this->map), array_values($this->map), $html);

      // ── Restore protected blocks ──────────────────────────────────────────────
      if (!empty($placeholders)) {
        $html = str_replace(array_keys($placeholders), array_values($placeholders), $html);
      }

      return $html;
    }
  }

  new PTAI_Register_Strings();

