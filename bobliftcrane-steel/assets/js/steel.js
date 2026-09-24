/* BOB-LIFT Steel — front-end behaviour (no dependencies). */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var body = doc.body;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var i18n = window.bobliftSteel || {};

  root.classList.add('js');

  function $(selector, scope) { return (scope || doc).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || doc).querySelectorAll(selector)); }

  /* ------------------------------------------------------------------ */
  /* Word masks for headlines                                             */
  /* ------------------------------------------------------------------ */
  function splitWords(el) {
    if (el.classList.contains('is-split')) return;
    var counter = 0;
    function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var parts = child.textContent.split(/(\s+)/);
          var frag = doc.createDocumentFragment();
          parts.forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) {
              frag.appendChild(doc.createTextNode(' '));
              return;
            }
            var outer = doc.createElement('span');
            outer.className = 'st-word';
            var inner = doc.createElement('span');
            inner.className = 'st-word__in';
            inner.textContent = part;
            inner.style.setProperty('--wd', (counter * 0.045).toFixed(3) + 's');
            counter++;
            outer.appendChild(inner);
            frag.appendChild(outer);
          });
          node.replaceChild(frag, child);
        } else if (child.nodeType === 1 && child.tagName !== 'BR') {
          walk(child);
        }
      });
    }
    walk(el);
    el.classList.add('is-split');
  }

  function initSplit() {
    if (reduceMotion) return;
    $$('[data-split]').forEach(splitWords);
  }

  /* ------------------------------------------------------------------ */
  /* Scroll reveal with automatic stagger inside grids                    */
  /* ------------------------------------------------------------------ */
  function initReveal() {
    $$('.st-grid').forEach(function (grid) {
      $$(':scope > .st-reveal', grid).forEach(function (el, index) {
        if (!el.style.getPropertyValue('--d')) el.style.setProperty('--d', ((index % 4) * 0.09).toFixed(2) + 's');
      });
    });
    var items = $$('.st-reveal');
    if (!items.length) return;
    if (!('IntersectionObserver' in window) || reduceMotion) {
      items.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0 });
    items.forEach(function (el) { observer.observe(el); });
  }

  /* ------------------------------------------------------------------ */
  /* Parallax for full-bleed backgrounds (transform only)                 */
  /* ------------------------------------------------------------------ */
  function initParallax() {
    var layers = $$('[data-parallax]');
    if (!layers.length || reduceMotion) return;
    var ticking = false;
    function update() {
      ticking = false;
      var vh = window.innerHeight;
      layers.forEach(function (layer) {
        var parent = layer.parentElement;
        var rect = parent.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        var factor = parseFloat(layer.getAttribute('data-parallax')) || 0.15;
        var progress = (rect.top + rect.height / 2 - vh / 2) / vh;
        layer.style.transform = 'translate3d(0,' + (-progress * factor * rect.height).toFixed(1) + 'px,0)';
      });
    }
    function onScroll() {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
  }

  /* ------------------------------------------------------------------ */
  /* Counters                                                             */
  /* ------------------------------------------------------------------ */
  function initCounters() {
    var counters = $$('[data-count]');
    if (!counters.length || reduceMotion || !('IntersectionObserver' in window)) return;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        observer.unobserve(el);
        var target = parseFloat(el.getAttribute('data-count'));
        if (isNaN(target)) return;
        var original = el.textContent;
        var decimals = (String(target).split('.')[1] || '').length;
        var start = performance.now();
        var duration = 1600;
        function frame(now) {
          var t = Math.min(1, (now - start) / duration);
          var eased = 1 - Math.pow(1 - t, 4);
          var value = target * eased;
          el.textContent = decimals ? value.toFixed(decimals) : Math.round(value).toLocaleString('en-US');
          if (t < 1) requestAnimationFrame(frame); else el.textContent = original;
        }
        requestAnimationFrame(frame);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { observer.observe(el); });
  }

  /* ------------------------------------------------------------------ */
  /* Header: stuck state, mega menu, dropdowns, language, dock            */
  /* ------------------------------------------------------------------ */
  function initHeader() {
    var header = $('[data-header]');
    var dock = $('.st-dock');
    if (!header) return;

    var onScroll = function () {
      var y = window.scrollY || window.pageYOffset;
      header.classList.toggle('is-stuck', y > 24);
      if (dock) dock.classList.toggle('is-scrolled', y > 600);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    var items = $$('[data-nav-item]', header);
    var closeTimer = null;

    function closeAll(except) {
      items.forEach(function (item) {
        if (item !== except) {
          item.classList.remove('is-open');
          var link = $('.st-nav__link', item);
          if (link) link.setAttribute('aria-expanded', 'false');
        }
      });
    }

    function open(item) {
      clearTimeout(closeTimer);
      closeAll(item);
      item.classList.add('is-open');
      var link = $('.st-nav__link', item);
      if (link) link.setAttribute('aria-expanded', 'true');
    }

    items.forEach(function (item) {
      var panel = $('[data-nav-panel]', item);
      var link = $('.st-nav__link', item);
      if (!panel || !link) return;

      item.addEventListener('mouseenter', function () { open(item); });
      item.addEventListener('mouseleave', function () {
        closeTimer = setTimeout(function () { closeAll(); }, 160);
      });
      link.addEventListener('click', function (event) {
        if (window.matchMedia('(hover: none)').matches && !item.classList.contains('is-open')) {
          event.preventDefault();
          open(item);
        }
      });
      link.addEventListener('focus', function () { open(item); });
      item.addEventListener('focusout', function (event) {
        if (!item.contains(event.relatedTarget)) closeAll();
      });
    });

    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAll();
    });

    var lang = $('[data-lang]');
    if (lang) {
      var toggle = $('.st-lang__toggle', lang);
      toggle.addEventListener('click', function () {
        var isOpen = lang.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
      });
      doc.addEventListener('click', function (event) {
        if (!lang.contains(event.target)) {
          lang.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    var top = $('[data-scroll-top]');
    if (top) {
      top.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    }
  }

  /* ------------------------------------------------------------------ */
  /* Mobile menu                                                          */
  /* ------------------------------------------------------------------ */
  function initMenu() {
    var menu = $('[data-menu]');
    var opener = $('[data-menu-open]');
    if (!menu || !opener) return;
    var closers = $$('[data-menu-close]', menu);
    var lastFocus = null;

    function setOpen(state) {
      menu.classList.toggle('is-open', state);
      menu.setAttribute('aria-hidden', String(!state));
      opener.setAttribute('aria-expanded', String(state));
      body.classList.toggle('is-locked', state);
      if (state) {
        lastFocus = doc.activeElement;
        var first = closers[0] || menu;
        setTimeout(function () { first.focus(); }, 300);
      } else if (lastFocus) {
        lastFocus.focus();
      }
    }

    opener.addEventListener('click', function () { setOpen(true); });
    closers.forEach(function (btn) { btn.addEventListener('click', function () { setOpen(false); }); });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && menu.classList.contains('is-open')) setOpen(false);
    });
    window.matchMedia('(min-width: 1100px)').addEventListener('change', function (event) {
      if (event.matches) setOpen(false);
    });
  }

  /* ------------------------------------------------------------------ */
  /* WeChat dialog                                                        */
  /* ------------------------------------------------------------------ */
  function initWechat() {
    var dialog = $('[data-wechat-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    $$('[data-wechat-open]').forEach(function (btn) {
      btn.addEventListener('click', function () { dialog.showModal(); });
    });
    $$('[data-wechat-close]', dialog).forEach(function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) dialog.close();
    });
  }

  /* ------------------------------------------------------------------ */
  /* Hero product stage rotation                                          */
  /* ------------------------------------------------------------------ */
  function initHeroStage() {
    var stage = $('[data-hero-stage]');
    if (!stage) return;
    var slides;
    try { slides = JSON.parse(stage.getAttribute('data-slides') || '[]'); } catch (e) { slides = []; }
    if (slides.length < 2) return;
    var images = $$('[data-hero-image]', stage);
    var ghost = $('[data-hero-ghost]', stage);
    var link = $('[data-hero-link]', stage);
    var chips = $('[data-hero-chips]', stage);
    var caption = $('[data-hero-caption]', stage);
    var buttons = $$('[data-hero-slide]', stage);
    var interval = 6500;
    var current = 0;
    var timer = null;
    stage.style.setProperty('--hero-interval', interval + 'ms');

    function renderChips(slide) {
      if (!chips) return;
      chips.innerHTML = '';
      (slide.chips || []).forEach(function (chip, index) {
        var wrap = doc.createElement('div');
        wrap.className = 'st-chip st-chip--' + (index + 1);
        var core = doc.createElement('div');
        core.className = 'st-chip__core';
        var label = doc.createElement('span');
        label.className = 'st-chip__label';
        label.textContent = chip.label;
        var value = doc.createElement('span');
        value.className = 'st-chip__value';
        value.textContent = chip.value;
        core.appendChild(label);
        core.appendChild(value);
        wrap.appendChild(core);
        chips.appendChild(wrap);
      });
    }

    function show(index) {
      if (index === current && stage.classList.contains('is-ready')) return;
      var slide = slides[index];
      stage.classList.add('is-switching');
      setTimeout(function () {
        images.forEach(function (img, i) { img.classList.toggle('is-active', i === index); });
        if (ghost) ghost.textContent = slide.model;
        if (link) {
          link.setAttribute('href', slide.url);
          link.setAttribute('aria-label', slide.model + ' — ' + slide.name);
        }
        if (caption) caption.innerHTML = '<span class="st-stage-hero__model"></span> — ';
        if (caption) {
          $('.st-stage-hero__model', caption).textContent = slide.model;
          caption.appendChild(doc.createTextNode(slide.name));
        }
        renderChips(slide);
        stage.classList.remove('is-switching');
      }, reduceMotion ? 0 : 320);
      buttons.forEach(function (btn, i) {
        btn.classList.toggle('is-active', i === index);
        if (i === index) {
          var bar = $('.st-stage-hero__bar i', btn);
          if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
        }
      });
      current = index;
      stage.classList.add('is-ready');
    }

    function schedule() {
      clearInterval(timer);
      if (reduceMotion) return;
      timer = setInterval(function () { show((current + 1) % slides.length); }, interval);
    }

    buttons.forEach(function (btn, i) {
      btn.addEventListener('click', function () { show(i); schedule(); });
    });
    stage.addEventListener('mouseenter', function () { clearInterval(timer); });
    stage.addEventListener('mouseleave', schedule);
    doc.addEventListener('visibilitychange', function () {
      if (doc.hidden) clearInterval(timer); else schedule();
    });
    stage.classList.add('is-ready');
    schedule();
  }

  /* ------------------------------------------------------------------ */
  /* Tabs (click, plus hover on wide screens when data-hover-tabs is set) */
  /* ------------------------------------------------------------------ */
  function initTabs() {
    $$('[data-tabs]').forEach(function (scope) {
      var tabs = $$('[data-tab]', scope);
      var panels = $$('[data-tab-panel]', scope);
      if (tabs.length < 2) return;
      var hover = scope.hasAttribute('data-hover-tabs');
      var hoverTimer = null;

      function activate(tab) {
        var id = tab.getAttribute('data-tab');
        if (tab.classList.contains('is-active')) return;
        tabs.forEach(function (t) {
          var active = t === tab;
          t.classList.toggle('is-active', active);
          t.setAttribute('aria-selected', String(active));
        });
        panels.forEach(function (panel) {
          var active = panel.getAttribute('data-tab-panel') === id;
          panel.hidden = !active;
          panel.classList.toggle('is-active', active);
          if (active) {
            $$('.st-reveal', panel).forEach(function (el) { el.classList.add('is-in'); });
          }
        });
      }

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { activate(tab); });
        if (hover) {
          tab.addEventListener('mouseenter', function () {
            if (!window.matchMedia('(min-width: 1024px) and (hover: hover)').matches) return;
            clearTimeout(hoverTimer);
            hoverTimer = setTimeout(function () { activate(tab); }, 120);
          });
          tab.addEventListener('mouseleave', function () { clearTimeout(hoverTimer); });
        }
      });
    });
  }

  /* ------------------------------------------------------------------ */
  /* Product archive: filters, sort, pagination                           */
  /* ------------------------------------------------------------------ */
  function initArchive() {
    var scope = $('[data-archive]');
    if (!scope) return;
    var cards = $$('[data-product-card]', scope);
    var grid = $('[data-grid]', scope);
    var empty = $('[data-empty]', scope);
    var countEls = $$('[data-count-current]', scope);
    var pagination = $('[data-pagination]', scope);
    var sortSelect = $('[data-sort]', scope);
    var filters = $('[data-filters]', scope);
    var backdrop = $('[data-filters-backdrop]', scope);
    var perPage = (i18n.archive && i18n.archive.perPage) || 12;
    var labels = i18n.archive || {};
    var page = 1;

    var initialCategory = scope.getAttribute('data-current-category') || '';
    var urlParams = new URLSearchParams(window.location.search);
    if (!initialCategory && urlParams.get('category')) initialCategory = urlParams.get('category');

    var data = cards.map(function (card) {
      return {
        el: card,
        title: card.getAttribute('data-title') || '',
        categories: (card.getAttribute('data-categories') || '').split(',').filter(Boolean),
        capacity: parseFloat(card.getAttribute('data-capacity')) || 0,
        boom: card.getAttribute('data-boom') || '',
        applications: (card.getAttribute('data-applications') || '').split(',').filter(Boolean),
        status: card.getAttribute('data-status') || '',
        popularity: parseInt(card.getAttribute('data-popularity'), 10) || 0,
        featured: card.getAttribute('data-featured') === '1',
        created: parseInt(card.getAttribute('data-created'), 10) || 0
      };
    });

    function checked(name) {
      return $$('input[data-filter="' + name + '"]:checked', scope).map(function (input) { return input.value; });
    }

    function capacityMatch(value, ranges) {
      if (!ranges.length) return true;
      return ranges.some(function (range) {
        var nums = (range.match(/\d+(?:\.\d+)?/g) || []).map(parseFloat);
        var min = nums[0] || 0;
        var max = nums.length > 1 ? nums[1] : null;
        if (max === null) return value > min;
        return min <= 0 ? (value > 0 && value <= max) : (value > min && value <= max);
      });
    }

    function matches(item) {
      var cats = checked('category');
      var caps = checked('capacity');
      var booms = checked('boom');
      var apps = checked('application');
      var statuses = checked('status');
      if (cats.length && !cats.some(function (c) { return item.categories.indexOf(c) !== -1; })) return false;
      if (!capacityMatch(item.capacity, caps)) return false;
      if (booms.length && booms.indexOf(item.boom) === -1) return false;
      if (apps.length && !apps.some(function (a) { return item.applications.indexOf(a) !== -1; })) return false;
      if (statuses.length && statuses.indexOf(item.status) === -1) return false;
      return true;
    }

    function sorted(list) {
      var mode = sortSelect ? sortSelect.value : 'recommended';
      var copy = list.slice();
      copy.sort(function (a, b) {
        switch (mode) {
          case 'capacityAsc': return a.capacity - b.capacity || a.title.localeCompare(b.title);
          case 'capacityDesc': return b.capacity - a.capacity || a.title.localeCompare(b.title);
          case 'newest': return b.created - a.created;
          case 'popular': return b.popularity - a.popularity;
          default:
            return (Number(b.featured) - Number(a.featured)) || (b.popularity - a.popularity) || (b.capacity - a.capacity);
        }
      });
      return copy;
    }

    function renderPagination(total) {
      if (!pagination) return;
      pagination.innerHTML = '';
      var pages = Math.ceil(total / perPage);
      if (pages < 2) return;
      function button(label, target, opts) {
        var btn = doc.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        if (opts && opts.active) btn.classList.add('is-active');
        if (opts && opts.disabled) btn.disabled = true;
        if (opts && opts.label) btn.setAttribute('aria-label', opts.label);
        btn.addEventListener('click', function () {
          page = target;
          render(true);
        });
        pagination.appendChild(btn);
      }
      button('←', Math.max(1, page - 1), { disabled: page === 1, label: labels.previous || 'Previous' });
      for (var p = 1; p <= pages; p++) {
        if (pages > 7 && Math.abs(p - page) > 2 && p !== 1 && p !== pages) {
          if (p === 2 || p === pages - 1) {
            var gap = doc.createElement('span');
            gap.textContent = '…';
            gap.className = 'st-mono';
            pagination.appendChild(gap);
          }
          continue;
        }
        button(String(p), p, { active: p === page });
      }
      button('→', Math.min(pages, page + 1), { disabled: page === pages, label: labels.next || 'Next' });
    }

    function updateAvailability(visible) {
      $$('input[data-filter]', scope).forEach(function (input) {
        var name = input.getAttribute('data-filter');
        var value = input.value;
        if (input.checked) return;
        var available = visible.some(function (item) {
          switch (name) {
            case 'category': return item.categories.indexOf(value) !== -1;
            case 'capacity': return capacityMatch(item.capacity, [value]);
            case 'boom': return item.boom === value;
            case 'application': return item.applications.indexOf(value) !== -1;
            case 'status': return item.status === value;
          }
          return true;
        });
        var label = input.closest('.st-check');
        if (label) label.classList.toggle('is-unavailable', !available);
      });
    }

    function render(scrollToTop) {
      var visible = sorted(data.filter(matches));
      var pages = Math.max(1, Math.ceil(visible.length / perPage));
      if (page > pages) page = pages;
      var start = (page - 1) * perPage;
      var slice = visible.slice(start, start + perPage);

      data.forEach(function (item) { item.el.classList.add('is-hidden'); });
      slice.forEach(function (item, index) {
        item.el.classList.remove('is-hidden');
        item.el.classList.add('is-in');
        item.el.style.order = String(index);
      });
      if (grid) grid.style.display = slice.length ? '' : 'none';
      if (empty) empty.hidden = slice.length > 0;
      countEls.forEach(function (el) { el.textContent = String(visible.length); });
      renderPagination(visible.length);
      updateAvailability(data.filter(matches));

      if (scrollToTop && grid) {
        var top = grid.getBoundingClientRect().top + window.scrollY - 140;
        window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
      }
    }

    $$('input[data-filter="category"][data-category-role="parent"]', scope).forEach(function (parent) {
      var panel = $('[data-children-of="' + parent.value + '"]', scope);
      if (!panel) return;
      var sync = function () {
        panel.hidden = !parent.checked;
        if (!parent.checked) {
          $$('input', panel).forEach(function (child) { child.checked = false; });
        }
      };
      parent.addEventListener('change', sync);
      sync();
    });
    $$('input[data-filter="category"][data-category-role="child"]', scope).forEach(function (child) {
      child.addEventListener('change', function () {
        if (!child.checked) return;
        var parent = $('input[data-filter="category"][value="' + child.getAttribute('data-category-parent') + '"]', scope);
        if (parent && !parent.checked) {
          parent.checked = true;
          parent.dispatchEvent(new Event('change'));
        }
      });
    });

    if (initialCategory) {
      var preset = $('input[data-filter="category"][value="' + initialCategory + '"]', scope);
      if (preset) {
        preset.checked = true;
        preset.dispatchEvent(new Event('change'));
      }
    }

    $$('input[data-filter]', scope).forEach(function (input) {
      input.addEventListener('change', function () { page = 1; render(false); });
    });
    if (sortSelect) sortSelect.addEventListener('change', function () { page = 1; render(false); });
    $$('[data-filter-reset]', scope).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('input[data-filter]', scope).forEach(function (input) { input.checked = false; });
        $$('[data-children-of]', scope).forEach(function (panel) { panel.hidden = true; });
        page = 1;
        render(false);
      });
    });

    function setDrawer(state) {
      if (!filters) return;
      filters.classList.toggle('is-open', state);
      if (backdrop) backdrop.classList.toggle('is-open', state);
      body.classList.toggle('is-locked', state);
    }
    $$('[data-filters-open]', scope).forEach(function (btn) { btn.addEventListener('click', function () { setDrawer(true); }); });
    $$('[data-filters-close]', scope).forEach(function (btn) { btn.addEventListener('click', function () { setDrawer(false); }); });
    if (backdrop) backdrop.addEventListener('click', function () { setDrawer(false); });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setDrawer(false);
    });

    render(false);
  }

  /* ------------------------------------------------------------------ */
  /* Product detail: stage, hotspots, sub navigation, lightbox            */
  /* ------------------------------------------------------------------ */
  function initProduct() {
    var stage = $('[data-stage]');
    if (stage) {
      var image = $('[data-stage-image]', stage);
      var meta = $('[data-stage-meta]', stage);
      var thumbs = $$('[data-thumb]');
      var hotspotLayer = $('[data-hotspots]', stage);
      thumbs.forEach(function (thumb, index) {
        thumb.addEventListener('click', function () {
          var src = thumb.getAttribute('data-thumb');
          var label = thumb.getAttribute('data-label') || '';
          if (!src || !image || image.getAttribute('src') === src) return;
          stage.classList.add('is-switching');
          setTimeout(function () {
            image.setAttribute('src', src);
            image.setAttribute('alt', label);
            if (meta) meta.textContent = label;
            stage.classList.remove('is-switching');
          }, reduceMotion ? 0 : 260);
          thumbs.forEach(function (t) { t.classList.toggle('is-active', t === thumb); });
          if (hotspotLayer) hotspotLayer.hidden = index !== 0;
        });
      });

      var hotspots = $$('[data-hotspot]', stage);
      var legend = $$('[data-legend]');
      function activate(id) {
        hotspots.forEach(function (h) { h.classList.toggle('is-active', h.getAttribute('data-hotspot') === id); });
        legend.forEach(function (l) { l.classList.toggle('is-active', l.getAttribute('data-legend') === id); });
      }
      hotspots.forEach(function (h) {
        h.addEventListener('click', function () {
          activate(h.classList.contains('is-active') ? '' : h.getAttribute('data-hotspot'));
        });
      });
      legend.forEach(function (l) {
        l.addEventListener('mouseenter', function () { activate(l.getAttribute('data-legend')); });
        l.addEventListener('mouseleave', function () { activate(''); });
        l.addEventListener('click', function () { activate(l.getAttribute('data-legend')); });
      });
    }

    var subnav = $('[data-subnav]');
    if (subnav && 'IntersectionObserver' in window) {
      var links = $$('a[href^="#"]', subnav);
      var sections = links.map(function (link) { return $(link.getAttribute('href')); }).filter(Boolean);
      var current = null;
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) current = entry.target.id;
        });
        links.forEach(function (link) {
          link.classList.toggle('is-active', link.getAttribute('href') === '#' + current);
        });
      }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
      sections.forEach(function (section) { observer.observe(section); });
    }

    var lightbox = $('[data-lightbox]');
    var triggers = $$('[data-lightbox-open]');
    if (lightbox && triggers.length && typeof lightbox.showModal === 'function') {
      var img = $('img', lightbox);
      var caption = $('[data-lightbox-caption]', lightbox);
      var index = 0;
      function show(i) {
        index = (i + triggers.length) % triggers.length;
        var trigger = triggers[index];
        img.src = trigger.getAttribute('data-lightbox-open');
        img.alt = trigger.getAttribute('data-label') || '';
        if (caption) caption.textContent = (index + 1) + ' / ' + triggers.length + (img.alt ? ' — ' + img.alt : '');
      }
      triggers.forEach(function (trigger, i) {
        trigger.addEventListener('click', function () {
          show(i);
          lightbox.showModal();
        });
      });
      $$('[data-lightbox-close]', lightbox).forEach(function (btn) { btn.addEventListener('click', function () { lightbox.close(); }); });
      $$('[data-lightbox-prev]', lightbox).forEach(function (btn) { btn.addEventListener('click', function () { show(index - 1); }); });
      $$('[data-lightbox-next]', lightbox).forEach(function (btn) { btn.addEventListener('click', function () { show(index + 1); }); });
      lightbox.addEventListener('click', function (event) { if (event.target === lightbox) lightbox.close(); });
      lightbox.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
      });
    }
  }

  /* ------------------------------------------------------------------ */
  /* Boot                                                                 */
  /* ------------------------------------------------------------------ */
  function boot() {
    initSplit();
    initHeader();
    initMenu();
    initWechat();
    initHeroStage();
    initTabs();
    initArchive();
    initProduct();
    initCounters();
    initParallax();
    initReveal();
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
