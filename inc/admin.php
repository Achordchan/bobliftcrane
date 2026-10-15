<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_admin_menu(): void
{
    add_menu_page('BOB-LIFT', 'BOB-LIFT', 'manage_options', 'boblift-global-settings', 'boblift_global_settings_page', 'dashicons-admin-generic', 58);
    add_submenu_page('boblift-global-settings', '全局设置', '全局设置', 'manage_options', 'boblift-global-settings', 'boblift_global_settings_page');
    add_submenu_page('boblift-global-settings', '导航菜单', '导航菜单', 'manage_options', 'boblift-navigation-settings', 'boblift_navigation_settings_page');
    add_submenu_page('boblift-global-settings', '页脚设置', '页脚设置', 'manage_options', 'boblift-footer-settings', 'boblift_footer_settings_page');
    add_submenu_page('boblift-global-settings', 'News 分类', 'News 分类', 'manage_options', 'boblift-news-settings', 'boblift_news_settings_page');
    add_submenu_page('boblift-global-settings', '表单安全', '表单安全', 'manage_options', 'boblift-form-security-settings', 'boblift_form_security_settings_page');
}
add_action('admin_menu', 'boblift_admin_menu');

function boblift_admin_assets(string $hook): void
{
    $settings_pages = ['boblift-global-settings', 'boblift-navigation-settings', 'boblift-footer-settings', 'boblift-news-settings', 'boblift-form-security-settings'];
    $is_settings_page = isset($_GET['page']) && in_array((string) $_GET['page'], $settings_pages, true);
    $is_about_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_about_page_post((int) $_GET['post']);
    $is_social_media_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_social_media_page_post((int) $_GET['post']);
    $is_global_customer_visits_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_global_customer_visits_page_post((int) $_GET['post']);
    $is_public_welfare_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_public_welfare_page_post((int) $_GET['post']);
    $is_privacy_policy_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_privacy_policy_page_post((int) $_GET['post']);
    $is_terms_of_use_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_terms_of_use_page_post((int) $_GET['post']);
    $is_sitemap_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_sitemap_page_post((int) $_GET['post']);
    $is_home_editor = $hook === 'post.php' && isset($_GET['post']) && boblift_is_home_page_post((int) $_GET['post']);
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_product_category_editor = $screen && ($screen->taxonomy ?? '') === 'product_category';
    if (!$is_settings_page && !$is_about_editor && !$is_social_media_editor && !$is_global_customer_visits_editor && !$is_public_welfare_editor && !$is_privacy_policy_editor && !$is_terms_of_use_editor && !$is_sitemap_editor && !$is_home_editor && !$is_product_category_editor) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', <<<'JS'
(function ($) {
  $(document).on('click', '.boblift-media-select', function (event) {
    event.preventDefault();
    var field = $(this).closest('.boblift-media-field');
    var frame = wp.media({
      title: '选择图片',
      button: { text: '使用这张图片' },
      multiple: false
    });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      field.find('.boblift-media-id').val(attachment.id || 0);
      field.find('.boblift-media-path-value').val(attachment.url || '');
      var preview = field.find('.boblift-media-preview');
      if (preview.is('img')) {
        preview.attr('src', attachment.url || '').removeClass('is-hidden');
      } else {
        preview.text(attachment.filename || attachment.url || '已选择媒体').removeClass('is-hidden');
      }
    });
    frame.open();
  });

  $(document).on('click', '.boblift-media-clear', function (event) {
    event.preventDefault();
    var field = $(this).closest('.boblift-media-field');
    field.find('.boblift-media-id').val('0');
    field.find('.boblift-media-path-value').val(field.data('fallback') || '');
    var preview = field.find('.boblift-media-preview');
    if (preview.is('img')) {
      preview.attr('src', field.data('fallback') || '').removeClass('is-hidden');
    } else {
      preview.text(field.data('fallback-label') || '使用默认媒体').removeClass('is-hidden');
    }
  });
})(jQuery);
JS);
    wp_add_inline_script('jquery', <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-customer-visits-editor]').forEach(function (editor) {
    var list = editor.querySelector('[data-customer-visits-list]');
    var template = editor.querySelector('template[data-customer-visit-template]');
    var addButton = editor.querySelector('[data-customer-visit-add]');
    if (!list || !template || !addButton) {
      return;
    }

    function reindex() {
      list.querySelectorAll('[data-customer-visit-row]').forEach(function (row, index) {
        row.dataset.visitIndex = String(index);
        var title = row.querySelector('[data-customer-visit-title]');
        if (title) {
          title.textContent = '客户来访图片 ' + String(index + 1);
        }
        row.querySelectorAll('[name]').forEach(function (field) {
          field.name = field.name.replace(/\[items]\[[^\]]+]/, '[items][' + index + ']');
        });
      });
    }

    addButton.addEventListener('click', function () {
      var index = list.querySelectorAll('[data-customer-visit-row]').length;
      var html = template.innerHTML.replace(/__INDEX__/g, String(index));
      var wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      var row = wrapper.firstElementChild;
      if (row) {
        list.appendChild(row);
        reindex();
      }
    });

    list.addEventListener('click', function (event) {
      var button = event.target.closest('[data-customer-visit-remove]');
      if (!button) {
        return;
      }
      event.preventDefault();
      var row = button.closest('[data-customer-visit-row]');
      if (row) {
        row.remove();
        reindex();
      }
    });

    reindex();
  });

  document.querySelectorAll('[data-about-certifications-editor]').forEach(function (editor) {
    var list = editor.querySelector('[data-about-certifications-list]');
    var template = editor.querySelector('template[data-about-certification-template]');
    var addButton = editor.querySelector('[data-about-certification-add]');
    if (!list || !template || !addButton) {
      return;
    }

    function reindexCertifications() {
      list.querySelectorAll('[data-about-certification-row]').forEach(function (row, index) {
        row.dataset.certificationIndex = String(index);
        var title = row.querySelector('[data-about-certification-title]');
        if (title) {
          title.textContent = '认证图片 ' + String(index + 1);
        }
        row.querySelectorAll('[name]').forEach(function (field) {
          field.name = field.name.replace(/\[certifications\]\[items\]\[[^\]]+\]/, '[certifications][items][' + index + ']');
        });
      });
    }

    addButton.addEventListener('click', function () {
      var index = list.querySelectorAll('[data-about-certification-row]').length;
      var html = template.innerHTML.replace(/__INDEX__/g, String(index));
      var wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      var row = wrapper.firstElementChild;
      if (row) {
        list.appendChild(row);
        reindexCertifications();
      }
    });

    list.addEventListener('click', function (event) {
      var button = event.target.closest('[data-about-certification-remove]');
      if (!button) {
        return;
      }
      event.preventDefault();
      var row = button.closest('[data-about-certification-row]');
      if (row) {
        row.remove();
        reindexCertifications();
      }
    });

    reindexCertifications();
  });
});
JS);
    wp_add_inline_script('jquery', <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
  var builder = document.querySelector('[data-nav-builder]');
  if (!builder) {
    return;
  }

  var navList = builder.querySelector('[data-nav-list]');
  var parking = builder.querySelector('[data-product-groups-parking]');
  // The group editor exists only while a saved custom product menu is active; without it an empty stand-in keeps the handlers inert.
  var editor = builder.querySelector('[data-product-groups-editor]') || document.createElement('div');
  var navTemplate = builder.querySelector('template[data-template="nav-row"]');
  var groupTemplate = builder.querySelector('template[data-template="group-row"]');
  var linkTemplate = builder.querySelector('template[data-template="link-row"]');

  function setDisabled(root, disabled) {
    root.querySelectorAll('input, textarea, select, button').forEach(function (field) {
      field.disabled = disabled;
    });
  }

  function replacePlaceholders(fragment, values) {
    fragment.querySelectorAll('[name]').forEach(function (field) {
      var name = field.getAttribute('name');
      Object.keys(values).forEach(function (key) {
        name = name.split(key).join(values[key]);
      });
      field.setAttribute('name', name);
    });
  }

  function rowLabel(row, type, fallback) {
    var input = row.querySelector('input[name$="[' + (type === 'group' ? 'title' : 'label') + ']"]');
    var value = input ? input.value.trim() : '';
    return value || fallback;
  }

  function reindexNav() {
    navList.querySelectorAll('.boblift-admin-nav-row').forEach(function (row, index) {
      row.querySelector('[data-role="nav-index"]').textContent = String(index + 1);
      row.querySelector('[data-role="nav-label"]').textContent = rowLabel(row, 'nav', '导航项 ' + String(index + 1));
      row.querySelectorAll('[name]').forEach(function (field) {
        field.name = field.name.replace(/boblift_theme_options\[nav_items]\[[^\]]+]/, 'boblift_theme_options[nav_items][' + index + ']');
      });
    });
    placeProductEditor();
  }

  function reindexGroups() {
    var groups = editor.querySelectorAll('.boblift-admin-product-group-row');
    groups.forEach(function (row, groupIndex) {
      row.querySelector('[data-role="group-index"]').textContent = String(groupIndex + 1);
      var label = row.querySelector('[data-role="group-label"]');
      var count = row.querySelectorAll('.boblift-admin-product-link-row').length;
      label.childNodes[0].nodeValue = rowLabel(row, 'group', '二级菜单组 ' + String(groupIndex + 1));
      row.querySelector('[data-role="link-count"]').textContent = '（子链接：' + count + ' 条）';
      row.querySelectorAll('[name]').forEach(function (field) {
        field.name = field.name.replace(/boblift_theme_options\[product_nav_groups]\[[^\]]+]/, 'boblift_theme_options[product_nav_groups][' + groupIndex + ']');
      });
      reindexLinks(row, groupIndex);
    });
  }

  function reindexLinks(groupRow, groupIndex) {
    groupRow.querySelectorAll('.boblift-admin-product-link-row').forEach(function (row, linkIndex) {
      row.querySelector('[data-role="link-index"]').textContent = String(groupIndex + 1) + '.' + String(linkIndex + 1);
      row.querySelector('[data-role="link-label"]').textContent = rowLabel(row, 'link', '子链接 ' + String(linkIndex + 1));
      row.querySelectorAll('[name]').forEach(function (field) {
        field.name = field.name
          .replace(/boblift_theme_options\[product_nav_groups]\[[^\]]+]/, 'boblift_theme_options[product_nav_groups][' + groupIndex + ']')
          .replace(/\[links]\[[^\]]+]/, '[links][' + linkIndex + ']');
      });
    });
    var count = groupRow.querySelector('[data-role="link-count"]');
    if (count) {
      count.textContent = '（子链接：' + groupRow.querySelectorAll('.boblift-admin-product-link-row').length + ' 条）';
    }
  }

  function checkedNavRows() {
    return Array.from(navList.querySelectorAll('.boblift-admin-nav-row')).filter(function (row) {
      var checkbox = row.querySelector('input[name$="[has_menu]"][type="checkbox"]');
      return checkbox && checkbox.checked;
    });
  }

  function placeProductEditor(targetRow) {
    var rows = checkedNavRows();
    var active = targetRow && rows.indexOf(targetRow) !== -1 ? targetRow : rows[0];
    navList.querySelectorAll('[data-menu-slot]').forEach(function (slot) {
      slot.hidden = true;
    });
    if (active) {
      var slot = active.querySelector('[data-menu-slot]');
      slot.hidden = false;
      slot.appendChild(editor);
      parking.hidden = true;
      setDisabled(editor, false);
    } else {
      parking.appendChild(editor);
      parking.hidden = false;
      setDisabled(editor, true);
    }
  }

  function addFromTemplate(template, values) {
    var fragment = template.content.cloneNode(true);
    replacePlaceholders(fragment, values);
    return fragment.firstElementChild;
  }

  builder.addEventListener('click', function (event) {
    var button = event.target.closest('[data-menu-action]');
    if (!button) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    var action = button.dataset.menuAction;
    var row = button.closest('.boblift-admin-menu-row');
    if (action === 'add-nav') {
      navList.appendChild(addFromTemplate(navTemplate, {'__NAV_INDEX__': String(navList.querySelectorAll('.boblift-admin-nav-row').length)}));
      reindexNav();
      return;
    }
    if (action === 'add-group') {
      editor.querySelector('[data-group-list]').appendChild(addFromTemplate(groupTemplate, {'__GROUP_INDEX__': String(editor.querySelectorAll('.boblift-admin-product-group-row').length)}));
      reindexGroups();
      return;
    }
    if (action === 'add-link') {
      var groupRow = button.closest('.boblift-admin-product-group-row');
      var groupIndex = Array.from(editor.querySelectorAll('.boblift-admin-product-group-row')).indexOf(groupRow);
      var linkIndex = groupRow.querySelectorAll('.boblift-admin-product-link-row').length;
      groupRow.querySelector('[data-link-list]').appendChild(addFromTemplate(linkTemplate, {'__GROUP_INDEX__': String(groupIndex), '__LINK_INDEX__': String(linkIndex)}));
      reindexLinks(groupRow, groupIndex);
      return;
    }
    if (!row) {
      return;
    }
    if (action === 'delete') {
      row.remove();
    } else if (action === 'up' && row.previousElementSibling && row.previousElementSibling.classList.contains('boblift-admin-menu-row')) {
      row.parentNode.insertBefore(row, row.previousElementSibling);
    } else if (action === 'down' && row.nextElementSibling) {
      row.parentNode.insertBefore(row.nextElementSibling, row);
    }
    reindexNav();
    reindexGroups();
  });

  builder.addEventListener('change', function (event) {
    if (event.target.matches('input[name$="[has_menu]"][type="checkbox"]')) {
      placeProductEditor(event.target.closest('.boblift-admin-nav-row'));
    }
  });

  builder.addEventListener('input', function (event) {
    if (event.target.matches('input[name$="[label]"], input[name$="[title]"]')) {
      reindexNav();
      reindexGroups();
    }
  });

  reindexNav();
  reindexGroups();
});
JS);
    wp_add_inline_script('jquery', <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-floating-contact-editor]').forEach(function (editor) {
    var list = editor.querySelector('[data-floating-contact-list]');
    var template = editor.querySelector('template[data-template="floating-contact-row"]');
    if (!list || !template) {
      return;
    }

    function rowLabel(row, index) {
      var label = row.querySelector('input[name$="[label]"]');
      var type = row.querySelector('select[name$="[type]"]');
      var value = label && label.value.trim() ? label.value.trim() : '';
      if (!value && type && type.selectedOptions[0]) {
        value = type.selectedOptions[0].textContent.trim();
      }
      return value || '客服按钮 ' + String(index + 1);
    }

    function reindexRows() {
      list.querySelectorAll('[data-floating-contact-row]').forEach(function (row, index) {
        row.querySelector('[data-role="floating-index"]').textContent = String(index + 1);
        row.querySelector('[data-role="floating-label"]').textContent = rowLabel(row, index);
        row.querySelectorAll('[name]').forEach(function (field) {
          field.name = field.name.replace(/boblift_theme_options\[floating_contact_buttons]\[[^\]]+]/, 'boblift_theme_options[floating_contact_buttons][' + index + ']');
        });
      });
    }

    function addRow() {
      var fragment = template.content.cloneNode(true);
      var row = fragment.firstElementChild;
      list.appendChild(row);
      reindexRows();
      row.open = true;
    }

    editor.addEventListener('click', function (event) {
      var button = event.target.closest('[data-floating-action]');
      if (!button) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      var action = button.dataset.floatingAction;
      var row = button.closest('[data-floating-contact-row]');
      if (action === 'add') {
        addRow();
        return;
      }
      if (!row) {
        return;
      }
      if (action === 'delete') {
        row.remove();
      } else if (action === 'up' && row.previousElementSibling && row.previousElementSibling.matches('[data-floating-contact-row]')) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
      } else if (action === 'down' && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
      }
      reindexRows();
    });

    editor.addEventListener('input', function (event) {
      if (event.target.matches('input[name$="[label]"]')) {
        reindexRows();
      }
    });
    editor.addEventListener('change', function (event) {
      if (event.target.matches('select[name$="[type]"]')) {
        reindexRows();
      }
    });

    reindexRows();
  });
});
JS);
}
add_action('admin_enqueue_scripts', 'boblift_admin_assets');

function boblift_register_settings(): void
{
    register_setting('boblift_settings', 'boblift_theme_options', [
        'type' => 'array',
        'sanitize_callback' => 'boblift_sanitize_options',
        'default' => boblift_default_options(),
    ]);
}
add_action('admin_init', 'boblift_register_settings');

function boblift_admin_current_language(): string
{
    $language = function_exists('boblift_mls_current_language') ? (string) boblift_mls_current_language() : 'en';
    if (isset($_REQUEST['boblift_admin_language'])) {
        $language = sanitize_key((string) wp_unslash($_REQUEST['boblift_admin_language']));
    }
    if ($language === '' || $language === 'all') {
        $language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
    }
    $languages = function_exists('boblift_mls_languages') ? boblift_mls_languages() : [$language];
    return in_array($language, $languages, true) ? $language : 'en';
}

function boblift_admin_language_option_key(string $language): string
{
    return function_exists('boblift_mls_option_key') ? boblift_mls_option_key($language) : 'boblift_theme_options';
}

function boblift_admin_raw_options_for_language(string $language): array
{
    $options = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    return is_array($options) ? $options : [];
}

function boblift_sanitize_options(mixed $value): array
{
    $language = boblift_admin_current_language();
    if (!is_array($value)) {
        return boblift_admin_raw_options_for_language($language);
    }
    $sanitized = boblift_sanitize_options_recursive($value);
    $sanitized = boblift_cleanup_admin_options($sanitized);
    return boblift_array_replace_recursive(boblift_admin_raw_options_for_language($language), $sanitized);
}

function boblift_handle_save_theme_settings(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('权限不足。', 'bobliftcrane'));
    }
    check_admin_referer('boblift_save_theme_settings');

    $language = boblift_admin_current_language();
    $posted = isset($_POST['boblift_theme_options']) && is_array($_POST['boblift_theme_options']) ? wp_unslash($_POST['boblift_theme_options']) : [];
    $posted = is_array($posted) ? $posted : [];
    if (isset($_POST['boblift_nav_items_present']) && !isset($posted['nav_items'])) {
        $posted['nav_items'] = [];
    }
    // "Reset to the generated Products menu": drop the curated groups by saving the built-in default list again.
    if (isset($_POST['boblift_reset_product_menu'])) {
        $posted['product_nav_groups'] = boblift_default_options()['product_nav_groups'] ?? [];
    }
    if (isset($_POST['boblift_product_nav_groups_present']) && !isset($posted['product_nav_groups'])) {
        $posted['product_nav_groups'] = [];
    }
    if (isset($_POST['boblift_floating_contact_buttons_present']) && !isset($posted['floating_contact_buttons'])) {
        $posted['floating_contact_buttons'] = [];
    }

    $sanitized = boblift_cleanup_admin_options(boblift_sanitize_options_recursive($posted));
    if (isset($sanitized['form_security'])) {
        $source_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
        $source_options = boblift_admin_raw_options_for_language($source_language);
        $source_options['form_security'] = is_array($sanitized['form_security']) ? $sanitized['form_security'] : [];
        if (function_exists('boblift_mls_update_language_options')) {
            boblift_mls_update_language_options($source_options, $source_language);
        } else {
            update_option('boblift_theme_options', $source_options);
        }
        unset($sanitized['form_security']);
    }

    if ($sanitized !== []) {
        $options = boblift_array_replace_recursive(boblift_admin_raw_options_for_language($language), $sanitized);
        if (function_exists('boblift_mls_update_language_options')) {
            boblift_mls_update_language_options($options, $language);
        } else {
            update_option('boblift_theme_options', $options);
        }
    }

    $page = isset($_POST['boblift_admin_page']) ? sanitize_key((string) wp_unslash($_POST['boblift_admin_page'])) : 'boblift-global-settings';
    wp_safe_redirect(add_query_arg(['page' => $page, 'lang' => $language, 'settings-updated' => 'true'], admin_url('admin.php')));
    exit;
}
add_action('admin_post_boblift_save_theme_settings', 'boblift_handle_save_theme_settings');

function boblift_admin_value_has_content(mixed $value): bool
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (boblift_admin_value_has_content($item)) {
                return true;
            }
        }
        return false;
    }
    $value = trim((string) $value);
    return $value !== '' && $value !== '0';
}

function boblift_cleanup_admin_options(array $options): array
{
    if (isset($options['nav_items']) && is_array($options['nav_items'])) {
        $options['nav_items'] = array_values(array_filter($options['nav_items'], static fn($row): bool => is_array($row) && (boblift_admin_value_has_content($row['label'] ?? '') || boblift_admin_value_has_content($row['href'] ?? ''))));
    }

    if (isset($options['product_nav_groups']) && is_array($options['product_nav_groups'])) {
        $groups = [];
        foreach ($options['product_nav_groups'] as $group) {
            if (!is_array($group)) {
                continue;
            }
            if (isset($group['links']) && is_array($group['links'])) {
                $group['links'] = array_values(array_filter($group['links'], static fn($row): bool => is_array($row) && (boblift_admin_value_has_content($row['label'] ?? '') || boblift_admin_value_has_content($row['href'] ?? ''))));
            }
            if (boblift_admin_value_has_content($group['title'] ?? '') || boblift_admin_value_has_content($group['href'] ?? '') || boblift_admin_value_has_content($group['links'] ?? [])) {
                $groups[] = $group;
            }
        }
        $options['product_nav_groups'] = $groups;
    }

    if (isset($options['news_categories']) && is_array($options['news_categories'])) {
        $rows = [];
        foreach ($options['news_categories'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $rows[] = ['slug' => $slug];
            }
        }
        $options['news_categories'] = $rows;
    }

    if (isset($options['floating_contact_buttons']) && is_array($options['floating_contact_buttons'])) {
        $rows = [];
        $allowed_types = array_keys(boblift_floating_contact_type_choices());
        $allowed_icons = array_keys(boblift_floating_contact_icon_choices());
        foreach ($options['floating_contact_buttons'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = sanitize_key((string) ($row['type'] ?? 'custom'));
            if (!in_array($type, $allowed_types, true)) {
                $type = 'custom';
            }
            $label = trim((string) ($row['label'] ?? ''));
            $href = trim((string) ($row['href'] ?? ''));
            if ($type === 'custom' && $label === '' && $href === '') {
                continue;
            }
            $icon = sanitize_key((string) ($row['icon'] ?? $type));
            if (!in_array($icon, $allowed_icons, true)) {
                $icon = $type === 'telegram' ? 'telegram' : 'arrow-right';
            }
            $rows[] = [
                'enabled' => !empty($row['enabled']) && (string) $row['enabled'] !== '0' ? '1' : '0',
                'type' => $type,
                'label' => $label,
                'href' => $href,
                'icon' => $icon,
                'target' => !empty($row['target']) && (string) $row['target'] !== '0' ? '_blank' : '',
            ];
        }
        $options['floating_contact_buttons'] = array_values($rows);
    }



    if (isset($options['about_page']) && is_array($options['about_page'])) {
        // headline, paragraph and proof_chips stay: a saved introduction is still shown (page-about.php) below the hero.
        foreach (['texture_image', 'label'] as $key) {
            unset($options['about_page']['logo'][$key]);
        }
        // strength.cards and the strength call to action stay: saved custom ones are still shown (page-about.php) and editable in the settings.
        foreach (['label', 'title', 'text'] as $key) {
            unset($options['about_page']['global_footprint'][$key]);
        }
        // The legacy team photo moves to team_image before its old key is dropped, so a saved custom image is never lost.
        if (!empty($options['about_page']['executives']['group_image']) && empty($options['about_page']['executives']['team_image'])) {
            $options['about_page']['executives']['team_image'] = $options['about_page']['executives']['group_image'];
        }
        unset($options['about_page']['executives']['group_image']);
    }

    if (isset($options['social_media_page']['hero']) && is_array($options['social_media_page']['hero'])) {
        // hero.title and hero.text stay: a customised old hero copy that differs from the new heading is still shown under the hero.
        foreach (['button_label', 'email_label'] as $key) {
            unset($options['social_media_page']['hero'][$key]);
        }
    }

    if (isset($options['social_media_page']) && is_array($options['social_media_page'])) {
        foreach (['featured_platforms', 'major_platforms', 'secondary_platforms'] as $platform_group) {
            if (!isset($options['social_media_page'][$platform_group]) || !is_array($options['social_media_page'][$platform_group])) {
                continue;
            }
            $rows = [];
            foreach ($options['social_media_page'][$platform_group] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $href = trim((string) ($row['href'] ?? ''));
                $icon = sanitize_key((string) ($row['icon'] ?? ''));
                if ($label === '' && $href === '' && $icon === '') {
                    continue;
                }
                $allowed_icons = function_exists('boblift_social_media_icon_choices') ? array_keys(boblift_social_media_icon_choices()) : [];
                if ($icon === '' || ($allowed_icons !== [] && !in_array($icon, $allowed_icons, true))) {
                    $icon = 'linkedin';
                }
                $row['enabled'] = !empty($row['enabled']) && (string) $row['enabled'] !== '0' ? '1' : '0';
                $row['label'] = $label;
                $row['href'] = $href;
                $row['icon'] = $icon;
                $rows[] = $row;
            }
            $options['social_media_page'][$platform_group] = array_values($rows);
        }
        if (isset($options['social_media_page']['content_cards']) && is_array($options['social_media_page']['content_cards'])) {
            $cards = [];
            foreach ($options['social_media_page']['content_cards'] as $card) {
                if (!is_array($card)) {
                    continue;
                }
                if (!boblift_admin_value_has_content($card['title'] ?? '') && !boblift_admin_value_has_content($card['text'] ?? '') && !boblift_admin_value_has_content($card['image'] ?? [])) {
                    continue;
                }
                $card['enabled'] = !empty($card['enabled']) && (string) $card['enabled'] !== '0' ? '1' : '0';
                $cards[] = $card;
            }
            $options['social_media_page']['content_cards'] = array_values($cards);
        }
        if (isset($options['social_media_page']['videos']) && is_array($options['social_media_page']['videos'])) {
            $options['social_media_page']['videos'] = array_values(array_filter($options['social_media_page']['videos'], static fn($row): bool => is_array($row) && (boblift_admin_value_has_content($row['video'] ?? []) || trim((string) ($row['youtube'] ?? '')) !== '')));
        }
        if (isset($options['social_media_page']['panel']['items']) && is_array($options['social_media_page']['panel']['items'])) {
            $options['social_media_page']['panel']['items'] = array_values(array_filter($options['social_media_page']['panel']['items'], static fn($row): bool => is_array($row) && boblift_admin_value_has_content($row['text'] ?? '')));
        }
    }

    if (isset($options['footer']) && is_array($options['footer'])) {
        foreach (['social_links', 'legal_links'] as $key) {
            if (isset($options['footer'][$key]) && is_array($options['footer'][$key])) {
                $options['footer'][$key] = array_values(array_filter($options['footer'][$key], static fn($row): bool => is_array($row) && (boblift_admin_value_has_content($row['label'] ?? '') || boblift_admin_value_has_content($row['href'] ?? ''))));
            }
        }
        if (isset($options['footer']['groups']) && is_array($options['footer']['groups'])) {
            $groups = [];
            foreach ($options['footer']['groups'] as $group) {
                if (!is_array($group)) {
                    continue;
                }
                if (isset($group['links']) && is_array($group['links'])) {
                    $group['links'] = array_values(array_filter($group['links'], static fn($row): bool => is_array($row) && (boblift_admin_value_has_content($row['label'] ?? '') || boblift_admin_value_has_content($row['href'] ?? ''))));
                }
                if (boblift_admin_value_has_content($group['title'] ?? '') || boblift_admin_value_has_content($group['links'] ?? [])) {
                    $groups[] = $group;
                }
            }
            $options['footer']['groups'] = $groups;
        }
    }

    return $options;
}

function boblift_sanitize_options_recursive(array $value): array
{
    $result = [];
    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $result[$key] = boblift_sanitize_options_recursive($item);
            continue;
        }
        if ($key === 'id') {
            $result[$key] = max(0, (int) $item);
            continue;
        }
        if (in_array($key, ['turnstile_enabled', 'turnstile_local_mode'], true)) {
            $result[$key] = (string) (int) !empty($item);
            continue;
        }
        $text = is_string($item) ? wp_unslash($item) : (string) $item;
        $result[$key] = in_array($key, ['text', 'paragraph'], true) ? sanitize_textarea_field($text) : sanitize_text_field($text);
    }
    return $result;
}

function boblift_is_about_page_post(int|WP_Post $post): bool
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return false;
    }
    if ($post->post_name === 'about' || get_page_template_slug($post) === 'page-about.php') {
        return true;
    }
    if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, 'about')) {
        return true;
    }

    return boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) === '/about.html';
}

function boblift_is_social_media_page_post(int|WP_Post $post): bool
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return false;
    }
    if ($post->post_name === 'social-media' || get_page_template_slug($post) === 'page-social-media.php') {
        return true;
    }
    if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, 'social-media')) {
        return true;
    }

    return boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) === '/social-media.html';
}

function boblift_is_global_customer_visits_page_post(int|WP_Post $post): bool
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return false;
    }
    if ($post->post_name === 'global-customer-visits' || get_page_template_slug($post) === 'page-global-customer-visits.php') {
        return true;
    }
    if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, 'global-customer-visits')) {
        return true;
    }

    return boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) === '/global-customer-visits.html';
}

function boblift_is_public_welfare_page_post(int|WP_Post $post): bool
{
    return boblift_is_page_for_controller($post, 'public-welfare', 'page-public-welfare.php', '/public-welfare.html');
}

function boblift_is_privacy_policy_page_post(int|WP_Post $post): bool
{
    return boblift_is_page_for_controller($post, 'privacy-policy', 'page-privacy-policy.php', '/privacy-policy.html');
}

function boblift_is_terms_of_use_page_post(int|WP_Post $post): bool
{
    return boblift_is_page_for_controller($post, 'terms-of-use', 'page-terms-of-use.php', '/terms-of-use.html');
}

function boblift_is_sitemap_page_post(int|WP_Post $post): bool
{
    return boblift_is_page_for_controller($post, 'sitemap', 'page-sitemap.php', '/sitemap.html');
}

function boblift_is_page_for_controller(int|WP_Post $post, string $slug, string $template, string $public_url): bool
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return false;
    }
    if ($post->post_name === $slug || get_page_template_slug($post) === $template) {
        return true;
    }
    if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, $slug)) {
        return true;
    }

    return boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) === $public_url;
}

function boblift_is_home_page_post(int|WP_Post $post): bool
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return false;
    }
    $front_page_id = (int) get_option('page_on_front');
    if (($front_page_id > 0 && (int) $post->ID === $front_page_id) || $post->post_name === 'home') {
        return true;
    }
    if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, 'home')) {
        return true;
    }

    return boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) === '/';
}

function boblift_custom_page_use_classic_editor(bool $use_block_editor, WP_Post $post): bool
{
    return boblift_is_controlled_page_post($post) ? false : $use_block_editor;
}
add_filter('use_block_editor_for_post', 'boblift_custom_page_use_classic_editor', 10, 2);

function boblift_is_controlled_page_post(int|WP_Post $post): bool
{
    return boblift_is_about_page_post($post)
        || boblift_is_social_media_page_post($post)
        || boblift_is_global_customer_visits_page_post($post)
        || boblift_is_public_welfare_page_post($post)
        || boblift_is_privacy_policy_page_post($post)
        || boblift_is_terms_of_use_page_post($post)
        || boblift_is_sitemap_page_post($post)
        || boblift_is_home_page_post($post);
}

function boblift_custom_page_editor_admin_head(): void
{
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($post_id <= 0 || !boblift_is_controlled_page_post($post_id)) {
        return;
    }
    echo '<style>#titlediv,#postdivrich{display:none!important}#post-body-content{margin-bottom:0}.boblift-admin-wrap{margin-top:0}</style><script>document.addEventListener("change",function(e){var picker=e.target.closest(".boblift-admin-link-picker");if(!picker||!picker.value){return;}var wrap=picker.closest(".boblift-admin-link-control");var input=wrap?wrap.querySelector(".boblift-admin-link-input"):null;if(input){input.value=picker.value;input.dispatchEvent(new Event("change",{bubbles:true}));}});</script>';
}
add_action('admin_head-post.php', 'boblift_custom_page_editor_admin_head');

function boblift_page_public_url_admin_value(WP_Post $post): string
{
    $saved = boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true));
    if ($saved !== '') {
        return $saved;
    }
    if ((int) get_option('page_on_front') === (int) $post->ID) {
        return '/';
    }
    return match ($post->post_name) {
        'products' => '/products.html',
        'cases' => '/cases.html',
        default => boblift_normalize_public_path('/' . $post->post_name . '.html'),
    };
}

function boblift_public_url_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_public_url_meta_box', 'boblift_public_url_nonce');
    $value = boblift_page_public_url_admin_value($post);
    echo '<p><label for="boblift-public-url"><strong>当前前台地址</strong></label></p>';
    echo '<input id="boblift-public-url" class="widefat" type="text" name="boblift_public_url" value="' . esc_attr($value) . '" placeholder="例如 /products.html">';
    echo '<p class="description">只填写站内根路径，不填写域名。这里的值就是前台主入口 URL。</p>';
}

function boblift_seo_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_seo_meta_box', 'boblift_seo_nonce');
    $title = trim((string) get_post_meta($post->ID, '_seo_title', true));
    $description = trim((string) get_post_meta($post->ID, '_seo_description', true));
    if ($title === '') {
        $title = boblift_seo_default_title_for_post($post);
    }
    if ($description === '') {
        $description = boblift_seo_default_description_for_post($post);
    }
    echo '<p><label for="boblift-seo-title"><strong>SEO 标题</strong></label></p>';
    echo '<input id="boblift-seo-title" class="widefat" type="text" name="boblift_seo_title" value="' . esc_attr($title) . '">';
    echo '<p><label for="boblift-seo-description"><strong>SEO 描述</strong></label></p>';
    echo '<textarea id="boblift-seo-description" class="widefat" rows="4" name="boblift_seo_description">' . esc_textarea($description) . '</textarea>';
    echo '<p class="description">前台 title 和 description 就读取这里保存的值。</p>';
}

function boblift_register_post_seo_meta_box(): void
{
    add_meta_box('boblift-post-seo-settings', 'SEO 设置', 'boblift_seo_meta_box', 'post', 'side', 'high');
}
add_action('add_meta_boxes_post', 'boblift_register_post_seo_meta_box');

function boblift_custom_page_meta_boxes(WP_Post $post): void
{
    add_meta_box('boblift-public-url-settings', '前台 URL', 'boblift_public_url_meta_box', 'page', 'side', 'high');
    add_meta_box('boblift-page-seo-settings', 'SEO 设置', 'boblift_seo_meta_box', 'page', 'side', 'high');
    if (boblift_is_about_page_post($post)) {
        add_meta_box('boblift-about-page-settings', 'BOB-LIFT 关于页设置', 'boblift_about_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_social_media_page_post($post)) {
        add_meta_box('boblift-social-media-page-settings', 'BOB-LIFT Social Media 页面设置', 'boblift_social_media_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_global_customer_visits_page_post($post)) {
        add_meta_box('boblift-global-customer-visits-page-settings', 'BOB-LIFT Global Customer Visits 页面设置', 'boblift_global_customer_visits_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_public_welfare_page_post($post)) {
        add_meta_box('boblift-public-welfare-page-settings', 'BOB-LIFT Public Welfare 页面设置', 'boblift_public_welfare_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_privacy_policy_page_post($post)) {
        add_meta_box('boblift-privacy-policy-page-settings', 'BOB-LIFT Privacy Policy 页面设置', 'boblift_privacy_policy_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_terms_of_use_page_post($post)) {
        add_meta_box('boblift-terms-of-use-page-settings', 'BOB-LIFT Terms of Use 页面设置', 'boblift_terms_of_use_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_sitemap_page_post($post)) {
        add_meta_box('boblift-sitemap-page-settings', 'BOB-LIFT Sitemap 页面设置', 'boblift_sitemap_page_meta_box', 'page', 'normal', 'high');
    }
    if (boblift_is_home_page_post($post)) {
        add_meta_box('boblift-home-page-settings', 'BOB-LIFT 首页设置', 'boblift_home_page_meta_box', 'page', 'normal', 'high');
    }
}
add_action('add_meta_boxes_page', 'boblift_custom_page_meta_boxes');

function boblift_about_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_about_page_meta_box', 'boblift_about_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 About 页面关键图片、标题、文案、时间线和高管信息。保存本页面后，前台 About 页面会同步更新。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'about_page');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $about = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('about_page', $language) : boblift_about_page_options();
    boblift_render_about_page_fields($about, 'boblift_about_page');
    echo '</div>';
}

function boblift_social_media_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_social_media_page_meta_box', 'boblift_social_media_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 Social Media 页面首屏、平台分组、真实图标、链接、内容卡片和 CTA。保存本页面后，前台 Social Media 页面会同步更新。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'social_media_page');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $social = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('social_media_page', $language) : boblift_social_media_page_options();
    boblift_render_social_media_page_fields($social, 'boblift_social_media_page');
    echo '</div>';
}

function boblift_global_customer_visits_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_global_customer_visits_page_meta_box', 'boblift_global_customer_visits_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 Global Customer Visits 独立页的标题、说明和客户来访参厂图片；首页客户来访展示也会读取同一组图片。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'about_page');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $about = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('about_page', $language) : boblift_about_page_options();
    $visits = is_array($about['customer_visits'] ?? null) ? $about['customer_visits'] : [];
    boblift_render_customer_visits_page_fields($visits, 'boblift_global_customer_visits_page');
    echo '</div>';
}

function boblift_public_welfare_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_public_welfare_page_meta_box', 'boblift_public_welfare_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 Public Welfare 页面的 Banner、主声明、责任卡片和底部 CTA。保存本页面后，前台页面会同步更新。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'public_welfare_page');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $options = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('public_welfare_page', $language) : boblift_public_welfare_page_options();
    boblift_render_public_welfare_page_fields($options, 'boblift_public_welfare_page');
    echo '</div>';
}

function boblift_privacy_policy_page_meta_box(WP_Post $post): void
{
    boblift_legal_page_meta_box($post, 'privacy_policy_page', 'boblift_privacy_policy_page', 'boblift_privacy_policy_page_nonce', 'boblift_privacy_policy_page_meta_box', 'Privacy Policy');
}

function boblift_terms_of_use_page_meta_box(WP_Post $post): void
{
    boblift_legal_page_meta_box($post, 'terms_of_use_page', 'boblift_terms_of_use_page', 'boblift_terms_of_use_page_nonce', 'boblift_terms_of_use_page_meta_box', 'Terms of Use');
}

function boblift_sitemap_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_sitemap_page_meta_box', 'boblift_sitemap_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 HTML Sitemap 页面的标题、区块标题和说明文案。链接列表由 WordPress 自动查询生成。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'sitemap_page');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $options = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('sitemap_page', $language) : boblift_sitemap_page_options();
    boblift_render_sitemap_page_fields($options, 'boblift_sitemap_page');
    echo '</div>';
}

function boblift_legal_page_meta_box(WP_Post $post, string $section, string $base, string $nonce_name, string $nonce_action, string $title): void
{
    wp_nonce_field($nonce_action, $nonce_name);
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护 ' . esc_html($title) . ' 严肃内容页的标题、说明、更新时间和正文条款。保存后前台页面会同步更新。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, $section);
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $fallback = $section === 'privacy_policy_page' ? boblift_privacy_policy_page_options() : boblift_terms_of_use_page_options();
    $options = function_exists('boblift_mls_section_options') ? boblift_mls_section_options($section, $language) : $fallback;
    boblift_render_legal_page_fields($options, $base, $section === 'privacy_policy_page');
    echo '</div>';
}

function boblift_admin_about_page_styles(): void
{
    echo '<style>.boblift-admin-wrap .form-table th{width:210px}.boblift-admin-section{margin:24px 0;padding:22px;border:1px solid #dcdcde;border-radius:14px;background:#fff}.boblift-admin-section h2{margin-top:0}.boblift-admin-grid{display:flex;max-width:1180px;flex-direction:column;gap:14px}.boblift-admin-panel{overflow:hidden;border:1px solid #dcdcde;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.02)}.boblift-admin-panel+.boblift-admin-panel{margin-top:16px}.boblift-admin-panel>summary{display:flex;align-items:center;gap:12px;cursor:pointer;list-style:none;padding:20px 22px;background:#f6f7f7;color:#3c434a;font-size:18px;font-weight:800;line-height:1.35}.boblift-admin-panel>summary::-webkit-details-marker{display:none}.boblift-admin-panel>summary:before{content:"";width:0;height:0;border-top:7px solid transparent;border-bottom:7px solid transparent;border-left:10px solid #3c434a;transition:transform .18s ease}.boblift-admin-panel[open]>summary{border-bottom:1px solid #dcdcde;background:#fff}.boblift-admin-panel[open]>summary:before{transform:rotate(90deg)}.boblift-admin-panel>summary:hover{background:#f0f0f1}.boblift-admin-panel>summary:focus{outline:2px solid #2271b1;outline-offset:-2px}.boblift-admin-panel-title{display:block}.boblift-admin-panel-description{display:block;margin-top:4px;color:#646970;font-size:13px;font-weight:500;line-height:1.5}.boblift-admin-panel-heading{display:block}.boblift-admin-panel-body{padding:20px 22px}.boblift-admin-card{padding:18px;border:1px solid #dcdcde;border-radius:14px;background:#fbfbfc}.boblift-admin-card+.boblift-admin-card{margin-top:14px}.boblift-admin-card h3{margin:0 0 16px;font-size:15px}.boblift-admin-subpanel{padding:0}.boblift-admin-subpanel>summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none;padding:16px 18px}.boblift-admin-subpanel>summary::-webkit-details-marker{display:none}.boblift-admin-subpanel>summary:before{content:"+";color:#8c8f94;font-size:18px;font-weight:700}.boblift-admin-subpanel[open]>summary{border-bottom:1px solid #dcdcde;background:#fff}.boblift-admin-subpanel[open]>summary:before{content:"−"}.boblift-admin-subpanel-title{flex:1;font-size:15px;font-weight:800;color:#1d2327}.boblift-admin-subpanel-meta{color:#646970;font-size:12px;font-weight:600}.boblift-admin-subpanel-body{padding:18px}.boblift-admin-field{display:grid;grid-template-columns:150px minmax(0,1fr);gap:12px;align-items:start;margin-bottom:14px}.boblift-admin-field label{margin-top:7px;font-weight:600}.boblift-admin-field input[type=text],.boblift-admin-field textarea,.boblift-admin-field select{width:100%;max-width:860px}.boblift-admin-field textarea{min-height:92px}.boblift-admin-checklist{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;max-width:920px}.boblift-admin-checkitem{display:flex;gap:9px;align-items:flex-start;padding:10px 12px;border:1px solid #dcdcde;border-radius:10px;background:#fff}.boblift-admin-checkitem span{font-weight:600;line-height:1.35}.boblift-admin-note{margin:0 0 14px;color:#646970}.boblift-media-field{grid-template-columns:150px 140px auto auto;align-items:center}.boblift-media-field label{margin:0}.boblift-media-preview{width:132px;height:82px;object-fit:cover;border:1px solid #dcdcde;border-radius:8px;background:#f6f7f7}.boblift-media-file-preview{display:flex;align-items:center;padding:8px;color:#50575e;font-size:12px;line-height:1.4;overflow:hidden}.boblift-media-help{grid-column:2/-1;margin:-6px 0 0;color:#646970;font-size:12px;line-height:1.6}.boblift-media-preview.is-hidden{display:none}.boblift-admin-link-control{display:grid;grid-template-columns:minmax(260px,520px) minmax(260px,360px);gap:10px;align-items:start;max-width:900px}.boblift-admin-link-control input,.boblift-admin-link-control select{width:100%;max-width:none}.boblift-admin-link-help{grid-column:1/-1;margin:0;color:#646970;font-size:12px}.boblift-admin-menu-table{overflow:hidden;border:1px solid #dcdcde;border-radius:14px;background:#fff}.boblift-admin-menu-head,.boblift-admin-menu-summary{display:grid;grid-template-columns:80px minmax(0,1fr);align-items:center}.boblift-admin-menu-head{background:#f6f7f7;color:#50575e;font-weight:700}.boblift-admin-menu-head span,.boblift-admin-menu-summary span{padding:14px 16px}.boblift-admin-menu-row{border-top:1px solid #f0f0f1}.boblift-admin-menu-row:first-of-type{border-top:0}.boblift-admin-menu-row>summary{display:grid;cursor:pointer;list-style:none;background:#fff}.boblift-admin-menu-row>summary::-webkit-details-marker{display:none}.boblift-admin-menu-row>summary:hover{background:#f6f7f7}.boblift-admin-menu-id{color:#646970;font-weight:700}.boblift-admin-menu-label{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:700;color:#1d2327}.boblift-admin-menu-label:before{content:"+";width:18px;color:#8c8f94;font-size:20px;font-weight:400;line-height:1}.boblift-admin-menu-row[open]>.boblift-admin-menu-summary .boblift-admin-menu-label:before{content:"−"}.boblift-admin-menu-count{color:#8c8f94;font-weight:500}.boblift-admin-menu-child .boblift-admin-menu-summary{grid-template-columns:80px minmax(0,1fr)}.boblift-admin-menu-child .boblift-admin-menu-label{padding-left:32px}.boblift-admin-menu-child .boblift-admin-menu-label:before{content:"└ +";width:auto}.boblift-admin-menu-child[open]>.boblift-admin-menu-summary .boblift-admin-menu-label:before{content:"└ −"}.boblift-admin-menu-body{padding:18px 22px;background:#fbfbfc;border-top:1px solid #f0f0f1}@media(max-width:782px){.boblift-admin-field,.boblift-media-field{display:block}.boblift-admin-field label{margin:0 0 6px}.boblift-media-preview{display:block;margin-bottom:10px}.boblift-media-help{margin:8px 0 0}.boblift-media-field .button{margin:0 8px 8px 0}}</style>';
    echo '<style>.boblift-admin-language-note{max-width:1180px}.boblift-admin-nav-builder{max-width:1180px}.boblift-admin-menu-head,.boblift-admin-menu-summary,.boblift-admin-menu-child .boblift-admin-menu-summary{grid-template-columns:80px minmax(0,1fr) 190px}.boblift-admin-menu-actions{display:flex;justify-content:flex-end;gap:6px;align-items:center;padding:10px 12px}.boblift-admin-menu-actions .button-link-delete{color:#b32d2e}.boblift-admin-product-groups-editor{margin-top:18px;padding:18px;border:1px solid #c3c4c7;border-radius:14px;background:#fff}.boblift-admin-product-groups-editor h3{margin:0 0 8px}.boblift-admin-menu-inline-slot[hidden],[data-product-groups-parking][hidden]{display:none}.boblift-admin-submenu-links{overflow:hidden;margin:14px 0;border:1px solid #dcdcde;border-radius:12px;background:#fff}@media(max-width:960px){.boblift-admin-menu-head,.boblift-admin-menu-summary,.boblift-admin-menu-child .boblift-admin-menu-summary{grid-template-columns:56px minmax(0,1fr)}.boblift-admin-menu-head span:last-child{display:none}.boblift-admin-menu-actions{grid-column:1/-1;justify-content:flex-start;padding:0 16px 12px}.boblift-admin-link-control{grid-template-columns:1fr}}</style>';
    echo '<style>.boblift-floating-contact-editor{max-width:1180px;padding:16px;border:1px solid #dcdcde;border-radius:14px;background:#fff}.boblift-floating-contact-list{display:grid;gap:10px;margin-top:12px}.boblift-floating-contact-row{overflow:hidden;border:1px solid #dcdcde;border-radius:12px;background:#fff}.boblift-floating-contact-summary{display:grid;grid-template-columns:40px 38px minmax(160px,1fr) minmax(120px,180px) auto;gap:10px;align-items:center;cursor:pointer;list-style:none;padding:12px 14px}.boblift-floating-contact-summary::-webkit-details-marker{display:none}.boblift-floating-contact-summary:hover{background:#f6f7f7}.boblift-floating-contact-summary:before{content:"+";grid-column:1;grid-row:1;color:#8c8f94;font-size:18px;font-weight:700}.boblift-floating-contact-row[open] .boblift-floating-contact-summary:before{content:"−"}.boblift-floating-contact-index{display:none}.boblift-floating-contact-icon{grid-column:2;display:grid;width:32px;height:32px;place-items:center;border:1px solid #dcdcde;border-radius:999px;background:#f8fafc;color:#08234a}.boblift-floating-contact-title{font-size:14px;font-weight:800;color:#1d2327}.boblift-floating-contact-type{color:#646970;font-size:12px;font-weight:700}.boblift-floating-contact-actions{display:flex;justify-content:flex-end;gap:6px}.boblift-floating-contact-actions .button-link-delete{color:#b32d2e}.boblift-floating-contact-body{padding:14px 16px 16px;border-top:1px solid #f0f0f1;background:#fbfbfc}.boblift-floating-contact-body .boblift-admin-field{grid-template-columns:120px minmax(0,1fr);margin-bottom:10px}.boblift-floating-contact-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:14px}.boblift-floating-contact-toolbar span{color:#646970;font-size:12px}@media(max-width:960px){.boblift-floating-contact-summary{grid-template-columns:32px minmax(0,1fr);}.boblift-floating-contact-summary:before{grid-column:1}.boblift-floating-contact-icon{display:none}.boblift-floating-contact-title{grid-column:2}.boblift-floating-contact-type,.boblift-floating-contact-actions{grid-column:2}.boblift-floating-contact-actions{justify-content:flex-start}.boblift-floating-contact-body .boblift-admin-field{display:block}}</style>';
}

function boblift_admin_panel(string $title, callable $render): void
{
    echo '<details class="boblift-admin-panel"><summary>' . esc_html($title) . '</summary><div class="boblift-admin-panel-body">';
    $render();
    echo '</div></details>';
}

function boblift_admin_subpanel(string $title, callable $render, bool $open = false, string $meta = ''): void
{
    echo '<details class="boblift-admin-card boblift-admin-subpanel" ' . ($open ? 'open' : '') . '>';
    echo '<summary><span class="boblift-admin-subpanel-title">' . esc_html($title) . '</span>';
    if ($meta !== '') {
        echo '<span class="boblift-admin-subpanel-meta">' . esc_html($meta) . '</span>';
    }
    echo '</summary><div class="boblift-admin-subpanel-body">';
    $render();
    echo '</div></details>';
}

function boblift_admin_settings_panel_start(string $title, string $description = '', bool $open = false): void
{
    echo '<details class="boblift-admin-panel boblift-admin-settings-panel" ' . ($open ? 'open' : '') . '>';
    echo '<summary><span class="boblift-admin-panel-heading"><span class="boblift-admin-panel-title">' . esc_html($title) . '</span>';
    if ($description !== '') {
        echo '<span class="boblift-admin-panel-description">' . esc_html($description) . '</span>';
    }
    echo '</span></summary><div class="boblift-admin-panel-body">';
}

function boblift_admin_settings_panel_end(): void
{
    echo '</div></details>';
}

function boblift_admin_nav_title(array $items, int $index, string $fallback, string $key = 'label'): string
{
    $label = trim((string) ($items[$index][$key] ?? ''));
    return $label !== '' ? $label : $fallback . ' ' . (string) ($index + 1);
}

function boblift_admin_link_choices(): array
{
    static $choices = null;
    if (is_array($choices)) {
        return $choices;
    }

    $choices = [
        '' => '不设置',
        '/' => '首页 /',
        '/products.html' => '产品总页 /products.html',
        '/cases.html' => '案例页 /cases.html',
        '/news.html' => '新闻页 /news.html',
        '/about.html' => '关于页 /about.html',
        '/contact.html' => '联系页 /contact.html',
        '/quote.html' => '询盘页 /quote.html',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => 80,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    ]);
    foreach ($pages as $page) {
        if (!$page instanceof WP_Post) {
            continue;
        }
        $path = boblift_page_public_url_admin_value($page);
        if ($path !== '' && !isset($choices[$path])) {
            $choices[$path] = '页面：' . get_the_title($page) . ' ' . $path;
        }
    }

    foreach (['product_category' => '产品分类', 'category' => '新闻分类'] as $taxonomy => $label) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 120]);
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $path = $taxonomy === 'product_category' ? boblift_product_category_public_url_admin_value($term) : boblift_article_category_public_url_admin_value($term);
            if ($path !== '' && !isset($choices[$path])) {
                $choices[$path] = $label . '：' . $term->name . ' ' . $path;
            }
        }
    }

    foreach (['product' => '产品', 'case' => '案例', 'post' => '文章'] as $post_type => $label) {
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 120,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        foreach ($posts as $post) {
            if (!$post instanceof WP_Post) {
                continue;
            }
            $path = wp_parse_url((string) get_permalink($post), PHP_URL_PATH);
            $path = is_string($path) ? untrailingslashit('/' . ltrim($path, '/')) : '';
            if ($path !== '' && !isset($choices[$path])) {
                $choices[$path] = $label . '：' . get_the_title($post) . ' ' . $path;
            }
        }
    }

    return $choices;
}

function boblift_render_about_certification_admin_row(array $certifications, string $base, int|string $index, bool $template = false): void
{
    $index_string = (string) $index;
    $items = is_array($certifications['items'] ?? null) ? $certifications['items'] : [];
    $item = is_array($items[$index] ?? null) ? $items[$index] : [];
    $row_attrs = 'class="boblift-admin-card boblift-admin-subpanel" data-about-certification-row data-certification-index="' . esc_attr($index_string) . '"';
    if ($template) {
        $row_attrs = 'class="boblift-admin-card boblift-admin-subpanel" data-about-certification-row data-certification-index="__INDEX__"';
    }
    $title = trim((string) ($item['title'] ?? ''));
    echo '<details ' . $row_attrs . ($template ? '' : '') . '>';
    echo '<summary><span class="boblift-admin-subpanel-title" data-about-certification-title>认证图片 ' . esc_html($template ? '__INDEX__' : (string) ((int) $index + 1)) . '</span>';
    if ($title !== '') {
        echo '<span class="boblift-admin-subpanel-meta">' . esc_html($title) . '</span>';
    }
    echo '</summary><div class="boblift-admin-subpanel-body">';
    echo '<p><button class="button button-link-delete" type="button" data-about-certification-remove>删除这张证书</button></p>';
    boblift_admin_media('certifications.items.' . $index_string . '.image', ['certifications' => ['items' => [$index => $item]]], '证书图片', $base, '上传真实证书图片。推荐竖版 1200×1600 或 1600×2200；横版也会自适应展示。');
    boblift_admin_input('certifications.items.' . $index_string . '.title', ['certifications' => ['items' => [$index => $item]]], '证书标题', $base);
    boblift_admin_textarea('certifications.items.' . $index_string . '.text', ['certifications' => ['items' => [$index => $item]]], '简短说明', $base);
    echo '</div></details>';
}


function boblift_render_about_page_fields(array $about, string $base): void
{
    echo '<div class="boblift-admin-grid">';

    boblift_admin_panel('1. About 二级导航', static function () use ($about, $base): void {
        echo '<p class="boblift-admin-note">控制顶部 About 下拉二级导航。label 会进入多语言翻译；href 和 slug 保持 URL 识别，不翻译。</p>';
        $items = is_array($about['secondary_nav'] ?? null) ? $about['secondary_nav'] : [];
        // Saving the menu records that it was reviewed, so newer default pages are no longer re-added to it on read.
        echo '<input type="hidden" name="' . esc_attr($base) . '[secondary_nav_version]" value="1">';
        for ($i = 0; $i < max(5, count($items)); $i++) {
            boblift_admin_subpanel('二级导航 ' . (string) ($i + 1), static function () use ($about, $base, $i): void {
                boblift_admin_input("secondary_nav.$i.label", $about, '显示文字', $base);
                boblift_admin_link_input("secondary_nav.$i.href", $about, '链接', $base);
                boblift_admin_input("secondary_nav.$i.slug", $about, '对应页面 Slug', $base);
            }, false, (string) ($items[$i]['label'] ?? ''));
        }
    });

    // A saved introduction from the old layout is still shown under the hero, so it stays editable (and removable) here.
    $legacy_intro = boblift_about_legacy_intro((array) ($about['logo'] ?? []));
    if ($legacy_intro['headline'] !== '' || $legacy_intro['paragraph'] !== '' || $legacy_intro['chips'] !== []) {
        boblift_admin_panel('旧版介绍（仍在前台显示）', static function () use ($about, $base, $legacy_intro): void {
            echo '<p class="boblift-admin-note">这是旧版 About 首屏保存的介绍文字，现在显示在新版首屏下方。清空全部内容并保存即可删除。</p>';
            boblift_admin_input('logo.headline', $about, '标题', $base);
            boblift_admin_textarea('logo.paragraph', $about, '段落', $base);
            for ($i = 0; $i < max(3, count($legacy_intro['chips']) + 1); $i++) {
                boblift_admin_input("logo.proof_chips.$i.number", $about, '要点 ' . (string) ($i + 1) . ' 编号', $base);
                boblift_admin_input("logo.proof_chips.$i.label", $about, '要点 ' . (string) ($i + 1) . ' 文字', $base);
            }
        });
    }

    $legacy_strength = boblift_about_legacy_strength((array) ($about['strength'] ?? []));
    if ($legacy_strength['cards'] !== [] || $legacy_strength['cta'] !== null || $legacy_strength['image'] !== null) {
        boblift_admin_panel('旧版实力图片、卡片与按钮（仍在前台显示）', static function () use ($about, $base, $legacy_strength): void {
            echo '<p class="boblift-admin-note">这是旧版 Company Strength 区保存的图片、卡片和按钮，现在显示在新版实力区下方。清空全部内容并保存即可删除。</p>';
            boblift_admin_media('strength.image', $about, '旧版实力图片', $base);
            for ($i = 0; $i < max(4, count($legacy_strength['cards']) + 1); $i++) {
                boblift_admin_input("strength.cards.$i.title", $about, '卡片 ' . (string) ($i + 1) . ' 标题', $base);
                boblift_admin_textarea("strength.cards.$i.text", $about, '卡片 ' . (string) ($i + 1) . ' 文字', $base);
            }
            boblift_admin_input('strength.cta_label', $about, '按钮文字', $base);
            boblift_admin_link_input('strength.cta_href', $about, '按钮链接', $base);
        });
    }

    boblift_admin_panel('2. 顶部大 LOGO 视觉区', static function () use ($about, $base): void {
        boblift_admin_media('logo.silhouette_image', $about, '开场剪影图', $base, '推荐与航拍图同尺寸同构图，16:9。');
        boblift_admin_input('logo.silhouette_alt', $about, '开场剪影图 Alt', $base);
        boblift_admin_media('logo.aerial_start_image', $about, '航拍开场帧', $base, '推荐与航拍视频同尺寸，作为首屏 poster 和开场图。');
        boblift_admin_media('logo.aerial_end_image', $about, '航拍结束帧', $base, '推荐与航拍视频同尺寸。');
        boblift_admin_file_media('logo.aerial_video', $about, '航拍视频 MP4', $base, '推荐 MP4/WebM，前台 preload=metadata，不会整段抢占首屏带宽。');
        boblift_admin_input('logo.aerial_start_alt', $about, '航拍开场帧 Alt', $base);
        boblift_admin_input('logo.aerial_end_alt', $about, '航拍结束帧 Alt', $base);
    });

    boblift_admin_panel('3. 公司简介与出口数据', static function () use ($about, $base): void {
        boblift_admin_input('strength.label', $about, '标签', $base);
        boblift_admin_input('strength.title', $about, '大标题', $base);
        boblift_admin_textarea('strength.text', $about, '正文', $base);
        echo '<div class="boblift-admin-card"><h3>下方三张出口数据卡片</h3>';
        echo '<p class="boblift-admin-note">前台使用公司简介下方原有三卡片样式展示这些数据，不再单独显示“全球出口版图”区块。</p>';
        for ($i = 0; $i < 3; $i++) {
            boblift_admin_subpanel('出口数据 ' . (string) ($i + 1), static function () use ($about, $base, $i): void {
                boblift_admin_input("global_footprint.stats.$i.number", $about, '数字', $base);
                boblift_admin_input("global_footprint.stats.$i.suffix", $about, '后缀', $base);
                boblift_admin_input("global_footprint.stats.$i.label", $about, '说明', $base);
            }, false, (string) ($about['global_footprint']['stats'][$i]['label'] ?? ''));
        }
        echo '</div>';
    });

    boblift_admin_panel('4. 发展时间线', static function () use ($about, $base): void {
        boblift_admin_input('timeline.label', $about, '标签', $base);
        boblift_admin_input('timeline.title', $about, '标题', $base);
        for ($i = 0; $i < 4; $i++) {
            boblift_admin_subpanel('时间线 ' . (string) ($i + 1), static function () use ($about, $base, $i): void {
                boblift_admin_media("timeline.items.$i.image", $about, '图片', $base, '推荐 1200×675，16:9；时间线缩略图会与文字垂直居中。');
                boblift_admin_input("timeline.items.$i.year", $about, '年份', $base);
                boblift_admin_input("timeline.items.$i.title", $about, '标题', $base);
                boblift_admin_textarea("timeline.items.$i.text", $about, '正文', $base);
            }, false, (string) ($about['timeline']['items'][$i]['year'] ?? ''));
        }
    });

    boblift_admin_panel('5. 国际资质认证', static function () use ($about, $base): void {
        $certifications = is_array($about['certifications'] ?? null) ? $about['certifications'] : [];
        $certification_items = is_array($certifications['items'] ?? null) ? array_values(array_filter($certifications['items'], static fn($item): bool => is_array($item))) : [];
        echo '<p class="boblift-admin-note">这里维护真实证书图片。点击“添加证书图片”可继续追加；前台会横向滚动，鼠标悬停暂停，点击查看大图。</p>';
        boblift_admin_input('certifications.label', $about, '标签', $base);
        boblift_admin_input('certifications.title', $about, '标题', $base);
        boblift_admin_textarea('certifications.text', $about, '说明', $base);
        echo '<div data-about-certifications-editor>';
        // Presence marker: when every row is deleted no certification input is posted, and the save must then clear the saved list.
        echo '<input type="hidden" name="' . esc_attr($base) . '[certifications][items_present]" value="1">';
        echo '<div data-about-certifications-list>';
        $rows = $certification_items !== [] ? $certification_items : [[]];
        foreach ($rows as $i => $item) {
            boblift_render_about_certification_admin_row(['items' => $rows], $base, $i);
        }
        echo '</div>';
        echo '<p><button class="button button-primary" type="button" data-about-certification-add>添加证书图片</button></p>';
        echo '<template data-about-certification-template>';
        boblift_render_about_certification_admin_row(['items' => []], $base, '__INDEX__', true);
        echo '</template>';
        echo '</div>';
    });

    boblift_admin_panel('6. 现代化制造基地横向画廊', static function () use ($about, $base): void {
        boblift_admin_input('manufacturing_gallery.label', $about, '标签', $base);
        boblift_admin_input('manufacturing_gallery.title', $about, '标题', $base);
        boblift_admin_textarea('manufacturing_gallery.text', $about, '说明', $base);
        echo '<p class="boblift-admin-note">前台为横向滚动画廊，建议 4 张。图片推荐 2200×1400 或更高，主体居中。</p>';
        $gallery_count = max(4, count((array) ($about['manufacturing_gallery']['items'] ?? [])));
        for ($i = 0; $i < $gallery_count; $i++) {
            boblift_admin_subpanel('画廊图片 ' . (string) ($i + 1), static function () use ($about, $base, $i): void {
                boblift_admin_media("manufacturing_gallery.items.$i.image", $about, '图片', $base, '推荐 2200×1400 或更高；4K 下会保持足够高度。');
                boblift_admin_input("manufacturing_gallery.items.$i.title", $about, '标题', $base);
                boblift_admin_textarea("manufacturing_gallery.items.$i.text", $about, '说明', $base);
            }, false, (string) ($about['manufacturing_gallery']['items'][$i]['title'] ?? ''));
        }
    });

    boblift_admin_panel('7. 领导与全体团队区', static function () use ($about, $base): void {
        echo '<p class="boblift-admin-note">正面展示领导成员；翻转后展示全体团队照片，两者文案分开维护。</p>';
        boblift_admin_input('executives.label', $about, '领导区标签', $base);
        boblift_admin_input('executives.title', $about, '领导区标题', $base);
        boblift_admin_input('executives.team_label', $about, '全体团队照片标签', $base);
        boblift_admin_input('executives.team_title', $about, '全体团队照片标题', $base);
        boblift_admin_media('executives.team_image', $about, '全体团队大图', $base, '推荐 3200×760 或 3840×900，超宽横图；用于翻转后的全屏展示。');
        boblift_admin_input('executives.team_image_alt', $about, '全体团队大图 Alt', $base);
        for ($i = 0; $i < 6; $i++) {
            boblift_admin_subpanel('领导成员 ' . (string) ($i + 1), static function () use ($about, $base, $i): void {
                boblift_admin_media("executives.members.$i.image", $about, '照片', $base, '推荐 1200×1200，正方形半身商务照；人脸居中，上方保留少量头部空间。');
                boblift_admin_input("executives.members.$i.role", $about, '职位', $base);
                boblift_admin_input("executives.members.$i.name", $about, '姓名', $base);
            }, false, (string) ($about['executives']['members'][$i]['name'] ?? ''));
        }
    });

    echo '</div>';
}


function boblift_render_customer_visit_admin_row(array $visits, string $base, int|string $index, bool $template = false): void
{
    $index_string = (string) $index;
    $item = is_array($visits['items'][$index] ?? null) ? $visits['items'][$index] : [];
    $row_attrs = 'class="boblift-admin-card boblift-admin-subpanel" data-customer-visit-row data-visit-index="' . esc_attr($index_string) . '"';
    if ($template) {
        $row_attrs = 'class="boblift-admin-card boblift-admin-subpanel" data-customer-visit-row data-visit-index="__INDEX__"';
    }
    $meta = trim((string) ($item['title'] ?? ($item['label'] ?? '')));
    echo '<details ' . $row_attrs . ($template ? '' : '') . '>';
    echo '<summary><span class="boblift-admin-subpanel-title" data-customer-visit-title>客户来访图片 ' . esc_html($template ? '__INDEX__' : (string) ((int) $index + 1)) . '</span>';
    if ($meta !== '') {
        echo '<span class="boblift-admin-subpanel-meta">' . esc_html($meta) . '</span>';
    }
    echo '</summary><div class="boblift-admin-subpanel-body">';
    echo '<p><button class="button button-link-delete" type="button" data-customer-visit-remove>删除这张图片</button></p>';
    boblift_admin_media('items.' . $index_string . '.image', ['items' => [$index => $item]], '图片', $base, '推荐 1200×900 或 1600×1200，展示客户来访、工厂参观、技术沟通等场景；前台仅展示图片。');
    boblift_admin_input('items.' . $index_string . '.country', ['items' => [$index => $item]], '来访国家（同一国家的图片在前台合并成一组；留空归入“更多来访”）', $base);
    boblift_admin_input('items.' . $index_string . '.label', ['items' => [$index => $item]], '小标签（可选，仅用于后台识别）', $base);
    boblift_admin_input('items.' . $index_string . '.title', ['items' => [$index => $item]], '图片说明（可选，用于 alt）', $base);
    boblift_admin_textarea('items.' . $index_string . '.text', ['items' => [$index => $item]], '备注（可选，不在前台展示）', $base);
    echo '</div></details>';
}


function boblift_render_customer_visits_page_fields(array $visits, string $base): void
{
    $items = is_array($visits['items'] ?? null) ? array_values(array_filter($visits['items'], static fn($item): bool => is_array($item))) : [];
    echo '<div class="boblift-admin-grid">';
    boblift_admin_panel('1. 页面标题与说明', static function () use ($visits, $base): void {
        boblift_admin_input('label', $visits, '标签', $base);
        boblift_admin_input('title', $visits, '标题', $base);
        boblift_admin_textarea('text', $visits, '正文', $base);
        boblift_admin_media('hero_image', $visits, '顶部照片', $base, '横向实景照片，推荐 1920×640（3:1）。主体放在右半边，左侧留出浅色空间给标题；手机上照片单独显示在标题上方。');
        boblift_admin_input('hero_image_alt', $visits, '顶部照片 Alt', $base);
        boblift_admin_input('cta_label', $visits, '按钮文字', $base);
        boblift_admin_link_input('cta_href', $visits, '按钮链接', $base);
        boblift_admin_input('home_title', $visits, '首页展示标题', $base);
        boblift_admin_input('home_cta_label', $visits, '首页查看更多按钮', $base);
        boblift_admin_input('visit_count_suffix', $visits, '图片数量后缀', $base);
        boblift_admin_input('pagination_label', $visits, '分页辅助标签', $base);
        boblift_admin_input('empty_text', $visits, '空状态文案', $base);
        boblift_admin_input('country_count_suffix', $visits, '顶部国家数量后缀（如 countries）', $base);
    });

    boblift_admin_panel('3. 来访国家与参观内容', static function () use ($visits, $base): void {
        echo '<p class="boblift-admin-note">国家列表和国家数量根据下方图片填写的“来访国家”自动生成。{count} 会替换为国家数量。</p>';
        boblift_admin_input('countries_label', $visits, '国家区标签', $base);
        boblift_admin_input('countries_title', $visits, '国家区标题', $base);
        boblift_admin_input('steps_label', $visits, '参观内容标签', $base);
        boblift_admin_input('steps_title', $visits, '参观内容标题', $base);
        for ($i = 0; $i < 4; $i++) {
            boblift_admin_subpanel('第 ' . (string) ($i + 1) . ' 步', static function () use ($visits, $base, $i): void {
                boblift_admin_media("steps.$i.image", $visits, '图片', $base, '横向照片，推荐 1600×1200（4:3）。');
                boblift_admin_input("steps.$i.title", $visits, '标题', $base);
                boblift_admin_textarea("steps.$i.text", $visits, '说明', $base);
            }, false, (string) ($visits['steps'][$i]['title'] ?? ''));
        }
    });

    boblift_admin_panel('4. 相册与预约参观', static function () use ($visits, $base): void {
        boblift_admin_input('wall_label', $visits, '相册标签', $base);
        boblift_admin_input('wall_title', $visits, '相册标题', $base);
        boblift_admin_input('wall_text', $visits, '相册说明', $base);
        boblift_admin_input('more_visits_label', $visits, '未填国家的图片分组名称', $base);
        boblift_admin_input('photo_count_suffix', $visits, '图片数量后缀（如 photos）', $base);
        boblift_admin_input('plan_label', $visits, '预约区标签', $base);
        boblift_admin_input('plan_title', $visits, '预约区标题', $base);
        boblift_admin_textarea('plan_text', $visits, '预约区说明', $base);
        boblift_admin_input('plan_secondary_label', $visits, '第二个按钮文字（链接用上面的“按钮链接”）', $base);
        for ($i = 0; $i < 3; $i++) {
            boblift_admin_subpanel('到访信息 ' . (string) ($i + 1), static function () use ($visits, $base, $i): void {
                boblift_admin_input("plan_points.$i.title", $visits, '名称', $base);
                boblift_admin_input("plan_points.$i.text", $visits, '内容', $base);
            }, false, (string) ($visits['plan_points'][$i]['title'] ?? ''));
        }
    });

    boblift_admin_panel('5. 客户来访参厂图片', static function () use ($visits, $items, $base): void {
        echo '<div data-customer-visits-editor>';
        echo '<p class="boblift-admin-note">点击“添加图片”可继续追加，不限制数量。每张图片填写“来访国家”，前台按国家合并成一张卡片，点开是这一组的相册；第一张作为卡片封面。</p>';
        echo '<div data-customer-visits-list>';
        $rows = $items !== [] ? $items : [[]];
        foreach ($rows as $i => $item) {
            boblift_render_customer_visit_admin_row(['items' => $rows], $base, $i);
        }
        echo '</div>';
        echo '<p><button class="button button-primary" type="button" data-customer-visit-add>添加图片</button></p>';
        echo '<template data-customer-visit-template>';
        boblift_render_customer_visit_admin_row(['items' => []], $base, '__INDEX__', true);
        echo '</template>';
        echo '</div>';
    });
    echo '</div>';
}

function boblift_render_public_welfare_page_fields(array $options, string $base): void
{
    echo '<div class="boblift-admin-grid">';
    boblift_admin_panel('1. 顶部卡片', static function () use ($options, $base): void {
        boblift_admin_input('statement.label', $options, '标签', $base);
        boblift_admin_input('statement.title', $options, '标题', $base);
        boblift_admin_input('statement.highlight', $options, '高亮词', $base);
        boblift_admin_textarea('statement.text', $options, '说明文字', $base);
        boblift_admin_media('hero.image', $options, '照片', $base, '横向实景照片，推荐 1920×640（3:1）。主体放在右半边，左侧留出浅色空间给标题；手机上照片单独显示在标题上方。');
        boblift_admin_input('hero.image_alt', $options, '照片 Alt', $base);
    });
    boblift_admin_panel('2. 责任卡片', static function () use ($options, $base): void {
        for ($i = 0; $i < 4; $i++) {
            boblift_admin_subpanel('责任卡片 ' . (string) ($i + 1), static function () use ($options, $base, $i): void {
                boblift_admin_input("focus_items.$i.icon", $options, '图标 key', $base);
                boblift_admin_media("focus_items.$i.image", $options, '图片', $base, '推荐 1600×1200，4:3。');
                boblift_admin_input("focus_items.$i.alt", $options, '图片 Alt', $base);
                boblift_admin_input("focus_items.$i.title", $options, '标题', $base);
                boblift_admin_textarea("focus_items.$i.text", $options, '正文', $base);
            }, false, (string) ($options['focus_items'][$i]['title'] ?? ''));
        }
    });
    boblift_admin_panel('3. 底部 CTA', static function () use ($options, $base): void {
        boblift_admin_input('cta.title', $options, '标题', $base);
        boblift_admin_textarea('cta.text', $options, '说明', $base);
        boblift_admin_input('cta.button_label', $options, '按钮文字', $base);
        boblift_admin_link_input('cta.button_href', $options, '按钮链接', $base);
    });
    echo '</div>';
}

function boblift_render_legal_page_fields(array $options, string $base, bool $with_side_card): void
{
    $sections = is_array($options['sections'] ?? null) ? array_values($options['sections']) : [];
    echo '<div class="boblift-admin-grid">';
    boblift_admin_panel('1. 页面头部', static function () use ($options, $base, $with_side_card): void {
        boblift_admin_input('label', $options, '标签', $base);
        boblift_admin_input('title', $options, '标题', $base);
        boblift_admin_textarea('description', $options, '说明', $base);
        boblift_admin_input('updated_label', $options, '更新时间标签', $base);
        boblift_admin_input('updated', $options, '更新时间', $base);
        if ($with_side_card) {
            boblift_admin_input('side_title', $options, '侧栏标题', $base);
            boblift_admin_textarea('side_text', $options, '侧栏正文', $base);
        }
    });
    boblift_admin_panel('2. 正文条款', static function () use ($sections, $base): void {
        $rows = $sections !== [] ? $sections : array_fill(0, 6, []);
        foreach ($rows as $i => $section) {
            $section = is_array($section) ? $section : [];
            boblift_admin_subpanel('条款 ' . (string) ($i + 1), static function () use ($section, $base, $i): void {
                boblift_admin_input("sections.$i.title", ['sections' => [$i => $section]], '标题', $base);
                boblift_admin_textarea("sections.$i.text", ['sections' => [$i => $section]], '正文', $base);
            }, false, (string) ($section['title'] ?? ''));
        }
    });
    echo '</div>';
}

function boblift_render_sitemap_page_fields(array $options, string $base): void
{
    echo '<div class="boblift-admin-grid">';
    boblift_admin_panel('1. 页面头部', static function () use ($options, $base): void {
        boblift_admin_input('label', $options, '标签', $base);
        boblift_admin_input('title', $options, '标题', $base);
        boblift_admin_textarea('description', $options, '说明', $base);
        boblift_admin_input('links_label', $options, '链接数量标签', $base);
    });
    boblift_admin_panel('2. 区块标题', static function () use ($options, $base): void {
        boblift_admin_input('core_pages_title', $options, '核心页面标题', $base);
        boblift_admin_input('product_range_label', $options, '产品区标签', $base);
        boblift_admin_input('product_range_title', $options, '产品区标题', $base);
        boblift_admin_input('view_products_label', $options, '产品按钮文字', $base);
        boblift_admin_input('product_categories_title', $options, '产品分类标题', $base);
        boblift_admin_input('products_title', $options, '产品标题', $base);
        boblift_admin_input('news_categories_title', $options, '新闻分类标题', $base);
        boblift_admin_input('news_title', $options, '新闻标题', $base);
        boblift_admin_input('cases_title', $options, '案例标题', $base);
    });
    echo '</div>';
}


function boblift_home_page_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_home_page_meta_box', 'boblift_home_page_nonce');
    echo '<div class="boblift-admin-wrap boblift-admin-section" style="border:0;padding:0;margin:0;background:transparent;">';
    boblift_admin_about_page_styles();
    echo '<p>这里维护首页关键图片、标题、文案和首页产品展示。保存本页面后，前台首页会同步更新。</p>';
    if (function_exists('boblift_mls_render_page_controller')) {
        boblift_mls_render_page_controller($post, 'home');
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : '';
    $home = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('home', $language) : (boblift_options()['home'] ?? []);
    boblift_render_home_page_fields($home, 'boblift_home_page');
    echo '</div>';
}

function boblift_render_home_page_fields(array $home, string $base): void
{
    echo '<div class="boblift-admin-grid">';

    boblift_admin_panel('1. 首屏轮播', static function () use ($home, $base): void {
        echo '<p class="boblift-admin-note">首页第一屏：左侧标题和按钮，右侧照片，每张约 7 秒自动切换，标题下方的切换按钮显示每张的“切换按钮文字”。填写了标题的才会显示；只剩一张时不显示切换按钮。英文默认文案已内置日文、中文翻译；在这里改成其他文字后，需要到对应语言的首页设置里分别填写。</p>';
        boblift_admin_input('hero.label', $home, '小标签（所有幻灯片共用）', $base);
        boblift_admin_input('hero.secondary_label', $home, '询价按钮文字（所有幻灯片共用，打开询价弹窗）', $base);
        for ($i = 0; $i < 3; $i++) {
            boblift_admin_subpanel('幻灯片 ' . (string) ($i + 1), static function () use ($home, $base, $i): void {
                boblift_admin_input("hero.slides.$i.tab", $home, '切换按钮文字（如产品类别）', $base);
                boblift_admin_input("hero.slides.$i.title", $home, $i === 0 ? '标题（第一张为页面 H1）' : '标题', $base);
                boblift_admin_textarea("hero.slides.$i.text", $home, '说明文字', $base);
                boblift_admin_input("hero.slides.$i.primary_label", $home, '主按钮文字', $base);
                boblift_admin_link_input("hero.slides.$i.primary_href", $home, '主按钮链接', $base);
                boblift_admin_media("hero.slides.$i.image", $home, '照片', $base, '横向实景照片，推荐 2:1（如 2400×1200）。主体放在右半边，左侧留出明亮、少细节的空间给标题；手机上照片单独显示在标题上方。');
                boblift_admin_input("hero.slides.$i.image_alt", $home, '照片 Alt', $base);
                boblift_admin_media("hero.slides.$i.mobile_image", $home, '手机端照片（可选）', $base, '手机端（宽度小于 768px）使用的照片；不上传则使用上面的照片。');
            }, $i === 0, (string) ($home['hero']['slides'][$i]['tab'] ?? ''));
        }
    });

    boblift_admin_panel('2. 认证栏', static function () use ($home, $base): void {
        boblift_admin_input('trust_bar.label', $home, '标签', $base);
        boblift_admin_input('trust_bar.title', $home, '标题', $base);
        boblift_admin_textarea('trust_bar.description', $home, '描述', $base);
        echo '<div class="boblift-admin-card"><h3>认证 Logo</h3><p class="boblift-admin-note">推荐 SVG；如果使用位图，推荐透明 PNG 600×240。</p>';
        for ($i = 0; $i < 4; $i++) {
            boblift_admin_subpanel('认证 ' . (string) ($i + 1), static function () use ($home, $base, $i): void {
                boblift_admin_input("trust_bar.marks.$i.name", $home, '名称', $base);
                boblift_admin_media("trust_bar.marks.$i.logo", $home, 'Logo', $base, '推荐 SVG 或透明 PNG 600×240。');
            }, false, (string) ($home['trust_bar']['marks'][$i]['name'] ?? ''));
        }
        echo '</div>';
    });

    boblift_admin_panel('3. 产品区', static function () use ($home, $base): void {
        boblift_admin_input('product_section.label', $home, '标签', $base);
        boblift_admin_input('product_section.title', $home, '标题', $base);
        boblift_admin_input('product_section.view_all_label', $home, '「查看全部」按钮文字（{count} 替换为该分类产品总数，留空用默认）', $base);
        boblift_admin_input('product_section.max_category_products', $home, '每个分类最多展示产品数', $base);
        boblift_admin_checkbox('product_section.show_category_scroll_buttons', $home, '显示分类左右切换按钮', $base);
        boblift_admin_checkbox('product_section.show_product_specs', $home, '产品卡片显示起重能力参数', $base);
        boblift_admin_subpanel('按钮和字段文案', static function () use ($home, $base): void {
            boblift_admin_input('product_section.details_short_label', $home, '移动端详情按钮', $base);
            boblift_admin_input('product_section.view_details_label', $home, '详情按钮', $base);
            boblift_admin_input('product_section.back_to_models_label', $home, '返回型号按钮', $base);
            boblift_admin_input('product_section.previous_categories_label', $home, '上一组分类按钮', $base);
            boblift_admin_input('product_section.next_categories_label', $home, '下一组分类按钮', $base);
            boblift_admin_input('product_section.product_name_label', $home, '产品名称标签', $base);
            boblift_admin_input('product_section.model_label', $home, '型号标签', $base);
            boblift_admin_input('product_section.model_spec_label', $home, '参数：型号', $base);
            boblift_admin_input('product_section.capacity_spec_label', $home, '参数：起重能力', $base);
            boblift_admin_input('product_section.type_spec_label', $home, '参数：类型', $base);
            boblift_admin_input('product_section.system_spec_label', $home, '参数：系统', $base);
            boblift_admin_input('product_section.system_value', $home, '系统默认值', $base);
            boblift_admin_input('product_section.visual_watermark_label', $home, '产品图水印', $base);
        });
        boblift_admin_subpanel('首页展示产品', static function () use ($home, $base): void {
            echo '<p class="boblift-admin-note">不勾选时使用系统原来的自动推荐逻辑；勾选后首页只展示这些产品。</p>';
            boblift_admin_product_checklist('product_section.selected_product_slugs', $home, $base);
        });
    });

    boblift_admin_panel('4. 首页 About 区', static function () use ($home, $base): void {
        boblift_admin_input('about.label', $home, '标签', $base);
        boblift_admin_input('about.title', $home, '标题', $base);
        boblift_admin_textarea('about.text', $home, '正文', $base);
        boblift_admin_input('about.cta_label', $home, '按钮文字', $base);
        boblift_admin_link_input('about.cta_href', $home, '按钮链接', $base);
        boblift_admin_subpanel('图库图片', static function () use ($home, $base): void {
            echo '<p class="boblift-admin-note">推荐 1600×1100 或 1600×1200；前台会 cover 裁切，主体放中间。</p>';
            for ($i = 0; $i < 5; $i++) {
                boblift_admin_subpanel('图库 ' . (string) ($i + 1), static function () use ($home, $base, $i): void {
                    boblift_admin_select("about.gallery.$i.type", $home, '媒体类型', ['image' => '图片', 'local_video' => '本地视频', 'youtube' => 'YouTube 外链'], $base);
                    boblift_admin_media("about.gallery.$i.src", $home, '封面 / 图片', $base, '图片类型直接作为展示图；视频类型作为封面图。推荐 1600×1100 或 1600×1200。');
                    boblift_admin_file_media("about.gallery.$i.video_file", $home, '本地视频', $base, '媒体库上传 MP4/WebM。选择“本地视频”时使用。');
                    boblift_admin_input("about.gallery.$i.youtube_url", $home, 'YouTube 链接', $base);
                    boblift_admin_input("about.gallery.$i.alt", $home, '图片说明', $base);
                }, false, (string) ($home['about']['gallery'][$i]['alt'] ?? ''));
            }
        });
        boblift_admin_subpanel('数据卡片', static function () use ($home, $base): void {
            for ($i = 0; $i < 3; $i++) {
                boblift_admin_subpanel('数据 ' . (string) ($i + 1), static function () use ($home, $base, $i): void {
                    boblift_admin_input("about.stats.$i.value", $home, '数值', $base);
                    boblift_admin_input("about.stats.$i.label", $home, '说明', $base);
                    boblift_admin_input("about.stats.$i.iconName", $home, '图标名称', $base);
                }, false, (string) ($home['about']['stats'][$i]['label'] ?? ''));
            }
        });
        boblift_admin_subpanel('小时间线', static function () use ($home, $base): void {
            for ($i = 0; $i < 4; $i++) {
                boblift_admin_input("about.timeline.$i", $home, '时间线 ' . (string) ($i + 1), $base);
            }
        });
        boblift_admin_subpanel('工厂视频区（About 区下方）', static function () use ($home, $base): void {
            echo '<p class="boblift-admin-note">首页 About 区下方的工厂航拍视频横幅。留空时使用默认文案：Built in our own 37,000 m² factory in Xuzhou.（中文站、日文站自动显示对应译文）。</p>';
            boblift_admin_input('factory.title', $home, '标题', $base);
            boblift_admin_textarea('factory.text', $home, '说明文字', $base);
        });
    });

    boblift_admin_panel('5. News 区', static function () use ($home, $base): void {
        boblift_admin_input('news.label', $home, '标签', $base);
        boblift_admin_input('news.title', $home, '标题', $base);
        boblift_admin_input('news.cta_label', $home, '按钮文字', $base);
        boblift_admin_link_input('news.cta_href', $home, '按钮链接', $base);
    });

    echo '</div>';
}


function boblift_admin_product_checklist(string $path, array $options, string $base): void
{
    $selected = boblift_admin_option_value($path, $options);
    $selected = is_array($selected) ? array_map('strval', $selected) : [];
    $name = boblift_admin_option_name($path, $base) . '[]';
    echo '<input type="hidden" name="' . esc_attr($name) . '" value="">';
    echo '<div class="boblift-admin-checklist">';
    foreach (boblift_all_products() as $product) {
        $slug = (string) ($product['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        echo '<label class="boblift-admin-checkitem"><input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr($slug) . '" ' . checked(in_array($slug, $selected, true), true, false) . '><span>' . esc_html($product['title'] ?? $slug) . '</span></label>';
    }
    echo '</div>';
}

function boblift_save_about_page_meta_box(int $post_id, WP_Post $post): void
{
    if (!boblift_is_about_page_post($post) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_about_page_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_about_page_nonce'])), 'boblift_about_page_meta_box')) {
        return;
    }
    if (!isset($_POST['boblift_about_page']) || !is_array($_POST['boblift_about_page'])) {
        return;
    }

    $posted = wp_unslash($_POST['boblift_about_page']);
    if (isset($posted['certifications']['items_present'])) {
        unset($posted['certifications']['items_present']);
        $posted['certifications']['items'] ??= [];
    }
    $sanitized = boblift_cleanup_admin_options(boblift_sanitize_options_recursive(['about_page' => $posted]));
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
    $existing = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    $options = boblift_array_replace_recursive(is_array($existing) ? $existing : [], $sanitized);
    if (function_exists('boblift_mls_update_language_options')) {
        boblift_mls_update_language_options($options, $language);
    } else {
        update_option('boblift_theme_options', $options);
    }
}
add_action('save_post_page', 'boblift_save_about_page_meta_box', 10, 2);

function boblift_save_social_media_page_meta_box(int $post_id, WP_Post $post): void
{
    if (!boblift_is_social_media_page_post($post) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_social_media_page_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_social_media_page_nonce'])), 'boblift_social_media_page_meta_box')) {
        return;
    }
    if (!isset($_POST['boblift_social_media_page']) || !is_array($_POST['boblift_social_media_page'])) {
        return;
    }

    $posted = wp_unslash($_POST['boblift_social_media_page']);
    $sanitized = boblift_cleanup_admin_options(boblift_sanitize_options_recursive(['social_media_page' => $posted]));
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
    $existing = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    $options = boblift_array_replace_recursive(is_array($existing) ? $existing : [], $sanitized);
    if (function_exists('boblift_mls_update_language_options')) {
        boblift_mls_update_language_options($options, $language);
    } else {
        update_option('boblift_theme_options', $options);
    }
}
add_action('save_post_page', 'boblift_save_social_media_page_meta_box', 10, 2);

function boblift_save_global_customer_visits_page_meta_box(int $post_id, WP_Post $post): void
{
    if (!boblift_is_global_customer_visits_page_post($post) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_global_customer_visits_page_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_global_customer_visits_page_nonce'])), 'boblift_global_customer_visits_page_meta_box')) {
        return;
    }
    if (!isset($_POST['boblift_global_customer_visits_page']) || !is_array($_POST['boblift_global_customer_visits_page'])) {
        return;
    }

    $posted = wp_unslash($_POST['boblift_global_customer_visits_page']);
    $sanitized = boblift_sanitize_options_recursive(['about_page' => ['customer_visits' => $posted]]);
    if (isset($sanitized['about_page']['customer_visits']['items']) && is_array($sanitized['about_page']['customer_visits']['items'])) {
        $sanitized['about_page']['customer_visits']['items'] = array_values(array_filter($sanitized['about_page']['customer_visits']['items'], static function ($item): bool {
            if (!is_array($item)) {
                return false;
            }
            $image = is_array($item['image'] ?? null) ? $item['image'] : [];
            return (int) ($image['id'] ?? 0) > 0 || trim((string) ($image['path'] ?? '')) !== '';
        }));
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
    $existing = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    $options = boblift_array_replace_recursive(is_array($existing) ? $existing : [], $sanitized);
    if (function_exists('boblift_mls_update_language_options')) {
        boblift_mls_update_language_options($options, $language);
    } else {
        update_option('boblift_theme_options', $options);
    }
}
add_action('save_post_page', 'boblift_save_global_customer_visits_page_meta_box', 10, 2);

function boblift_save_home_page_meta_box(int $post_id, WP_Post $post): void
{
    if (!boblift_is_home_page_post($post) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_home_page_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_home_page_nonce'])), 'boblift_home_page_meta_box')) {
        return;
    }
    if (!isset($_POST['boblift_home_page']) || !is_array($_POST['boblift_home_page'])) {
        return;
    }

    $posted = wp_unslash($_POST['boblift_home_page']);
    $sanitized = boblift_sanitize_options_recursive(['home' => $posted]);
    if (isset($sanitized['home']['product_section']['selected_product_slugs']) && is_array($sanitized['home']['product_section']['selected_product_slugs'])) {
        $sanitized['home']['product_section']['selected_product_slugs'] = array_values(array_filter(array_map('sanitize_title', $sanitized['home']['product_section']['selected_product_slugs'])));
    }
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
    $existing = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    $options = boblift_array_replace_recursive(is_array($existing) ? $existing : [], $sanitized);
    if (function_exists('boblift_mls_update_language_options')) {
        boblift_mls_update_language_options($options, $language);
    } else {
        update_option('boblift_theme_options', $options);
    }
}
add_action('save_post_page', 'boblift_save_home_page_meta_box', 10, 2);

function boblift_save_public_welfare_page_meta_box(int $post_id, WP_Post $post): void
{
    boblift_save_page_section_meta_box($post_id, $post, 'public_welfare_page', 'boblift_public_welfare_page', 'boblift_public_welfare_page_nonce', 'boblift_public_welfare_page_meta_box', 'boblift_is_public_welfare_page_post');
}
add_action('save_post_page', 'boblift_save_public_welfare_page_meta_box', 10, 2);

function boblift_save_privacy_policy_page_meta_box(int $post_id, WP_Post $post): void
{
    boblift_save_page_section_meta_box($post_id, $post, 'privacy_policy_page', 'boblift_privacy_policy_page', 'boblift_privacy_policy_page_nonce', 'boblift_privacy_policy_page_meta_box', 'boblift_is_privacy_policy_page_post');
}
add_action('save_post_page', 'boblift_save_privacy_policy_page_meta_box', 10, 2);

function boblift_save_terms_of_use_page_meta_box(int $post_id, WP_Post $post): void
{
    boblift_save_page_section_meta_box($post_id, $post, 'terms_of_use_page', 'boblift_terms_of_use_page', 'boblift_terms_of_use_page_nonce', 'boblift_terms_of_use_page_meta_box', 'boblift_is_terms_of_use_page_post');
}
add_action('save_post_page', 'boblift_save_terms_of_use_page_meta_box', 10, 2);

function boblift_save_sitemap_page_meta_box(int $post_id, WP_Post $post): void
{
    boblift_save_page_section_meta_box($post_id, $post, 'sitemap_page', 'boblift_sitemap_page', 'boblift_sitemap_page_nonce', 'boblift_sitemap_page_meta_box', 'boblift_is_sitemap_page_post');
}
add_action('save_post_page', 'boblift_save_sitemap_page_meta_box', 10, 2);

function boblift_save_page_section_meta_box(int $post_id, WP_Post $post, string $section, string $field, string $nonce_name, string $nonce_action, callable $matcher): void
{
    if (!$matcher($post) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST[$nonce_name]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action)) {
        return;
    }
    if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
        return;
    }

    $posted = wp_unslash($_POST[$field]);
    $sanitized = boblift_sanitize_options_recursive([$section => $posted]);
    $language = function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
    $existing = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language) : get_option('boblift_theme_options', []);
    $options = boblift_array_replace_recursive(is_array($existing) ? $existing : [], $sanitized);
    if (function_exists('boblift_mls_update_language_options')) {
        boblift_mls_update_language_options($options, $language);
    } else {
        update_option('boblift_theme_options', $options);
    }
}

function boblift_save_public_url_meta_box(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'page' || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_public_url_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_public_url_nonce'])), 'boblift_public_url_meta_box')) {
        return;
    }

    $old = (string) get_post_meta($post_id, '_boblift_public_url', true);
    $new = isset($_POST['boblift_public_url']) ? boblift_normalize_public_path((string) wp_unslash($_POST['boblift_public_url'])) : '';
    if ($new === '') {
        $new = boblift_page_public_url_admin_value($post);
    }
    update_post_meta($post_id, '_boblift_public_url', $new);
    if ($old !== $new) {
        flush_rewrite_rules(false);
    }
}
add_action('save_post_page', 'boblift_save_public_url_meta_box', 20, 2);

function boblift_save_seo_meta_box(int $post_id, WP_Post $post): void
{
    if (!in_array($post->post_type, ['page', 'post'], true) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['boblift_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_seo_nonce'])), 'boblift_seo_meta_box')) {
        return;
    }
    $title = isset($_POST['boblift_seo_title']) ? sanitize_text_field(wp_unslash($_POST['boblift_seo_title'])) : '';
    $description = isset($_POST['boblift_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['boblift_seo_description'])) : '';
    update_post_meta($post_id, '_seo_title', $title !== '' ? $title : boblift_seo_default_title_for_post($post));
    update_post_meta($post_id, '_seo_description', $description !== '' ? $description : boblift_seo_default_description_for_post($post));
}
add_action('save_post_page', 'boblift_save_seo_meta_box', 30, 2);
add_action('save_post_post', 'boblift_save_seo_meta_box', 30, 2);


function boblift_settings_page(): void
{
    boblift_global_settings_page();
}

function boblift_admin_page_start(string $title, string $description): void
{
    $language = boblift_admin_current_language();
    $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : 'boblift-global-settings';
    echo '<div class="wrap boblift-admin-wrap">';
    echo '<h1>' . esc_html($title) . '</h1>';
    echo '<p>' . esc_html($description) . '</p>';
    boblift_admin_about_page_styles();
    boblift_admin_settings_search_controls($page);
    if (function_exists('boblift_mls_render_settings_controller')) {
        boblift_mls_render_settings_controller($page, $language);
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="boblift_save_theme_settings">';
    echo '<input type="hidden" name="boblift_admin_language" value="' . esc_attr($language) . '">';
    echo '<input type="hidden" name="boblift_admin_page" value="' . esc_attr($page) . '">';
    wp_nonce_field('boblift_save_theme_settings');
}

function boblift_admin_page_end(): void
{
    submit_button();
    echo '</form></div>';
}

function boblift_admin_site_ui_section_label(string $section): string
{
    return [
        'header' => '通用导航',
        'contactPage' => 'Contact 页面',
        'quotePage' => 'Quote 页面',
        'quoteForm' => '询盘表单',
        'quoteList' => '统一询盘清单',
        'newsPage' => 'News 列表页',
        'newsDetail' => 'News 详情页',
        'casesPage' => 'Cases 列表页',
        'caseDetail' => 'Case 详情页',
        'successPage' => 'Success 页面',
        'productArchive' => '产品列表页',
        'productDetail' => '产品详情页',
        'aboutPage' => 'About 页面补充',
        'homePage' => '首页通用文案',
        'common' => '通用兜底文案',
        'videoCenter' => '视频中心 / 产品页视频',
        'qualityTesting' => '质量与测试页面',
        'factoryTour' => '工厂实景页面',
        'mountingShipping' => '装车与出口页面',
        'service' => '售后与操作页面',
        'search' => '站内搜索',
        'siteSwitcher' => '站点切换',
    ][$section] ?? $section;
}

function boblift_admin_site_ui_field_label(string $key): string
{
    if (ctype_digit($key)) {
        return '选项 ' . (string) ((int) $key + 1);
    }
    $labels = [
        'label' => '小标签', 'title' => '标题', 'description' => '描述', 'text' => '正文', 'button' => '按钮', 'image' => '图片',
        'heroImage' => 'Hero 图片', 'heroImageAlt' => 'Hero 图片 Alt',
        'heroLabel' => 'Hero 小标签', 'heroTitle' => 'Hero 标题', 'heroDescription' => 'Hero 描述', 'heroAriaLabel' => 'Hero 区域无障碍标签',
        'buyerDecisionLabel' => '快速适配检查小标签', 'buyerDecisionTitle' => '快速适配检查标题', 'buyerDecisionDescription' => '快速适配检查说明',
        'modalLabel' => '弹窗小标签', 'modalTitle' => '弹窗标题', 'submitLabel' => '提交按钮', 'sideLabel' => '侧栏小标签', 'sideTitle' => '侧栏标题', 'sideDescription' => '侧栏描述',
        'requestQuoteLabel' => '询盘按钮', 'productLinkHref' => '产品入口链接', 'productLinkLabel' => '产品入口文案', 'addToQuoteListLabel' => '加入询价清单按钮', 'addedToQuoteListLabel' => '已加入询价清单按钮', 'heroQuoteListLabel' => '首屏询价清单按钮', 'heroQuoteListActiveLabel' => '首屏已加入询价清单按钮', 'heroPdfLabel' => '首屏 PDF 按钮', 'productIntroLabel' => '产品介绍小标签', 'viewDetailsLabel' => '详情按钮', 'backLabel' => '返回按钮', 'readMoreLabel' => '阅读全文按钮',
        'cinemaSpecsLabel' => '电影感参数区小标签', 'cinemaApplicationsLabel' => '电影感应用场景小标签', 'cinemaDetailsLabel' => '电影感详情长图小标签', 'cinemaGalleryLabel' => '电影感图库小标签',
        'placeholder' => '占位提示', 'messagePlaceholder' => '详情占位提示', 'successInlineText' => '成功提示', 'defaultInquiryText' => '默认询盘内容', 'inquirySubject' => '询盘邮件主题',
        'openLabel' => '打开按钮', 'closeLabel' => '关闭按钮', 'emptyText' => '空状态', 'clearLabel' => '清空按钮', 'requestLabel' => '提交询盘按钮', 'countSuffix' => '数量后缀',
        'selectedProductsLabel' => '已选产品小标签', 'selectedProductsTitle' => '已选产品标题', 'selectedProductsSummary' => '已选产品数量说明', 'selectedProductsAriaLabel' => '已选产品区域辅助标签', 'selectedProductRemoveLabelTemplate' => '移除已选产品按钮文案',
        'pauseHeroLabel' => '首屏轮播暂停按钮文案', 'playHeroLabel' => '首屏轮播播放按钮文案',
        'qualityCertificationsLabel' => '质量认证标题', 'showGalleryTemplate' => '切换图库项文案', 'galleryFallbackLabel' => '图库默认图片说明', 'videoLabel' => '视频标签',
        'detailImagesTitle' => '产品详情长图标题', 'detailImageAltTemplate' => '详情长图 Alt 模板', 'photosLabel' => '产品照片导航标签', 'photoGalleryTitle' => '产品照片区块标题', 'productPhotoLabel' => '产品照片默认说明',
        'home' => '首页', 'news' => '新闻', 'summary' => '摘要标题', 'category' => '分类', 'published' => '发布时间',
        'sourceLabel' => '来源标签', 'sourceValue' => '来源名称', 'related' => '相关推荐', 'relatedLabel' => '相关推荐标签', 'quoteCtaLabel' => '询盘 CTA 文案',
        'languageMarkets' => '语言市场文案', 'regionalMarketLabel' => '默认市场文案', 'languageFlagAltTemplate' => '国旗 Alt 模板',
        'defaultGalleryAlts' => '默认图库 Alt', 'breadcrumbLabel' => '面包屑辅助标签', 'brandLabel' => '品牌文案', 'featuredLabel' => '精选文章标签',
        'maxLift' => '最大起重量', 'boomLength' => '臂长', 'rotation' => '回转', 'customValue' => '自定义参数值',
        'filtersTitle' => '筛选器标题', 'clearAllLabel' => '清空全部按钮', 'clearFiltersLabel' => '清空筛选按钮', 'closeFiltersLabel' => '关闭筛选按钮',
        'categoryFilterLabel' => '分类筛选标签', 'capacityFilterLabel' => '起重量筛选标签', 'boomFilterLabel' => '臂架类型筛选标签', 'applicationFilterLabel' => '应用场景筛选标签',
        'availabilityFilterLabel' => '供货状态筛选标签', 'showingTemplate' => '产品数量显示模板', 'filterButtonLabel' => '筛选按钮', 'sortByLabel' => '排序标签',
        'viewModeLabel' => '视图模式标签', 'gridViewLabel' => '网格视图按钮', 'listViewLabel' => '列表视图按钮', 'paginationLabel' => '分页标签',
        'previousLabel' => '上一页按钮', 'nextLabel' => '下一页按钮', 'emptyTitle' => '空结果标题',
    ];
    if (isset($labels[$key])) {
        return $labels[$key];
    }
    $fallback = preg_replace('/(?<!^)([A-Z])/', ' $1', $key) ?? $key;
    return ucwords(str_replace(['_', '-'], ' ', $fallback));
}

function boblift_admin_site_ui_is_editable_scalar(string $key, mixed $value): bool
{
    if (!is_scalar($value)) {
        return false;
    }
    if (in_array($key, ['id', 'href', 'url', 'src', 'path', 'slug', 'icon', 'iconName', 'relatedArticleCount', 'galleryImageCount'], true)) {
        return false;
    }
    $text = trim((string) $value);
    return $text !== '' && !preg_match('#^(https?:)?//#i', $text) && !preg_match('#^[\d\s.,+\-/%°×x]+$#u', $text);
}

function boblift_admin_site_ui_is_image_field(string $key, mixed $value): bool
{
    if (!is_scalar($value)) {
        return false;
    }
    $path = trim((string) $value);
    if ($path === '') {
        return false;
    }
    return in_array($key, ['heroImage', 'image'], true) || str_ends_with($key, 'Image');
}

function boblift_admin_site_ui_media_path(string $field_path, string $key, mixed $value, mixed $default_value): void
{
    $path_value = trim((string) $value);
    $default_path = trim((string) $default_value);
    $preview = function_exists('boblift_image_src') ? boblift_image_src($path_value) : $path_value;
    $label = boblift_admin_site_ui_field_label($key);
    echo '<div class="boblift-admin-field boblift-site-ui-field boblift-media-field" data-site-ui-field data-site-ui-label="' . esc_attr($label) . '" data-site-ui-key="' . esc_attr($key) . '" data-site-ui-path="' . esc_attr($field_path) . '" data-fallback="' . esc_attr($default_path) . '">';
    echo '<label><span>' . esc_html($label) . '</span><span class="boblift-site-ui-key"><code>' . esc_html($key) . '</code></span></label>';
    echo '<img class="boblift-media-preview' . ($preview === '' ? ' is-hidden' : '') . '" src="' . esc_url($preview) . '" alt="">';
    echo '<input class="boblift-media-path-value" type="hidden" name="' . esc_attr(boblift_admin_option_name($field_path)) . '" value="' . esc_attr($path_value) . '">';
    echo '<button class="button boblift-media-select" type="button">选择图片</button>';
    echo '<button class="button boblift-media-clear" type="button">使用默认图</button>';
    echo '<p class="boblift-media-help">该字段保存为前台可直接读取的图片地址，适合 Contact / News / Quote / Cases 等通用页面 Hero 图片。</p>';
    echo '</div>';
}

function boblift_admin_site_ui_array_meta(array $value): string
{
    foreach (['title', 'label', 'name', 'question', 'role', 'text'] as $key) {
        if (isset($value[$key]) && is_scalar($value[$key])) {
            $meta = trim((string) $value[$key]);
            if ($meta !== '') {
                if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                    return mb_strlen($meta) > 36 ? mb_substr($meta, 0, 36) . '…' : $meta;
                }
                return strlen($meta) > 72 ? substr($meta, 0, 72) . '…' : $meta;
            }
        }
    }
    return '';
}

function boblift_admin_site_ui_array_label(string $key, mixed $value): string
{
    if (ctype_digit($key)) {
        return '项目 ' . (string) ((int) $key + 1);
    }
    return boblift_admin_site_ui_field_label($key);
}

function boblift_admin_site_ui_panel(string $label, string $key, string $field_path, callable $render, string $class = 'boblift-admin-panel boblift-site-ui-panel', string $meta = ''): void
{
    $panel_search = trim($label . ' ' . $key . ' ' . $field_path . ' ' . $meta);
    echo '<details class="' . esc_attr($class) . '" style="margin:10px 0;" data-site-ui-panel data-site-ui-search="' . esc_attr($panel_search) . '">';
    echo '<summary><span>' . esc_html($label) . '</span>';
    if ($meta !== '') {
        echo '<span class="boblift-admin-subpanel-meta">' . esc_html($meta) . '</span>';
    }
    echo '<span class="boblift-site-ui-key"><code>' . esc_html($key) . '</code></span></summary><div class="boblift-admin-panel-body">';
    $render();
    echo '</div></details>';
}

function boblift_admin_settings_search_controls(string $page): void
{
    static $rendered_assets = false;
    $input_id = 'boblift-settings-search-' . wp_generate_uuid4();
    if (!$rendered_assets) {
        $rendered_assets = true;
        echo '<style>.boblift-settings-search{margin:14px 0 16px;padding:14px;border-left:4px solid #3b5bff;background:#fff}.boblift-settings-search-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.boblift-settings-search input{min-width:280px;max-width:560px;width:100%}.boblift-settings-search-result{color:#646970}.boblift-site-ui-key{display:block;margin-top:4px;color:#646970;font-size:12px;font-weight:500;line-height:1.4}.boblift-site-ui-key code{font-size:12px}.boblift-admin-search-hidden{display:none!important}@media(max-width:782px){.boblift-settings-search input{min-width:0}}</style>';
        echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-boblift-settings-search-root]').forEach(function (root) {
    var input = root.querySelector('[data-boblift-settings-search-input]');
    var clear = root.querySelector('[data-boblift-settings-search-clear]');
    var result = root.querySelector('[data-boblift-settings-search-result]');
    var wrap = root.closest('.boblift-admin-wrap');
    var form = wrap ? wrap.querySelector('form') : null;
    if (!input || !form) {
      return;
    }
    var itemSelector = '.boblift-admin-field, .boblift-admin-card, .boblift-admin-menu-row, .boblift-admin-product-groups-editor, .boblift-floating-contact-row, .boblift-floating-contact-editor';
    var items = Array.prototype.slice.call(form.querySelectorAll(itemSelector));
    var panels = Array.prototype.slice.call(form.querySelectorAll('.boblift-admin-panel'));
    function itemText(item) {
      var values = Array.prototype.slice.call(item.querySelectorAll('input, textarea, select')).map(function (control) {
        if (control.type === 'hidden') {
          return '';
        }
        if (control.tagName === 'SELECT') {
          var option = control.options[control.selectedIndex];
          return [control.value || '', option ? option.text : ''].join(' ');
        }
        return control.value || '';
      });
      return [item.innerText || '', item.dataset.siteUiLabel || '', item.dataset.siteUiKey || '', item.dataset.siteUiPath || ''].concat(values).join(' ').toLowerCase();
    }
    function panelOwnText(panel) {
      var summary = panel.querySelector(':scope > summary');
      return summary ? summary.innerText.toLowerCase() : '';
    }
    function hasVisibleSearchItem(panel) {
      return !!panel.querySelector(itemSelector + ':not(.boblift-admin-search-hidden), .boblift-admin-panel:not(.boblift-admin-search-hidden)');
    }
    function applySearch() {
      var keyword = input.value.trim().toLowerCase();
      var matched = 0;
      items.forEach(function (item) {
        var ok = keyword === '' || itemText(item).indexOf(keyword) !== -1;
        item.classList.toggle('boblift-admin-search-hidden', !ok);
        if (ok && keyword !== '') {
          matched += 1;
        }
      });
      panels.slice().reverse().forEach(function (panel) {
        var ownMatch = keyword !== '' && panelOwnText(panel).indexOf(keyword) !== -1;
        var ok = keyword === '' || ownMatch || hasVisibleSearchItem(panel);
        panel.classList.toggle('boblift-admin-search-hidden', !ok);
        if (keyword !== '' && ok) {
          panel.open = true;
        } else if (keyword === '') {
          panel.open = false;
        }
      });
      if (result) {
        result.textContent = keyword === '' ? '输入关键词可搜索本设置页的标题、标签、英文 key 和字段内容。' : (matched > 0 ? '找到 ' + matched + ' 个匹配项。' : '没有找到匹配字段。');
      }
    }
    input.addEventListener('input', applySearch);
    if (clear) {
      clear.addEventListener('click', function () {
        input.value = '';
        applySearch();
        input.focus();
      });
    }
    applySearch();
  });
});
</script>
HTML;
    }
    echo '<div class="boblift-settings-search" data-boblift-settings-search-root data-page="' . esc_attr($page) . '">';
    echo '<label for="' . esc_attr($input_id) . '" style="display:block;margin:0 0 8px;font-weight:700;">搜索当前设置页</label>';
    echo '<div class="boblift-settings-search-row"><input id="' . esc_attr($input_id) . '" type="search" class="regular-text" data-boblift-settings-search-input placeholder="输入标题、标签、英文 key 或当前内容"><button type="button" class="button" data-boblift-settings-search-clear>清空</button><span class="boblift-settings-search-result" data-boblift-settings-search-result></span></div>';
    echo '</div>';
}


function boblift_admin_site_ui_fields(array $defaults, array $values, string $path = 'site_ui', int $depth = 0): void
{
    foreach ($defaults as $key => $default_value) {
        $key_string = (string) $key;
        $current = is_array($values) && array_key_exists($key, $values) ? $values[$key] : $default_value;
        $field_path = $path . '.' . $key_string;
        if (is_array($default_value)) {
            if ($depth <= 1) {
                $panel_label = $depth === 0 ? boblift_admin_site_ui_section_label($key_string) : boblift_admin_site_ui_field_label($key_string);
                boblift_admin_site_ui_panel($panel_label, $key_string, $field_path, static function () use ($default_value, $current, $field_path, $depth): void {
                    boblift_admin_site_ui_fields($default_value, is_array($current) ? $current : [], $field_path, $depth + 1);
                });
            } else {
                $subpanel_label = boblift_admin_site_ui_array_label($key_string, $default_value);
                $meta = boblift_admin_site_ui_array_meta(is_array($current) ? $current : $default_value);
                boblift_admin_site_ui_panel($subpanel_label, $key_string, $field_path, static function () use ($default_value, $current, $field_path, $depth): void {
                    boblift_admin_site_ui_fields($default_value, is_array($current) ? $current : [], $field_path, $depth + 1);
                }, 'boblift-admin-card boblift-admin-subpanel boblift-site-ui-panel', $meta);
            }
            continue;
        }
        $value = is_scalar($current) && trim((string) $current) !== '' ? $current : $default_value;
        if (boblift_admin_site_ui_is_image_field($key_string, $value)) {
            boblift_admin_site_ui_media_path($field_path, $key_string, $value, $default_value);
            continue;
        }
        if (!boblift_admin_site_ui_is_editable_scalar($key_string, $default_value)) {
            continue;
        }
        $label = boblift_admin_site_ui_field_label($key_string);
        $string_value = (string) $value;
        echo '<div class="boblift-admin-field boblift-site-ui-field" data-site-ui-field data-site-ui-label="' . esc_attr($label) . '" data-site-ui-key="' . esc_attr($key_string) . '" data-site-ui-path="' . esc_attr($field_path) . '">';
        echo '<label><span>' . esc_html($label) . '</span><span class="boblift-site-ui-key"><code>' . esc_html($key_string) . '</code></span></label>';
        if (strlen($string_value) > 90 || str_contains($string_value, '.')) {
            echo '<textarea name="' . esc_attr(boblift_admin_option_name($field_path)) . '">' . esc_textarea($string_value) . '</textarea>';
        } else {
            echo '<input class="regular-text" type="text" name="' . esc_attr(boblift_admin_option_name($field_path)) . '" value="' . esc_attr($string_value) . '">';
        }
        echo '</div>';
    }
}



function boblift_floating_contact_type_choices(): array
{
    return [
        'quote' => '询盘弹窗',
        'email' => 'Email 邮箱',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'phone' => '电话',
        'wechat' => '微信二维码',
        'custom' => '自定义链接',
    ];
}

function boblift_floating_contact_icon_choices(): array
{
    return [
        'send' => '询盘 / 纸飞机',
        'mail' => '邮箱',
        'message-circle' => '聊天气泡',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'phone-call' => '电话',
        'qr' => '二维码',
        'arrow-right' => '箭头',
    ];
}

function boblift_admin_floating_contact_buttons_editor(array $options): void
{
    $buttons = is_array($options['floating_contact_buttons'] ?? null) ? $options['floating_contact_buttons'] : (function_exists('boblift_floating_contact_button_defaults') ? boblift_floating_contact_button_defaults() : []);
    ?>
    <div class="boblift-floating-contact-editor" data-floating-contact-editor>
        <p class="boblift-admin-note">这里控制前台悬浮客服按钮。配置保存在当前语言下，切换后台语言后可维护不同语言的按钮文字和链接。</p>
        <input type="hidden" name="boblift_floating_contact_buttons_present" value="1">
        <div class="boblift-floating-contact-list" data-floating-contact-list>
            <?php foreach ($buttons as $index => $button) : ?>
                <?php if (is_array($button)) : ?>
                    <?php boblift_admin_floating_contact_button_row($button, (int) $index); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="boblift-floating-contact-toolbar"><button type="button" class="button button-primary" data-floating-action="add">新增客服按钮</button><span>使用上移 / 下移排序。</span></div>
        <p class="boblift-admin-note">常用类型的链接可以留空：Email、WhatsApp、Telegram、电话会自动读取上方联系方式。Telegram 可直接填写号码、@用户名或完整链接。</p>
        <template data-template="floating-contact-row">
            <?php boblift_admin_floating_contact_button_row(['enabled' => '1', 'type' => 'custom', 'label' => '', 'href' => '', 'icon' => 'arrow-right', 'target' => ''], '__INDEX__'); ?>
        </template>
    </div>
    <?php
}

function boblift_admin_floating_contact_button_row(array $button, int|string $index): void
{
    $index_string = (string) $index;
    $label = trim((string) ($button['label'] ?? ''));
    $type = sanitize_key((string) ($button['type'] ?? 'custom'));
    $title = $label !== '' ? $label : (boblift_floating_contact_type_choices()[$type] ?? '客服按钮');
    $base_path = "floating_contact_buttons.$index_string";
    ?>
    <details class="boblift-floating-contact-row" data-floating-contact-row>
        <summary class="boblift-floating-contact-summary">
            <span class="boblift-floating-contact-index" data-role="floating-index"><?php echo esc_html(is_int($index) ? (string) ($index + 1) : '#'); ?></span>
            <span class="boblift-floating-contact-icon"><?php echo boblift_icon($type === 'whatsapp' ? 'whatsapp' : ($type === 'telegram' ? 'telegram' : (string) ($button['icon'] ?? 'arrow-right')), 'h-5 w-5'); ?></span>
            <span class="boblift-floating-contact-title" data-role="floating-label"><?php echo esc_html($title); ?></span>
            <span class="boblift-floating-contact-type"><?php echo esc_html(boblift_floating_contact_type_choices()[$type] ?? '自定义链接'); ?></span>
            <span class="boblift-floating-contact-actions">
                <button type="button" class="button button-small" data-floating-action="up">上移</button>
                <button type="button" class="button button-small" data-floating-action="down">下移</button>
                <button type="button" class="button button-small button-link-delete" data-floating-action="delete">删除</button>
            </span>
        </summary>
        <div class="boblift-floating-contact-body">
            <?php boblift_admin_checkbox($base_path . '.enabled', ['floating_contact_buttons' => [$index_string => $button]], '启用'); ?>
            <?php boblift_admin_select($base_path . '.type', ['floating_contact_buttons' => [$index_string => $button]], '按钮类型', boblift_floating_contact_type_choices()); ?>
            <?php boblift_admin_input($base_path . '.label', ['floating_contact_buttons' => [$index_string => $button]], '显示名称'); ?>
            <?php boblift_admin_input($base_path . '.href', ['floating_contact_buttons' => [$index_string => $button]], '链接地址'); ?>
            <?php boblift_admin_select($base_path . '.icon', ['floating_contact_buttons' => [$index_string => $button]], '图标', boblift_floating_contact_icon_choices()); ?>
            <?php $target_name = boblift_admin_option_name($base_path . '.target'); ?>
            <div class="boblift-admin-field">
                <input type="hidden" name="<?php echo esc_attr($target_name); ?>" value="0">
                <label><input type="checkbox" name="<?php echo esc_attr($target_name); ?>" value="_blank" <?php checked((string) ($button['target'] ?? ''), '_blank'); ?>> 新窗口打开</label>
            </div>
        </div>
    </details>
    <?php
}

function boblift_global_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = boblift_options();
    boblift_admin_page_start('BOB-LIFT 全局设置', '维护全站通用品牌、联系方式、顶部公告条、底部 CTA 和产品页默认 Banner。');
    ?>
    <?php boblift_admin_settings_panel_start('品牌 Logo', '对应前台 Header 和 Footer 的 BOB-LIFT Logo。'); ?>
        <?php boblift_admin_media('branding.logo', $options, 'Logo 图片'); ?>
        <?php boblift_admin_input('branding.logo_alt', $options, 'Logo Alt 文案'); ?>
        <?php boblift_admin_input('branding.logo_width', $options, 'Logo 宽度'); ?>
        <?php boblift_admin_input('branding.logo_height', $options, 'Logo 高度'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('联系方式', '对应 Header 二级菜单、Footer 联系区、悬浮联系按钮和询盘入口。'); ?>
        <?php foreach ([['contact.email','销售邮箱'],['contact.phone','电话'],['contact.whatsapp','WhatsApp'],['contact.telegram','Telegram 号码 / 用户名'],['contact.address','地址'],['contact.phone_href','电话链接'],['contact.wechat_id','微信号']] as [$path, $label]) : ?>
            <?php boblift_admin_input($path, $options, $label); ?>
        <?php endforeach; ?>
        <?php boblift_admin_link_input('contact.contact_path', $options, 'Contact 页面链接'); ?>
        <?php boblift_admin_link_input('contact.quote_path', $options, 'Quote 页面链接'); ?>
        <?php boblift_admin_media('contact.wechat_qr', $options, '微信二维码'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('悬浮客服按钮', '对应前台底部 / 侧边悬浮客服按钮，可新增、删除、排序，并按当前语言分别保存。'); ?>
        <?php boblift_admin_floating_contact_buttons_editor($options); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    $top_bar_options = $options;
    if (!isset($top_bar_options['top_bar']) || !is_array($top_bar_options['top_bar'])) {
        $top_bar_options['top_bar'] = [];
    }
    if (!array_key_exists('enabled', $top_bar_options['top_bar'])) {
        $top_bar_options['top_bar']['enabled'] = '1';
    }
    ?>
    <?php boblift_admin_settings_panel_start('顶部公告条', '控制网站最顶部深蓝色公告条，适合活动月、临时通知或展会公告。'); ?>
        <?php boblift_admin_checkbox('top_bar.enabled', $top_bar_options, '前台显示顶部公告条'); ?>
        <?php boblift_admin_input('top_bar.highlight', $options, '高亮文案'); ?>
        <?php boblift_admin_input('top_bar.text', $options, '普通文案'); ?>
        <?php boblift_admin_input('top_bar.link_label', $options, '链接文字'); ?>
        <?php boblift_admin_link_input('top_bar.link_href', $options, '链接地址'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('底部 CTA', '对应多数页面 Footer 上方的大图询盘横幅。'); ?>
        <?php boblift_admin_input('final_cta.label', $options, '小标签'); ?>
        <?php boblift_admin_input('final_cta.title', $options, '标题'); ?>
        <?php boblift_admin_input('final_cta.button_label', $options, '按钮文字'); ?>
        <?php boblift_admin_link_input('final_cta.button_href', $options, '按钮链接'); ?>
        <?php boblift_admin_media('final_cta.image', $options, '照片', 'boblift_theme_options', '横向实景照片，推荐 2172×724（3:1）。主体放在右半边，左侧会渐隐进深蓝底色放标题；色调偏冷、偏暗的照片和深蓝底色更协调。'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('产品页默认 Banner', '对应产品总页未选择分类时的顶部 Banner。单个分类 Banner 请到「产品展示 → 产品分类」里编辑。'); ?>
        <?php $product_archive = boblift_product_archive_options(); ?>
        <?php boblift_admin_input('label', $product_archive, '小标签', 'boblift_theme_options[product_archive]'); ?>
        <?php boblift_admin_input('title', $product_archive, '标题', 'boblift_theme_options[product_archive]'); ?>
        <?php boblift_admin_textarea('text', $product_archive, '描述', 'boblift_theme_options[product_archive]'); ?>
        <?php
        // Without an uploaded image the banner shows the built-in photo, so preview that one.
        // A saved custom path or URL is kept; only an empty path or the retired default image shows the built-in photo.
        $saved_banner_path = trim((string) ($product_archive['image']['path'] ?? ''));
        if ((int) ($product_archive['image']['id'] ?? 0) <= 0 && ($saved_banner_path === '' || $saved_banner_path === 'images/products/truck-mounted-crane.png')) {
            $product_archive['image'] = ['id' => 0, 'path' => 'images/product-banners/default.jpg'];
        }
        ?>
        <?php boblift_admin_media('image', $product_archive, 'Banner 照片', 'boblift_theme_options[product_archive]', '横向实景照片，推荐 1920×640（3:1）。主体放在右半边，左侧留出浅色空间给标题；手机上照片单独显示在标题上方。不上传则使用主题内置照片。'); ?>
        <?php boblift_admin_input('image_alt', $product_archive, '照片 Alt（上传照片时使用）', 'boblift_theme_options[product_archive]'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('通用页面文案', '对应导航、询盘弹窗、Quote List、News/Success/详情页等模板固定文案。默认折叠；可按中文标签、英文 key 或当前内容搜索。'); ?>
        <?php $site_ui_defaults = function_exists('boblift_site_ui_defaults') ? boblift_site_ui_defaults() : []; ?>
        <?php $site_ui_values = is_array($options['site_ui'] ?? null) ? $options['site_ui'] : []; ?>
        <div class="boblift-site-ui-editor">
            <?php boblift_admin_site_ui_fields($site_ui_defaults, $site_ui_values); ?>
        </div>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    boblift_admin_page_end();
}

function boblift_admin_nav_row(array $options, array $nav_items, int|string $index, bool $open = false): void
{
    $index_string = (string) $index;
    $label = is_int($index) ? boblift_admin_nav_title($nav_items, $index, '导航项') : '新导航项';
    $has_menu = (string) boblift_admin_option_value("nav_items.$index_string.has_menu", $options) === '1' || boblift_admin_option_value("nav_items.$index_string.has_menu", $options) === true;
    ?>
    <details class="boblift-admin-menu-row boblift-admin-nav-row" data-reindex-item="nav" <?php echo $open ? 'open' : ''; ?>>
        <summary class="boblift-admin-menu-summary">
            <span class="boblift-admin-menu-id" data-role="nav-index"><?php echo esc_html(is_int($index) ? (string) ($index + 1) : '#'); ?></span>
            <span class="boblift-admin-menu-label" data-role="nav-label"><?php echo esc_html($label); ?></span>
            <span class="boblift-admin-menu-actions">
                <button type="button" class="button button-small" data-menu-action="up">上移</button>
                <button type="button" class="button button-small" data-menu-action="down">下移</button>
                <button type="button" class="button button-small button-link-delete" data-menu-action="delete">删除</button>
            </span>
        </summary>
        <div class="boblift-admin-menu-body">
            <?php boblift_admin_input("nav_items.$index_string.label", $options, '显示文字'); ?>
            <?php boblift_admin_link_input("nav_items.$index_string.href", $options, '链接地址'); ?>
            <?php boblift_admin_checkbox("nav_items.$index_string.has_menu", $options, '启用二级菜单'); ?>
            <div class="boblift-admin-menu-inline-slot" data-menu-slot <?php echo $has_menu ? '' : 'hidden'; ?>></div>
        </div>
    </details>
    <?php
}

function boblift_admin_product_group_row(array $options, array $product_groups, int|string $group_index, bool $open = false): void
{
    $group_index_string = (string) $group_index;
    $links = is_int($group_index) && is_array($product_groups[$group_index]['links'] ?? null) ? $product_groups[$group_index]['links'] : [];
    $title = is_int($group_index) ? boblift_admin_nav_title($product_groups, $group_index, '二级菜单组', 'title') : '新二级菜单组';
    ?>
    <details class="boblift-admin-menu-row boblift-admin-product-group-row" data-reindex-item="group" <?php echo $open ? 'open' : ''; ?>>
        <summary class="boblift-admin-menu-summary">
            <span class="boblift-admin-menu-id" data-role="group-index"><?php echo esc_html(is_int($group_index) ? (string) ($group_index + 1) : '#'); ?></span>
            <span class="boblift-admin-menu-label" data-role="group-label"><?php echo esc_html($title); ?><em class="boblift-admin-menu-count" data-role="link-count">（子链接：<?php echo esc_html((string) count($links)); ?> 条）</em></span>
            <span class="boblift-admin-menu-actions">
                <button type="button" class="button button-small" data-menu-action="up">上移</button>
                <button type="button" class="button button-small" data-menu-action="down">下移</button>
                <button type="button" class="button button-small button-link-delete" data-menu-action="delete">删除</button>
            </span>
        </summary>
        <div class="boblift-admin-menu-body">
            <?php boblift_admin_input("product_nav_groups.$group_index_string.title", $options, '组标题'); ?>
            <?php boblift_admin_link_input("product_nav_groups.$group_index_string.href", $options, '组链接'); ?>
            <?php boblift_admin_textarea("product_nav_groups.$group_index_string.description", $options, '组描述'); ?>
            <?php boblift_admin_input("product_nav_groups.$group_index_string.icon", $options, '图标名'); ?>
            <?php boblift_admin_media("product_nav_groups.$group_index_string.image", $options, '组背景图'); ?>
            <div class="boblift-admin-submenu-links" data-link-list>
                <div class="boblift-admin-menu-head"><span>ID</span><span>子链接</span><span>操作</span></div>
                <?php foreach ($links as $link_index => $link) : ?>
                    <?php if (is_array($link)) : ?>
                        <?php boblift_admin_product_link_row($options, $links, $group_index_string, (int) $link_index); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" data-menu-action="add-link">新增子链接</button></p>
        </div>
    </details>
    <?php
}

function boblift_admin_product_link_row(array $options, array $links, int|string $group_index, int|string $link_index): void
{
    $group_index_string = (string) $group_index;
    $link_index_string = (string) $link_index;
    $label = is_int($link_index) ? boblift_admin_nav_title($links, $link_index, '子链接') : '新子链接';
    ?>
    <details class="boblift-admin-menu-row boblift-admin-menu-child boblift-admin-product-link-row" data-reindex-item="link">
        <summary class="boblift-admin-menu-summary">
            <span class="boblift-admin-menu-id" data-role="link-index"><?php echo esc_html(is_int($link_index) ? (string) ($link_index + 1) : '#'); ?></span>
            <span class="boblift-admin-menu-label" data-role="link-label"><?php echo esc_html($label); ?></span>
            <span class="boblift-admin-menu-actions">
                <button type="button" class="button button-small" data-menu-action="up">上移</button>
                <button type="button" class="button button-small" data-menu-action="down">下移</button>
                <button type="button" class="button button-small button-link-delete" data-menu-action="delete">删除</button>
            </span>
        </summary>
        <div class="boblift-admin-menu-body">
            <?php boblift_admin_input("product_nav_groups.$group_index_string.links.$link_index_string.label", $options, '显示文字'); ?>
            <?php boblift_admin_link_input("product_nav_groups.$group_index_string.links.$link_index_string.href", $options, '链接地址'); ?>
            <?php boblift_admin_input("product_nav_groups.$group_index_string.links.$link_index_string.icon", $options, '图标名'); ?>
        </div>
    </details>
    <?php
}

function boblift_admin_product_groups_editor(array $options, array $product_groups): void
{
    ?>
    <div class="boblift-admin-product-groups-editor" data-product-groups-editor>
        <h3>二级菜单</h3>
        <p class="boblift-admin-note">这里编辑保存的自定义产品菜单（<code>product_nav_groups</code>），前台按它显示。</p>
        <div class="boblift-admin-note" style="border:1px solid #f0b429;padding:12px;margin:12px 0;">
            <label><input type="checkbox" name="boblift_reset_product_menu" value="1"> 恢复为按产品分类自动生成的菜单（自定义分组会被清除，无法撤销）</label>
        </div>
        <input type="hidden" name="boblift_product_nav_groups_present" value="1">
        <div class="boblift-admin-menu-table" data-group-list>
            <div class="boblift-admin-menu-head"><span>ID</span><span>二级菜单组</span><span>操作</span></div>
            <?php foreach ($product_groups as $group_index => $group) : ?>
                <?php if (is_array($group)) : ?>
                    <?php boblift_admin_product_group_row($options, $product_groups, (int) $group_index); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button" data-menu-action="add-group">新增二级菜单组</button></p>
    </div>
    <?php
}

function boblift_navigation_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = boblift_options();
    $nav_items = is_array($options['nav_items'] ?? null) ? $options['nav_items'] : [];
    $menu_index = null;
    foreach ($nav_items as $index => $item) {
        if (is_array($item) && !empty($item['has_menu'])) {
            $menu_index = (int) $index;
            break;
        }
    }
    $product_groups = is_array($options['product_nav_groups'] ?? null) ? $options['product_nav_groups'] : [];
    $curated_menu_active = function_exists('boblift_curated_product_menu_families') && boblift_curated_product_menu_families() !== [];
    boblift_admin_page_start('BOB-LIFT 导航菜单', '维护前台主导航；勾选“启用二级菜单”的导航项会显示产品下拉菜单，菜单内容按产品分类自动生成。');
    ?>
    <?php boblift_admin_settings_panel_start('主导航', '对应 Header 横向导航；这里只保留一个主导航折叠管理器。'); ?>
        <input type="hidden" name="boblift_nav_items_present" value="1">
        <div class="boblift-admin-nav-builder" data-nav-builder>
            <div class="boblift-admin-menu-table" data-nav-list>
                <div class="boblift-admin-menu-head"><span>ID</span><span>栏目名称</span><span>操作</span></div>
                <?php foreach ($nav_items as $i => $item) : ?>
                    <?php if (is_array($item)) : ?>
                        <?php boblift_admin_nav_row($options, $nav_items, (int) $i, (int) $i === $menu_index); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button button-primary" data-menu-action="add-nav">新增主导航</button></p>
            <div data-product-groups-parking <?php echo $menu_index === null ? '' : 'hidden'; ?>>
                <?php if ($curated_menu_active) : ?>
                    <?php boblift_admin_product_groups_editor($options, $product_groups); ?>
                <?php else : ?>
                    <div class="boblift-admin-product-groups-editor">
                        <h3>二级菜单</h3>
                        <p class="boblift-admin-note">产品下拉菜单按「产品展示 → 产品分类」自动生成：每个顶级分类一行，右侧显示它的 Banner 照片、子分类（没有两个以上子分类时显示型号）和型号数量。要调整菜单，请修改产品分类的名称、层级和 Banner 图片。</p>
                    </div>
                <?php endif; ?>
            </div>
            <template data-template="nav-row"><?php boblift_admin_nav_row([], [], '__NAV_INDEX__', true); ?></template>
            <?php if ($curated_menu_active) : ?>
                <template data-template="group-row"><?php boblift_admin_product_group_row([], [], '__GROUP_INDEX__', true); ?></template>
                <template data-template="link-row"><?php boblift_admin_product_link_row([], [], '__GROUP_INDEX__', '__LINK_INDEX__'); ?></template>
            <?php endif; ?>
        </div>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    boblift_admin_page_end();
}

function boblift_social_media_icon_choices(): array
{
    return [
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'douyin' => 'Douyin',
        'xiaohongshu' => 'Xiaohongshu',
        'wechat' => 'WeChat / 视频号',
        'weibo' => 'Weibo',
        'instagram' => 'Instagram',
        'x' => 'X',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'pinterest' => 'Pinterest',
        'bilibili' => 'Bilibili',
        'kuaishou' => 'Kuaishou',
        'zhihu' => 'Zhihu',
        'baidu' => 'Baidu / Baijiahao',
        'toutiao' => 'Toutiao',
        'youku' => 'Youku',
        'qq' => 'QQ',
        'line' => 'LINE',
        'vk' => 'VK',
        'ok' => 'OK',
        'threads' => 'Threads',
        'viber' => 'Viber',
        'medium' => 'Medium',
        'quora' => 'Quora',
    ];
}

function boblift_admin_social_media_icon_preview(string $icon): string
{
    $icon = sanitize_key($icon);
    $path = 'images/social-icons/' . $icon . '.svg';
    if ($icon === '' || !function_exists('boblift_asset_path') || !is_readable(boblift_asset_path($path))) {
        return '';
    }
    return '<span class="boblift-admin-social-icon-preview" style="display:inline-grid;width:42px;height:42px;place-items:center;border-radius:12px;background:#0B3F7D;margin:0 0 8px 0;"><img src="' . esc_url(boblift_asset($path)) . '" alt="" style="width:24px;height:24px;object-fit:contain;display:block;"></span>';
}


function boblift_render_social_media_page_fields(array $social, string $base): void
{
    $icon_choices = boblift_social_media_icon_choices();
    ?>
    <?php boblift_admin_settings_panel_start('顶部卡片', '页面顶部的标签、标题、说明和照片。'); ?>
        <?php boblift_admin_input('hero.label', $social, '标签', $base); ?>
        <?php boblift_admin_input('labels.main_platforms_title', $social, '标题', $base); ?>
        <?php boblift_admin_textarea('labels.main_platforms_text', $social, '说明', $base); ?>
        <?php boblift_admin_media('hero.image', $social, '照片', $base, '横向实景照片，推荐 1920×640（3:1）。主体放在右半边，左侧留出浅色空间给标题；手机上照片单独显示在标题上方。'); ?>
        <?php boblift_admin_input('hero.image_alt', $social, '照片 Alt', $base); ?>
        <?php if (trim((string) ($social['hero']['title'] ?? '')) !== '' && trim((string) $social['hero']['title']) !== trim((string) ($social['labels']['main_platforms_title'] ?? ''))) : ?>
            <?php boblift_admin_input('hero.title', $social, '旧版首屏标题（仍显示在首屏下方，清空即可移除）', $base); ?>
            <?php boblift_admin_textarea('hero.text', $social, '旧版首屏说明', $base); ?>
        <?php endif; ?>
    <?php boblift_admin_settings_panel_end(); ?>

    <?php boblift_admin_settings_panel_start('区块标题', '官方账号、最近发布两块的标题文字。'); ?>
        <?php boblift_admin_input('labels.main_platforms_label', $social, '官方账号小标签', $base); ?>
        <?php boblift_admin_input('labels.overseas_title', $social, '海外平台分组标题', $base); ?>
        <?php boblift_admin_input('labels.china_title', $social, '国内平台分组标题', $base); ?>
        <?php boblift_admin_input('labels.more_title', $social, '更多平台分组标题', $base); ?>
        <?php boblift_admin_input('labels.posts_label', $social, '最近发布小标签', $base); ?>
        <?php boblift_admin_input('labels.posts_title', $social, '最近发布标题', $base); ?>
        <?php boblift_admin_textarea('labels.posts_text', $social, '最近发布说明', $base); ?>
        <?php boblift_admin_input('labels.videos_title', $social, '短视频列标题', $base); ?>
        <?php boblift_admin_input('labels.image_posts_title', $social, '图文帖子列标题', $base); ?>
    <?php boblift_admin_settings_panel_end(); ?>

    <?php boblift_admin_social_platform_panel('海外平台', 'featured_platforms', $social, $icon_choices, 6, true, $base); ?>
    <?php boblift_admin_social_platform_panel('国内平台', 'major_platforms', $social, $icon_choices, 6, true, $base); ?>
    <?php boblift_admin_social_platform_panel('更多平台（可选）', 'secondary_platforms', $social, $icon_choices, 2, true, $base); ?>

    <?php boblift_admin_settings_panel_start('短视频', '竖版短视频（9:16），点击才加载播放。建议 MP4、540×960、单条 5MB 以内。'); ?>
        <?php $videos = is_array($social['videos'] ?? null) ? $social['videos'] : []; ?>
        <?php for ($i = 0; $i < max(3, count($videos) + 1); $i++) : ?>
            <?php boblift_admin_subpanel('短视频 ' . (string) ($i + 1), static function () use ($social, $base, $i): void { ?>
                <?php boblift_admin_input("videos.$i.title", $social, '标题', $base); ?>
                <?php boblift_admin_input("videos.$i.youtube", $social, 'YouTube 链接或视频 ID（填了就优先从 YouTube 播放）', $base); ?>
                <?php boblift_admin_file_media("videos.$i.video", $social, '视频 MP4（本地预览用，上线后改用 YouTube）', $base, '和 YouTube 都留空则不显示这一条。'); ?>
                <?php boblift_admin_media("videos.$i.poster", $social, '封面图', $base, '竖版 9:16，推荐 720×1280。'); ?>
            <?php }, false, (string) ($videos[$i]['title'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>

    <?php boblift_admin_settings_panel_start('图文帖子', '从社媒已发布的图片里挑选，正方形 1080×1080 最合适；点击可看大图。'); ?>
        <?php $cards = is_array($social['content_cards'] ?? null) ? $social['content_cards'] : []; ?>
        <?php for ($i = 0; $i < max(12, count($cards) + 1); $i++) : ?>
            <?php boblift_admin_subpanel('帖子 ' . (string) ($i + 1), static function () use ($social, $base, $i): void { ?>
                <?php boblift_admin_checkbox("content_cards.$i.enabled", $social, '启用', $base); ?>
                <?php boblift_admin_media("content_cards.$i.image", $social, '帖子图片', $base); ?>
                <?php boblift_admin_input("content_cards.$i.title", $social, '说明（大图下方显示，也用作图片 Alt）', $base); ?>
                <?php boblift_admin_textarea("content_cards.$i.text", $social, '描述（可选，显示在大图说明里）', $base); ?>
            <?php }, false, (string) ($cards[$i]['title'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>

    <?php boblift_admin_settings_panel_start('底部直接沟通区', '页面最后的询盘入口；WhatsApp 按钮读取联系方式设置里的号码。'); ?>
        <?php boblift_admin_input('cta.label', $social, '标签', $base); ?>
        <?php boblift_admin_input('cta.title', $social, '标题', $base); ?>
        <?php boblift_admin_textarea('cta.text', $social, '描述', $base); ?>
        <?php boblift_admin_input('cta.button_label', $social, '按钮文字', $base); ?>
        <?php boblift_admin_link_input('cta.button_href', $social, '按钮链接', $base); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
}


function boblift_social_media_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = boblift_options();
    $social = is_array($options['social_media_page'] ?? null) ? $options['social_media_page'] : (function_exists('boblift_social_media_page_defaults') ? boblift_social_media_page_defaults() : []);
    boblift_admin_page_start('BOB-LIFT Social Media 页面', '维护 Social Media 独立页的首屏、平台层级、链接、真实图标和内容类型。');
    boblift_render_social_media_page_fields($social, 'boblift_theme_options[social_media_page]');
    boblift_admin_page_end();
}

function boblift_admin_social_platform_panel(string $title, string $key, array $social, array $icon_choices, int $minimum, bool $with_text = false, string $base = 'boblift_theme_options[social_media_page]'): void
{
    $rows = is_array($social[$key] ?? null) ? $social[$key] : [];
    boblift_admin_settings_panel_start($title, '填好账号主页链接后，前台卡片才可点击并在新窗口打开。');
    for ($i = 0; $i < max($minimum, count($rows) + 2); $i++) : ?>
        <?php boblift_admin_subpanel('平台 ' . (string) ($i + 1), static function () use ($key, $i, $social, $icon_choices, $base, $with_text): void { ?>
            <?php boblift_admin_checkbox("$key.$i.enabled", $social, '启用', $base); ?>
            <?php boblift_admin_input("$key.$i.label", $social, '显示名称', $base); ?>
            <div class="boblift-admin-field">
                <label>当前真实图标预览</label>
                <?php echo boblift_admin_social_media_icon_preview((string) boblift_admin_option_value("$key.$i.icon", $social)); ?>
            </div>
            <?php boblift_admin_select("$key.$i.icon", $social, '真实图标', $icon_choices, $base); ?>
            <?php boblift_admin_link_input("$key.$i.href", $social, '账号主页链接（https:// 开头；留空则卡片不可点击）', $base); ?>
            <?php boblift_admin_input("$key.$i.handle", $social, '账号名（如 @boblift，可留空）', $base); ?>
            <?php if ($with_text) : ?>
                <?php boblift_admin_textarea("$key.$i.text", $social, '一句话说明这个账号发什么', $base); ?>
            <?php endif; ?>
            <?php boblift_admin_media("$key.$i.qr", $social, '二维码（可选）', $base, '抖音、视频号、小红书等扫码关注的平台可上传，卡片右侧显示。'); ?>
        <?php }, false, (string) ($rows[$i]['label'] ?? '')); ?>
    <?php endfor;
    boblift_admin_settings_panel_end();
}


function boblift_footer_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = boblift_options();
    $footer = is_array($options['footer'] ?? null) ? $options['footer'] : [];
    $groups = is_array($footer['groups'] ?? null) ? $footer['groups'] : [];
    $social_links = is_array($footer['social_links'] ?? null) ? $footer['social_links'] : [];
    $legal_links = is_array($footer['legal_links'] ?? null) ? $footer['legal_links'] : [];
    boblift_admin_page_start('BOB-LIFT 页脚设置', '维护通用底部简介、社媒链接、链接组、联系标题、版权和法律链接。');
    ?>
    <?php boblift_admin_settings_panel_start('页脚基础内容'); ?>
        <?php boblift_admin_textarea('footer.intro', $options, '页脚简介'); ?>
        <?php boblift_admin_input('footer.contact_title', $options, '联系区标题'); ?>
        <?php boblift_admin_input('footer.copyright', $options, '版权文案'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('社媒链接'); ?>
        <p class="boblift-admin-note">只有填写了外部主页地址（https://…）的社媒才会在页脚显示；指向本站页面的占位链接会自动隐藏。</p>
        <?php for ($i = 0; $i < max(5, count($social_links) + 1); $i++) : ?>
            <?php boblift_admin_subpanel('社媒 ' . (string) ($i + 1), static function () use ($options, $i): void { ?>
                <?php boblift_admin_input("footer.social_links.$i.label", $options, '名称'); ?>
                <?php boblift_admin_link_input("footer.social_links.$i.href", $options, '链接'); ?>
            <?php }, false, (string) ($social_links[$i]['label'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('页脚链接组'); ?>
        <?php for ($g = 0; $g < max(4, count($groups) + 1); $g++) : ?>
            <?php boblift_admin_subpanel('链接组 ' . (string) ($g + 1), static function () use ($options, $groups, $g): void { ?>
                <?php boblift_admin_input("footer.groups.$g.title", $options, '分组标题'); ?>
                <?php if (is_array($groups[$g] ?? null) && boblift_footer_is_product_group($groups[$g])) : ?>
                    <input type="hidden" name="<?php echo esc_attr(boblift_admin_option_name("footer.groups.$g.source", 'boblift_theme_options')); ?>" value="product_categories">
                    <p class="boblift-admin-note">这一组的链接按「产品展示 → 产品分类」的大类自动生成，和导航的产品下拉菜单一致，这里只需维护分组标题。</p>
                    <?php return; ?>
                <?php endif; ?>
                <?php $links = is_array($groups[$g]['links'] ?? null) ? $groups[$g]['links'] : []; ?>
                <?php for ($i = 0; $i < max(8, count($links) + 1); $i++) : ?>
                    <?php boblift_admin_subpanel('链接 ' . (string) ($i + 1), static function () use ($options, $g, $i): void { ?>
                        <?php boblift_admin_input("footer.groups.$g.links.$i.label", $options, '显示文字'); ?>
                        <?php boblift_admin_link_input("footer.groups.$g.links.$i.href", $options, '链接地址'); ?>
                    <?php }, false, (string) ($links[$i]['label'] ?? '')); ?>
                <?php endfor; ?>
            <?php }, false, (string) ($groups[$g]['title'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php boblift_admin_settings_panel_start('法律链接'); ?>
        <?php for ($i = 0; $i < max(4, count($legal_links) + 1); $i++) : ?>
            <?php boblift_admin_subpanel('法律链接 ' . (string) ($i + 1), static function () use ($options, $i): void { ?>
                <?php boblift_admin_input("footer.legal_links.$i.label", $options, '显示文字'); ?>
                <?php boblift_admin_link_input("footer.legal_links.$i.href", $options, '链接地址'); ?>
            <?php }, false, (string) ($legal_links[$i]['label'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    boblift_admin_page_end();
}


function boblift_news_category_choices(): array
{
    $choices = ['' => '不显示'];
    $default_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : (function_exists('pll_default_language') ? (string) pll_default_language('slug') : '');
    $terms = get_terms([
        'taxonomy' => 'category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'lang' => '',
    ]);
    if (is_wp_error($terms)) {
        return $choices;
    }

    foreach ($terms as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }
        $language = function_exists('pll_get_term_language') ? (string) pll_get_term_language($term->term_id, 'slug') : '';
        if ($default_language !== '' && $language !== '' && $language !== $default_language) {
            continue;
        }
        $label = $term->name . ' (' . $term->slug . ')';
        $choices[$term->slug] = $label;
    }

    return $choices;
}

function boblift_news_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = boblift_options();
    $news_categories = function_exists('boblift_news_category_options') ? boblift_news_category_options() : [];
    $choices = boblift_news_category_choices();
    boblift_admin_page_start('BOB-LIFT News 分类', '维护 News 页面顶部分类筛选。这里选择主语言分类，前台会按 Polylang 自动显示当前语言译文。');
    ?>
    <?php boblift_admin_settings_panel_start('分类筛选', '新增语言时，只需要在「文章 → 分类」里给这些分类补译文；新增分类时，在这里新增一行即可，不需要改代码。'); ?>
        <?php for ($i = 0; $i < max(8, count($news_categories) + 1); $i++) : ?>
            <?php boblift_admin_subpanel('分类 ' . (string) ($i + 1), static function () use ($options, $choices, $i): void { ?>
                <?php boblift_admin_select("news_categories.$i.slug", $options, '主语言分类', $choices); ?>
            <?php }, false, (string) ($news_categories[$i]['slug'] ?? '')); ?>
        <?php endfor; ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    boblift_admin_page_end();
}

function boblift_form_security_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    boblift_admin_page_start('BOB-LIFT 表单安全', '维护 Cloudflare Turnstile，用于拦截垃圾询盘。');
    $form_security = boblift_form_security_options();
    ?>
    <?php boblift_admin_settings_panel_start('Turnstile 设置', '生产上线前请关闭本地测试模式，并替换为正式站点的 Site Key 和 Secret Key。'); ?>
        <?php boblift_admin_checkbox('turnstile_enabled', $form_security, '启用 Turnstile', 'boblift_theme_options[form_security]'); ?>
        <?php boblift_admin_checkbox('turnstile_local_mode', $form_security, '本地测试模式', 'boblift_theme_options[form_security]'); ?>
        <?php boblift_admin_input('turnstile_site_key', $form_security, 'Site Key', 'boblift_theme_options[form_security]'); ?>
        <?php boblift_admin_input('turnstile_secret_key', $form_security, 'Secret Key', 'boblift_theme_options[form_security]'); ?>
    <?php boblift_admin_settings_panel_end(); ?>
    <?php
    boblift_admin_page_end();
}

function boblift_admin_option_value(string $path, array $options): mixed
{
    $value = $options;
    foreach (explode('.', $path) as $segment) {
        $key = is_numeric($segment) ? (int) $segment : $segment;
        $value = is_array($value) && array_key_exists($key, $value) ? $value[$key] : '';
    }
    return $value;
}

function boblift_admin_option_name(string $path, string $base = 'boblift_theme_options'): string
{
    $name = $base;
    foreach (explode('.', $path) as $segment) {
        $name .= '[' . $segment . ']';
    }
    return $name;
}

function boblift_admin_input(string $path, array $options, string $label = '', string $base = 'boblift_theme_options'): void
{
    $value = boblift_admin_option_value($path, $options);
    echo '<div class="boblift-admin-field">';
    if ($label !== '') {
        echo '<label>' . esc_html($label) . '</label>';
    }
    echo '<input class="regular-text" type="text" name="' . esc_attr(boblift_admin_option_name($path, $base)) . '" value="' . esc_attr((string) $value) . '">';
    echo '</div>';
}

function boblift_admin_link_input(string $path, array $options, string $label = '', string $base = 'boblift_theme_options'): void
{
    $value = (string) boblift_admin_option_value($path, $options);
    $choices = boblift_admin_link_choices();
    echo '<div class="boblift-admin-field boblift-admin-link-field">';
    if ($label !== '') {
        echo '<label>' . esc_html($label) . '</label>';
    }
    echo '<div class="boblift-admin-link-control">';
    echo '<input class="regular-text boblift-admin-link-input" type="text" name="' . esc_attr(boblift_admin_option_name($path, $base)) . '" value="' . esc_attr($value) . '" placeholder="/about.html">';
    echo '<select class="boblift-admin-link-picker" aria-label="选择已有链接">';
    echo '<option value="">选择已有页面 / 分类 / 产品</option>';
    foreach ($choices as $choice_value => $choice_label) {
        if ((string) $choice_value === '') {
            continue;
        }
        echo '<option value="' . esc_attr((string) $choice_value) . '" ' . selected($value, (string) $choice_value, false) . '>' . esc_html($choice_label) . '</option>';
    }
    echo '</select>';
    echo '<p class="boblift-admin-link-help">可直接选择站内链接；外部链接或特殊链接仍可手动填写。</p>';
    echo '</div></div>';
}

function boblift_admin_checkbox(string $path, array $options, string $label, string $base = 'boblift_theme_options'): void
{
    $value = (string) boblift_admin_option_value($path, $options);
    $name = boblift_admin_option_name($path, $base);
    echo '<div class="boblift-admin-field">';
    echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
    echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($value, '1', false) . '> ' . esc_html($label) . '</label>';
    echo '</div>';
}


function boblift_admin_select(string $path, array $options, string $label, array $choices, string $base = 'boblift_theme_options'): void
{
    $value = (string) boblift_admin_option_value($path, $options);
    echo '<div class="boblift-admin-field"><label>' . esc_html($label) . '</label><select name="' . esc_attr(boblift_admin_option_name($path, $base)) . '">';
    foreach ($choices as $choice_value => $choice_label) {
        echo '<option value="' . esc_attr((string) $choice_value) . '" ' . selected($value, (string) $choice_value, false) . '>' . esc_html($choice_label) . '</option>';
    }
    echo '</select></div>';
}

function boblift_admin_textarea(string $path, array $options, string $label = '', string $base = 'boblift_theme_options'): void
{
    $value = boblift_admin_option_value($path, $options);
    echo '<div class="boblift-admin-field">';
    if ($label !== '') {
        echo '<label>' . esc_html($label) . '</label>';
    }
    echo '<textarea name="' . esc_attr(boblift_admin_option_name($path, $base)) . '">' . esc_textarea((string) $value) . '</textarea>';
    echo '</div>';
}

function boblift_admin_media(string $path, array $options, string $label, string $base = 'boblift_theme_options', string $help = ''): void
{
    $value = boblift_admin_option_value($path, $options);
    $image = is_array($value) ? $value : ['id' => 0, 'path' => (string) $value];
    $id = (int) ($image['id'] ?? 0);
    $path_value = (string) ($image['path'] ?? '');
    $preview = boblift_image_setting_url($image, $path_value);
    $fallback_preview = $path_value !== '' ? boblift_image_src($path_value) : $preview;
    $base_name = boblift_admin_option_name($path, $base);
    echo '<div class="boblift-admin-field boblift-media-field" data-fallback="' . esc_attr($fallback_preview) . '">';
    echo '<label>' . esc_html($label) . '</label>';
    echo '<img class="boblift-media-preview' . ($preview === '' ? ' is-hidden' : '') . '" src="' . esc_url($preview) . '" alt="">';
    echo '<input class="boblift-media-id" type="hidden" name="' . esc_attr($base_name . '[id]') . '" value="' . esc_attr((string) $id) . '">';
    echo '<input type="hidden" name="' . esc_attr($base_name . '[path]') . '" value="' . esc_attr($path_value) . '">';
    echo '<button class="button boblift-media-select" type="button">选择图片</button>';
    echo '<button class="button boblift-media-clear" type="button">使用默认图</button>';
    if ($help !== '') {
        echo '<p class="boblift-media-help">' . esc_html($help) . '</p>';
    }
    echo '</div>';
}


function boblift_admin_file_media(string $path, array $options, string $label, string $base = 'boblift_theme_options', string $help = ''): void
{
    $value = boblift_admin_option_value($path, $options);
    $media = is_array($value) ? $value : ['id' => 0, 'path' => (string) $value];
    $id = (int) ($media['id'] ?? 0);
    $path_value = (string) ($media['path'] ?? '');
    $preview = $id > 0 ? basename((string) get_attached_file($id)) : ($path_value !== '' ? basename($path_value) : '未选择媒体');
    $base_name = boblift_admin_option_name($path, $base);
    echo '<div class="boblift-admin-field boblift-media-field" data-fallback="' . esc_attr($path_value) . '" data-fallback-label="' . esc_attr($preview) . '">';
    echo '<label>' . esc_html($label) . '</label>';
    echo '<span class="boblift-media-preview boblift-media-file-preview' . ($preview === '未选择媒体' ? ' is-hidden' : '') . '">' . esc_html($preview) . '</span>';
    echo '<input class="boblift-media-id" type="hidden" name="' . esc_attr($base_name . '[id]') . '" value="' . esc_attr((string) $id) . '">';
    echo '<input type="hidden" name="' . esc_attr($base_name . '[path]') . '" value="' . esc_attr($path_value) . '">';
    echo '<button class="button boblift-media-select" type="button">选择媒体</button>';
    echo '<button class="button boblift-media-clear" type="button">使用默认媒体</button>';
    if ($help !== '') {
        echo '<p class="boblift-media-help">' . esc_html($help) . '</p>';
    }
    echo '</div>';
}

function boblift_product_category_admin_head(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || ($screen->taxonomy ?? '') !== 'product_category') {
        return;
    }
    echo '<style>.taxonomy-product_category .term-slug-wrap{display:none}.boblift-term-media{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.boblift-term-media .boblift-media-preview{width:180px;height:82px;object-fit:contain;border:1px solid #c3c4c7;border-radius:8px;background:#f6f7f7}.boblift-term-field-note{margin:6px 0 0;color:#646970;font-size:12px;line-height:1.5}.form-field .boblift-term-short{width:100%;max-width:520px}</style><script>document.addEventListener("DOMContentLoaded",function(){var url=document.querySelector(".term-boblift-public-url-wrap");var slug=document.querySelector(".term-slug-wrap");if(url&&slug){slug.insertAdjacentElement("afterend",url);}});</script>';
}
add_action('admin_head-edit-tags.php', 'boblift_product_category_admin_head');
add_action('admin_head-term.php', 'boblift_product_category_admin_head');


function boblift_article_category_public_url_add_field(): void
{
    ?>
    <div class="form-field">
        <label>前台自定义 URL</label>
        <input type="text" name="boblift_category_public_url" value="" placeholder="例如 /Company-News.html">
        <p>填写站内根路径，不填写域名。新分类保存时会按最终分类别名写入 /分类别名.html，你也可以直接填写老站地址。</p>
    </div>
    <div class="form-field"><label>SEO 标题</label><input type="text" name="boblift_category_seo_title" value="" placeholder="保存后写入 分类名称 | BOB-LIFT"><p>前台 title 就读取这里保存的值。</p></div>
    <div class="form-field"><label>SEO 描述</label><textarea name="boblift_category_seo_description" rows="4"></textarea><p>前台 description 就读取这里保存的值。</p></div>
    <?php
}
add_action('category_add_form_fields', 'boblift_article_category_public_url_add_field');

function boblift_article_category_public_url_admin_value(WP_Term $term): string
{
    $saved = boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true));
    return $saved !== '' ? $saved : boblift_normalize_public_path('/' . $term->slug . '.html');
}

function boblift_article_category_public_url_edit_field(WP_Term $term): void
{
    $value = boblift_article_category_public_url_admin_value($term);
    $seo_title = trim((string) get_term_meta($term->term_id, '_boblift_seo_title', true));
    $seo_description = trim((string) get_term_meta($term->term_id, '_boblift_seo_description', true));
    if ($seo_title === '') {
        $seo_title = boblift_seo_default_title_for_term($term);
    }
    if ($seo_description === '') {
        $seo_description = boblift_seo_default_description_for_term($term);
    }
    ?>
    <tr class="form-field">
        <th scope="row"><label>前台自定义 URL</label></th>
        <td>
            <input class="regular-text" type="text" name="boblift_category_public_url" value="<?php echo esc_attr($value); ?>" placeholder="例如 /Company-News.html">
            <p class="description">只填写站内根路径，不填写域名。这里的值就是该文章分类的前台主入口 URL。</p>
        </td>
    </tr>
    <tr class="form-field"><th scope="row"><label>SEO 标题</label></th><td><input class="regular-text" type="text" name="boblift_category_seo_title" value="<?php echo esc_attr($seo_title); ?>"><p class="description">前台 title 就读取这里保存的值。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>SEO 描述</label></th><td><textarea class="large-text" rows="4" name="boblift_category_seo_description"><?php echo esc_textarea($seo_description); ?></textarea><p class="description">前台 description 就读取这里保存的值。</p></td></tr>
    <?php
}
add_action('category_edit_form_fields', 'boblift_article_category_public_url_edit_field');

function boblift_save_article_category_public_url(int $term_id): void
{
    if (!current_user_can('manage_categories')) {
        return;
    }
    $term = get_term($term_id, 'category');
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return;
    }
    $old = (string) get_term_meta($term_id, '_boblift_public_url', true);
    $new = isset($_POST['boblift_category_public_url']) ? boblift_normalize_public_path((string) wp_unslash($_POST['boblift_category_public_url'])) : '';
    if ($new === '') {
        $new = boblift_article_category_public_url_admin_value($term);
    }
    update_term_meta($term_id, '_boblift_public_url', $new);
    $seo_title = isset($_POST['boblift_category_seo_title']) ? sanitize_text_field(wp_unslash($_POST['boblift_category_seo_title'])) : '';
    $seo_description = isset($_POST['boblift_category_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['boblift_category_seo_description'])) : '';
    update_term_meta($term_id, '_boblift_seo_title', $seo_title !== '' ? $seo_title : boblift_seo_default_title_for_term($term));
    update_term_meta($term_id, '_boblift_seo_description', $seo_description !== '' ? $seo_description : boblift_seo_default_description_for_term($term));
    if ($old !== $new) {
        flush_rewrite_rules(false);
    }
}
add_action('created_category', 'boblift_save_article_category_public_url');
add_action('edited_category', 'boblift_save_article_category_public_url');

function boblift_product_category_public_url_admin_value(WP_Term $term): string
{
    $saved = boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true));
    return $saved !== '' ? $saved : boblift_normalize_public_path('/' . $term->slug);
}

function boblift_product_category_meta_values(?WP_Term $term = null): array
{
    $term_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
    return [
        'public_url' => $term instanceof WP_Term ? boblift_product_category_public_url_admin_value($term) : '',
        'icon' => $term_id > 0 ? (string) get_term_meta($term_id, '_boblift_icon', true) : '',
        'image_id' => $term_id > 0 ? (int) get_term_meta($term_id, '_boblift_image_id', true) : 0,
        'banner_label' => $term_id > 0 ? (string) get_term_meta($term_id, '_boblift_banner_label', true) : '',
        'banner_title' => $term_id > 0 ? (string) get_term_meta($term_id, '_boblift_banner_title', true) : '',
        'sort' => $term_id > 0 ? (string) get_term_meta($term_id, '_boblift_sort', true) : '',
        'term' => $term,
    ];
}

function boblift_product_category_image_preview(array $meta): string
{
    $image_id = (int) ($meta['image_id'] ?? 0);
    if ($image_id > 0) {
        $url = (string) wp_get_attachment_image_url($image_id, 'medium');
        if ($url !== '') {
            return $url;
        }
    }
    return boblift_product_banner_photo_url($meta['term'] ?? null);
}

function boblift_product_category_add_fields(): void
{
    $meta = boblift_product_category_meta_values();
    ?>
    <div class="form-field term-boblift-public-url-wrap"><label>前台自定义 URL</label><input class="boblift-term-short" type="text" name="boblift_product_category_meta[public_url]" value="" placeholder="例如 /truck-mounted-crane"><p class="boblift-term-field-note">只填写站内根路径，不填写域名。产品分类 URL 不带 .html；保存后这里的值就是前台分类页地址。</p></div>
    <div class="form-field"><label>分类图标 Key</label><input class="boblift-term-short" type="text" name="boblift_product_category_meta[icon]" value="truck-crane" placeholder="例如 truck-crane"><p class="boblift-term-field-note">用于产品二级菜单和分类卡片的图标 key，需与主题已有图标名称一致。</p></div>
    <div class="form-field term-boblift-banner-wrap">
        <label>产品页 Banner 图片</label>
        <div class="boblift-media-field boblift-term-media" data-fallback="<?php echo esc_attr(boblift_product_category_image_preview($meta)); ?>">
            <img class="boblift-media-preview" src="<?php echo esc_url(boblift_product_category_image_preview($meta)); ?>" alt="">
            <input class="boblift-media-id" type="hidden" name="boblift_product_category_meta[image_id]" value="0">
            <button class="button boblift-media-select" type="button">选择图片</button>
            <button class="button boblift-media-clear" type="button">使用默认图</button>
        </div>
        <p class="boblift-term-field-note">产品列表页顶部 Banner 的横向实景照片，推荐 1920×640（3:1），主体放在右半边，左侧留出浅色空间给标题。不上传则使用该产品族的内置照片，子分类沿用父分类的照片。</p>
    </div>
    <div class="form-field"><label>Banner 小标签</label><input class="boblift-term-short" type="text" name="boblift_product_category_meta[banner_label]" value="BOB-LIFT Product Range"><p class="boblift-term-field-note">前台 Banner 左上角的小标签，可直接改成当前分类的营销标签。</p></div>
    <div class="form-field"><label>Banner 标题</label><input class="boblift-term-short" type="text" name="boblift_product_category_meta[banner_title]" value="" placeholder="保存后写入分类名称"><p class="boblift-term-field-note">建议与上方分类名称一致；保存时没有填写会自动写入分类名称。</p></div>
    <div class="form-field"><label>SEO 标题</label><input class="boblift-term-short" type="text" name="boblift_product_category_meta[seo_title]" value="" placeholder="保存后写入 分类名称 | BOB-LIFT"><p class="boblift-term-field-note">前台 title 就读取这里保存的值。</p></div>
    <div class="form-field"><label>SEO 描述</label><textarea name="boblift_product_category_meta[seo_description]" rows="4"></textarea><p class="boblift-term-field-note">前台 description 就读取这里保存的值。</p></div>
    <div class="form-field"><label>排序</label><input class="small-text" type="number" name="boblift_product_category_meta[sort]" value="100"></div>
    <?php
}
add_action('product_category_add_form_fields', 'boblift_product_category_add_fields');

function boblift_product_category_edit_fields(WP_Term $term): void
{
    $meta = boblift_product_category_meta_values($term);
    $preview = boblift_product_category_image_preview($meta);
    $seo_title = trim((string) get_term_meta($term->term_id, '_boblift_seo_title', true));
    $seo_description = trim((string) get_term_meta($term->term_id, '_boblift_seo_description', true));
    if ($seo_title === '') {
        $seo_title = boblift_seo_default_title_for_term($term);
    }
    if ($seo_description === '') {
        $seo_description = boblift_seo_default_description_for_term($term);
    }
    ?>
    <tr class="form-field term-boblift-public-url-wrap"><th scope="row"><label>前台自定义 URL</label></th><td><input class="regular-text" type="text" name="boblift_product_category_meta[public_url]" value="<?php echo esc_attr($meta['public_url']); ?>"><p class="description">只填写站内根路径，不填写域名。产品分类 URL 不带 .html；这里的值就是前台分类页地址。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>分类图标 Key</label></th><td><input class="regular-text" type="text" name="boblift_product_category_meta[icon]" value="<?php echo esc_attr($meta['icon'] !== '' ? $meta['icon'] : 'truck-crane'); ?>" placeholder="例如 truck-crane"><p class="description">用于产品二级菜单和分类卡片的图标 key，需与主题已有图标名称一致。</p></td></tr>
    <tr class="form-field term-boblift-banner-wrap">
        <th scope="row"><label>产品页 Banner 图片</label></th>
        <td>
            <div class="boblift-media-field boblift-term-media" data-fallback="<?php echo esc_attr($preview); ?>">
                <img class="boblift-media-preview" src="<?php echo esc_url($preview); ?>" alt="">
                <input class="boblift-media-id" type="hidden" name="boblift_product_category_meta[image_id]" value="<?php echo esc_attr((string) $meta['image_id']); ?>">
                <button class="button boblift-media-select" type="button">选择图片</button>
                <button class="button boblift-media-clear" type="button">使用默认图</button>
            </div>
            <p class="description">产品列表页顶部 Banner 的横向实景照片，推荐 1920×640（3:1），主体放在右半边，左侧留出浅色空间给标题。不上传则使用该产品族的内置照片，子分类沿用父分类的照片。</p>
        </td>
    </tr>
    <tr class="form-field"><th scope="row"><label>Banner 小标签</label></th><td><input class="regular-text" type="text" name="boblift_product_category_meta[banner_label]" value="<?php echo esc_attr($meta['banner_label'] !== '' ? $meta['banner_label'] : 'BOB-LIFT Product Range'); ?>"><p class="description">前台 Banner 左上角的小标签，可直接改成当前分类的营销标签。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>Banner 标题</label></th><td><input class="regular-text" type="text" name="boblift_product_category_meta[banner_title]" value="<?php echo esc_attr($meta['banner_title'] !== '' ? $meta['banner_title'] : $term->name); ?>"><p class="description">前台 Banner 主标题，通常与产品分类名称一致。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>SEO 标题</label></th><td><input class="regular-text" type="text" name="boblift_product_category_meta[seo_title]" value="<?php echo esc_attr($seo_title); ?>"><p class="description">前台 title 就读取这里保存的值。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>SEO 描述</label></th><td><textarea class="large-text" rows="4" name="boblift_product_category_meta[seo_description]"><?php echo esc_textarea($seo_description); ?></textarea><p class="description">前台 description 就读取这里保存的值。</p></td></tr>
    <tr class="form-field"><th scope="row"><label>排序</label></th><td><input class="small-text" type="number" name="boblift_product_category_meta[sort]" value="<?php echo esc_attr($meta['sort'] !== '' ? $meta['sort'] : '100'); ?>"></td></tr>
    <?php
}
add_action('product_category_edit_form_fields', 'boblift_product_category_edit_fields');

function boblift_save_product_category_meta(int $term_id): void
{
    if (!current_user_can('manage_categories') || !isset($_POST['boblift_product_category_meta']) || !is_array($_POST['boblift_product_category_meta'])) {
        return;
    }
    $meta = wp_unslash($_POST['boblift_product_category_meta']);
    $term = get_term($term_id, 'product_category');
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return;
    }
    $public_url = boblift_normalize_public_path((string) ($meta['public_url'] ?? ''));
    if ($public_url === '') {
        $public_url = boblift_normalize_public_path('/' . $term->slug);
    }
    $icon = sanitize_key((string) ($meta['icon'] ?? ''));
    $banner_label = sanitize_text_field((string) ($meta['banner_label'] ?? ''));
    $banner_title = sanitize_text_field((string) ($meta['banner_title'] ?? ''));
    $seo_title = sanitize_text_field((string) ($meta['seo_title'] ?? ''));
    $seo_description = sanitize_textarea_field((string) ($meta['seo_description'] ?? ''));
    update_term_meta($term_id, '_boblift_public_url', $public_url);
    update_term_meta($term_id, '_boblift_icon', $icon !== '' ? $icon : 'truck-crane');
    update_term_meta($term_id, '_boblift_image_id', max(0, (int) ($meta['image_id'] ?? 0)));
    update_term_meta($term_id, '_boblift_banner_label', $banner_label !== '' ? $banner_label : 'BOB-LIFT Product Range');
    update_term_meta($term_id, '_boblift_banner_title', $banner_title !== '' ? $banner_title : $term->name);
    update_term_meta($term_id, '_boblift_seo_title', $seo_title !== '' ? $seo_title : boblift_seo_default_title_for_term($term));
    update_term_meta($term_id, '_boblift_seo_description', $seo_description !== '' ? $seo_description : boblift_seo_default_description_for_term($term));
    update_term_meta($term_id, '_boblift_sort', (int) ($meta['sort'] ?? 100));
}
add_action('created_product_category', 'boblift_save_product_category_meta');
add_action('edited_product_category', 'boblift_save_product_category_meta');


function boblift_product_category_default_sort(WP_Term $term): int
{
    foreach (boblift_seed_product_categories() as $category) {
        if (($category['slug'] ?? '') === $term->slug) {
            return (int) ($category['sort'] ?? 100);
        }
    }
    return 100;
}

function boblift_ensure_product_category_sort_meta(): void
{
    if (!function_exists('taxonomy_exists') || !taxonomy_exists('product_category')) {
        return;
    }
    $terms = get_terms(['taxonomy' => 'product_category', 'hide_empty' => false]);
    if (is_wp_error($terms)) {
        return;
    }
    foreach ($terms as $term) {
        if ($term instanceof WP_Term && get_term_meta($term->term_id, '_boblift_sort', true) === '') {
            update_term_meta($term->term_id, '_boblift_sort', boblift_product_category_default_sort($term));
        }
    }
}
add_action('admin_init', 'boblift_ensure_product_category_sort_meta');

function boblift_product_category_admin_columns(array $columns): array
{
    $next = [];
    foreach ($columns as $key => $label) {
        $next[$key] = $label;
        if ($key === 'slug') {
            $next['boblift_sort'] = '前台排序';
        }
    }
    return $next;
}
add_filter('manage_edit-product_category_columns', 'boblift_product_category_admin_columns');

function boblift_product_category_admin_column_value(string $content, string $column, int $term_id): string
{
    if ($column !== 'boblift_sort') {
        return $content;
    }
    $sort = get_term_meta($term_id, '_boblift_sort', true);
    return esc_html($sort !== '' ? (string) $sort : '100');
}
add_filter('manage_product_category_custom_column', 'boblift_product_category_admin_column_value', 10, 3);

function boblift_product_category_sortable_columns(array $columns): array
{
    $columns['boblift_sort'] = 'boblift_sort';
    return $columns;
}
add_filter('manage_edit-product_category_sortable_columns', 'boblift_product_category_sortable_columns');

function boblift_product_category_admin_term_order(WP_Term_Query $query): void
{
    if (!is_admin()) {
        return;
    }
    $taxonomy = $query->query_vars['taxonomy'] ?? '';
    $taxonomies = is_array($taxonomy) ? $taxonomy : [$taxonomy];
    if (!in_array('product_category', $taxonomies, true)) {
        return;
    }
    $requested_taxonomy = isset($_GET['taxonomy']) ? sanitize_key((string) wp_unslash($_GET['taxonomy'])) : '';
    if ($requested_taxonomy !== 'product_category') {
        return;
    }
    $orderby = isset($_GET['orderby']) ? sanitize_key((string) wp_unslash($_GET['orderby'])) : '';
    if ($orderby !== '' && $orderby !== 'boblift_sort') {
        return;
    }
    $query->query_vars['meta_key'] = '_boblift_sort';
    $query->query_vars['orderby'] = 'meta_value_num';
    $query->query_vars['order'] = isset($_GET['order']) && strtolower((string) wp_unslash($_GET['order'])) === 'desc' ? 'DESC' : 'ASC';
}
add_action('pre_get_terms', 'boblift_product_category_admin_term_order');
