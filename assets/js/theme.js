(() => {
  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  // Phones show only the current page between the first and last; wider screens add one page either side.
  const pagerWide = window.matchMedia('(min-width: 640px)');
  const pagerPageNumbers = (totalPages, currentPage) => {
    const radius = pagerWide.matches ? 1 : 0;
    const pages = [];
    for (let page = 1; page <= totalPages; page += 1) {
      if (page === 1 || page === totalPages || Math.abs(page - currentPage) <= radius) pages.push(page);
    }
    const items = [];
    pages.forEach((page, index) => {
      const gap = index > 0 ? page - pages[index - 1] : 1;
      // A gap of one page shows that page rather than an ellipsis standing in for it.
      if (gap === 2) items.push(page - 1);
      else if (gap > 2) items.push(null);
      items.push(page);
    });
    return items;
  };
  const i18n = window.bobliftI18n || {};
  const i18nText = (section, key, fallback) => {
    const value = i18n?.[section]?.[key];
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
  };
  const quoteFormText = (key, fallback) => i18nText('quoteForm', key, fallback);
  const quoteFormMessage = (key, fallback) => {
    const value = i18n?.quoteForm?.messages?.[key];
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
  };
  const quoteListText = (key, fallback) => i18nText('quoteList', key, fallback);
  const headerText = (key, fallback) => i18nText('header', key, fallback);
  const productArchiveText = (key, fallback) => i18nText('productArchive', key, fallback);

  const loadLazyIframe = (iframe) => {
    const src = iframe?.dataset?.lazyIframeSrc || '';
    if (!src || iframe.dataset.lazyIframeLoaded === 'true') return;
    iframe.src = src;
    iframe.dataset.lazyIframeLoaded = 'true';
    iframe.removeAttribute('data-lazy-iframe-src');
  };

  const lazyIframes = qsa('iframe[data-lazy-iframe-src]');
  if (lazyIframes.length) {
    if ('IntersectionObserver' in window) {
      const lazyIframeObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          loadLazyIframe(entry.target);
          lazyIframeObserver.unobserve(entry.target);
        });
      }, { rootMargin: '280px 0px' });
      lazyIframes.forEach((iframe) => lazyIframeObserver.observe(iframe));
    } else {
      lazyIframes.forEach(loadLazyIframe);
    }
  }

  const searchToggle = qs('[data-search-toggle]');
  const searchPanel = qs('[data-search-panel]');
  const searchInput = qs('[data-search-input]', searchPanel || document);
  const setSearchOpen = (open, restoreFocus = false) => {
    if (!searchToggle || !searchPanel) return;
    if (open && qs('#mobile-menu:not(.hidden)')) qs('[data-mobile-toggle]')?.click();
    searchPanel.classList.toggle('hidden', !open);
    searchToggle.setAttribute('aria-expanded', String(open));
    if (open) {
      qsa('[data-language-root]').forEach((root) => {
        qs('[data-language-menu]', root)?.classList.add('hidden');
        qs('[data-language-toggle]', root)?.setAttribute('aria-expanded', 'false');
      });
      window.requestAnimationFrame(() => {
        searchInput?.focus();
        searchInput?.select();
      });
    } else if (restoreFocus) {
      searchToggle.focus();
    }
  };
  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', () => setSearchOpen(searchPanel.classList.contains('hidden')));
    qs('[data-mobile-toggle]')?.addEventListener('click', () => setSearchOpen(false));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !searchPanel.classList.contains('hidden')) setSearchOpen(false, true);
    });
    document.addEventListener('click', (event) => {
      if (searchPanel.classList.contains('hidden')) return;
      if (searchPanel.contains(event.target) || searchToggle.contains(event.target)) return;
      setSearchOpen(false);
    });
    qs('form', searchPanel)?.addEventListener('submit', (event) => {
      if (searchInput && searchInput.value.trim() === '') {
        event.preventDefault();
        searchInput.focus();
      }
    });
  }

  const languageRoots = qsa('[data-language-root]');
  const setLanguageOpen = (root, open) => {
    const trigger = qs('[data-language-toggle]', root);
    const menu = qs('[data-language-menu]', root);
    if (!trigger || !menu) return;
    menu.classList.toggle('hidden', !open);
    if (open) {
      window.requestAnimationFrame(() => positionLanguageMenu(root));
    } else {
      menu.style.transform = '';
    }
    trigger.setAttribute('aria-expanded', String(open));
  };
  const positionLanguageMenu = (root) => {
    const menu = qs('[data-language-menu]', root);
    if (!menu || menu.classList.contains('hidden')) return;
    menu.style.transform = '';
    const panel = menu.firstElementChild || menu;
    const rect = panel.getBoundingClientRect();
    const margin = 16;
    let offset = 0;
    if (rect.left < margin) {
      offset = margin - rect.left;
    } else if (rect.right > window.innerWidth - margin) {
      offset = window.innerWidth - margin - rect.right;
    }
    menu.style.transform = offset ? `translateX(${offset}px)` : '';
  };
  const closeLanguageMenus = (exceptRoot = null) => {
    languageRoots.forEach((root) => {
      if (root !== exceptRoot) setLanguageOpen(root, false);
    });
  };

  const secondaryNavRoots = qsa('[data-secondary-nav-root]');
  let closeSecondaryNavs = () => {};
  if (secondaryNavRoots.length) {
    let secondaryNavCloseTimer = 0;
    const secondaryNavOpenClasses = ['pointer-events-auto', 'visible', 'opacity-100'];
    const secondaryNavClosedClasses = ['pointer-events-none', 'invisible', 'opacity-0'];
    const setClassState = (node, open) => {
      if (!node) return;
      secondaryNavOpenClasses.forEach((className) => node.classList.toggle(className, open));
      secondaryNavClosedClasses.forEach((className) => node.classList.toggle(className, !open));
    };
    const positionSecondaryNav = (root) => {
      const menu = qs('[data-secondary-nav-menu]', root);
      if (menu) menu.style.top = '';
    };
    closeSecondaryNavs = (exceptRoot = null) => {
      secondaryNavRoots.forEach((root) => {
        if (root === exceptRoot) return;
        const trigger = qs('[data-secondary-nav-trigger]', root);
        root.dataset.secondaryNavOpen = 'false';
        root.classList.remove('is-secondary-nav-open');
        setClassState(qs('[data-secondary-nav-menu]', root), false);
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      });
    };
    const setSecondaryNavOpen = (root, open) => {
      window.clearTimeout(secondaryNavCloseTimer);
      if (open) {
        closeSecondaryNavs(root);
        positionSecondaryNav(root);
      }
      const trigger = qs('[data-secondary-nav-trigger]', root);
      root.dataset.secondaryNavOpen = open ? 'true' : 'false';
      root.classList.toggle('is-secondary-nav-open', open);
      setClassState(qs('[data-secondary-nav-menu]', root), open);
      if (trigger) trigger.setAttribute('aria-expanded', String(open));
    };
    secondaryNavRoots.forEach((root) => {
      root.addEventListener('mouseenter', () => setSecondaryNavOpen(root, true));
      root.addEventListener('mouseleave', () => {
        window.clearTimeout(secondaryNavCloseTimer);
        secondaryNavCloseTimer = window.setTimeout(() => setSecondaryNavOpen(root, false), 260);
      });
      root.addEventListener('focusin', () => setSecondaryNavOpen(root, true));
      root.addEventListener('focusout', (event) => {
        if (root.contains(event.relatedTarget)) return;
        window.clearTimeout(secondaryNavCloseTimer);
        secondaryNavCloseTimer = window.setTimeout(() => setSecondaryNavOpen(root, false), 180);
      });
    });
    window.addEventListener('resize', () => {
      secondaryNavRoots.forEach((root) => {
        if (root.dataset.secondaryNavOpen === 'true') positionSecondaryNav(root);
      });
    });
    window.addEventListener('scroll', () => {
      secondaryNavRoots.forEach((root) => {
        if (root.dataset.secondaryNavOpen === 'true') positionSecondaryNav(root);
      });
    }, { passive: true });
  }



  const waitForProductImage = (image, src) => new Promise((resolve, reject) => {
    if (!image || !src) {
      reject(new Error('Missing image target'));
      return;
    }
    let settled = false;
    const cleanup = () => {
      image.removeEventListener('load', onLoad);
      image.removeEventListener('error', onError);
    };
    const finish = () => {
      if (settled) return;
      settled = true;
      cleanup();
      if (typeof image.decode === 'function') {
        image.decode().catch(() => {}).then(resolve);
        return;
      }
      resolve();
    };
    const onLoad = () => finish();
    const onError = () => {
      if (settled) return;
      settled = true;
      cleanup();
      reject(new Error('Image failed to load'));
    };
    image.addEventListener('load', onLoad, { once: true });
    image.addEventListener('error', onError, { once: true });
    if (image.getAttribute('src') !== src) image.src = src;
    if (image.complete && image.naturalWidth > 0) finish();
  });


  qsa('[data-product-hero-gallery-root]').forEach((root) => {
    const currentImage = qs('[data-product-hero-gallery-current]', root) || qs('[data-product-hero-gallery-image]', root);
    const nextImage = qs('[data-product-hero-gallery-next]', root);
    const thumbs = qsa('[data-product-hero-gallery-thumb]', root);
    const primaryOnlyItems = qsa('[data-product-hero-primary-only]', root);
    if (!currentImage || !thumbs.length) return;

    let switchToken = 0;
    const galleryActiveClasses = ['border-blue-500', 'bg-blue-50', 'shadow-card'];
    const galleryInactiveClasses = ['border-[#DDE5EF]', 'bg-white', 'hover:border-blue-300'];
    const setGalleryActive = (button, active) => {
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      galleryActiveClasses.forEach((className) => button.classList.toggle(className, active));
      galleryInactiveClasses.forEach((className) => button.classList.toggle(className, !active));
    };
    const setPrimaryOnlyVisible = (visible) => {
      primaryOnlyItems.forEach((item) => {
        item.hidden = !visible;
        item.style.display = visible ? '' : 'none';
        item.setAttribute('aria-hidden', visible ? 'false' : 'true');
      });
    };
    const resetNextLayer = () => {
      if (!nextImage) return;
      nextImage.classList.add('transition-none');
      nextImage.classList.remove('opacity-100');
      nextImage.classList.add('opacity-0');
      nextImage.offsetHeight;
      nextImage.classList.remove('transition-none');
    };
    // Cut-outs stand whole on the stage floor; photos show whole. The fit comes from the thumb.
    const cutoutFit = ['object-bottom', 'px-3', 'pt-3', 'pb-6', 'sm:px-4', 'sm:pt-4', 'sm:pb-8', 'lg:px-6', 'lg:pt-6', 'lg:pb-10'];
    const photoFit = ['bg-[#EDF1F5]'];
    const applyFit = (image, fit) => {
      if (!image?.classList) return;
      const cutout = fit === 'cutout';
      cutoutFit.forEach((className) => image.classList.toggle(className, cutout));
      // Photos show whole on a plain mat instead of being cropped to the stage.
      photoFit.forEach((className) => image.classList.toggle(className, !cutout));
      image.classList.add('object-contain');
      image.classList.remove('object-cover');
    };
    const switchProductImage = async (src, label = '', fit = 'photo') => {
      if (!src) return;
      const token = ++switchToken;
      currentImage.alt = label || currentImage.alt || '';
      currentImage.classList.remove('opacity-0');
      if (currentImage.getAttribute('src') === src) {
        resetNextLayer();
        return;
      }
      try {
        const targetImage = nextImage || new Image();
        targetImage.classList?.add('transition-none');
        targetImage.classList?.remove('opacity-100');
        targetImage.classList?.add('opacity-0');
        applyFit(targetImage, fit);
        await waitForProductImage(targetImage, src);
        if (token !== switchToken) return;
        if (nextImage) {
          nextImage.classList.add('transition-none');
          nextImage.classList.remove('opacity-0');
          nextImage.classList.add('opacity-100');
          nextImage.offsetHeight;
        }
        currentImage.src = src;
        applyFit(currentImage, fit);
        currentImage.classList.remove('opacity-0');
        window.requestAnimationFrame(() => {
          if (token !== switchToken) return;
          resetNextLayer();
        });
      } catch (error) {
        if (token === switchToken) resetNextLayer();
      }
    };

    root.dataset.productHeroActiveIndex = root.dataset.productHeroActiveIndex || '0';
    setPrimaryOnlyVisible(root.dataset.productHeroActiveIndex === '0');

    thumbs.forEach((button) => {
      button.addEventListener('click', () => {
        const index = button.dataset.productHeroGalleryIndex || '0';
        const src = button.dataset.productHeroGallerySrc || '';
        const label = button.dataset.productHeroGalleryLabel || '';
        root.dataset.productHeroActiveIndex = index;
        setPrimaryOnlyVisible(index === '0');
        thumbs.forEach((item) => setGalleryActive(item, item === button));
        switchProductImage(src, label, button.dataset.productHeroGalleryFit || 'photo');
      });
    });

    // The arrows on the stage step through the thumbs, wrapping at either end.
    qsa('[data-product-hero-gallery-step]', root).forEach((arrow) => {
      arrow.addEventListener('click', () => {
        const current = thumbs.findIndex((item) => (item.dataset.productHeroGalleryIndex || '0') === root.dataset.productHeroActiveIndex);
        const target = thumbs[(Math.max(current, 0) + Number(arrow.dataset.productHeroGalleryStep) + thumbs.length) % thumbs.length];
        target.click();
        target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      });
    });
  });



  // Hotspots are placed in the editor on a square stage holding the whole image (object-contain). The product stage is
  // wider and pads the image, so lay the hotspot layer over the image as displayed and map the editor coordinates onto it.
  const placeHotspots = (root) => {
    const image = qs('[data-product-hero-gallery-current]', root.parentElement || document);
    if (!image || !image.naturalWidth || !image.naturalHeight) return;
    const style = window.getComputedStyle(image);
    const px = (value) => parseFloat(value) || 0;
    const boxW = image.clientWidth - px(style.paddingLeft) - px(style.paddingRight);
    const boxH = image.clientHeight - px(style.paddingTop) - px(style.paddingBottom);
    if (boxW <= 0 || boxH <= 0) return;
    const ratio = image.naturalWidth / image.naturalHeight;
    const width = boxW / boxH > ratio ? boxH * ratio : boxW;
    const height = width / ratio;
    const [posX, posY] = style.objectPosition.split(' ').map((part) => (part.endsWith('%') ? parseFloat(part) / 100 : 0.5));
    Object.assign(root.style, {
      inset: 'auto',
      left: `${image.offsetLeft + px(style.paddingLeft) + (boxW - width) * posX}px`,
      top: `${image.offsetTop + px(style.paddingTop) + (boxH - height) * (Number.isFinite(posY) ? posY : 0.5)}px`,
      width: `${width}px`,
      height: `${height}px`,
    });
    qsa('[data-product-hotspot]', root).forEach((button) => {
      const x = parseFloat(button.dataset.hotspotX || '50');
      const y = parseFloat(button.dataset.hotspotY || '50');
      // Editor square: a wide image spans the full width and is centred vertically; a tall one the reverse.
      const u = ratio >= 1 ? x : (x - ((1 - ratio) / 2) * 100) / ratio;
      const v = ratio >= 1 ? (y - ((1 - 1 / ratio) / 2) * 100) * ratio : y;
      button.style.left = `${Math.max(0, Math.min(100, u))}%`;
      button.style.top = `${Math.max(0, Math.min(100, v))}%`;
    });
  };
  qsa('[data-product-hotspot-root]').forEach((root) => {
    const image = qs('[data-product-hero-gallery-current]', root.parentElement || document);
    const place = () => placeHotspots(root);
    if (image) {
      if (image.complete) place();
      image.addEventListener('load', place);
    }
    window.addEventListener('resize', place, { passive: true });
  });

  qsa('[data-product-hotspot-root]').forEach((root) => {
    const closeHotspots = () => {
      qsa('[data-product-hotspot]', root).forEach((button) => {
        button.setAttribute('aria-expanded', 'false');
        button.classList.remove('z-30');
        qs('[data-product-hotspot-label]', button)?.classList.add('hidden');
      });
    };
    root.addEventListener('click', (event) => {
      const button = event.target.closest('[data-product-hotspot]');
      if (!button || !root.contains(button)) return;
      event.stopPropagation();
      const shouldOpen = button.getAttribute('aria-expanded') !== 'true';
      closeHotspots();
      if (!shouldOpen) return;
      button.setAttribute('aria-expanded', 'true');
      button.classList.add('z-30');
      qs('[data-product-hotspot-label]', button)?.classList.remove('hidden');
    });
    document.addEventListener('click', closeHotspots);
  });

  const mobileToggle = qs('[data-mobile-toggle]');
  const mobileMenu = qs('#mobile-menu');
  const resetMobileSubmenus = () => {
    if (!mobileMenu) return;
    qsa('[data-mobile-submenu-panel]', mobileMenu).forEach((panel) => panel.classList.add('hidden'));
    qsa('[data-mobile-submenu-toggle]', mobileMenu).forEach((button) => {
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-label', headerText('expandProductCategoriesLabel', 'Expand product categories'));
    });
  };
  const setMobileMenuOpen = (open) => {
    if (!mobileToggle || !mobileMenu) return;
    resetMobileSubmenus();
    mobileMenu.classList.toggle('hidden', !open);
    mobileToggle.setAttribute('aria-expanded', String(open));
  };
  languageRoots.forEach((root) => {
    const trigger = qs('[data-language-toggle]', root);
    const menu = qs('[data-language-menu]', root);
    if (!trigger || !menu) return;
    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      const open = menu.classList.contains('hidden');
      setMobileMenuOpen(false);
      closeLanguageMenus(root);
      setLanguageOpen(root, open);
    });
    root.addEventListener('click', (event) => event.stopPropagation());
    qsa('a', menu).forEach((link) => link.addEventListener('click', () => setLanguageOpen(root, false)));
  });
  document.addEventListener('click', () => closeLanguageMenus());
  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeLanguageMenus();
      setMobileMenuOpen(false);
    }
  });
  window.addEventListener('resize', () => languageRoots.forEach(positionLanguageMenu));

  const header = qs('#top');
  const desktopNav = qs('[data-desktop-nav]');
  const fitDesktopNav = () => {
    if (!header || !desktopNav) return;
    const wasCollapsed = header.classList.contains('boblift-nav-collapsed');
    header.classList.remove('boblift-nav-collapsed');
    let collapse = false;
    if (getComputedStyle(desktopNav).display !== 'none') {
      const items = [...desktopNav.children].filter((item) => item.getClientRects().length);
      const before = desktopNav.previousElementSibling?.getBoundingClientRect();
      const after = desktopNav.nextElementSibling?.getBoundingClientRect();
      const rtl = getComputedStyle(desktopNav).direction === 'rtl';
      const left = Math.min(...items.map((item) => item.getBoundingClientRect().left));
      const right = Math.max(...items.map((item) => item.getBoundingClientRect().right));
      const startEdge = rtl ? before?.left : before?.right;
      const endEdge = rtl ? after?.right : after?.left;
      const overlapsStart = startEdge !== undefined && (rtl ? right > startEdge + 1 : left < startEdge - 1);
      const overlapsEnd = endEdge !== undefined && (rtl ? left < endEdge - 1 : right > endEdge + 1);
      collapse = desktopNav.scrollWidth > desktopNav.clientWidth + 1 || (items.length > 0 && (overlapsStart || overlapsEnd));
    }
    header.classList.toggle('boblift-nav-collapsed', collapse);
    if (wasCollapsed && !collapse) setMobileMenuOpen(false);
  };
  fitDesktopNav();
  document.fonts?.ready.then(fitDesktopNav);
  let fitNavFrame = 0;
  window.addEventListener('resize', () => {
    cancelAnimationFrame(fitNavFrame);
    fitNavFrame = requestAnimationFrame(fitDesktopNav);
  });

  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', (event) => {
      event.stopPropagation();
      closeLanguageMenus();
      setMobileMenuOpen(mobileMenu.classList.contains('hidden'));
    });
    mobileMenu.addEventListener('click', (event) => event.stopPropagation());
    qsa('[data-mobile-submenu-toggle]', mobileMenu).forEach((button) => {
      const panel = document.getElementById(button.getAttribute('aria-controls') || '');
      if (!panel) return;
      const setSubmenuOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        button.setAttribute('aria-expanded', String(open));
        button.setAttribute('aria-label', open ? headerText('collapseProductCategoriesLabel', 'Collapse product categories') : headerText('expandProductCategoriesLabel', 'Expand product categories'));
      };
      const toggleSubmenu = (event) => {
        event.preventDefault();
        event.stopPropagation();
        setSubmenuOpen(button.getAttribute('aria-expanded') !== 'true');
      };
      button.addEventListener('click', toggleSubmenu);
      const row = button.closest('[data-mobile-submenu-row]');
      if (row) {
        row.addEventListener('click', (event) => {
          if (event.target.closest('a')) return;
          toggleSubmenu(event);
        });
      }
    });
    document.addEventListener('click', (event) => {
      if (mobileMenu.classList.contains('hidden')) return;
      if (mobileMenu.contains(event.target) || mobileToggle.contains(event.target)) return;
      setMobileMenuOpen(false);
    });
  }

  const aboutLogoSections = qsa('[data-about-logo-scroll]');
  if (aboutLogoSections.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    gsap.registerPlugin(ScrollTrigger);

    aboutLogoSections.forEach((section) => {
      const pin = qs('[data-about-logo-pin]', section);
      const bg = qs('[data-about-crane-bg]', section);
      const logo = qs('[data-about-logo-text]', section);
      const logoSolid = qs('[data-about-logo-solid]', section);
      const logoImage = qs('[data-about-logo-image]', section);
      const silhouette = qs('[data-about-silhouette]', section);
      const mask = qs('[data-about-mask]', section);
      const image = qs('img', bg);
      const aerialVideo = qs('[data-about-aerial-video]', section);
      const aerialStart = qs('[data-about-aerial-start]', section);
      const aerialEnd = qs('[data-about-aerial-end]', section);
      const logoVideo = qs('[data-about-logo-video]', section);
      const heroCopy = qs('[data-about-hero-copy]', section);
      if (!pin || !bg || !logo) return;

      const mm = gsap.matchMedia();
      mm.add('(min-width: 901px) and (prefers-reduced-motion: no-preference)', () => {
        section.classList.add('is-gsap-ready');
        gsap.set(bg, { scale: 1.08, yPercent: 0, opacity: 0, borderRadius: '4px', overflow: 'hidden', force3D: true });
        if (silhouette) gsap.set(silhouette, { scale: 1.02, opacity: 1, force3D: true });
        // The aerial clip loops while the hero is in view (it used to stop on its last frame after one play),
        // and the letters play the same clip in step with it.
        [aerialVideo, logoVideo].filter(Boolean).forEach((video) => {
          video.muted = true;
          video.playsInline = true;
          video.loop = true;
          video.pause();
          video.load();
        });
        gsap.set([aerialStart, aerialVideo, aerialEnd].filter(Boolean), { force3D: true });
        gsap.set(aerialStart, { opacity: 1 });
        gsap.set(aerialVideo, { opacity: 0 });
        gsap.set(aerialEnd, { opacity: 0 });
        if (mask) gsap.set(mask, { opacity: 1, force3D: true });
        gsap.set(logo, { scale: 0.58, y: () => -Math.max(160, window.innerHeight * 0.26), opacity: 1, force3D: true });
        gsap.set(logoSolid, { opacity: 1 });
        gsap.set(logoImage, { opacity: 0 });

        const aerialStartProgress = 0.08;
        let aerialPhase = 'start';
        let aerialIsPlaying = false;

        const setAerialLayer = (phase) => {
          if (aerialPhase === phase) return;
          aerialPhase = phase;
          if (aerialStart) aerialStart.style.opacity = phase === 'start' ? '1' : '0';
          if (aerialVideo) aerialVideo.style.opacity = phase === 'playing' ? '1' : '0';
          if (aerialEnd) aerialEnd.style.opacity = phase === 'end' ? '1' : '0';
        };
        const pauseAerialVideo = () => {
          if (!aerialVideo) return;
          aerialVideo.pause();
          if (logoVideo) logoVideo.pause();
          aerialIsPlaying = false;
        };
        const syncLogoVideo = () => {
          if (!logoVideo || !aerialVideo) return;
          if (Math.abs(logoVideo.currentTime - aerialVideo.currentTime) > 0.2) {
            try {
              logoVideo.currentTime = aerialVideo.currentTime;
            } catch {}
          }
          if (!aerialVideo.paused && logoVideo.paused) logoVideo.play().catch(() => {});
        };
        const resetAerialVideo = () => {
          if (!aerialVideo) {
            setAerialLayer('start');
            return;
          }
          pauseAerialVideo();
          try {
            if (aerialVideo.currentTime > 0.04) aerialVideo.currentTime = 0;
            if (logoVideo && logoVideo.currentTime > 0.04) logoVideo.currentTime = 0;
          } catch {}
          setAerialLayer('start');
        };
        const showAerialEnd = () => {
          aerialIsPlaying = false;
          setAerialLayer('end');
        };
        const playAerialVideo = () => {
          if (!aerialVideo) return;
          if (aerialVideo.ended) {
            showAerialEnd();
            return;
          }
          setAerialLayer('playing');
          if (aerialIsPlaying && !aerialVideo.paused) return;
          const playResult = aerialVideo.play();
          if (logoVideo) {
            syncLogoVideo();
            logoVideo.play().catch(() => {});
          }
          aerialIsPlaying = true;
          if (playResult && typeof playResult.catch === 'function') {
            playResult.catch(() => {
              aerialIsPlaying = false;
            });
          }
        };
        const updateAerialMedia = (progress) => {
          if (!aerialVideo) {
            setAerialLayer(progress <= aerialStartProgress ? 'start' : 'end');
            return;
          }

          if (progress <= aerialStartProgress) {
            if (aerialPhase !== 'start' || aerialVideo.currentTime > 0.04) resetAerialVideo();
            return;
          }

          if (aerialPhase !== 'end') playAerialVideo();
        };

        const tl = gsap.timeline({
          scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: '+=90%', // About one screen of scrolling; the section is 160svh tall (input.css)
            scrub: 0.65,
            pin,
            pinSpacing: false,
            anticipatePin: 1,
            invalidateOnRefresh: true,
            markers: false,
            onToggle: (self) => {
              section.classList.toggle('is-about-logo-active', self.isActive);
              // Looping clips stop once the hero scrolls away; scrolling back resumes them through onUpdate.
              if (self.isActive) updateAerialMedia(self.progress);
              else pauseAerialVideo();
            },
            onUpdate: (self) => updateAerialMedia(self.progress),
          },
        });

        tl.to(bg, { scale: 1.1, yPercent: -2, opacity: 1, duration: 0.42, ease: 'none' }, 0)
          .to(silhouette, { scale: 1.04, opacity: 0, duration: 0.34, ease: 'none' }, 0.08)
          .to(mask, { opacity: 0.42, duration: 0.42, ease: 'none' }, 0)
          .to(logo, { scale: 1.04, y: 0, opacity: 1, duration: 0.42, ease: 'power2.out' }, 0)
          .to(logoSolid, { opacity: 0, duration: 0.1, ease: 'none' }, 0)
          .to(logoImage, { opacity: 1, duration: 0.1, ease: 'none' }, 0)
          // The page title fades as the aerial footage comes in, so it never sits over the busy photo.
          .to(heroCopy, { opacity: 0, y: -16, duration: 0.16, ease: 'none' }, 0.04)
          // The scene already has a static clip radius; scaling reveals it without repainting the radius every frame.
          .to(bg, { scale: 0.94, yPercent: -5, duration: 0.48, ease: 'power2.inOut' }, 0.42)
          .to(logo, { scale: 0.6, y: () => -window.innerHeight * 0.15, opacity: 0, duration: 0.48, ease: 'power2.inOut' }, 0.42)
          .to(mask, { opacity: 0.85, duration: 0.48, ease: 'power2.inOut' }, 0.42);

        if (image && !image.complete) {
          image.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
        }
        const handleAerialLoadedMetadata = () => ScrollTrigger.refresh();
        const handleAerialEnded = () => showAerialEnd();
        const handleAerialPause = () => {
          aerialIsPlaying = false;
        };
        if (aerialVideo) {
          aerialVideo.addEventListener('loadedmetadata', handleAerialLoadedMetadata, { once: true });
          aerialVideo.addEventListener('ended', handleAerialEnded);
          aerialVideo.addEventListener('pause', handleAerialPause);
          ['timeupdate', 'play', 'seeked'].forEach((type) => aerialVideo.addEventListener(type, syncLogoVideo));
        }

        return () => {
          section.classList.remove('is-gsap-ready');
          section.classList.remove('is-about-logo-active');
          if (aerialVideo) {
            aerialVideo.removeEventListener('loadedmetadata', handleAerialLoadedMetadata);
            aerialVideo.removeEventListener('ended', handleAerialEnded);
            aerialVideo.removeEventListener('pause', handleAerialPause);
            ['timeupdate', 'play', 'seeked'].forEach((type) => aerialVideo.removeEventListener(type, syncLogoVideo));
            pauseAerialVideo();
          }
          tl.kill();
          gsap.set([bg, silhouette, mask, logo, logoSolid, logoImage, heroCopy, aerialStart, aerialVideo, aerialEnd].filter(Boolean), { clearProps: 'all' });
        };
      });

      window.addEventListener('pagehide', () => mm.revert(), { once: true });
    });
  }

  const aboutStrengthSections = qsa('[data-about-strength]');
  if (aboutStrengthSections.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    gsap.registerPlugin(ScrollTrigger);

    aboutStrengthSections.forEach((section) => {
      const copy = qs('[data-about-strength-copy]', section);
      const bridge = qs('[data-about-strength-bridge]', section);
      const mm = gsap.matchMedia();

      mm.add('(min-width: 901px) and (prefers-reduced-motion: no-preference)', () => {
        if (!copy) return undefined;
        section.classList.add('is-gsap-ready');
        gsap.set(copy, { y: 72, opacity: 0, force3D: true });
        gsap.set(bridge, { scaleY: 1.22, opacity: 1, force3D: true });

        const tl = gsap.timeline({
          scrollTrigger: {
            trigger: section,
            start: 'top 82%',
            end: 'top 24%',
            scrub: 0.85,
            invalidateOnRefresh: true,
          },
        });

        tl.to(bridge, { scaleY: 0.64, opacity: 0.45, duration: 0.34, ease: 'none' }, 0)
          .to(copy, { y: 0, opacity: 1, duration: 0.62, ease: 'power2.out' }, 0.04);

        return () => {
          section.classList.remove('is-gsap-ready');
          tl.kill();
          gsap.set([copy, bridge].filter(Boolean), { clearProps: 'all' });
        };
      });

      window.addEventListener('pagehide', () => mm.revert(), { once: true });
    });
  }

  const aboutTimelineSections = qsa('[data-about-timeline]');
  if (aboutTimelineSections.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    gsap.registerPlugin(ScrollTrigger);

    aboutTimelineSections.forEach((section) => {
      const header  = qs('[data-about-timeline-header]', section);
      const spine   = qs('[data-about-timeline-spine]', section);
      const entries = qsa('[data-about-timeline-entry]', section);
      const mm = gsap.matchMedia();

      mm.add('(min-width: 901px) and (prefers-reduced-motion: no-preference)', () => {
        if (!entries.length) return undefined;
        section.classList.add('is-gsap-ready');

        // Set initial states (CSS .is-gsap-ready handles opacity:0 for elements;
        // here we add translate offsets so GSAP can tween from them)
        if (header) gsap.set(header, { y: 52, opacity: 0, force3D: true });
        if (spine)  gsap.set(spine,  { scaleY: 0, transformOrigin: 'top center', force3D: true });

        entries.forEach((entry) => {
          const isRight = entry.classList.contains('entry--right');
          const dot     = qs('[data-about-timeline-dot]',   entry);
          const year    = qs('[data-about-timeline-year]',  entry);
          const panel   = qs('[data-about-timeline-panel]', entry);
          const xOffset = isRight ? 56 : -56;

          if (dot)   gsap.set(dot,   { scale: 0.3, opacity: 0, force3D: true });
          if (year)  gsap.set(year,  { x: isRight ? -32 : 32, opacity: 0, force3D: true });
          if (panel) gsap.set(panel, { x: xOffset, opacity: 0, force3D: true });
        });

        // Header ScrollTrigger
        if (header) {
          ScrollTrigger.create({
            trigger: header,
            start: 'top 82%',
            once: true,
            onEnter: () => {
              gsap.to(header, { y: 0, opacity: 1, duration: 0.56, ease: 'power2.out' });
            },
          });
        }

        // Spine draws down as the section scrolls into view
        if (spine) {
          ScrollTrigger.create({
            trigger: section,
            start: 'top 72%',
            end: 'bottom 28%',
            scrub: 0.9,
            onUpdate: (self) => {
              gsap.set(spine, { scaleY: self.progress });
            },
          });
        }

        // Each entry has its own ScrollTrigger
        entries.forEach((entry) => {
          const isRight = entry.classList.contains('entry--right');
          const dot     = qs('[data-about-timeline-dot]',   entry);
          const year    = qs('[data-about-timeline-year]',  entry);
          const panel   = qs('[data-about-timeline-panel]', entry);

          ScrollTrigger.create({
            trigger: entry,
            start: 'top 78%',
            once: true,
            onEnter: () => {
              const tl = gsap.timeline();
              if (dot) {
                tl.to(dot, {
                  scale: 1, opacity: 1,
                  duration: 0.36, ease: 'back.out(1.8)',
                }, 0);
              }
              if (year) {
                tl.to(year, {
                  x: 0, opacity: 1,
                  duration: 0.44, ease: 'power3.out',
                }, 0.06);
              }
              if (panel) {
                tl.to(panel, {
                  x: 0, opacity: 1,
                  duration: 0.52, ease: 'power3.out',
                }, 0.12);
              }
            },
          });
        });

        return () => {
          section.classList.remove('is-gsap-ready');
          ScrollTrigger.getAll().forEach((st) => {
            if (st.trigger === header || st.trigger === section || entries.includes(st.trigger)) {
              st.kill();
            }
          });
          const targets = [
            header, spine,
            ...entries.map((e) => [
              qs('[data-about-timeline-dot]', e),
              qs('[data-about-timeline-year]', e),
              qs('[data-about-timeline-panel]', e),
            ]).flat(),
          ].filter(Boolean);
          gsap.set(targets, { clearProps: 'all' });
        };
      });

      window.addEventListener('pagehide', () => mm.revert(), { once: true });
    });
  }



  const gallerySections = qsa('[data-about-gallery]');
  if (gallerySections.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    gallerySections.forEach((section) => {
      const pin = qs('[data-gallery-pin]', section);
      const track = qs('[data-gallery-track]', section);
      const spacer = qs('[data-gallery-spacer]', section);
      if (!pin || !track || reduceMotion) return;

      const getTravel = () => Math.max(0, track.scrollWidth - window.innerWidth + 64);
      const syncGallerySpacer = () => {
        if (!spacer) return;
        const height = Math.max(getTravel(), 520);
        spacer.style.height = `${height}px`;
      };

      const mm = gsap.matchMedia();
      mm.add('(min-width: 901px)', () => {
        syncGallerySpacer();
        const tween = gsap.to(track, {
          x: () => -getTravel(),
          ease: 'none',
          scrollTrigger: {
            trigger: pin,
            start: 'center center',
            end: () => `+=${getTravel()}`,
            pin: true,
            pinSpacing: false,
            scrub: 1,
            anticipatePin: 1,
            invalidateOnRefresh: true,
            onRefreshInit: syncGallerySpacer,
          }
        });
        return () => {
          tween.scrollTrigger?.kill();
          tween.kill();
          if (spacer) spacer.style.height = '';
          gsap.set(track, { clearProps: 'transform' });
        };
      });
    });
  }

  const globalSections = qsa('[data-about-global]');
  if (globalSections.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    globalSections.forEach((section) => {
      const stats = qsa('[data-global-stat]', section);
      if (!stats.length) return;
      if (reduceMotion) {
        stats.forEach((stat) => {
          stat.textContent = stat.getAttribute('data-global-stat') || '0';
        });
        return;
      }

      ScrollTrigger.create({
        trigger: section,
        start: 'top 75%',
        once: true,
        onEnter: () => {
          stats.forEach((stat) => {
            const target = parseInt(stat.getAttribute('data-global-stat') || '0', 10);
            const obj = { val: 0 };
            gsap.to(obj, {
              val: target,
              duration: 2.5,
              ease: 'power3.out',
              onUpdate: () => {
                stat.textContent = String(Math.floor(obj.val));
              }
            });
          });
        }
      });
    });
  }

  const marqueeSections = qsa('[data-about-certifications]');
  if (marqueeSections.length) {
    const gsap = window.gsap;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    marqueeSections.forEach((section) => {
      const tracks = qsa('[data-marquee-track]', section);
      let marqueeTween = null;
      // Phones swipe the certificates by hand (CSS turns the row into a scroller), so the loop runs from 768px up only.
      if (gsap && tracks.length >= 2 && !reduceMotion) {
        gsap.matchMedia().add('(min-width: 768px)', () => {
          // A row swiped on a phone keeps its scroll offset after rotating; the loop must start from the first card.
          const row = qs('[data-certification-marquee]', section);
          if (row) row.scrollLeft = 0;
          marqueeTween = gsap.to(tracks, {
            xPercent: -100,
            repeat: -1,
            duration: 25,
            ease: 'none'
          });
          return () => {
            marqueeTween?.kill();
            marqueeTween = null;
            gsap.set(tracks, { clearProps: 'transform' });
          };
        });
        section.addEventListener('mouseenter', () => marqueeTween?.pause());
        section.addEventListener('mouseleave', () => marqueeTween?.resume());
      }

      // The viewer is rendered after the section (outside its stacking context); older markup kept it inside.
      const lightbox = qs('[data-certification-lightbox]', section) || qs('[data-certification-lightbox]');
      const lightboxImage = lightbox ? qs('[data-certification-lightbox-image]', lightbox) : null;
      const lightboxTitle = lightbox ? qs('[data-certification-lightbox-title]', lightbox) : null;
      const openButtons = qsa('[data-certification-open]', section);
      const closeButtons = lightbox ? qsa('[data-certification-close]', lightbox) : [];
      if (!lightbox || !lightboxImage || !openButtons.length) return;
      if (lightbox.parentElement !== document.body) {
        document.body.appendChild(lightbox);
      }

      const closeLightbox = () => {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImage.setAttribute('src', '');
        lightboxImage.setAttribute('alt', '');
        document.body.classList.remove('overflow-hidden');
      };

      openButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const src = button.getAttribute('data-certification-src') || '';
          const title = button.getAttribute('data-certification-title') || '';
          if (!src) return;
          lightboxImage.setAttribute('src', src);
          lightboxImage.setAttribute('alt', title);
          if (lightboxTitle) lightboxTitle.textContent = title;
          lightbox.classList.remove('hidden');
          lightbox.classList.add('flex');
          lightbox.setAttribute('aria-hidden', 'false');
          document.body.classList.add('overflow-hidden');
        });
      });

      closeButtons.forEach((button) => button.addEventListener('click', closeLightbox));
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && lightbox.getAttribute('aria-hidden') === 'false') {
          closeLightbox();
        }
      });
    });
  }

  const heroMarquee = qsa('[data-social-bg-marquee]');
  if (heroMarquee.length && window.gsap) {
    const gsap = window.gsap;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion) {
      heroMarquee.forEach((section) => {
        const line1 = qsa('[data-marquee-line]', section);
        const line2 = qsa('[data-marquee-line-reverse]', section);
        if (line1.length) {
          gsap.to(line1, { xPercent: -50, repeat: -1, duration: 60, ease: 'none' });
        }
        if (line2.length) {
          gsap.fromTo(line2, { xPercent: -50 }, { xPercent: 0, repeat: -1, duration: 80, ease: 'none' });
        }
      });
    }
  }

  const socialMarqueeSections = qsa('[data-social-marquee]');
  if (socialMarqueeSections.length && window.gsap) {
    const gsap = window.gsap;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    socialMarqueeSections.forEach((section) => {
      const tracks = qsa('[data-marquee-track]', section);
      if (tracks.length < 2 || reduceMotion) return;
      gsap.to(tracks, { xPercent: -100, repeat: -1, duration: 45, ease: 'none' });
    });
  }

  const socialCardsContainer = qsa('[data-social-cards]');
  if (socialCardsContainer.length && window.gsap && window.ScrollTrigger) {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion) {
      socialCardsContainer.forEach((container) => {
        const cards = qsa('[data-social-card]', container);
        if (!cards.length) return;
        gsap.from(cards, {
          y: 80,
          opacity: 0,
          rotationX: -15,
          stagger: 0.1,
          duration: 0.8,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: container,
            start: 'top 85%',
          }
        });
      });
    }
  }

  qsa('[data-back-top]').forEach((button) => {
    const toggle = () => {
      const visible = window.scrollY > 520;
      button.classList.toggle('pointer-events-none', !visible);
      button.classList.toggle('translate-y-4', !visible);
      button.classList.toggle('opacity-0', !visible);
      button.classList.toggle('translate-y-0', visible);
      button.classList.toggle('opacity-100', visible);
    };
    window.addEventListener('scroll', toggle, { passive: true });
    button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    toggle();
  });

  // Product dimension drawing: opens full size in a dialog; Esc, the close button or the backdrop close it.
  qsa('[data-drawing-root]').forEach((root) => {
    const dialog = qs('[data-drawing-dialog]', root);
    if (!dialog || typeof dialog.showModal !== 'function') return;
    qs('[data-drawing-open]', root)?.addEventListener('click', () => dialog.showModal());
    qs('[data-drawing-close]', root)?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  // Home hero slideshow. CSS runs the active tab's progress bar; when it finishes, the next
  // slide shows. Mouse hover and keyboard focus hold it, and the toggle stops or restarts it.
  qsa('[data-home-hero]').forEach((hero) => {
    const slides = qsa('[data-home-hero-slide]', hero);
    const images = qsa('[data-home-hero-image]', hero);
    const tabs = qsa('[data-home-hero-tab]', hero);
    const toggle = qs('[data-home-hero-toggle]', hero);
    if (slides.length < 2 || tabs.length !== slides.length) return;
    let index = 0;
    let hovered = false;
    const setHidden = (element, hidden) => {
      if (hidden) element.setAttribute('aria-hidden', 'true');
      else element.removeAttribute('aria-hidden');
    };
    const show = (nextIndex) => {
      index = (nextIndex + slides.length) % slides.length;
      slides.forEach((slide, i) => setHidden(slide, i !== index));
      images.forEach((image, i) => setHidden(image, i !== index));
      tabs.forEach((tab, i) => {
        tab.setAttribute('aria-selected', String(i === index));
        tab.tabIndex = i === index ? 0 : -1;
      });
    };
    const setPlaying = (playing) => {
      hero.toggleAttribute('data-home-hero-playing', playing);
      if (!toggle) return;
      toggle.setAttribute('aria-label', (playing ? toggle.dataset.pauseLabel : toggle.dataset.playLabel) || '');
      const [pauseIcon, playIcon] = qsa('svg', toggle);
      pauseIcon?.classList.toggle('hidden', !playing);
      playIcon?.classList.toggle('hidden', playing);
    };
    const updateHold = () => {
      const active = document.activeElement;
      const keyboardFocus = Boolean(active && hero.contains(active) && active.matches(':focus-visible'));
      hero.toggleAttribute('data-home-hero-held', hovered || keyboardFocus);
    };
    hero.addEventListener('pointerenter', (event) => {
      if (event.pointerType !== 'mouse') return;
      hovered = true;
      updateHold();
    });
    hero.addEventListener('pointerleave', (event) => {
      if (event.pointerType !== 'mouse') return;
      hovered = false;
      updateHold();
    });
    hero.addEventListener('focusin', updateHold);
    hero.addEventListener('focusout', () => window.setTimeout(updateHold));
    hero.addEventListener('animationend', (event) => {
      if (event.animationName === 'boblift-home-hero-progress') show(index + 1);
    });
    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => show(i));
      tab.addEventListener('keydown', (event) => {
        const targets = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 };
        if (!(event.key in targets)) return;
        event.preventDefault();
        show(targets[event.key]);
        tabs[index].focus();
      });
    });
    const media = qs('.boblift-home-hero-media', hero);
    let touchStart = null;
    media?.addEventListener('touchstart', (event) => {
      touchStart = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
    }, { passive: true });
    media?.addEventListener('touchend', (event) => {
      if (!touchStart) return;
      const dx = event.changedTouches[0].clientX - touchStart.x;
      const dy = event.changedTouches[0].clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.5) show(index + (dx < 0 ? 1 : -1));
    }, { passive: true });
    toggle?.addEventListener('click', () => setPlaying(!hero.hasAttribute('data-home-hero-playing')));
    setPlaying(!window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  });

  qsa('[data-about-gallery]').forEach((root) => {
    const stage = qs('[data-about-active-stage]', root);
    const play = qs('[data-about-play]', root);
    const thumbs = qsa('[data-about-thumb]', root);
    if (!stage || !thumbs.length) return;
    const renderMedia = (thumb) => {
      const type = thumb.dataset.type || 'image';
      const poster = thumb.dataset.src || '';
      const alt = thumb.dataset.alt || '';
      const videoSrc = thumb.dataset.videoSrc || '';
      const youtubeSrc = thumb.dataset.youtubeSrc || '';
      qsa('img, video, iframe', stage).forEach((node) => node.remove());
      let node;
      if (type === 'youtube' && youtubeSrc) {
        node = document.createElement('iframe');
        node.className = 'h-full w-full';
        node.src = youtubeSrc;
        node.title = alt || 'BOB-LIFT video';
        node.loading = 'lazy';
        node.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        node.allowFullscreen = true;
      } else if (type === 'local_video' && videoSrc) {
        node = document.createElement('video');
        node.className = 'h-full w-full object-cover';
        node.controls = true;
        node.preload = 'metadata';
        if (poster) node.poster = poster;
        const source = document.createElement('source');
        source.src = videoSrc;
        node.appendChild(source);
      } else {
        node = document.createElement('img');
        node.className = 'h-full w-full object-cover';
        node.src = poster;
        node.alt = alt;
        node.loading = 'lazy';
        node.decoding = 'async';
        node.dataset.aboutActiveImage = 'true';
      }
      stage.prepend(node);
      const video = Boolean((type === 'youtube' && youtubeSrc) || (type === 'local_video' && videoSrc));
      play?.classList.toggle('hidden', !video);
      play?.classList.toggle('grid', video);
    };
    thumbs.forEach((thumb) => {
      thumb.addEventListener('click', () => {
        renderMedia(thumb);
        thumbs.forEach((item) => {
          const active = item === thumb;
          item.classList.toggle('border-blue-500', active);
          item.classList.toggle('ring-2', active);
          item.classList.toggle('ring-blue-500/20', active);
          item.classList.toggle('border-white', !active);
        });
      });
    });
  });

  const qrModal = qs('[data-qr-modal]');
  const openQr = () => {
    if (!qrModal) return;
    qrModal.classList.remove('hidden');
    qrModal.classList.add('grid');
  };
  const closeQr = () => {
    if (!qrModal) return;
    qrModal.classList.add('hidden');
    qrModal.classList.remove('grid');
  };
  qsa('[data-qr-open]').forEach((button) => button.addEventListener('click', openQr));
  qsa('[data-qr-close]').forEach((button) => button.addEventListener('click', closeQr));
  qrModal?.addEventListener('click', (event) => {
    if (event.target === qrModal) closeQr();
  });
  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeQr();
  });

  const inquiryModal = qs('[data-global-inquiry-modal]');
  let inquiryModalLastFocus = null;
  let inquiryCharacterApi = null;
  let inquiryModalSelectedProducts = [];
  let inquiryScrollLock = null;
  const lockInquiryPageScroll = () => {
    if (inquiryScrollLock) return;
    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
    inquiryScrollLock = {
      scrollY,
      htmlOverflow: document.documentElement.style.overflow,
      bodyOverflow: document.body.style.overflow,
      bodyPosition: document.body.style.position,
      bodyTop: document.body.style.top,
      bodyLeft: document.body.style.left,
      bodyRight: document.body.style.right,
      bodyWidth: document.body.style.width,
      htmlScrollBehavior: document.documentElement.style.scrollBehavior,
      bodyScrollBehavior: document.body.style.scrollBehavior,
    };
    document.documentElement.classList.add('boblift-inquiry-scroll-locked');
    document.body.classList.add('boblift-inquiry-scroll-locked');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
  };
  const unlockInquiryPageScroll = () => {
    if (!inquiryScrollLock) return;
    const { scrollY, htmlOverflow, bodyOverflow, bodyPosition, bodyTop, bodyLeft, bodyRight, bodyWidth, htmlScrollBehavior, bodyScrollBehavior } = inquiryScrollLock;
    inquiryScrollLock = null;
    document.documentElement.classList.remove('boblift-inquiry-scroll-locked');
    document.body.classList.remove('boblift-inquiry-scroll-locked');
    document.documentElement.style.overflow = htmlOverflow;
    document.body.style.overflow = bodyOverflow;
    document.body.style.position = bodyPosition;
    document.body.style.top = bodyTop;
    document.body.style.left = bodyLeft;
    document.body.style.right = bodyRight;
    document.body.style.width = bodyWidth;
    document.documentElement.style.scrollBehavior = 'auto';
    document.body.style.scrollBehavior = 'auto';
    window.scrollTo({ top: scrollY, left: 0, behavior: 'auto' });
    document.documentElement.style.scrollBehavior = htmlScrollBehavior;
    document.body.style.scrollBehavior = bodyScrollBehavior;
  };
  const initAnimatedCharacters = (rootElement) => {
    if (!rootElement) return null;
    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let isTyping = false;
    let isLookingAtEachOther = false;
    let isAway = false;
    let lookTimer = null;
    let blinkPurpleTimer = null;
    let blinkBlackTimer = null;
    let raf = null;

    const characterStageShell = rootElement.closest('[data-inquiry-characters]');
    const privacyNote = characterStageShell?.querySelector('[data-character-privacy-note]');
    const setPrivacyNoteVisible = (visible) => {
      characterStageShell?.classList.toggle('is-privacy-visible', !!visible);
      privacyNote?.setAttribute('aria-hidden', visible ? 'false' : 'true');
    };
    const getEl = (role, attr, val) => (
      val ? rootElement.querySelector(`[data-character-role="${role}"] [data-character-${attr}="${val}"]`) : rootElement.querySelector(`[data-character-role="${role}"]`)
    );
    const chars = {
      purple: {
        body: getEl('purple'),
        face: getEl('purple', 'face', 'purple'),
        eyeL: getEl('purple', 'eye', 'left'),
        eyeR: getEl('purple', 'eye', 'right'),
        pupilL: getEl('purple', 'pupil', 'left'),
        pupilR: getEl('purple', 'pupil', 'right'),
      },
      black: {
        body: getEl('black'),
        face: getEl('black', 'face', 'black'),
        eyeL: getEl('black', 'eye', 'left'),
        eyeR: getEl('black', 'eye', 'right'),
        pupilL: getEl('black', 'pupil', 'left'),
        pupilR: getEl('black', 'pupil', 'right'),
      },
      orange: {
        body: getEl('orange'),
        face: getEl('orange', 'face', 'orange'),
        eyeL: getEl('orange', 'eye', 'left'),
        eyeR: getEl('orange', 'eye', 'right'),
        pupilL: getEl('orange', 'pupil', 'left'),
        pupilR: getEl('orange', 'pupil', 'right'),
      },
      yellow: {
        body: getEl('yellow'),
        face: getEl('yellow', 'face', 'yellow'),
        pupilL: getEl('yellow', 'pupil', 'left'),
        pupilR: getEl('yellow', 'pupil', 'right'),
        mouth: rootElement.querySelector('[data-character-mouth]'),
      },
    };

    const calculatePosition = (element) => {
      if (!element) return { faceX: 0, faceY: 0, bodySkew: 0 };
      const rect = element.getBoundingClientRect();
      const deltaX = mouseX - (rect.left + rect.width / 2);
      const deltaY = mouseY - (rect.top + rect.height / 3);
      return {
        faceX: Math.max(-15, Math.min(15, deltaX / 20)),
        faceY: Math.max(-10, Math.min(10, deltaY / 30)),
        bodySkew: Math.max(-6, Math.min(6, -deltaX / 120)),
      };
    };
    const getPupilPosition = (element, maxDistance, forceX, forceY) => {
      if (forceX !== undefined && forceY !== undefined) return { x: forceX, y: forceY };
      if (!element) return { x: 0, y: 0 };
      const rect = element.getBoundingClientRect();
      const deltaX = mouseX - (rect.left + rect.width / 2);
      const deltaY = mouseY - (rect.top + rect.height / 2);
      const distance = Math.min(Math.sqrt(deltaX ** 2 + deltaY ** 2), maxDistance);
      const angle = Math.atan2(deltaY, deltaX);
      return { x: Math.cos(angle) * distance, y: Math.sin(angle) * distance };
    };
    const move = (element, x, y) => {
      if (element) element.style.transform = `translate(${x}px, ${y}px)`;
    };
    const orangeFaceBase = chars.orange.body?.classList.contains('boblift-character-body-crane')
      ? { left: 134, top: 132, awayLeft: 118, awayTop: 126, gap: 18 }
      : { left: 82, top: 90, awayLeft: 50, awayTop: 85, gap: 32 };
    if (chars.orange.face) {
      chars.orange.face.style.gap = `${orangeFaceBase.gap}px`;
    }
    const updateAnimations = () => {
      const pPos = calculatePosition(chars.purple.body);
      const bPos = calculatePosition(chars.black.body);
      const oPos = calculatePosition(chars.orange.body);
      const yPos = calculatePosition(chars.yellow.body);

      if (chars.purple.body) {
        chars.purple.body.style.height = (isTyping && !isAway) ? '440px' : '400px';
        chars.purple.body.style.transform = isAway ? 'skewX(0deg)' : (isTyping ? `skewX(${pPos.bodySkew - 12}deg) translateX(40px)` : `skewX(${pPos.bodySkew}deg)`);
        chars.purple.face.style.left = isAway ? '20px' : (isLookingAtEachOther ? '55px' : `${45 + pPos.faceX}px`);
        chars.purple.face.style.top = isAway ? '35px' : (isLookingAtEachOther ? '65px' : `${40 + pPos.faceY}px`);
        const forceX = isAway ? -4 : (isLookingAtEachOther ? 3 : undefined);
        const forceY = isAway ? -4 : (isLookingAtEachOther ? 4 : undefined);
        const left = getPupilPosition(chars.purple.eyeL, 5, forceX, forceY);
        const right = getPupilPosition(chars.purple.eyeR, 5, forceX, forceY);
        move(chars.purple.pupilL, left.x, left.y);
        move(chars.purple.pupilR, right.x, right.y);
      }

      if (chars.black.body) {
        chars.black.body.style.transform = isAway ? 'skewX(0deg)' : (isLookingAtEachOther ? `skewX(${bPos.bodySkew * 1.5 + 10}deg) translateX(20px)` : (isTyping ? `skewX(${bPos.bodySkew * 1.5}deg)` : `skewX(${bPos.bodySkew}deg)`));
        chars.black.face.style.left = isAway ? '10px' : (isLookingAtEachOther ? '32px' : `${26 + bPos.faceX}px`);
        chars.black.face.style.top = isAway ? '28px' : (isLookingAtEachOther ? '12px' : `${32 + bPos.faceY}px`);
        const forceX = isAway ? -4 : (isLookingAtEachOther ? 0 : undefined);
        const forceY = isAway ? -4 : (isLookingAtEachOther ? -4 : undefined);
        const left = getPupilPosition(chars.black.eyeL, 4, forceX, forceY);
        const right = getPupilPosition(chars.black.eyeR, 4, forceX, forceY);
        move(chars.black.pupilL, left.x, left.y);
        move(chars.black.pupilR, right.x, right.y);
      }

      if (chars.orange.body) {
        chars.orange.body.style.transform = isAway ? 'skewX(0deg)' : `skewX(${oPos.bodySkew}deg)`;
        chars.orange.face.style.left = isAway ? `${orangeFaceBase.awayLeft}px` : `${orangeFaceBase.left + oPos.faceX}px`;
        chars.orange.face.style.top = isAway ? `${orangeFaceBase.awayTop}px` : `${orangeFaceBase.top + oPos.faceY}px`;
        const forceX = isAway ? -5 : undefined;
        const forceY = isAway ? -4 : undefined;
        const left = getPupilPosition(chars.orange.eyeL || chars.orange.pupilL, 5, forceX, forceY);
        const right = getPupilPosition(chars.orange.eyeR || chars.orange.pupilR, 5, forceX, forceY);
        move(chars.orange.pupilL, left.x, left.y);
        move(chars.orange.pupilR, right.x, right.y);
      }

      if (chars.yellow.body) {
        chars.yellow.body.style.transform = isAway ? 'skewX(0deg)' : `skewX(${yPos.bodySkew}deg)`;
        chars.yellow.face.style.left = isAway ? '20px' : `${52 + yPos.faceX}px`;
        chars.yellow.face.style.top = isAway ? '35px' : `${40 + yPos.faceY}px`;
        if (chars.yellow.mouth) {
          chars.yellow.mouth.style.left = isAway ? '10px' : `${40 + yPos.faceX}px`;
          chars.yellow.mouth.style.top = isAway ? '88px' : `${88 + yPos.faceY}px`;
        }
        const forceX = isAway ? -5 : undefined;
        const forceY = isAway ? -4 : undefined;
        const left = getPupilPosition(chars.yellow.pupilL, 5, forceX, forceY);
        const right = getPupilPosition(chars.yellow.pupilR, 5, forceX, forceY);
        move(chars.yellow.pupilL, left.x, left.y);
        move(chars.yellow.pupilR, right.x, right.y);
      }
    };
    const scheduleUpdate = () => {
      if (raf) return;
      raf = window.requestAnimationFrame(() => {
        raf = null;
        updateAnimations();
      });
    };
    const blink = (eyeL, eyeR, pupilL, pupilR) => {
      if (!eyeL || !eyeR || !pupilL || !pupilR) return;
      eyeL.style.height = '2px';
      eyeR.style.height = '2px';
      pupilL.style.opacity = '0';
      pupilR.style.opacity = '0';
      window.setTimeout(() => {
        eyeL.style.height = '';
        eyeR.style.height = '';
        pupilL.style.opacity = '1';
        pupilR.style.opacity = '1';
      }, 150);
    };
    const schedulePurpleBlink = () => {
      blinkPurpleTimer = window.setTimeout(() => {
        blink(chars.purple.eyeL, chars.purple.eyeR, chars.purple.pupilL, chars.purple.pupilR);
        schedulePurpleBlink();
      }, Math.random() * 4000 + 3000);
    };
    const scheduleBlackBlink = () => {
      blinkBlackTimer = window.setTimeout(() => {
        blink(chars.black.eyeL, chars.black.eyeR, chars.black.pupilL, chars.black.pupilR);
        scheduleBlackBlink();
      }, Math.random() * 4000 + 3000);
    };
    const onMouseMove = (event) => {
      mouseX = event.clientX;
      mouseY = event.clientY;
      scheduleUpdate();
    };
    window.addEventListener('mousemove', onMouseMove, { passive: true });
    schedulePurpleBlink();
    scheduleBlackBlink();
    scheduleUpdate();

    return {
      setTyping: (status) => {
        isTyping = !!status;
        isAway = false;
        setPrivacyNoteVisible(false);
        if (isTyping) {
          isLookingAtEachOther = true;
          window.clearTimeout(lookTimer);
          lookTimer = window.setTimeout(() => {
            isLookingAtEachOther = false;
            updateAnimations();
          }, 800);
        } else {
          isLookingAtEachOther = false;
        }
        updateAnimations();
      },
      setAway: (status) => {
        isAway = !!status;
        setPrivacyNoteVisible(isAway);
        if (isAway) {
          isTyping = false;
          isLookingAtEachOther = false;
        }
        updateAnimations();
      },
      reset: () => {
        rootElement.classList.remove('is-submitting');
        setPrivacyNoteVisible(false);
        isTyping = false;
        isLookingAtEachOther = false;
        isAway = false;
        window.clearTimeout(lookTimer);
        updateAnimations();
      },
      submitting: () => {
        rootElement.classList.add('is-submitting');
      },
      success: () => {
        rootElement.classList.remove('is-submitting');
        setPrivacyNoteVisible(false);
        isTyping = false;
        isLookingAtEachOther = false;
        isAway = false;
        updateAnimations();
      },
      destroy: () => {
        window.removeEventListener('mousemove', onMouseMove);
        window.clearTimeout(lookTimer);
        window.clearTimeout(blinkPurpleTimer);
        window.clearTimeout(blinkBlackTimer);
        if (raf) window.cancelAnimationFrame(raf);
      },
    };
  };
  const initInquiryCharacters = () => {
    const root = qs('[data-character-stage]', inquiryModal || document);
    if (!root || inquiryCharacterApi) return inquiryCharacterApi;
    const api = initAnimatedCharacters(root);
    const resetAfterBlur = () => {
      window.setTimeout(() => {
        const active = document.activeElement;
        if (!active?.matches?.('[data-quote-field]')) api?.reset();
      }, 0);
    };
    qsa('[data-quote-field]', inquiryModal || document).forEach((field) => {
      const update = (event) => {
        const fieldName = event.currentTarget?.dataset?.quoteField || '';
        if (fieldName === 'name') {
          api?.setTyping(true);
          return;
        }
        if (fieldName === 'email' || fieldName === 'phone') {
          api?.setAway(true);
          return;
        }
        if (['company', 'website', 'country', 'message'].includes(fieldName)) api?.reset();
      };
      field.addEventListener('focus', update);
      field.addEventListener('input', update);
      field.addEventListener('change', update);
      field.addEventListener('blur', resetAfterBlur);
    });
    inquiryCharacterApi = api;
    return inquiryCharacterApi;
  };
  const countryNameFromCode = (code) => {
    const countryCode = String(code || '').trim().toUpperCase();
    if (!/^[A-Z]{2}$/.test(countryCode)) return '';
    try {
      const language = document.documentElement.lang || navigator.language || 'en';
      const displayNames = new Intl.DisplayNames([language], { type: 'region' });
      return displayNames.of(countryCode) || countryCode;
    } catch {
      return countryCode;
    }
  };
  const initCountryAutofill = (form) => {
    if (!form || form.dataset.countryAutofillInitialized === 'true') return;
    const phoneField = qs('[data-quote-field="phone"]', form);
    const countryField = qs('[data-quote-field="country"]', form);
    const endpoint = form.dataset.countryDetectUrl || '';
    if (!phoneField || !countryField || !endpoint) return;
    form.dataset.countryAutofillInitialized = 'true';
    let pending = false;
    let lastRequestedPhone = '';
    const requestCountry = async () => {
      const phoneValue = String(phoneField.value || '').trim();
      if (pending || phoneValue.length < 4 || phoneValue === lastRequestedPhone || String(countryField.value || '').trim()) return;
      pending = true;
      lastRequestedPhone = phoneValue;
      countryField.dataset.countryAutofill = 'loading';
      try {
        const response = await fetch(endpoint, {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });
        const data = await response.json();
        const country = countryNameFromCode(data?.data?.countryCode || data?.countryCode || '');
        if (country && !String(countryField.value || '').trim()) {
          countryField.value = country;
          countryField.dispatchEvent(new Event('change', { bubbles: true }));
        }
      } catch {
      } finally {
        pending = false;
        delete countryField.dataset.countryAutofill;
      }
    };
    phoneField.addEventListener('blur', requestCountry);
    phoneField.addEventListener('change', requestCountry);
  };
  let inquiryCustomSelectEventsReady = false;
  const syncInquiryCustomSelect = (root) => {
    if (!root) return;
    const input = qs('[data-inquiry-select-input]', root);
    const label = qs('[data-inquiry-select-label]', root);
    const toggle = qs('[data-inquiry-select-toggle]', root);
    const value = input?.value || '';
    const option = qsa('[data-inquiry-select-option]', root).find((item) => (item.dataset.value || '') === value);
    const optionLabel = option ? qs('[data-inquiry-select-option-label]', option) : null;
    if (label) label.textContent = optionLabel?.textContent?.trim() || input?.dataset.placeholder || 'Select one';
    toggle?.classList.toggle('text-slate-500', value === '');
    qsa('[data-inquiry-select-option]', root).forEach((item) => {
      const selected = (item.dataset.value || '') === value;
      item.classList.toggle('bg-blue-50', selected);
      item.classList.toggle('text-blue-700', selected);
      item.classList.toggle('text-slate-700', !selected && item.dataset.value !== '');
      const check = qs('[data-inquiry-select-check]', item);
      check?.classList.toggle('hidden', !selected);
    });
  };
  const setInquiryCustomSelectOpen = (root, open) => {
    const toggle = qs('[data-inquiry-select-toggle]', root);
    const menu = qs('[data-inquiry-select-menu]', root);
    if (!toggle || !menu) return;
    const isOpen = !!open;
    menu.classList.toggle('hidden', !isOpen);
    root.classList.toggle('relative', isOpen);
    root.classList.toggle('z-50', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.classList.toggle('border-blue-300', isOpen);
    toggle.classList.toggle('ring-4', isOpen);
    toggle.classList.toggle('ring-blue-100', isOpen);
  };
  const closeInquiryCustomSelects = (scope = document, exceptRoot = null) => {
    qsa('[data-inquiry-select]', scope).forEach((root) => {
      if (root !== exceptRoot) setInquiryCustomSelectOpen(root, false);
    });
  };
  const syncInquiryCustomSelects = (scope = document) => {
    qsa('[data-inquiry-select]', scope).forEach(syncInquiryCustomSelect);
  };
  const initInquiryCustomSelects = (form) => {
    if (!form) return;
    qsa('[data-inquiry-select]', form).forEach((root) => {
      if (root.dataset.inquirySelectInitialized === 'true') {
        syncInquiryCustomSelect(root);
        return;
      }
      const input = qs('[data-inquiry-select-input]', root);
      const toggle = qs('[data-inquiry-select-toggle]', root);
      const menu = qs('[data-inquiry-select-menu]', root);
      if (!input || !toggle || !menu) return;
      root.dataset.inquirySelectInitialized = 'true';
      syncInquiryCustomSelect(root);
      toggle.addEventListener('click', () => {
        const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';
        closeInquiryCustomSelects(form, root);
        setInquiryCustomSelectOpen(root, shouldOpen);
      });
      qsa('[data-inquiry-select-option]', root).forEach((option) => {
        option.addEventListener('click', () => {
          input.value = option.dataset.value || '';
          syncInquiryCustomSelect(root);
          setInquiryCustomSelectOpen(root, false);
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });
      input.addEventListener('change', () => syncInquiryCustomSelect(root));
    });
    if (form.dataset.inquirySelectResetInitialized !== 'true') {
      form.dataset.inquirySelectResetInitialized = 'true';
      form.addEventListener('reset', () => window.requestAnimationFrame(() => syncInquiryCustomSelects(form)));
    }
    if (!inquiryCustomSelectEventsReady) {
      inquiryCustomSelectEventsReady = true;
      document.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-inquiry-select]')) return;
        closeInquiryCustomSelects(document);
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeInquiryCustomSelects(document);
      });
    }
  };

  const setInquiryExtraOpen = (form, open, options = {}) => {
    const toggle = qs('[data-inquiry-extra-toggle]', form);
    const panel = qs('[data-inquiry-extra-panel]', form);
    const label = qs('[data-inquiry-extra-toggle-label]', toggle || document);
    if (!toggle || !panel) return;
    const isOpen = !!open;
    const modal = form.closest('[data-global-inquiry-modal]');
    panel.classList.toggle('hidden', !isOpen);
    form.classList.toggle('is-inquiry-extra-open', isOpen);
    modal?.classList.toggle('is-inquiry-extra-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    if (label) label.textContent = isOpen ? (toggle.dataset.labelClose || 'Hide additional details') : (toggle.dataset.labelOpen || 'Add details for an accurate solution');
    if (isOpen && options.scroll) {
      window.requestAnimationFrame(() => {
        const scrollRoot = qs(':scope > .mt-5.overflow-y-auto', form);
        const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        if (!scrollRoot) {
          panel.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
          return;
        }
        const rootRect = scrollRoot.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();
        scrollRoot.scrollTo({
          top: scrollRoot.scrollTop + panelRect.top - rootRect.top - 12,
          behavior: prefersReducedMotion ? 'auto' : 'smooth',
        });
      });
    }
  };
  const initInquiryExtraFields = (form) => {
    if (!form || form.dataset.inquiryExtraInitialized === 'true') return;
    const toggle = qs('[data-inquiry-extra-toggle]', form);
    const panel = qs('[data-inquiry-extra-panel]', form);
    if (!toggle || !panel) return;
    initInquiryCustomSelects(form);
    const truckField = qs('[data-quote-field="truck_available"]', panel);
    const truckFollowFields = ['truck_model', 'truck_specs'].map((name) => qs(`[data-quote-field="${name}"]`, panel)).filter(Boolean);
    const updateTruckFollowFields = () => {
      const showTruckFields = truckField?.value === 'Yes';
      truckFollowFields.forEach((field) => {
        const wrapper = field.closest('label') || field.parentElement;
        wrapper?.classList.toggle('hidden', !showTruckFields);
        field.disabled = !showTruckFields;
        if (!showTruckFields) field.value = '';
      });
    };
    form.dataset.inquiryExtraInitialized = 'true';
    setInquiryExtraOpen(form, false);
    updateTruckFollowFields();
    toggle.addEventListener('click', () => {
      const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';
      setInquiryExtraOpen(form, shouldOpen, { scroll: shouldOpen });
    });
    truckField?.addEventListener('change', updateTruckFollowFields);
  };
  qsa('[data-quote-form]').forEach((form) => {
    initCountryAutofill(form);
    initInquiryExtraFields(form);
  });
  const inquiryModalStatus = (message = '', tone = 'info') => {
    if (!inquiryModal) return;
    const status = qs('[data-inquiry-modal-status]', inquiryModal);
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('hidden', !message);
    status.classList.toggle('border-red-100', tone === 'error');
    status.classList.toggle('bg-red-50', tone === 'error');
    status.classList.toggle('text-red-800', tone === 'error');
    status.classList.toggle('border-emerald-100', tone === 'success');
    status.classList.toggle('bg-emerald-50', tone === 'success');
    status.classList.toggle('text-emerald-800', tone === 'success');
  };
  const updateInquiryModalProductSlug = () => {
    if (!inquiryModal) return;
    const productField = qs('[data-quote-field="product"]', inquiryModal);
    const productSlugField = qs('[data-product-slug-field]', inquiryModal);
    if (!productField || !productSlugField) return;
    productSlugField.value = productField.selectedOptions?.[0]?.dataset.productSlug || productSlugField.value || '';
  };
  const setInquiryModalProduct = (product = '', slug = '') => {
    if (!inquiryModal) return;
    const productField = qs('[data-quote-field="product"]', inquiryModal);
    const productSlugField = qs('[data-product-slug-field]', inquiryModal);
    if (!productField) return;
    if (productField.tagName === 'SELECT' && product) {
      let option = [...productField.options].find((item) => item.value === product);
      if (!option) {
        option = document.createElement('option');
        option.value = product;
        option.textContent = product;
        productField.appendChild(option);
      }
      option.dataset.productSlug = slug || option.dataset.productSlug || '';
    }
    productField.value = product;
    if (productSlugField) productSlugField.value = slug || '';
    updateInquiryModalProductSlug();
  };
  const normalizeInquiryProduct = (item) => {
    if (!item || typeof item !== 'object') return null;
    const title = typeof item.title === 'string' ? item.title.trim() : '';
    if (!title) return null;
    return {
      id: typeof item.id === 'string' ? item.id : '',
      title,
      slug: typeof item.slug === 'string' ? item.slug : '',
      image: typeof item.image === 'string' ? item.image : '',
      category: typeof item.category === 'string' ? item.category : 'BOB-LIFT',
      detailUrl: typeof item.detailUrl === 'string' ? item.detailUrl : '',
      badge: typeof item.badge === 'string' ? item.badge : '',
    };
  };
  const cardInquiryProduct = (card) => normalizeInquiryProduct({
    id: card?.dataset?.productId || '',
    title: card?.dataset?.title || '',
    slug: card?.dataset?.productSlug || '',
    image: card?.dataset?.image || '',
    category: card?.dataset?.categoryTitle || card?.dataset?.category || 'BOB-LIFT',
    detailUrl: card?.dataset?.detailUrl || '',
  });
  const inquiryProductsFromTrigger = (trigger, product = '', slug = '') => {
    const datasetProducts = trigger?.dataset?.inquiryProducts || '';
    if (datasetProducts) {
      try {
        const parsed = JSON.parse(datasetProducts);
        if (Array.isArray(parsed)) return parsed.map(normalizeInquiryProduct).filter(Boolean);
      } catch {}
    }
    const cardItem = cardInquiryProduct(trigger?.closest?.('.product-card'));
    if (cardItem) return [cardItem];
    const singleItem = normalizeInquiryProduct({ title: product, slug });
    return singleItem ? [singleItem] : [];
  };
  const currentProductPageInquiryProducts = () => {
    const productHero = qs('#product-hero');
    if (!productHero) return [];
    const quoteAddButton = qs('[data-product-quote-add][data-title]', productHero);
    const quoteAddItem = cardInquiryProduct(quoteAddButton);
    if (quoteAddItem) return [quoteAddItem];
    const productTrigger = qs('[data-inquiry-modal-open][data-inquiry-product]', productHero);
    if (!productTrigger) return [];
    return inquiryProductsFromTrigger(productTrigger, productTrigger.dataset.inquiryProduct || '', productTrigger.dataset.inquiryProductSlug || '');
  };
  const escapeInquiryText = (value) => {
    const span = document.createElement('span');
    span.textContent = String(value || '');
    return span.innerHTML;
  };
  const syncInquirySelectedProductFields = () => {
    if (!inquiryModal) return;
    const productValue = inquiryModalSelectedProducts.map((item) => item.title).filter(Boolean).join('; ');
    const slugValue = inquiryModalSelectedProducts.map((item) => item.slug).filter(Boolean).join(',');
    setInquiryModalProduct(productValue, slugValue);
    const quantityField = qs('[data-quote-field="quantity"]', inquiryModal);
    if (quantityField) quantityField.value = inquiryModalSelectedProducts.length ? String(inquiryModalSelectedProducts.length) : '';
  };
  const updateInquirySelectedProducts = (products) => {
    if (!inquiryModal) return;
    inquiryModalSelectedProducts = Array.isArray(products) ? products : [];
    syncInquirySelectedProductFields();
    const section = qs('[data-inquiry-selected-products]', inquiryModal);
    const listRoot = qs('[data-inquiry-selected-products-list]', inquiryModal);
    const count = qs('[data-inquiry-selected-products-count]', inquiryModal);
    if (!section || !listRoot) return;
    inquiryModal.classList.toggle('has-selected-products', inquiryModalSelectedProducts.length > 0);
    section.classList.toggle('hidden', !inquiryModalSelectedProducts.length);
    section.classList.toggle('flex', inquiryModalSelectedProducts.length > 0);
    if (count) count.textContent = String(inquiryModalSelectedProducts.length);
    listRoot.replaceChildren();
    const removeLabelTemplate = quoteFormText('selectedProductRemoveLabelTemplate', quoteListText('removeLabelTemplate', 'Remove {product} from Quote List'));
    inquiryModalSelectedProducts.forEach((item, index) => {
      const row = document.createElement('article');
      // The remove button has a column of its own, so a long product name wraps beside it instead of running under it.
      row.className = 'group relative grid min-h-[68px] shrink-0 grid-cols-[46px_minmax(0,1fr)_36px] items-center gap-3 rounded border border-line bg-white p-2.5 shadow-card transition hover:border-blue-200 hover:bg-white';
      row.innerHTML = `
        <div class="h-[46px] w-[46px] overflow-hidden rounded-sm bg-[#eef2f7]">${item.image ? `<img class="h-full w-full object-contain p-1" src="${escapeInquiryText(item.image)}" alt="${escapeInquiryText(item.title)}" loading="lazy" decoding="async">` : ''}</div>
        <div class="min-w-0 self-center">
          ${item.badge ? `<span class="mb-1 inline-flex items-center rounded-sm border border-red-600 bg-red-50 px-1.5 py-0.5 text-[10px] font-black leading-none text-red-700">${escapeInquiryText(item.badge)}</span>` : ''}
          <h3 class="line-clamp-2 text-xs font-black leading-4 text-ink">${escapeInquiryText(item.title)}</h3>
        </div>
        <button class="grid h-9 w-9 place-items-center rounded-sm border border-line bg-white text-slate-500 opacity-100 transition hover:border-red-100 hover:bg-red-50 hover:text-red-600 focus:opacity-100 focus:outline-none focus:ring-4 focus:ring-red-100 md:opacity-0 md:group-hover:opacity-100" type="button" data-inquiry-product-remove="${index}" aria-label="${escapeInquiryText(removeLabelTemplate.replace('{product}', item.title || ''))}">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      `;
      listRoot.appendChild(row);
    });
    listRoot.scrollTop = 0;
  };
  let turnstileLoadPromise = null;
  const loadInquiryModalMedia = () => {
    if (!inquiryModal) return;
    qsa('img[data-inquiry-lazy-src]', inquiryModal).forEach((image) => {
      if (!image.getAttribute('src')) image.setAttribute('src', image.dataset.inquiryLazySrc || '');
    });
  };
  const ensureTurnstileScript = () => {
    if (window.turnstile?.render) return Promise.resolve(window.turnstile);
    if (turnstileLoadPromise) return turnstileLoadPromise;
    turnstileLoadPromise = new Promise((resolve, reject) => {
      const done = () => {
        if (window.turnstile?.render) {
          resolve(window.turnstile);
          return;
        }
        window.setTimeout(() => {
          if (window.turnstile?.render) resolve(window.turnstile);
          else reject(new Error('Verification could not be loaded. Please try again.'));
        }, 120);
      };
      const existing = document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');
      if (existing) {
        existing.addEventListener('load', done, { once: true });
        existing.addEventListener('error', () => reject(new Error('Verification could not be loaded. Please try again.')), { once: true });
        done();
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
      script.async = true;
      script.defer = true;
      script.addEventListener('load', done, { once: true });
      script.addEventListener('error', () => reject(new Error('Verification could not be loaded. Please try again.')), { once: true });
      document.head.appendChild(script);
    });
    return turnstileLoadPromise;
  };
  const renderLazyTurnstile = async (scope = document) => {
    const widgets = qsa('[data-turnstile-widget]', scope).filter((widget) => !widget.dataset.turnstileWidgetId);
    if (!widgets.length) return;
    const turnstile = await ensureTurnstileScript();
    widgets.forEach((widget) => {
      if (widget.dataset.turnstileWidgetId) return;
      const id = turnstile.render(widget, {
        sitekey: widget.dataset.sitekey || '',
        theme: widget.dataset.theme || 'light',
      });
      widget.dataset.turnstileWidgetId = String(id);
    });
  };
  const resetTurnstile = (scope = document) => {
    if (!window.turnstile?.reset) return;
    const lazyWidgets = qsa('[data-turnstile-widget][data-turnstile-widget-id]', scope);
    if (lazyWidgets.length) {
      lazyWidgets.forEach((widget) => window.turnstile.reset(widget.dataset.turnstileWidgetId));
      return;
    }
    if (qs('.cf-turnstile', scope)) window.turnstile.reset();
  };
  const openInquiryModal = (trigger = null) => {
    if (!inquiryModal) return;
    inquiryModalLastFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
    const form = qs('[data-inquiry-modal-form]', inquiryModal);
    const success = qs('[data-inquiry-modal-success]', inquiryModal);
    const shouldResetForm = !!form && (form.dataset.initialized !== 'true' || form.dataset.submitted === 'true');
    if (form) {
      if (shouldResetForm) {
        form.reset();
        syncInquiryCustomSelects(form);
        inquiryModalSelectedProducts = [];
      }
      syncInquiryCustomSelects(form);
      setInquiryExtraOpen(form, false);
      form.dataset.initialized = 'true';
      form.dataset.submitted = 'false';
    }
    success?.classList.add('hidden');
    inquiryModalStatus('');
    loadInquiryModalMedia();
    const characters = initInquiryCharacters();
    characters?.reset();
    const sourceField = form && qs('[data-inquiry-source-field]', form);
    if (sourceField) {
      const label = trigger instanceof HTMLElement ? (trigger.dataset.inquirySource || trigger.getAttribute('aria-label') || trigger.innerText || '') : '';
      sourceField.value = label.replace(/\s+/g, ' ').trim().slice(0, 120) || '询价弹窗';
    }
    let product = trigger?.dataset?.inquiryProduct || '';
    let slug = trigger?.dataset?.inquiryProductSlug || '';
    let selectedProducts = inquiryProductsFromTrigger(trigger, product, slug);
    const hasDirectTriggerContext = !!(product || slug || trigger?.dataset?.inquiryProducts || trigger?.dataset?.inquiryQuantity);
    if (!hasDirectTriggerContext && !selectedProducts.length) {
      selectedProducts = currentProductPageInquiryProducts();
      product = selectedProducts.length ? selectedProducts.map((item) => item.title).join('; ') : '';
      slug = selectedProducts.length ? selectedProducts.map((item) => item.slug).filter(Boolean).join(',') : '';
    }
    const productValue = selectedProducts.length ? selectedProducts.map((item) => item.title).join('; ') : product;
    const slugValue = selectedProducts.length ? selectedProducts.map((item) => item.slug).filter(Boolean).join(',') : slug;
    const quantity = trigger?.dataset?.inquiryQuantity || (selectedProducts.length ? String(selectedProducts.length) : '');
    const clearQuoteList = trigger?.dataset?.clearQuoteList === 'true';
    const hasTriggerContext = !!(productValue || slugValue || quantity || trigger?.dataset?.inquiryProducts || clearQuoteList);
    if (shouldResetForm || hasTriggerContext) {
      setInquiryModalProduct(productValue, slugValue);
      updateInquirySelectedProducts(selectedProducts);
    } else {
      updateInquirySelectedProducts([]);
    }
    if (form) {
      if (shouldResetForm || hasTriggerContext) form.dataset.clearQuoteListOnSuccess = clearQuoteList ? 'true' : 'false';
      else form.dataset.clearQuoteListOnSuccess ||= 'false';
      const quantityField = qs('[data-quote-field="quantity"]', form);
      if (quantityField && (shouldResetForm || hasTriggerContext)) quantityField.value = quantity;
    }
    inquiryModal.classList.remove('hidden');
    inquiryModal.classList.add('flex');
    inquiryModal.setAttribute('aria-hidden', 'false');
    lockInquiryPageScroll();
    renderLazyTurnstile(form || inquiryModal).then(() => {
      resetTurnstile(form || inquiryModal);
    }).catch((error) => {
      inquiryModalStatus(error.message || quoteFormMessage('verificationLoadFailed', 'Verification could not be loaded. Please try again.'), 'error');
    });
    window.setTimeout(() => qs('[role="dialog"]', inquiryModal)?.focus(), 0);
  };
  const closeInquiryModal = () => {
    if (!inquiryModal) return;
    inquiryModal.classList.add('hidden');
    inquiryModal.classList.remove('flex');
    initInquiryCharacters()?.reset();
    inquiryModal.setAttribute('aria-hidden', 'true');
    unlockInquiryPageScroll();
    if (inquiryModalLastFocus instanceof HTMLElement) inquiryModalLastFocus.focus({ preventScroll: true });
  };
  const validateInquiryModalForm = () => {
    const form = qs('[data-inquiry-modal-form]', inquiryModal || document);
    if (!form) return true;
    const invalid = qsa('input, select, textarea', form).find((field) => !field.checkValidity());
    if (invalid) {
      window.setTimeout(() => invalid.reportValidity(), 0);
      return false;
    }
    return true;
  };
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-inquiry-modal-open]');
    if (!trigger || !inquiryModal) return;
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    qsa('[data-quick-view-modal].flex').forEach((modal) => {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    });
    openInquiryModal(trigger);
  });
  qsa('[data-inquiry-modal-close]').forEach((button) => button.addEventListener('click', closeInquiryModal));
  inquiryModal?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-inquiry-product-remove]');
    if (!button) return;
    event.preventDefault();
    const index = Number(button.dataset.inquiryProductRemove || -1);
    if (!Number.isInteger(index) || index < 0) return;
    const removedProduct = inquiryModalSelectedProducts[index] || null;
    updateInquirySelectedProducts(inquiryModalSelectedProducts.filter((item, itemIndex) => itemIndex !== index));
    window.bobliftQuoteList?.removeProduct?.(removedProduct);
  });
  inquiryModal?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeInquiryModal();
  });
  qs('[data-inquiry-modal-form]', inquiryModal || document)?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (form.dataset.submitting === 'true') return;
    if (!validateInquiryModalForm()) return;
    const pendingTurnstile = qsa('[data-turnstile-widget]', form).filter((widget) => !widget.dataset.turnstileWidgetId);
    if (pendingTurnstile.length) {
      inquiryModalStatus(quoteFormMessage('verificationLoading', 'Loading verification...'), 'info');
      try {
        await renderLazyTurnstile(form);
      } catch (error) {
        inquiryModalStatus(error.message || quoteFormMessage('verificationLoadFailed', 'Verification could not be loaded. Please try again.'), 'error');
        return;
      }
      inquiryModalStatus(quoteFormMessage('verificationSubmitAgain', 'Please complete the verification and submit again.'), 'error');
      return;
    }
    updateInquiryModalProductSlug();
    const submit = qs('[data-inquiry-modal-submit]', form);
    form.dataset.submitting = 'true';
    submit?.setAttribute('disabled', 'disabled');
    inquiryModalStatus(quoteFormMessage('saving', 'Sending your inquiry...'), 'info');
    initInquiryCharacters()?.submitting();
    try {
      const endpoint = form.getAttribute('action') || '/?boblift_inquiry_submit=1';
      const response = await fetch(endpoint, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const responseText = await response.text();
      let data = null;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        const title = responseText.match(/<title[^>]*>([\s\S]*?)<\/title>/i)?.[1]?.replace(/&#8211;/g, '-').replace(/\s+/g, ' ').trim();
        throw new Error(title ? `The server returned ${title}. Please refresh and try again.` : quoteFormMessage('serverInvalid', 'The server returned an invalid response. Please refresh and try again.'));
      }
      if (!response.ok || !data.success) throw new Error(data?.data?.message || quoteFormMessage('saveFailed', 'The inquiry could not be sent.'));
      qs('[data-inquiry-modal-success]', inquiryModal)?.classList.remove('hidden');
      inquiryModalStatus(data?.data?.message || quoteFormText('successInlineText', 'Inquiry sent. Our team will contact you soon.'), 'success');
      initInquiryCharacters()?.success();
      form.dataset.submitted = 'true';
      if (form.dataset.clearQuoteListOnSuccess === 'true') window.bobliftQuoteList?.clear();
      resetTurnstile(form);
      window.location.href = form.dataset.successUrl || data?.data?.redirect || '/success.html';
    } catch (error) {
      form.dataset.submitting = 'false';
      inquiryModalStatus(error.message || quoteFormMessage('saveFailed', 'The inquiry could not be sent. Please try again.'), 'error');
      submit?.removeAttribute('disabled');
      resetTurnstile(form);
    }
  });


  const catalogModal = qs('[data-catalog-modal]');
  let catalogModalLastFocus = null;
  let catalogCloseTimer = null;
  const catalogText = (key, fallback) => {
    const value = i18n?.productDetail?.catalogLead?.[key];
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
  };
  const catalogModalStatus = (message = '', tone = 'info') => {
    const status = qs('[data-catalog-status]', catalogModal || document);
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('hidden', !message);
    status.classList.toggle('border-red-100', tone === 'error');
    status.classList.toggle('bg-red-50', tone === 'error');
    status.classList.toggle('text-red-800', tone === 'error');
    status.classList.toggle('border-emerald-100', tone === 'success');
    status.classList.toggle('bg-emerald-50', tone === 'success');
    status.classList.toggle('text-emerald-800', tone === 'success');
  };
  const openCatalogModal = (trigger = null) => {
    if (!catalogModal) return;
    if (catalogCloseTimer) window.clearTimeout(catalogCloseTimer);
    catalogCloseTimer = null;
    catalogModalLastFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
    const form = qs('[data-catalog-form]', catalogModal);
    if (form) {
      form.reset();
      form.dataset.submitting = 'false';
      qs('[data-catalog-product-field]', form).value = trigger?.dataset?.catalogProduct || '';
      qs('[data-catalog-product-slug-field]', form).value = trigger?.dataset?.catalogProductSlug || '';
      const catalogUrl = trigger?.dataset?.catalogUrl || '';
      qs('[data-catalog-url-field]', form).value = catalogUrl;
      const description = qs('[data-catalog-description]', catalogModal);
      const directText = catalogModal.dataset.catalogDirectText || '';
      if (description) description.textContent = catalogUrl ? (directText || catalogText('description', 'Enter your email to receive the PDF catalog. The download link will appear immediately after submission.')) : catalogText('noFileDescription', 'Enter your email and our team will send the correct PDF catalog to you shortly.');
      qs('[data-catalog-submit]', form)?.removeAttribute('disabled');
    }
    qs('[data-catalog-success]', catalogModal)?.classList.add('hidden');
    const downloadLink = qs('[data-catalog-download-link]', catalogModal);
    if (downloadLink) {
      downloadLink.classList.add('hidden');
      downloadLink.removeAttribute('href');
    }
    // A known PDF is offered as a direct download too; the email form stays available below it.
    const directLink = qs('[data-catalog-direct-link]', catalogModal);
    if (directLink) {
      const directUrl = trigger?.dataset?.catalogUrl || '';
      directLink.classList.toggle('hidden', !directUrl);
      directLink.classList.toggle('inline-flex', !!directUrl);
      if (directUrl) directLink.setAttribute('href', directUrl);
      else directLink.removeAttribute('href');
    }
    catalogModalStatus('');
    catalogModal.classList.remove('hidden');
    catalogModal.classList.add('flex');
    catalogModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.setTimeout(() => qs('[role="dialog"]', catalogModal)?.focus(), 0);
  };
  const closeCatalogModal = () => {
    if (!catalogModal) return;
    if (catalogCloseTimer) window.clearTimeout(catalogCloseTimer);
    catalogCloseTimer = null;
    catalogModal.classList.add('hidden');
    catalogModal.classList.remove('flex');
    catalogModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (catalogModalLastFocus instanceof HTMLElement) catalogModalLastFocus.focus({ preventScroll: true });
  };
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-catalog-modal-open]');
    if (!trigger || !catalogModal) return;
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    openCatalogModal(trigger);
  });
  qsa('[data-catalog-modal-close]').forEach((button) => button.addEventListener('click', closeCatalogModal));
  // Product archive: show the brochure for the selected category family (all of them when no category is selected).
  const brochureBlock = qs('[data-brochures][data-brochure-map]');
  const brochureRoot = qs('[data-product-listing]');
  if (brochureBlock && brochureRoot) {
    let brochureMap = {};
    try { brochureMap = JSON.parse(brochureBlock.dataset.brochureMap || '{}'); } catch (error) { brochureMap = {}; }
    const syncBrochures = () => {
      const category = brochureRoot.dataset.currentCategory || '';
      const family = category ? (brochureMap[category] || '') : '';
      brochureBlock.classList.toggle('hidden', category !== '' && family === '');
      brochureBlock.toggleAttribute('data-brochure-single', family !== '');
      qsa('[data-brochure-card]', brochureBlock).forEach((card) => {
        const show = family === '' || card.dataset.brochureFamily === family;
        card.classList.toggle('hidden', !show);
        card.classList.toggle('flex', show);
      });
    };
    new MutationObserver(syncBrochures).observe(brochureRoot, { attributes: true, attributeFilter: ['data-current-category'] });
    syncBrochures();
  }
  catalogModal?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeCatalogModal();
  });
  qs('[data-catalog-form]', catalogModal || document)?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (form.dataset.submitting === 'true') return;
    if (!form.checkValidity()) {
      window.setTimeout(() => form.reportValidity(), 0);
      return;
    }
    const submit = qs('[data-catalog-submit]', form);
    form.dataset.submitting = 'true';
    submit?.setAttribute('disabled', 'disabled');
    catalogModalStatus(catalogText('saving', 'Saving your catalog request...'), 'info');
    try {
      const response = await fetch(form.getAttribute('action') || '/?boblift_catalog_request=1', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const responseText = await response.text();
      let data = null;
      try {
        data = JSON.parse(responseText);
      } catch {
        throw new Error(catalogText('saveFailed', 'The catalog request could not be saved. Please try again.'));
      }
      if (!response.ok || !data.success) throw new Error(data?.data?.message || catalogText('saveFailed', 'The catalog request could not be saved. Please try again.'));
      const downloadUrl = data?.data?.downloadUrl || '';
      const success = qs('[data-catalog-success]', catalogModal);
      const downloadLink = qs('[data-catalog-download-link]', catalogModal);
      const message = data?.data?.message || catalogText('successText', 'Catalog request saved. We also sent the catalog link to your email.');
      form.dataset.submitting = 'false';
      submit?.removeAttribute('disabled');
      if (downloadUrl && downloadLink) {
        downloadLink.href = downloadUrl;
        downloadLink.classList.remove('hidden');
        const successText = qs('[data-catalog-success-text]', catalogModal);
        if (successText) successText.textContent = message;
        success?.classList.remove('hidden');
        catalogModalStatus('', 'success');
        return;
      }
      success?.classList.add('hidden');
      catalogModalStatus(message, 'success');
      catalogCloseTimer = window.setTimeout(closeCatalogModal, 1200);
    } catch (error) {
      form.dataset.submitting = 'false';
      catalogModalStatus(error.message || catalogText('saveFailed', 'The catalog request could not be saved. Please try again.'), 'error');
      submit?.removeAttribute('disabled');
    }
  });

  const initQuoteList = () => {
    const storageKey = 'boblift-product-favorites';
    const root = qs('[data-quote-list-root]');
    const drawer = qs('[data-quote-list-drawer]');
    const panel = qs('[data-quote-list-panel]', drawer || document);
    const count = qs('[data-quote-list-count]', root || document);
    const panelCount = qs('[data-quote-list-panel-count]', drawer || document);
    const itemsRoot = qs('[data-quote-list-items]', drawer || document);
    const empty = qs('[data-quote-list-empty]', drawer || document);
    const requestButton = qs('[data-quote-list-request]', drawer || document);
    const clearButton = qs('[data-quote-list-clear]', drawer || document);
    let lastFocus = null;
    let items = new Map();

    const escapeText = (value) => {
      const span = document.createElement('span');
      span.textContent = String(value || '');
      return span.innerHTML;
    };
    const normalizeItem = (item) => {
      if (!item || typeof item !== 'object' || typeof item.id !== 'string' || !item.id) return null;
      return {
        id: item.id,
        slug: typeof item.slug === 'string' ? item.slug : '',
        title: typeof item.title === 'string' && item.title ? item.title : 'Selected product',
        image: typeof item.image === 'string' ? item.image : '',
        category: typeof item.category === 'string' ? item.category : 'BOB-LIFT',
        detailUrl: typeof item.detailUrl === 'string' ? item.detailUrl : '',
        summary: typeof item.summary === 'string' ? item.summary : '',
      };
    };
    const itemFromElement = (element) => {
      if (!element?.dataset?.productId) return null;
      return normalizeItem({
        id: element.dataset.productId,
        slug: element.dataset.productSlug || '',
        title: element.dataset.title || '',
        image: element.dataset.image || '',
        category: element.dataset.categoryTitle || element.dataset.category || 'BOB-LIFT',
        detailUrl: element.dataset.detailUrl || '',
        summary: element.dataset.summary || '',
      });
    };
    const itemFromCard = itemFromElement;
    const findCardById = (id) => qsa('.product-card, [data-product-quote-add]').find((card) => (card.dataset.productId || '') === id);
    const quoteAddButtons = qsa('[data-product-quote-add]');
    const updateQuoteAddButtons = () => {
      quoteAddButtons.forEach((button) => {
        const item = itemFromElement(button);
        const active = !!(item && items.has(item.id));
        button.classList.toggle('is-active', active);
        button.classList.toggle('boblift-product-favorite-active', active);
        button.setAttribute('aria-pressed', String(active));
        const label = active ? (button.dataset.labelActive || quoteListText('addedLabel', 'Added to Quote List')) : (button.dataset.labelAdd || quoteListText('addLabel', 'Add to Quote List'));
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        const labelNode = qs('[data-product-quote-add-label]', button);
        if (labelNode) labelNode.textContent = label;
      });
    };
    const selectedItems = () => [...items.values()];
    const save = () => {
      try {
        window.localStorage.setItem(storageKey, JSON.stringify(selectedItems()));
      } catch {}
    };
    const notify = () => {
      document.dispatchEvent(new CustomEvent('boblift:quote-list-change'));
    };
    const setOpen = (open) => {
      if (!drawer) return;
      drawer.classList.toggle('hidden', !open);
      drawer.classList.toggle('block', open);
      drawer.setAttribute('aria-hidden', String(!open));
      qs('[data-quote-list-toggle]', root || document)?.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) {
        lastFocus = document.activeElement;
        window.setTimeout(() => panel?.focus(), 0);
      } else if (lastFocus instanceof HTMLElement) {
        lastFocus.focus({ preventScroll: true });
      }
    };
    const render = () => {
      const list = selectedItems();
      const total = list.length;
      root?.classList.toggle('hidden', total === 0);
      if (count) count.textContent = String(total);
      if (panelCount) panelCount.textContent = String(total);
      updateQuoteAddButtons();
      empty?.classList.toggle('hidden', total !== 0);
      requestButton?.toggleAttribute('disabled', total === 0);
      clearButton?.toggleAttribute('disabled', total === 0);
      if (!itemsRoot) return;
      itemsRoot.replaceChildren();
      list.forEach((item) => {
        const node = document.createElement('article');
        node.className = 'grid grid-cols-[72px_minmax(0,1fr)_36px] gap-3 rounded border border-line bg-white p-3 shadow-card';
        node.innerHTML = `
          <div class="h-[72px] overflow-hidden rounded bg-[#eef2f7]">${item.image ? `<img class="h-full w-full object-cover" src="${escapeText(item.image)}" alt="${escapeText(item.title)}" loading="lazy" decoding="async">` : ''}</div>
          <div class="min-w-0">
            <p class="boblift-kicker truncate">${escapeText(item.category)}</p>
            <h3 class="mt-1 line-clamp-2 text-sm font-black leading-5 text-ink">${escapeText(item.title)}</h3>
            ${item.summary ? `<p class="mt-1 line-clamp-2 text-xs font-normal leading-5 text-slate-500">${escapeText(item.summary)}</p>` : ''}
            ${item.detailUrl ? `<a class="mt-2 inline-flex text-xs font-black text-blue-700 hover:text-navy" href="${escapeText(item.detailUrl)}">${escapeText(quoteListText('viewDetailsLabel', 'View details'))}</a>` : ''}
          </div>
          <button class="grid h-9 w-9 place-items-center rounded-sm border border-line bg-white text-slate-500 transition hover:border-red-100 hover:bg-red-50 hover:text-red-600" type="button" data-quote-list-remove="${escapeText(item.id)}" aria-label="${escapeText(quoteListText('removeLabelTemplate', 'Remove {product} from Quote List').replace('{product}', item.title || ''))}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
        `;
        itemsRoot.appendChild(node);
      });
      if (!total) setOpen(false);
    };
    const commit = () => {
      save();
      render();
      notify();
    };
    const load = () => {
      try {
        const parsed = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
        if (!Array.isArray(parsed)) return;
        parsed.forEach((entry) => {
          const item = typeof entry === 'string' ? itemFromCard(findCardById(entry)) : normalizeItem(entry);
          if (item) items.set(item.id, item);
        });
      } catch {
        items = new Map();
      }
      save();
      render();
    };
    const clear = () => {
      if (!items.size) return;
      items.clear();
      commit();
    };
    const removeProduct = (product) => {
      if (!product || !items.size) return false;
      const targetId = typeof product.id === 'string' ? product.id : '';
      const targetSlug = typeof product.slug === 'string' ? product.slug : '';
      const targetTitle = typeof product.title === 'string' ? product.title : '';
      let removed = false;
      items.forEach((item, id) => {
        if (removed) return;
        if ((targetId && item.id === targetId) || (targetSlug && item.slug === targetSlug) || (targetTitle && item.title === targetTitle)) {
          items.delete(id);
          removed = true;
        }
      });
      if (removed) commit();
      return removed;
    };
    qs('[data-quote-list-toggle]', root || document)?.addEventListener('click', () => setOpen(true));
    qsa('[data-quote-list-close]', drawer || document).forEach((button) => button.addEventListener('click', () => setOpen(false)));
    drawer?.addEventListener('click', (event) => { if (event.target === drawer) setOpen(false); });
    itemsRoot?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-quote-list-remove]');
      if (!button) return;
      items.delete(button.dataset.quoteListRemove || '');
      commit();
    });
    clearButton?.addEventListener('click', clear);
    quoteAddButtons.forEach((button) => button.addEventListener('click', () => {
      const item = itemFromElement(button);
      if (!item) return;
      if (items.has(item.id)) items.delete(item.id);
      else items.set(item.id, item);
      commit();
    }));
    requestButton?.addEventListener('click', () => {
      const list = selectedItems();
      if (!list.length) return;
      requestButton.dataset.inquiryProduct = list.map((item) => item.title).join('; ');
      requestButton.dataset.inquiryProductSlug = list.map((item) => item.slug).filter(Boolean).join(',');
      requestButton.dataset.inquiryQuantity = String(list.length);
      requestButton.dataset.inquiryProducts = JSON.stringify(list);
      delete requestButton.dataset.inquiryMessage;
      requestButton.dataset.clearQuoteList = 'true';
      requestButton.dataset.inquirySource = '询价清单';
      setOpen(false);
      openInquiryModal(requestButton);
    });
    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawer && !drawer.classList.contains('hidden')) setOpen(false);
    });

    load();
    return {
      has: (id) => items.has(id),
      toggleFromCard: (card) => {
        const item = itemFromElement(card);
        if (!item) return;
        if (items.has(item.id)) items.delete(item.id);
        else items.set(item.id, item);
        commit();
      },
      clear,
      removeProduct,
    };
  };
  const quoteList = initQuoteList();
  window.bobliftQuoteList = quoteList;

  const productSectionLinks = qsa('[data-product-section-link]');
  if (productSectionLinks.length) {
    const productSectionNav = qs('[data-product-section-nav]');
    // Fade whichever physical side still hides tabs; scrollLeft runs negative in right-to-left pages.
    const updateProductSectionNavFade = () => {
      if (!productSectionNav) return;
      const hidden = productSectionNav.scrollWidth - productSectionNav.clientWidth;
      const offset = Math.abs(productSectionNav.scrollLeft);
      const rtl = getComputedStyle(productSectionNav).direction === 'rtl';
      const left = (rtl ? hidden - offset : offset) > 2;
      const right = (rtl ? offset : hidden - offset) > 2;
      const fade = left && right ? 'both' : left ? 'left' : right ? 'right' : '';
      if (fade) productSectionNav.dataset.fade = fade;
      else delete productSectionNav.dataset.fade;
    };
    if (productSectionNav) {
      productSectionNav.addEventListener('scroll', updateProductSectionNavFade, { passive: true });
      window.addEventListener('resize', updateProductSectionNavFade);
      updateProductSectionNavFade();
    }
    // Keep the current tab in view when the row scrolls sideways; only the row moves, never the page.
    const revealProductSectionLink = (link) => {
      if (!productSectionNav || productSectionNav.scrollWidth <= productSectionNav.clientWidth) return;
      const navRect = productSectionNav.getBoundingClientRect();
      const linkRect = link.getBoundingClientRect();
      if (linkRect.left >= navRect.left + 48 && linkRect.right <= navRect.right - 48) return;
      const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      productSectionNav.scrollBy({ left: (linkRect.left + linkRect.width / 2) - (navRect.left + navRect.width / 2), behavior: reduceMotion ? 'auto' : 'smooth' });
    };
    const activateProductSectionLink = (id) => {
      productSectionLinks.forEach((link) => {
        const active = (link.getAttribute('href') || '') === `#${id}`;
        const changed = active && !link.classList.contains('is-active');
        link.classList.toggle('is-active', active);
        if (active) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
        if (changed) revealProductSectionLink(link);
      });
    };
    const sectionIds = productSectionLinks.map((link) => (link.getAttribute('href') || '').replace('#', '')).filter(Boolean);
    const sections = sectionIds.map((id) => document.getElementById(id)).filter(Boolean);
    if ('IntersectionObserver' in window && sections.length) {
      const observer = new IntersectionObserver((entries) => {
        const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
        if (visible?.target?.id) activateProductSectionLink(visible.target.id);
      }, { rootMargin: '-25% 0px -60% 0px', threshold: [0.08, 0.22, 0.4] });
      sections.forEach((section) => observer.observe(section));
    }
    const currentHash = window.location.hash ? window.location.hash.slice(1) : sectionIds[0];
    if (currentHash) activateProductSectionLink(currentHash);
  }

  qsa('[data-quote-form]').forEach((form) => {
    const emailLink = qs('[data-quote-email]', form);
    const whatsappLink = qs('[data-quote-whatsapp]', form);
    const productField = qs('[data-quote-field="product"]', form);
    const productSlugField = qs('[data-product-slug-field]', form);
    const labels = Object.assign({
      name: 'Name',
      email: 'Email',
      phone: 'Phone / WhatsApp',
      company: 'Company',
      website: 'Website',
      country: 'Country',
      product: 'Product',
      quantity: 'Quantity',
      message: 'Project details',
      truck_available: 'Truck available',
      truck_model: 'Truck model or chassis brand',
      truck_specs: 'Truck chassis specs',
      imported_from_china: 'Imported from China before',
      customs_clearance: 'Customs clearance',
      destination_port: 'Destination port / city',
      preferred_delivery: 'Preferred delivery time',
      contact_method: 'Preferred contact method',
      preferred_contact_time: 'Preferred contact time',
      other_requirements: 'Other requirements',
    }, i18n?.quoteForm?.inquiryLineLabels || {});
    const valueFor = (name) => {
      const field = qs(`[data-quote-field="${name}"]`, form);
      if (!field) return '';
      return field.value || '';
    };
    const updateProductSlug = () => {
      if (!productField || !productSlugField) return;
      if (productField.matches('select')) {
        const option = productField.selectedOptions?.[0];
        productSlugField.value = option?.dataset.productSlug || '';
        return;
      }
      const root = productField.closest('[data-inquiry-select]');
      const value = productField.value || '';
      const option = root ? qsa('[data-inquiry-select-option]', root).find((item) => (item.dataset.value || '') === value) : null;
      productSlugField.value = option?.dataset.productSlug || productField.dataset.productSlug || '';
    };
    const inquiryText = () => {
      const lines = [form.dataset.defaultInquiryText || quoteFormText('defaultInquiryText', 'Hello BOB-LIFT, I would like to request a quotation.')];
      Object.keys(labels).forEach((name) => {
        const fallback = name === 'product' ? (i18n?.quoteForm?.fields?.productNotDecided || 'Not decided yet') : '-';
        lines.push(`${labels[name]}: ${valueFor(name) || fallback}`);
      });
      return lines.join('\n');
    };
    const updateLinks = () => {
      const text = inquiryText();
      const subject = encodeURIComponent(form.dataset.inquirySubject || 'BOB-LIFT quotation request');
      if (emailLink) {
        emailLink.href = `mailto:${form.dataset.contactEmail || ''}?subject=${subject}&body=${encodeURIComponent(text)}`;
      }
      if (whatsappLink) {
        whatsappLink.href = `https://wa.me/${form.dataset.whatsappNumber || '8615852499415'}?text=${encodeURIComponent(text)}`;
      }
      updateProductSlug();
    };
    qsa('[data-quote-field]', form).forEach((field) => {
      field.addEventListener('input', updateLinks);
      field.addEventListener('change', updateLinks);
    });
    if (!form.matches('[data-inquiry-modal-form]')) {
      form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
          event.preventDefault();
          return;
        }
        form.dataset.submitting = 'true';
        qsa('button[type="submit"]', form).forEach((button) => button.setAttribute('disabled', 'disabled'));
      });
    }
    updateLinks();
  });

  // Heavy media below the fold: stills load when within 400px of the viewport (native lazy loading starts
  // far earlier), background videos only once they scroll into view, and they play only while visible.
  const lazyStills = qsa('img[data-inview-src]');
  const showStill = (img) => { img.src = img.dataset.inviewSrc; };
  if ('IntersectionObserver' in window) {
    const stillObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(({ target, isIntersecting }) => {
        if (!isIntersecting) return;
        showStill(target);
        observer.unobserve(target);
      });
    }, { rootMargin: '400px 0px' });
    lazyStills.forEach((img) => stillObserver.observe(img));
  } else {
    lazyStills.forEach(showStill);
  }

  // Reduced-motion and data-saver users keep the still image.
  const quietMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || navigator.connection?.saveData;
  if (!quietMotion && 'IntersectionObserver' in window) {
    const videoObserver = new IntersectionObserver((entries) => {
      entries.forEach(({ target, isIntersecting }) => {
        if (!isIntersecting) {
          target.pause();
          return;
        }
        if (!target.getAttribute('src') && target.dataset.src) {
          const phone = window.matchMedia('(max-width: 767px)').matches;
          target.src = (phone && target.dataset.srcMobile) || target.dataset.src;
        }
        target.play().catch(() => {});
      });
    }, { threshold: 0.2 });
    qsa('[data-inview-video]').forEach((video) => videoObserver.observe(video));
  }

  // Product page films play in place; the list below the player switches which one is loaded.
  qsa('[data-inline-videos]').forEach((root) => {
    const stage = qs('[data-inline-video-stage]', root);
    const caption = qs('[data-inline-video-caption]', root);
    const items = qsa('[data-inline-video-item]', root);
    let current = qs('[data-inline-video-play]', stage);
    const play = (source) => {
      stage.innerHTML = '';
      if (source.dataset.videoPlayer === 'youtube' && source.dataset.videoYoutube) {
        const frame = document.createElement('iframe');
        frame.className = 'absolute inset-0 h-full w-full';
        frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(source.dataset.videoYoutube)}?autoplay=1&rel=0`;
        frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        frame.allowFullscreen = true;
        frame.title = source.dataset.videoTitle || '';
        stage.appendChild(frame);
      } else {
        const video = document.createElement('video');
        video.className = 'absolute inset-0 h-full w-full bg-black object-contain';
        video.src = source.dataset.videoSrc;
        video.poster = source.dataset.videoPoster || '';
        video.controls = true;
        video.playsInline = true;
        video.setAttribute('aria-label', source.dataset.videoTitle || '');
        stage.appendChild(video);
        video.play().catch(() => {});
      }
    };
    stage.addEventListener('click', (event) => {
      if (event.target.closest('[data-inline-video-play]')) play(current);
    });
    items.forEach((item) => item.addEventListener('click', () => {
      items.forEach((other) => other.setAttribute('aria-pressed', String(other === item)));
      if (caption) caption.textContent = item.dataset.videoTitle || '';
      // Picking from the list starts that film at once.
      current = item;
      play(item);
    }));
  });

  // Sister-site links ask first: the dialog names the site with a green check, then opens it in a new tab.
  const leaveDialog = qs('[data-leave-dialog]');
  if (leaveDialog && typeof leaveDialog.showModal === 'function') {
    const leaveGo = qs('[data-leave-go]', leaveDialog);
    const leaveGoLabel = qs('[data-leave-go-label]', leaveDialog);
    // Capture phase: the site switcher stops clicks from bubbling out of its menu.
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[data-leave-site]');
      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      const domain = link.dataset.leaveDomain || link.hostname;
      qs('[data-leave-name]', leaveDialog).textContent = link.dataset.leaveName || domain;
      qs('[data-leave-domain]', leaveDialog).textContent = domain;
      leaveGoLabel.textContent = (leaveDialog.dataset.leaveContinueTemplate || 'Continue to {domain}').replace('{domain}', domain);
      leaveGo.href = link.href;
      // The menu that held the link closes behind the dialog.
      qsa('[data-language-root]').forEach((root) => {
        qs('[data-language-menu]', root)?.classList.add('hidden');
        qs('[data-language-toggle]', root)?.setAttribute('aria-expanded', 'false');
      });
      leaveDialog.showModal();
    }, true);
    qsa('[data-leave-stay]', leaveDialog).forEach((button) => button.addEventListener('click', () => leaveDialog.close()));
    leaveGo.addEventListener('click', () => leaveDialog.close());
    leaveDialog.addEventListener('click', (event) => {
      if (event.target === leaveDialog) leaveDialog.close();
    });
  }

  // Video library cards open one shared player: a local MP4 now, a YouTube embed once ids are set.
  const videoDialog = qs('[data-video-dialog]');
  if (videoDialog) {
    document.body.appendChild(videoDialog);
    const stage = qs('[data-video-stage]', videoDialog);
    const title = qs('[data-video-dialog-title]', videoDialog);
    let lastTrigger = null;
    let background = [];
    let wasScrollLocked = false;
    const focusableControls = () => qsa('button, a[href], input, select, textarea, iframe, video[controls], [tabindex]', videoDialog)
      .filter((control) => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length > 0);
    const closeVideo = () => {
      if (videoDialog.classList.contains('hidden')) return;
      stage.innerHTML = '';
      videoDialog.classList.add('hidden');
      videoDialog.classList.remove('flex');
      videoDialog.setAttribute('aria-hidden', 'true');
      background.forEach(([element, wasInert]) => { element.inert = wasInert; });
      background = [];
      document.documentElement.classList.toggle('overflow-hidden', wasScrollLocked);
      lastTrigger?.focus();
    };
    const openVideo = (trigger) => {
      if (!videoDialog.classList.contains('hidden')) return;
      lastTrigger = trigger;
      const portrait = trigger.hasAttribute('data-video-portrait');
      const size = portrait ? 'h-full max-h-full w-auto aspect-[9/16]' : 'w-full max-w-6xl aspect-video max-h-full';
      stage.innerHTML = '';
      if (trigger.dataset.videoPlayer === 'youtube' && trigger.dataset.videoYoutube) {
        const frame = document.createElement('iframe');
        frame.className = `${size} rounded-sm bg-black`;
        frame.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(trigger.dataset.videoYoutube)}?autoplay=1&rel=0`;
        frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        frame.title = trigger.dataset.videoTitle || '';
        stage.appendChild(frame);
      } else {
        const video = document.createElement('video');
        video.className = `${size} rounded-sm bg-black object-contain`;
        video.src = trigger.dataset.videoSrc;
        video.controls = true;
        video.autoplay = true;
        video.playsInline = true;
        stage.appendChild(video);
        video.play().catch(() => {});
      }
      title.textContent = trigger.dataset.videoTitle || '';
      videoDialog.setAttribute('aria-label', trigger.dataset.videoTitle || '');
      videoDialog.classList.remove('hidden');
      videoDialog.classList.add('flex');
      videoDialog.setAttribute('aria-hidden', 'false');
      wasScrollLocked = document.documentElement.classList.contains('overflow-hidden');
      background = [...document.body.children].filter((element) => element !== videoDialog)
        .map((element) => [element, element.inert]);
      background.forEach(([element]) => { element.inert = true; });
      document.documentElement.classList.add('overflow-hidden');
      qs('[data-video-close]', videoDialog)?.focus();
    };
    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-video-open]');
      if (trigger) {
        event.preventDefault();
        openVideo(trigger);
        return;
      }
      if (event.target.closest('[data-video-close]') || event.target === stage) closeVideo();
    });
    document.addEventListener('keydown', (event) => {
      if (videoDialog.classList.contains('hidden')) return;
      if (event.key === 'Escape') closeVideo();
      else if (event.key === 'Tab') {
        const controls = focusableControls();
        const first = controls[0];
        const last = controls[controls.length - 1];
        if (!videoDialog.contains(document.activeElement) || (event.shiftKey ? document.activeElement === first : document.activeElement === last)) {
          event.preventDefault();
          (event.shiftKey ? last : first)?.focus();
        }
      }
    });
    document.addEventListener('focusin', (event) => {
      if (!videoDialog.classList.contains('hidden') && !videoDialog.contains(event.target)) {
        qs('[data-video-close]', videoDialog)?.focus();
      }
    });
  }

  // Video center tabs filter the grid by group.
  qsa('[data-video-filter-root]').forEach((root) => {
    const tabs = qsa('[data-video-filter]', root);
    const items = qsa('[data-video-group]', root);
    tabs.forEach((tab) => tab.addEventListener('click', () => {
      const group = tab.dataset.videoFilter;
      tabs.forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
      items.forEach((item) => item.classList.toggle('hidden', group !== 'all' && item.dataset.videoGroup !== group));
    }));
  });

  // Footer on phones: the link groups start folded behind their titles (they ship open, so they show without script).
  const footerPhone = window.matchMedia('(max-width: 767px)');
  // Wider screens show every group open with no disclosure control, so their titles cannot be focused or toggled
  // (Enter and Space on a summary arrive as a click).
  const syncFooterGroups = () => qsa('[data-footer-group]').forEach((group) => {
    group.toggleAttribute('open', !footerPhone.matches);
    const summary = qs('summary', group);
    if (!summary) return;
    if (footerPhone.matches) summary.removeAttribute('tabindex');
    else summary.setAttribute('tabindex', '-1');
  });
  qsa('[data-footer-group] > summary').forEach((summary) => summary.addEventListener('click', (event) => {
    if (!footerPhone.matches) event.preventDefault();
  }));
  syncFooterGroups();
  footerPhone.addEventListener('change', syncFooterGroups);

  // Footer on phones: the company's other addresses fold behind a button once script runs (without it they all show),
  // and the button shows them and then goes away.
  qsa('[data-footer-more-addresses]').forEach((button) => {
    const extras = qsa('[data-footer-more-address]', button.parentElement);
    extras.forEach((item) => item.classList.add('hidden'));
    button.classList.replace('hidden', 'inline-flex');
    button.addEventListener('click', () => {
      extras.forEach((item) => item.classList.remove('hidden'));
      button.setAttribute('aria-expanded', 'true');
      button.classList.replace('inline-flex', 'hidden');
    });
  });

  // Social shorts play one at a time.
  const socialVideos = qsa('[data-social-video]');
  socialVideos.forEach((video) => video.addEventListener('play', () => {
    socialVideos.forEach((other) => { if (other !== video) other.pause(); });
  }));

  qsa('[data-home-products]').forEach((root) => {
    const scroller = qs('[data-product-category-scroller]', root);
    const tabs = qsa('[data-product-category-tab]', root);
    const grids = qsa('[data-product-grid-panel]', root);
    const previous = qs('[data-product-scroll="prev"]', root);
    const next = qs('[data-product-scroll="next"]', root);
    const scrollDirection = () => (scroller && getComputedStyle(scroller).direction === 'rtl' ? -1 : 1);
    const updateScrollButtons = () => {
      if (!scroller) return;
      const max = scroller.scrollWidth - scroller.clientWidth;
      const offset = Math.abs(scroller.scrollLeft);
      if (previous) previous.disabled = offset <= 2;
      if (next) next.disabled = offset >= max - 2;
    };
    const setTab = (index) => {
      tabs.forEach((tab, current) => {
        tab.setAttribute('aria-selected', String(current === index));
        tab.tabIndex = current === index ? 0 : -1;
      });
      grids.forEach((panel, current) => panel.classList.toggle('hidden', current !== index));
    };
    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => setTab(Number(tab.dataset.productCategoryTab || 0)));
      // Arrow keys move along the row (mirrored in right-to-left pages), Home and End jump to its ends.
      tab.addEventListener('keydown', (event) => {
        const step = scrollDirection();
        const targets = { ArrowRight: i + step, ArrowLeft: i - step, Home: 0, End: tabs.length - 1 };
        if (!(event.key in targets)) return;
        event.preventDefault();
        const target = (targets[event.key] + tabs.length) % tabs.length;
        setTab(target);
        tabs[target].focus();
      });
    });
    // Each family's models scroll in one row; the arrows page through it and switch off at either end.
    qsa('[data-product-carousel]', root).forEach((carousel) => {
      const row = qs('[data-product-row]', carousel);
      const back = qs('[data-product-row-scroll="prev"]', carousel);
      const forward = qs('[data-product-row-scroll="next"]', carousel);
      if (!row || (!back && !forward)) return;
      const direction = () => (getComputedStyle(row).direction === 'rtl' ? -1 : 1);
      const update = () => {
        const offset = Math.abs(row.scrollLeft);
        const max = row.scrollWidth - row.clientWidth;
        if (back) back.disabled = offset <= 2;
        if (forward) forward.disabled = offset >= max - 2;
      };
      back?.addEventListener('click', () => row.scrollBy({ left: -direction() * row.clientWidth, behavior: 'smooth' }));
      forward?.addEventListener('click', () => row.scrollBy({ left: direction() * row.clientWidth, behavior: 'smooth' }));
      row.addEventListener('scroll', update, { passive: true });
      if ('ResizeObserver' in window) new ResizeObserver(update).observe(row);
      update();
    });
    previous?.addEventListener('click', () => scroller?.scrollBy({ left: -scrollDirection() * Math.min(scroller.clientWidth * 0.72, 620), behavior: 'smooth' }));
    next?.addEventListener('click', () => scroller?.scrollBy({ left: scrollDirection() * Math.min(scroller.clientWidth * 0.72, 620), behavior: 'smooth' }));
    scroller?.addEventListener('scroll', updateScrollButtons, { passive: true });
    updateScrollButtons();
    window.requestAnimationFrame(updateScrollButtons);
    window.addEventListener('load', updateScrollButtons, { once: true });
    if (scroller && 'ResizeObserver' in window) {
      const categoryResizeObserver = new ResizeObserver(updateScrollButtons);
      categoryResizeObserver.observe(scroller);
      if (scroller.firstElementChild) categoryResizeObserver.observe(scroller.firstElementChild);
    } else {
      window.addEventListener('resize', updateScrollButtons);
    }
  });

  qsa('[data-product-listing]').forEach((root) => {
    const cards = qsa('.product-card', root);
    const checks = qsa('input[data-filter]', root);
    const capacitySelect = qs('[data-capacity-select]', root);
    const count = qs('[data-product-count]', root);
    const total = qs('[data-product-total]', root);
    const empty = qs('[data-product-empty]', root);
    const sortTrigger = qs('[data-sort-trigger]', root);
    const sortMenu = qs('[data-sort-menu]', root);
    const sortLabel = qs('[data-sort-label]', root);
    const sortDropdown = qs('[data-sort-dropdown]', root);
    const sortOptions = qsa('[data-sort-option]', root);
    const resets = qsa('[data-filter-reset]', root);
    const applyFilterButtons = qsa('[data-filter-apply]', root);
    const categoryChildPanels = qsa('[data-category-children-panel]', root);
    const filterGroups = qsa('[data-filter-group]', root);
    const drawer = qs('[data-filter-drawer]', root);
    const filterBar = qs('[data-filter-bar]', root);
    const categoryTabs = qsa('[data-category-tab]', root);
    const categorySubtabRows = qsa('[data-category-subtabs]', root);
    const filterDropdowns = qsa('[data-filter-dropdown]', root);
    const filterChips = qs('[data-filter-chips]', root);
    const filterChipsRow = qs('[data-filter-chips-row]', root);
    const filterChipIcon = qs('[data-filter-chip-icon]', root);
    const grid = qs('[data-products-grid]', root);
    const banner = qs('[data-product-banner]', root);
    const bannerLabel = qs('[data-banner-label]', root);
    const bannerTitle = qs('[data-banner-title]', root);
    const bannerText = qs('[data-banner-text]', root);
    const bannerImage = qs('[data-banner-image]', root);
    const pagination = qs('[data-product-pagination]', root);
    const toolbar = qs('[data-products-toolbar]', root);
    const pageStatus = qs('[data-product-page]', root);
    const pageStatusText = qs('[data-product-page-text]', root);
    const results = qs('[data-products-results]', root);
    const quickModal = qs('[data-quick-view-modal]', root);
    const quickPanel = qs('[data-quick-view-panel]', root);
    const quickImage = qs('[data-quick-image]', root);
    const quickTitle = qs('[data-quick-title]', root);
    const quickCategory = qs('[data-quick-category]', root);
    const quickSummary = qs('[data-quick-summary]', root);
    const quickSpecs = qs('[data-quick-specs]', root);
    const quickInquiry = qs('[data-quick-inquiry]', root);
    const quickDetail = qs('[data-quick-detail]', root);
    const quickFavorite = qs('[data-quick-favorite]', root);
    let currentPage = 1;
    let activeQuickCard = null;

    cards.forEach((card, index) => { card.dataset.originalIndex = String(index); });

    const updateFavoriteButton = (button, active) => {
      if (!button) return;
      button.classList.toggle('text-blue-700', active);
      button.classList.toggle('boblift-product-favorite-active', active);
      button.setAttribute('aria-label', active ? quoteListText('removeLabel', 'Remove from Quote List') : quoteListText('addLabel', 'Add to Quote List'));
      button.setAttribute('title', active ? quoteListText('removeLabel', 'Remove from Quote List') : quoteListText('addLabel', 'Add to Quote List'));
    };
    const syncFavoriteStates = () => {
      cards.forEach((card) => qsa('[data-product-favorite]', card).forEach((button) => updateFavoriteButton(button, quoteList.has(card.dataset.productId || ''))));
      updateFavoriteButton(quickFavorite, activeQuickCard ? quoteList.has(activeQuickCard.dataset.productId || '') : false);
    };
    const toggleFavorite = (card) => {
      if (!card) return;
      quoteList.toggleFromCard(card);
      syncFavoriteStates();
    };
    document.addEventListener('boblift:quote-list-change', syncFavoriteStates);

    // 12 fills whole rows in the 1-4 column grids.
    const productsPerPage = () => 12;
    const activeValues = (name) => [...new Set(checks.filter((input) => input.dataset.filter === name && input.checked).map((input) => input.value))];
    const categoryInputs = () => checks.filter((input) => input.dataset.filter === 'category');
    const parentChildren = new Map(categoryChildPanels.map((panel) => [
      panel.dataset.categoryChildrenPanel || '',
      (panel.dataset.categoryChildren || '').split(',').map((value) => value.trim()).filter(Boolean),
    ]).filter(([parentValue]) => parentValue));
    const childParent = new Map();
    parentChildren.forEach((children, parentValue) => children.forEach((childValue) => childParent.set(childValue, parentValue)));
    const setCategoryChecked = (value, checked) => {
      categoryInputs().filter((input) => input.value === value).forEach((input) => { input.checked = checked; });
    };
    const isCategoryChecked = (value) => categoryInputs().some((input) => input.value === value && input.checked);
    const checkedCategoryValues = (role = '') => [...new Set(categoryInputs()
      .filter((input) => input.checked && (!role || input.dataset.categoryRole === role))
      .map((input) => input.value))];
    const syncCategoryScope = (changedInput) => {
      if (!changedInput || changedInput.dataset.filter !== 'category') return;
      const value = changedInput.value || '';
      if (changedInput.dataset.categoryRole === 'parent' && !changedInput.checked) {
        (parentChildren.get(value) || []).forEach((childValue) => setCategoryChecked(childValue, false));
        return;
      }
      if (changedInput.dataset.categoryRole === 'child') {
        const parentValue = changedInput.dataset.categoryParent || childParent.get(value) || '';
        if (!parentValue) return;
        if (changedInput.checked) {
          setCategoryChecked(parentValue, true);
          return;
        }
        const siblingChecked = (parentChildren.get(parentValue) || []).some((childValue) => isCategoryChecked(childValue));
        if (!siblingChecked) setCategoryChecked(parentValue, false);
      }
    };
    const syncAllCategoryScopes = () => {
      checkedCategoryValues('child').forEach((childValue) => {
        const parentValue = childParent.get(childValue);
        if (parentValue) setCategoryChecked(parentValue, true);
      });
      parentChildren.forEach((children, parentValue) => {
        const childChecked = children.some((childValue) => isCategoryChecked(childValue));
        if (!childChecked && !isCategoryChecked(parentValue)) {
          children.forEach((childValue) => setCategoryChecked(childValue, false));
        }
      });
    };
    const activeCategoryDisplayValues = () => {
      const childValues = checkedCategoryValues('child');
      const singleValues = checkedCategoryValues('single');
      const parentValues = checkedCategoryValues('parent').filter((parentValue) => !(parentChildren.get(parentValue) || []).some((childValue) => childValues.includes(childValue)));
      return [...new Set([...childValues, ...singleValues, ...parentValues])];
    };
    const activeCategoryFilterValues = () => [...new Set(activeCategoryDisplayValues().flatMap((value) => parentChildren.get(value) || [value]))];
    const updateCategoryChildrenVisibility = () => {
      categoryChildPanels.forEach((panel) => {
        const parentValue = panel.dataset.categoryChildrenPanel || '';
        const childValues = parentChildren.get(parentValue) || [];
        const parentChecked = isCategoryChecked(parentValue);
        const childChecked = childValues.some((value) => isCategoryChecked(value));
        panel.classList.toggle('hidden', !parentChecked && !childChecked);
      });
    };
    const parseBannerJson = (value, fallback) => {
      try {
        const parsed = JSON.parse(value || '');
        return parsed && typeof parsed === 'object' ? parsed : fallback;
      } catch {
        return fallback;
      }
    };
    const bannerDefault = parseBannerJson(banner?.dataset.bannerDefault, {});
    const bannerCategories = parseBannerJson(banner?.dataset.bannerCategories, {});
    const categoryUrls = parseBannerJson(root.dataset.categoryUrls, {});
    const productArchiveUrl = root.dataset.productArchiveUrl || '/';
    const categoryIntros = qsa('[data-category-intro], [data-category-extra]', root);
    const updateBanner = () => {
      const categories = activeCategoryDisplayValues();
      // The intro for a single selected category; none on the full list or a mixed filter.
      categoryIntros.forEach((intro) => { intro.hidden = !(categories.length === 1 && (intro.dataset.categoryIntro || intro.dataset.categoryExtra) === categories[0]); });
      if (!banner) return;
      const content = categories.length === 1 && bannerCategories[categories[0]] ? bannerCategories[categories[0]] : bannerDefault;
      if (bannerLabel) bannerLabel.textContent = content.label || '';
      if (bannerTitle) bannerTitle.textContent = content.title || '';
      if (bannerText) bannerText.textContent = content.text || '';
      if (bannerImage && content.image) {
        if (bannerImage.getAttribute('src') !== content.image) bannerImage.setAttribute('src', content.image);
        bannerImage.setAttribute('alt', content.imageAlt || '');
      }
    };
    const filterParamMap = { category: 'category', boom: 'boom', capacity: 'capacity', moment: 'moment', weight: 'weight' };
    // A capacity range from a link that is not one of the bins (old-site tonnage pages redirect to ?capacity=4-5) filters
    // as given and shows as its own chip until cleared.
    let customCapacity = '';
    // Spec filters hold a number per card (tonnes, t·m, kg; 0 = not given) matched against "min-max" / "min+" bins.
    const specFilters = ['capacity', 'moment', 'weight'];
    const cardNumber = (card, filterName) => Number(card.dataset[filterName] || 0);
    const slugifyFilter = (value) => value.toLowerCase().replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const optionLabel = (input) => qs('span', input.closest('label') || input.parentElement)?.textContent?.trim() || input.value;
    const paramValues = (params, key) => params.getAll(key).flatMap((value) => value.split(',')).map((value) => value.trim()).filter(Boolean);
    const optionMatchesParam = (input, value) => {
      if (input.dataset.filter === 'capacity') value = value.replace(/-tons$/i, '');
      const normalized = slugifyFilter(value);
      return value === input.value || normalized === slugifyFilter(input.value) || normalized === slugifyFilter(optionLabel(input));
    };
    const syncMatchingFilterInputs = (changedInput) => {
      checks
        .filter((input) => input !== changedInput && input.dataset.filter === changedInput.dataset.filter && input.value === changedInput.value)
        .forEach((input) => { input.checked = changedInput.checked; });
    };
    const syncFiltersFromUrl = () => {
      const params = new URLSearchParams(window.location.search);
      Object.entries(filterParamMap).forEach(([filterName, paramName]) => {
        const hashValue = filterName === 'category' ? window.location.hash.replace(/^#/, '').trim() : '';
        const pathValue = filterName === 'category' ? (root.dataset.currentCategory || '') : '';
        const values = [...paramValues(params, paramName), ...(hashValue ? [hashValue] : []), ...(pathValue ? [pathValue] : [])];
        const inputs = checks.filter((input) => input.dataset.filter === filterName);
        inputs.forEach((input) => {
          input.checked = values.some((value) => optionMatchesParam(input, value));
        });
        if (filterName === 'capacity') {
          customCapacity = values.find((value) => /^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/.test(value) && !inputs.some((input) => optionMatchesParam(input, value))) || '';
        }
      });
      syncAllCategoryScopes();
      const clearedSelection = updateFilterAvailability();
      if (clearedSelection) updateUrlFromFilters();
      apply({ resetPage: true });
    };
    const updateUrlFromFilters = ({ replace = true } = {}) => {
      const url = new URL(window.location.href);
      Object.values(filterParamMap).forEach((paramName) => url.searchParams.delete(paramName));
      const categoryValues = activeCategoryDisplayValues();
      const selectedCategory = categoryValues.length === 1 ? slugifyFilter(categoryValues[0]) : '';
      root.dataset.currentCategory = selectedCategory;
      const categoryUrl = selectedCategory && categoryUrls[selectedCategory] ? new URL(categoryUrls[selectedCategory], window.location.origin) : null;
      const archiveUrl = new URL(productArchiveUrl, window.location.origin);
      url.pathname = categoryUrl ? categoryUrl.pathname : archiveUrl.pathname;
      Object.entries(filterParamMap).forEach(([filterName, paramName]) => {
        if (filterName === 'category') return;
        activeValues(filterName).forEach((value) => url.searchParams.append(paramName, slugifyFilter(value)));
      });
      if (customCapacity && !activeValues('capacity').length) url.searchParams.set(filterParamMap.capacity, customCapacity);
      url.hash = '';
      const nextUrl = `${url.pathname}${url.search}`;
      if (replace) window.history.replaceState(null, '', nextUrl);
      else window.history.pushState(null, '', nextUrl);
    };
    const inAnyRange = (numberValue, ranges) => ranges.some((range) => {
      const parts = String(range).replace(/_/g, '.').match(/\d+(?:\.\d+)?/g) || [];
      const min = Number(parts[0] || 0);
      const max = parts[1] === undefined ? null : Number(parts[1]);
      if (max === null) return numberValue > min;
      if (min <= 0) return numberValue > 0 && numberValue <= max;
      return numberValue > min && numberValue <= max;
    });
    const cardCategories = (card) => (card.dataset.categories || card.dataset.category || '').split(',').filter(Boolean);
    const cardMatchesFilterOption = (card, filterName, value) => {
      if (specFilters.includes(filterName)) return inAnyRange(cardNumber(card, filterName), [value]);
      if (filterName === 'boom') return (card.dataset.boom || '') === value;
      return false;
    };
    const cardHasFilterValue = (card, filterName) => {
      if (specFilters.includes(filterName)) return cardNumber(card, filterName) > 0;
      if (filterName === 'boom') return (card.dataset.boom || '') !== '';
      return false;
    };
    // Facet counts: each group counts the cards that pass the category and every other group's selection,
    // so the numbers always say how many products a click would leave. Checked options stay visible;
    // unchecked ones are hidden when they match nothing or would not narrow the list.
    const passesOtherGroups = (card, filterName) => {
      if (filterName !== 'boom') {
        const booms = activeValues('boom');
        if (booms.length && !booms.includes(card.dataset.boom || '')) return false;
      }
      return specFilters.every((name) => name === filterName || matchesSpec(card, name));
    };
    const updateFilterAvailability = () => {
      const categories = activeCategoryFilterValues();
      const categoryCards = cards.filter((card) => hasAny(cardCategories(card), categories));
      let clearedSelection = false;

      filterGroups.forEach((group) => {
        const filterName = group.dataset.filterGroup || '';
        const eligibleCards = categoryCards.filter((card) => passesOtherGroups(card, filterName));
        const groupTotal = categoryCards.filter((card) => cardHasFilterValue(card, filterName)).length;
        let usefulOptions = 0;
        qsa('[data-filter-option]', group).forEach((option) => {
          const input = qs('input[data-filter]', option);
          if (!input) return;
          const optionCount = eligibleCards.filter((card) => cardMatchesFilterOption(card, filterName, input.value)).length;
          // A category switch can leave a selection that no product in the new category has: drop it.
          if (input.checked && !categoryCards.some((card) => cardMatchesFilterOption(card, filterName, input.value))
            && (filterName !== 'capacity' || cards.some((card) => cardMatchesFilterOption(card, filterName, input.value)))) {
            input.checked = false;
            clearedSelection = true;
          }
          const useful = input.checked || (optionCount > 0 && optionCount < eligibleCards.length);
          input.disabled = !useful;
          option.classList.toggle('hidden', !useful);
          option.setAttribute('aria-hidden', String(!useful));
          const countLabel = qs('[data-filter-count]', option);
          if (countLabel) countLabel.textContent = String(optionCount);
          if (useful) usefulOptions += 1;
        });
        // Keep a group once it has options to choose from in this category, even if the others narrow it to one.
        const groupUseful = usefulOptions > 0 || groupTotal > 0 && qsa('input[data-filter]:checked', group).length > 0;
        group.classList.toggle('hidden', !groupUseful);
        group.setAttribute('aria-hidden', String(!groupUseful));
      });

      return clearedSelection;
    };
    const matchesSpec = (card, filterName) => {
      let ranges = activeValues(filterName);
      // The capacity dropdown, where an archive renders one, applies while no capacity box is ticked.
      if (!ranges.length && filterName === 'capacity' && capacitySelect?.value) ranges = [capacitySelect.value];
      if (!ranges.length && filterName === 'capacity' && customCapacity) ranges = [customCapacity];
      return !ranges.length || inAnyRange(cardNumber(card, filterName), ranges);
    };
    const hasAny = (source, values) => !values.length || values.some((value) => source.includes(value));
    const cardMatches = (card) => {
      const categories = activeCategoryFilterValues();
      const booms = activeValues('boom');
      return hasAny(cardCategories(card), categories)
        && (!booms.length || booms.includes(card.dataset.boom || ''))
        && specFilters.every((filterName) => matchesSpec(card, filterName));
    };
    const setFlexVisible = (element, visible) => {
      element?.classList.toggle('hidden', !visible);
      element?.classList.toggle('flex', visible);
    };
    // Desktop filter bar state: pressed tabs, the open category's sub-tabs, per-dropdown counts and the chips.
    // The same range in the phone filter drawer, which has no chips row.
    const customCapacityBox = qs('[data-filter-custom-capacity]', root);
    const syncCustomCapacityChip = () => {
      if (!customCapacityBox) return;
      const shown = !!customCapacity && !activeValues('capacity').length;
      customCapacityBox.classList.toggle('hidden', !shown);
      const title = qs('[data-filter-group="capacity"] legend', root)?.textContent.trim() || '';
      const label = qs('[data-filter-custom-capacity-label]', customCapacityBox);
      if (label) label.textContent = shown ? `${title}: ${customCapacity.replace('-', '–')} t` : '';
    };
    qs('[data-filter-custom-capacity-clear]', root)?.addEventListener('click', () => {
      customCapacity = '';
      updateUrlFromFilters();
      apply({ resetPage: true });
    });
    const updateFilterBar = () => {
      if (!filterBar) return;
      const selected = activeCategoryDisplayValues();
      const parents = new Set(selected.map((value) => childParent.get(value) || value));
      categoryTabs.forEach((tab) => {
        const value = tab.dataset.categoryTab || '';
        const active = tab.closest('[data-category-subtabs]') ? selected.includes(value) : (value === '' ? selected.length === 0 : parents.has(value));
        tab.setAttribute('aria-pressed', String(active));
      });
      categorySubtabRows.forEach((row) => setFlexVisible(row, parents.size === 1 && parents.has(row.dataset.categorySubtabs || '')));
      filterDropdowns.forEach((dropdown) => {
        const badge = qs('[data-filter-dropdown-count]', dropdown);
        const checked = qsa('input[data-filter]:checked', dropdown).length;
        if (badge) badge.textContent = String(checked);
        badge?.classList.toggle('hidden', !checked);
        badge?.classList.toggle('inline-flex', !!checked);
      });
      setFlexVisible(qs('[data-filter-dropdowns]', filterBar), filterDropdowns.some((dropdown) => !dropdown.classList.contains('hidden')));
      if (!filterChips) return;
      const chips = filterDropdowns.flatMap((dropdown) => qsa('input[data-filter]:checked', dropdown).map((input) => {
        const label = `${dropdown.dataset.filterTitle || ''}: ${qs('[data-filter-label]', input.closest('label'))?.textContent.trim() || input.value}`;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'boblift-filter-chip';
        chip.setAttribute('aria-label', productArchiveText('removeFilterLabel', 'Remove {label}').replace('{label}', label));
        chip.append(Object.assign(document.createElement('span'), { textContent: label }));
        if (filterChipIcon) chip.append(filterChipIcon.content.cloneNode(true));
        chip.addEventListener('click', () => {
          input.checked = false;
          input.dispatchEvent(new Event('change'));
        });
        return chip;
      }));
      syncCustomCapacityChip();
      if (customCapacity && !activeValues('capacity').length) {
        const title = qs('[data-filter-dropdown][data-filter-group="capacity"]', root)?.dataset.filterTitle || '';
        const label = `${title}: ${customCapacity.replace('-', '–')} t`;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'boblift-filter-chip';
        chip.setAttribute('aria-label', productArchiveText('removeFilterLabel', 'Remove {label}').replace('{label}', label));
        chip.append(Object.assign(document.createElement('span'), { textContent: label }));
        if (filterChipIcon) chip.append(filterChipIcon.content.cloneNode(true));
        chip.addEventListener('click', () => {
          customCapacity = '';
          updateUrlFromFilters();
          apply({ resetPage: true });
        });
        chips.unshift(chip);
      }
      filterChips.replaceChildren(...chips);
      setFlexVisible(filterChipsRow, chips.length > 0);
    };
    const setFilterDropdownOpen = (dropdown, open) => {
      qs('[data-filter-dropdown-panel]', dropdown)?.classList.toggle('hidden', !open);
      qs('[data-filter-dropdown-trigger]', dropdown)?.setAttribute('aria-expanded', String(open));
    };
    const selectCategory = (value) => {
      categoryInputs().forEach((input) => { input.checked = false; });
      if (value) {
        setCategoryChecked(value, true);
        if (childParent.has(value)) setCategoryChecked(childParent.get(value), true);
      }
      syncAllCategoryScopes();
      updateFilterAvailability();
      updateUrlFromFilters({ replace: false });
      apply({ resetPage: true });
    };
    const getSortMode = () => sortTrigger?.dataset.sortValue || 'recommended';
    const cardOrder = new Map(cards.map((card, index) => [card, index]));
    const sortedCards = () => [...cards].sort((a, b) => {
      const av = Number(a.dataset.capacity || 0);
      const bv = Number(b.dataset.capacity || 0);
      const ap = Number(a.dataset.popularity || 0);
      const bp = Number(b.dataset.popularity || 0);
      const af = Number(a.dataset.featured || 0);
      const bf = Number(b.dataset.featured || 0);
      const ad = Date.parse(a.dataset.created || '') || 0;
      const bd = Date.parse(b.dataset.created || '') || 0;
      const mode = getSortMode();
      if (mode === 'capacityAsc') return (av > 0 ? av : Number.MAX_SAFE_INTEGER) - (bv > 0 ? bv : Number.MAX_SAFE_INTEGER);
      if (mode === 'capacityDesc') return (bv > 0 ? bv : -1) - (av > 0 ? av : -1);
      if (mode === 'popular') return bp - ap;
      if (mode === 'newest') return bd - ad;
      // Recommended keeps the server order: featured, then families in tab order, largest first.
      return bf - af || cardOrder.get(a) - cardOrder.get(b);
    });
    const renderPagination = (totalPages) => {
      if (!pagination) return;
      pagination.replaceChildren();
      pagination.classList.toggle('hidden', totalPages <= 1);
      if (totalPages <= 1) return;
      const makeButton = (label, page, active = false, disabled = false, arrow = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.disabled = disabled;
        if (arrow) {
          // Previous and Next shrink to an arrow on phones so the whole pager fits one row.
          button.setAttribute('aria-label', label);
          const text = document.createElement('span');
          text.className = 'hidden sm:inline';
          text.textContent = label;
          const icon = document.createElement('span');
          icon.className = 'inline-block text-lg leading-none rtl:-scale-x-100 sm:hidden';
          icon.dir = 'ltr';
          icon.setAttribute('aria-hidden', 'true');
          icon.textContent = arrow;
          button.append(text, icon);
        } else {
          button.textContent = label;
        }
        button.className = active
          ? 'h-11 min-w-11 rounded-sm bg-[#0B1B33] px-4 text-sm font-black text-white transition'
          : (arrow
            ? 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-3 text-sm font-black text-[#0B1B33] transition hover:border-blue-300 disabled:cursor-not-allowed disabled:opacity-40 sm:px-5'
            : 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-4 text-sm font-black text-[#0B1B33] transition hover:border-blue-300');
        button.addEventListener('click', () => changePage(page));
        if (active) button.setAttribute('aria-current', 'page');
        return button;
      };
      pagination.appendChild(makeButton(productArchiveText('previousLabel', 'Previous'), currentPage - 1, false, currentPage === 1, '\u2039'));
      pagerPageNumbers(totalPages, currentPage).forEach((page) => {
        if (page === null) {
          const ellipsis = document.createElement('span');
          ellipsis.className = 'inline-flex h-11 min-w-6 items-center justify-center text-sm font-black text-slate-400';
          ellipsis.setAttribute('aria-hidden', 'true');
          ellipsis.textContent = '\u2026';
          pagination.appendChild(ellipsis);
          return;
        }
        pagination.appendChild(makeButton(String(page), page, page === currentPage));
      });
      pagination.appendChild(makeButton(productArchiveText('nextLabel', 'Next'), currentPage + 1, false, currentPage === totalPages, '\u203A'));
    };
    const apply = ({ resetPage = false, scroll = false } = {}) => {
      updateCategoryChildrenVisibility();
      updateFilterAvailability();
      updateFilterBar();
      if (resetPage) currentPage = 1;
      const ordered = sortedCards();
      const filtered = ordered.filter(cardMatches);
      const perPage = productsPerPage();
      const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
      if (currentPage > totalPages) currentPage = totalPages;
      const start = (currentPage - 1) * perPage;
      const shown = filtered.slice(start, start + perPage);
      ordered.forEach((card) => grid?.appendChild(card));
      cards.forEach((card) => card.classList.add('hidden'));
      shown.forEach((card) => card.classList.remove('hidden'));
      if (count) count.textContent = String(shown.length);
      if (total) total.textContent = String(filtered.length);
      updateBanner();
      if (empty) {
        empty.classList.toggle('hidden', filtered.length !== 0);
        empty.classList.toggle('flex', filtered.length === 0);
      }
      if (grid) grid.classList.toggle('hidden', filtered.length === 0);
      renderPagination(filtered.length > 0 ? totalPages : 1);
      if (pageStatusText) pageStatusText.textContent = productArchiveText('pageTemplate', 'Page {page} / {pages}').replace('{page}', String(currentPage)).replace('{pages}', String(totalPages));
      setFlexVisible(pageStatus, filtered.length > 0 && totalPages > 1);
      // A new page starts at the count and sort row, so the reader sees where they are.
      if (scroll) (toolbar || results)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    const changePage = (page) => {
      const filteredCount = sortedCards().filter(cardMatches).length;
      const totalPages = Math.max(1, Math.ceil(filteredCount / productsPerPage()));
      const nextPage = Math.min(Math.max(page, 1), totalPages);
      if (nextPage === currentPage) return;
      currentPage = nextPage;
      apply({ scroll: true });
    };
    pagerWide.addEventListener('change', () => {
      const filteredCount = sortedCards().filter(cardMatches).length;
      renderPagination(filteredCount > 0 ? Math.ceil(filteredCount / productsPerPage()) : 1);
    });
    const setSortMenuOpen = (open) => {
      if (!sortMenu || !sortTrigger) return;
      sortMenu.classList.toggle('hidden', !open);
      sortTrigger.setAttribute('aria-expanded', String(open));
      qs('svg', sortTrigger)?.classList.toggle('rotate-180', open);
    };
    const setSortMode = (mode, label) => {
      if (sortTrigger) sortTrigger.dataset.sortValue = mode;
      if (sortLabel) sortLabel.textContent = label;
      sortOptions.forEach((option) => {
        const selected = option.dataset.sortOption === mode;
        const check = qs('[data-sort-check]', option);
        option.setAttribute('aria-selected', String(selected));
        option.classList.toggle('bg-blue-50', selected);
        option.classList.toggle('text-blue-700', selected);
        option.classList.toggle('text-[#0B1B33]', !selected);
        option.classList.toggle('hover:bg-[#F8FAFC]', !selected);
        check?.classList.toggle('hidden', !selected);
        check?.classList.toggle('inline-flex', selected);
      });
      setSortMenuOpen(false);
      apply({ resetPage: true });
    };
    const syncFilterDrawerOffset = () => {
      if (!drawer) return;
      const header = document.getElementById('top');
      const headerBottom = header ? Math.max(0, Math.round(header.getBoundingClientRect().bottom)) : 0;
      drawer.style.setProperty('--boblift-filter-drawer-top', `${headerBottom}px`);
    };
    const isMobileFilterDrawerActive = (element = null) => !!(drawer
      && !drawer.classList.contains('hidden')
      && window.matchMedia('(max-width: 1023px)').matches
      && (!element || drawer.contains(element)));
    const captureFilterState = () => ({
      checks: checks.map((input) => input.checked),
      capacity: capacitySelect ? capacitySelect.value : '',
      currentCategory: root.dataset.currentCategory || '',
    });
    const restoreFilterState = (state) => {
      if (!state) return;
      checks.forEach((input, index) => { input.checked = !!state.checks[index]; });
      if (capacitySelect) capacitySelect.value = state.capacity || '';
      root.dataset.currentCategory = state.currentCategory || '';
      syncAllCategoryScopes();
      updateCategoryChildrenVisibility();
      updateFilterAvailability();
    };
    let filterDrawerScrollY = 0;
    let filterDrawerSnapshot = null;
    let filterDrawerCommitted = false;
    const lockFilterDrawerPage = () => {
      filterDrawerScrollY = window.scrollY || document.documentElement.scrollTop || 0;
      document.documentElement.classList.add('boblift-filter-drawer-locked');
      document.body.classList.add('boblift-filter-drawer-locked');
      document.body.style.position = 'fixed';
      document.body.style.top = `-${filterDrawerScrollY}px`;
      document.body.style.left = '0';
      document.body.style.right = '0';
      document.body.style.width = '100%';
    };
    const unlockFilterDrawerPage = () => {
      document.documentElement.classList.remove('boblift-filter-drawer-locked');
      document.body.classList.remove('boblift-filter-drawer-locked');
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.left = '';
      document.body.style.right = '';
      document.body.style.width = '';
      window.scrollTo(0, filterDrawerScrollY);
    };
    const openDrawer = () => {
      if (!drawer) return;
      filterDrawerSnapshot = captureFilterState();
      filterDrawerCommitted = false;
      syncFilterDrawerOffset();
      drawer.classList.remove('hidden');
      lockFilterDrawerPage();
    };
    const closeDrawer = ({ restore = true } = {}) => {
      if (!drawer) return;
      const shouldRestore = restore && !filterDrawerCommitted && window.matchMedia('(max-width: 1023px)').matches;
      drawer.classList.add('hidden');
      if (shouldRestore) {
        restoreFilterState(filterDrawerSnapshot);
        apply({ resetPage: true });
      }
      filterDrawerSnapshot = null;
      filterDrawerCommitted = false;
      unlockFilterDrawerPage();
    };
    const applyMobileDrawerFilters = () => {
      if (!drawer) return;
      filterDrawerCommitted = true;
      syncAllCategoryScopes();
      updateFilterAvailability();
      updateUrlFromFilters({ replace: false });
      apply({ resetPage: true });
      closeDrawer({ restore: false });
    };
    const specMarkup = (specs) => specs.slice(0, 6).map((spec) => `<div class="min-w-0 border-b border-r border-[#E5E7EB] px-3 py-3 md:px-4"><dt class="text-[11px] font-normal leading-4 text-slate-500">${escapeHtml(spec.label || '')}</dt><dd class="mt-1 break-words text-base font-black leading-tight text-[#0B1B33] [font-variant-numeric:tabular-nums]">${escapeHtml(String(spec.value || '-'))}</dd></div>`).join('');
    const openQuickView = (card) => {
      if (!quickModal || !card) return;
      activeQuickCard = card;
      const specs = (() => {
        try {
          const parsed = JSON.parse(card.dataset.specs || '[]');
          return Array.isArray(parsed) ? parsed : [];
        } catch {
          return [];
        }
      })();
      if (quickImage) {
        quickImage.src = card.dataset.image || '';
        quickImage.alt = card.dataset.title || '';
      }
      if (quickTitle) quickTitle.textContent = card.dataset.title || '';
      if (quickCategory) quickCategory.textContent = card.dataset.categoryTitle || 'BOB-LIFT';
      if (quickSummary) quickSummary.textContent = card.dataset.summary || '';
      if (quickInquiry) {
        quickInquiry.dataset.inquiryUrl = card.dataset.inquiryUrl || '';
        quickInquiry.dataset.inquiryProduct = card.dataset.title || '';
        quickInquiry.dataset.inquiryProductSlug = card.dataset.productSlug || '';
        quickInquiry.dataset.inquiryProducts = JSON.stringify([{
          title: card.dataset.title || '',
          slug: card.dataset.productSlug || '',
          image: card.dataset.image || '',
          category: card.dataset.categoryTitle || card.dataset.category || 'BOB-LIFT',
          detailUrl: card.dataset.detailUrl || '',
        }]);
      }
      if (quickDetail) quickDetail.href = card.dataset.detailUrl || root.dataset.productArchiveUrl || '/';
      if (quickSpecs) quickSpecs.innerHTML = specMarkup(specs);
      quickModal.classList.remove('hidden');
      quickModal.classList.add('flex');
      document.body.style.overflow = 'hidden';
      syncFavoriteStates();
    };
    const closeQuickView = () => {
      if (!quickModal) return;
      quickModal.classList.add('hidden');
      quickModal.classList.remove('flex');
      activeQuickCard = null;
      document.body.style.overflow = '';
    };
    function escapeHtml(value) {
      const span = document.createElement('span');
      span.textContent = value;
      return span.innerHTML;
    }

    qsa('[data-filter-drawer-open]', root).forEach((button) => button.addEventListener('click', openDrawer));
    qsa('[data-filter-drawer-close]', root).forEach((button) => button.addEventListener('click', () => closeDrawer()));
    applyFilterButtons.forEach((button) => button.addEventListener('click', applyMobileDrawerFilters));
    drawer?.addEventListener('click', (event) => { if (event.target === drawer) closeDrawer(); });
    sortTrigger?.addEventListener('click', () => setSortMenuOpen(sortMenu?.classList.contains('hidden') ?? true));
    sortOptions.forEach((button) => button.addEventListener('click', () => setSortMode(button.dataset.sortOption || 'recommended', button.textContent.trim())));
    document.addEventListener('pointerdown', (event) => {
      if (sortDropdown && !sortDropdown.contains(event.target)) setSortMenuOpen(false);
      filterDropdowns.forEach((dropdown) => { if (!dropdown.contains(event.target)) setFilterDropdownOpen(dropdown, false); });
    });
    categoryTabs.forEach((tab) => tab.addEventListener('click', () => selectCategory(tab.dataset.categoryTab || '')));
    filterDropdowns.forEach((dropdown) => qs('[data-filter-dropdown-trigger]', dropdown)?.addEventListener('click', () => {
      const open = qs('[data-filter-dropdown-panel]', dropdown)?.classList.contains('hidden') ?? false;
      filterDropdowns.forEach((item) => setFilterDropdownOpen(item, item === dropdown && open));
    }));
    qsa('[data-filter-clear-specs]', root).forEach((button) => button.addEventListener('click', () => {
      customCapacity = '';
      checks.filter((input) => input.dataset.filter !== 'category').forEach((input) => { input.checked = false; });
      updateUrlFromFilters({ replace: false });
      apply({ resetPage: true });
    }));
    qsa('[data-view-toggle]', root).forEach((button) => button.addEventListener('click', () => {
      const view = button.dataset.viewToggle || 'grid';
      root.dataset.view = view;
      qsa('[data-view-toggle]', root).forEach((item) => {
        const active = item === button;
        item.setAttribute('aria-pressed', String(active));
        item.classList.toggle('bg-[#0B1B33]', active);
        item.classList.toggle('text-white', active);
        item.classList.toggle('text-[#0B1B33]', !active);
        item.classList.toggle('hover:bg-slate-100', !active);
      });
      apply({ resetPage: true });
    }));
    cards.forEach((card) => {
      card.addEventListener('click', (event) => {
        if (event.target.closest('a, button, input, select, textarea, label')) return;
        const detailUrl = card.dataset.detailUrl || '';
        if (detailUrl) window.location.href = detailUrl;
      });
      qsa('[data-product-favorite]', card).forEach((button) => button.addEventListener('click', () => toggleFavorite(card)));
      qsa('[data-product-quick-view]', card).forEach((button) => button.addEventListener('click', () => openQuickView(card)));
    });
    quickFavorite?.addEventListener('click', () => toggleFavorite(activeQuickCard));
    qsa('[data-quick-close]', root).forEach((button) => button.addEventListener('click', closeQuickView));
    quickModal?.addEventListener('mousedown', (event) => { if (event.target === quickModal) closeQuickView(); });
    quickPanel?.addEventListener('mousedown', (event) => event.stopPropagation());
    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setSortMenuOpen(false);
        filterDropdowns.forEach((dropdown) => setFilterDropdownOpen(dropdown, false));
        closeQuickView();
      }
    });
    checks.forEach((input) => input.addEventListener('change', () => {
      if (input.dataset.filter === 'capacity') customCapacity = '';
      syncMatchingFilterInputs(input);
      syncCategoryScope(input);
      if (input.dataset.filter === 'category') updateFilterAvailability();
      if (isMobileFilterDrawerActive(input)) {
        updateCategoryChildrenVisibility();
        return;
      }
      updateUrlFromFilters();
      apply({ resetPage: true });
    }));
    capacitySelect?.addEventListener('change', () => {
      if (isMobileFilterDrawerActive(capacitySelect)) return;
      updateUrlFromFilters();
      apply({ resetPage: true });
    });
    resets.forEach((button) => button.addEventListener('click', () => {
      customCapacity = '';
      checks.forEach((input) => { input.checked = false; });
      if (capacitySelect) capacitySelect.value = '';
      root.dataset.currentCategory = '';
      syncAllCategoryScopes();
      updateCategoryChildrenVisibility();
      updateFilterAvailability();
      if (isMobileFilterDrawerActive(button)) return;
      updateUrlFromFilters({ replace: false });
      apply({ resetPage: true });
    }));
    window.addEventListener('popstate', syncFiltersFromUrl);
    window.addEventListener('hashchange', syncFiltersFromUrl);
    window.addEventListener('resize', () => {
      apply({ resetPage: true });
      if (drawer && !drawer.classList.contains('hidden')) syncFilterDrawerOffset();
    });
    syncFavoriteStates();
    syncFiltersFromUrl();
  });

  // Previous models: a tab that toggles on its own beside the category tabs. While on, the selected category's previous models replace the
  // grid; changing the category keeps it on and rescopes the list, and a category without any shows a request form
  // instead. ?models=previous turns it on directly.
  qsa('[data-product-listing]').forEach((root) => {
    const panel = qs('[data-legacy-panel]', root);
    if (!panel) return;
    const groups = qsa('[data-legacy-group]', panel);
    const entries = qsa('[data-legacy-entry]', root);
    const counts = qsa('[data-legacy-count]', root);
    const toggle = qs('[data-legacy-toggle]', root);
    const empty = qs('[data-legacy-empty]', panel);
    const groupList = qs('[data-legacy-groups]', panel);
    const scopeTitle = qs('[data-legacy-scope-title]', panel);
    const isOpen = () => root.hasAttribute('data-legacy-view');
    // Scoped by the ticked categories (the tabs tick them too) and their sub-categories; none ticked is All Products.
    const scopedGroups = () => {
      const checked = qsa('input[data-filter="category"]:checked', root);
      if (!checked.length) return groups;
      // A ticked sub-category also ticks its parent (selectCategory()); then the parent stands for that child only,
      // not for all its siblings, as in the product listing.
      const chosenParents = new Set(checked.filter((input) => input.dataset.categoryRole === 'child').map((input) => input.dataset.categoryParent || ''));
      const slugs = new Set(checked.flatMap((input) => (input.dataset.categoryRole === 'parent' && chosenParents.has(input.value))
        ? []
        : [input.value, ...(input.dataset.categoryChildren || '').split(',').filter(Boolean)]));
      return groups.filter((group) => slugs.has(group.dataset.legacyGroup || ''));
    };
    const syncScope = () => {
      const scoped = scopedGroups();
      const total = scoped.reduce((sum, group) => sum + Number(group.dataset.legacySize || 0), 0);
      counts.forEach((count) => { count.textContent = String(total); });
      // The entries stay even at zero: the panel then offers a request form, and on phones the entry is the only way in.
      groups.forEach((group) => { group.hidden = !scoped.includes(group); });
      empty?.classList.toggle('hidden', total > 0);
      empty?.classList.toggle('flex', total === 0);
      groupList?.classList.toggle('hidden', total === 0);
      const current = root.dataset.currentCategory || '';
      const tab = current ? qs(`[data-category-tab="${CSS.escape(current)}"]`, root) : null;
      const label = tab ? [...tab.childNodes].filter((node) => node.nodeType === Node.TEXT_NODE).map((node) => node.textContent).join('').trim() : '';
      if (scopeTitle) {
        scopeTitle.textContent = label ? ` · ${label}` : '';
        scopeTitle.hidden = !label;
      }
    };
    const setUrl = (open) => {
      const url = new URL(window.location.href);
      if (open) url.searchParams.set('models', 'previous');
      else url.searchParams.delete('models');
      window.history.replaceState(window.history.state, '', url);
    };
    const setOpen = (open, { scroll = true } = {}) => {
      syncScope();
      root.toggleAttribute('data-legacy-view', open);
      toggle?.setAttribute('aria-pressed', String(open));
      setUrl(open);
      if (!scroll) return;
      const bar = qs('[data-filter-bar]', root);
      (open ? (bar?.offsetParent ? bar : panel) : (qs('[data-products-toolbar]', root) || root)).scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    toggle?.addEventListener('click', () => setOpen(!isOpen()));
    qsa('[data-legacy-open]', root).forEach((button) => button.addEventListener('click', () => setOpen(true)));
    qsa('[data-legacy-close]', panel).forEach((button) => button.addEventListener('click', () => setOpen(false)));
    // The listing script settles the category (path, query or hash) first; the scope follows it.
    new MutationObserver(() => {
      syncScope();
      if (isOpen()) setUrl(true);
    }).observe(root, { attributes: true, attributeFilter: ['data-current-category'] });
    syncScope();
    // The listing script rewrites the address when the category changes; keep the switch in it.
    window.addEventListener('popstate', () => setOpen(new URLSearchParams(window.location.search).get('models') === 'previous', { scroll: false }));
    if (new URLSearchParams(window.location.search).get('models') === 'previous') setOpen(true);
  });

  qsa('[data-news-filter-root]').forEach((root) => {
    const buttons = qsa('[data-news-filter-button]', root);
    const viewButtons = qsa('[data-news-view-button]', root);
    const cards = qsa('[data-news-card]', root);
    const grid = qs('[data-news-grid]', root);
    const empty = qs('[data-news-empty]', root);
    const pagination = qs('[data-news-pagination]', root);
    const perPage = Number.parseInt(root.dataset.newsMaxVisible || '9', 10) || 9;
    const urlPage = Number.parseInt(new URLSearchParams(window.location.search).get('news_page') || '1', 10);
    let currentPage = Number.isFinite(urlPage) && urlPage > 0 ? urlPage : 1;
    const allowedCategories = buttons.map((button) => button.value || '');
    const urlCategory = new URLSearchParams(window.location.search).get('news_category') || '';
    let activeCategory = allowedCategories.includes(urlCategory) ? urlCategory : (root.dataset.newsInitialCategory || '');
    if (!allowedCategories.includes(activeCategory)) activeCategory = '';
    let activeView = 'grid';

    try {
      activeView = window.localStorage.getItem('bobliftNewsView') === 'list' ? 'list' : 'grid';
    } catch {
      activeView = 'grid';
    }

    const updateNewsUrl = (category) => {
      const url = new URL(window.location.href);
      if (category) {
        url.searchParams.set('news_category', category);
      } else {
        url.searchParams.delete('news_category');
      }
      if (currentPage > 1) {
        url.searchParams.set('news_page', String(currentPage));
      } else {
        url.searchParams.delete('news_page');
      }
      window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
    };

    const newsPagerWide = pagerWide;
    const newsPageNumbers = (totalPages) => pagerPageNumbers(totalPages, currentPage);

    const renderNewsPagination = (totalPages) => {
      if (!pagination) return;
      pagination.replaceChildren();
      pagination.classList.toggle('hidden', totalPages <= 1);
      if (totalPages <= 1) return;
      const makeButton = (label, page, active = false, disabled = false, arrow = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.disabled = disabled;
        if (arrow) {
          // Previous and Next shrink to an arrow on phones so the whole pager fits one row.
          button.setAttribute('aria-label', label);
          const text = document.createElement('span');
          text.className = 'hidden sm:inline';
          text.textContent = label;
          const icon = document.createElement('span');
          // Kept left-to-right so the browser never mirrors the glyph itself; the rtl: flip then points it the right way.
          icon.className = 'inline-block text-lg leading-none rtl:-scale-x-100 sm:hidden';
          icon.dir = 'ltr';
          icon.setAttribute('aria-hidden', 'true');
          icon.textContent = arrow;
          button.append(text, icon);
        } else {
          button.textContent = label;
        }
        button.className = active
          ? 'h-11 min-w-11 rounded-sm bg-[#0B1B33] px-4 text-sm font-black text-white transition'
          : (arrow
            ? 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-3 text-sm font-black text-[#0B1B33] transition hover:border-blue-300 disabled:cursor-not-allowed disabled:opacity-40 sm:px-5'
            : 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-4 text-sm font-black text-[#0B1B33] transition hover:border-blue-300');
        button.addEventListener('click', () => {
          applyNewsFilter(activeCategory, { page });
          // The pager is rebuilt, so move focus to the first article of the new page.
          cards.find((card) => !card.classList.contains('hidden'))?.focus({ preventScroll: true });
          root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        if (active) button.setAttribute('aria-current', 'page');
        return button;
      };
      pagination.appendChild(makeButton(pagination.dataset.previousLabel || 'Previous', currentPage - 1, false, currentPage === 1, '\u2039'));
      newsPageNumbers(totalPages).forEach((page) => {
        if (page === null) {
          const ellipsis = document.createElement('span');
          ellipsis.className = 'inline-flex h-11 min-w-6 items-center justify-center text-sm font-black text-slate-400';
          ellipsis.setAttribute('aria-hidden', 'true');
          ellipsis.textContent = '\u2026';
          pagination.appendChild(ellipsis);
          return;
        }
        pagination.appendChild(makeButton(String(page), page, page === currentPage));
      });
      pagination.appendChild(makeButton(pagination.dataset.nextLabel || 'Next', currentPage + 1, false, currentPage === totalPages, '\u203A'));
    };
    newsPagerWide.addEventListener('change', () => applyNewsFilter(activeCategory, { page: currentPage, updateUrl: false }));

    const applyNewsFilter = (category, options = {}) => {
      activeCategory = allowedCategories.includes(category) ? category : '';
      const matching = cards.filter((card) => !activeCategory || card.dataset.newsCategory === activeCategory);
      const totalPages = Math.max(1, Math.ceil(matching.length / perPage));
      currentPage = Math.min(Math.max(1, options.page || 1), totalPages);
      const start = (currentPage - 1) * perPage;
      const pageCards = new Set(matching.slice(start, start + perPage));
      const shown = pageCards.size;
      cards.forEach((card) => card.classList.toggle('hidden', !pageCards.has(card)));
      renderNewsPagination(totalPages);
      buttons.forEach((button) => {
        const active = (button.value || '') === activeCategory;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
      if (empty) empty.classList.toggle('hidden', shown !== 0);
      if (grid) grid.classList.toggle('hidden', shown === 0);
      if (options.updateUrl !== false) updateNewsUrl(activeCategory);
    };

    const applyNewsView = (view) => {
      activeView = view === 'list' ? 'list' : 'grid';
      root.classList.toggle('is-list-view', activeView === 'list');
      root.classList.toggle('is-grid-view', activeView !== 'list');
      viewButtons.forEach((button) => {
        const active = button.dataset.newsViewButton === activeView;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
      try {
        window.localStorage.setItem('bobliftNewsView', activeView);
      } catch {
      }
    };

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        applyNewsFilter(button.value || '');
      });
    });
    viewButtons.forEach((button) => {
      button.addEventListener('click', () => {
        applyNewsView(button.dataset.newsViewButton || 'grid');
      });
    });

    applyNewsView(activeView);
    applyNewsFilter(activeCategory, { updateUrl: false, page: currentPage });
  });

  qsa('[data-cases-root]').forEach((root) => {
    const tabButtons = qsa('[data-cases-tab-button]', root);
    const cards = qsa('[data-cases-card]', root);
    const countryFilter = qs('[data-cases-filter="country"]', root);
    const productFilter = qs('[data-cases-filter="product"]', root);
    const resets = qsa('[data-cases-reset]', root);
    const empty = qs('[data-cases-empty]', root);
    const grid = qs('[data-cases-grid]', root);
    const modal = qs('[data-cases-modal]', root);
    const modalTitle = qs('[data-cases-modal-title]', root);
    const modalMedia = qs('[data-cases-modal-media]', root);
    const mediaButtons = qsa('[data-cases-media-open]', root);
    const closeButtons = qsa('[data-cases-modal-close]', root);
    const customSelects = qsa('[data-cases-custom-select]', root);
    const modalNav = qs('[data-cases-modal-nav]', root);
    const modalCounter = qs('[data-cases-modal-counter]', root);
    const modalPrev = qs('[data-cases-modal-prev]', root);
    const modalNext = qs('[data-cases-modal-next]', root);
    // Feedback cards carry several photos and videos; the modal steps through them.
    let gallery = [];
    let galleryIndex = 0;
    let galleryTitle = '';
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    let activeKind = urlTab === 'customer_feedback' ? 'customer_feedback' : 'scene_case';
    // 101 feedback entries made the page ~26k px tall: the grid shows one page at a time.
    const casesPerPage = 12;
    let casesPage = 1;
    const pager = qs('[data-cases-pager]', root);
    const summary = qs('[data-cases-summary]', root);

    const resetModalMedia = () => {
      if (!modalMedia) return;
      qsa('video', modalMedia).forEach((video) => {
        video.pause();
        video.removeAttribute('src');
        video.load();
      });
      modalMedia.innerHTML = '';
    };

    const setModalOpen = (open) => {
      if (!modal) return;
      modal.hidden = !open;
      modal.classList.toggle('hidden', !open);
      modal.classList.toggle('flex', open);
      document.body.style.overflow = open ? 'hidden' : '';
      if (!open) resetModalMedia();
    };

    const escapeCasesAttr = (value) => String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;');

    const youtubeEmbedUrl = (url) => {
      try {
        const parsed = new URL(url, window.location.href);
        let host = parsed.hostname.toLowerCase().replace(/^(www|m|music)\./, '');
        let videoId = '';
        if (host === 'youtu.be') {
          videoId = parsed.pathname.split('/').filter(Boolean)[0] || '';
        } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
          videoId = parsed.searchParams.get('v') || '';
          if (!videoId) {
            const parts = parsed.pathname.split('/').filter(Boolean);
            if (['embed', 'shorts', 'live'].includes(parts[0])) videoId = parts[1] || '';
          }
        }
        return /^[A-Za-z0-9_-]{6,}$/.test(videoId)
          ? `https://www.youtube.com/embed/${encodeURIComponent(videoId)}?rel=0&modestbranding=1&playsinline=1&autoplay=1`
          : '';
      } catch {
        return '';
      }
    };

    const showGalleryItem = (index) => {
      if (!gallery.length || !modalMedia) return;
      galleryIndex = (index + gallery.length) % gallery.length;
      const item = gallery[galleryIndex] || {};
      resetModalMedia();
      if (item.type === 'video') {
        modalMedia.innerHTML = `<video class="h-full max-h-[72vh] w-full rounded bg-black object-contain" src="${escapeCasesAttr(item.src || '')}" poster="${escapeCasesAttr(item.poster || '')}" controls muted playsinline autoplay preload="metadata"></video>`;
      } else {
        modalMedia.innerHTML = `<img class="h-full max-h-[72vh] w-full rounded object-contain" src="${escapeCasesAttr(item.src || '')}" alt="${escapeCasesAttr(galleryTitle)}">`;
      }
      if (modalCounter) modalCounter.textContent = `${galleryIndex + 1} / ${gallery.length}`;
      if (modalNav) {
        modalNav.classList.toggle('hidden', gallery.length < 2);
        modalNav.classList.toggle('flex', gallery.length > 1);
      }
      // Warm the next photo so stepping feels instant.
      const next = gallery[(galleryIndex + 1) % gallery.length];
      if (next && next.type === 'image') {
        const preload = new Image();
        preload.src = next.src;
      }
    };

    const openCasesModal = (button) => {
      if (!button || !modalMedia) return;
      if (button.dataset.mediaList) {
        try {
          gallery = JSON.parse(button.dataset.mediaList) || [];
        } catch {
          gallery = [];
        }
        if (!gallery.length) return;
        galleryTitle = button.dataset.mediaTitle || '';
        if (modalTitle) modalTitle.textContent = galleryTitle;
        showGalleryItem(0);
        setModalOpen(true);
        return;
      }
      gallery = [];
      if (modalNav) {
        modalNav.classList.add('hidden');
        modalNav.classList.remove('flex');
      }
      const mediaType = button.dataset.mediaType === 'video' ? 'video' : 'image';
      const mediaSrc = button.dataset.mediaSrc || '';
      const title = button.dataset.mediaTitle || '';
      if (!mediaSrc) return;
      resetModalMedia();
      if (modalTitle) modalTitle.textContent = title;
      const youtubeSrc = mediaType === 'video' ? youtubeEmbedUrl(mediaSrc) : '';
      if (youtubeSrc) {
        modalMedia.innerHTML = `<iframe class="h-[72vh] min-h-[260px] w-full rounded bg-black" src="${escapeCasesAttr(youtubeSrc)}" title="${escapeCasesAttr(title)}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;
      } else if (mediaType === 'video') {
        modalMedia.innerHTML = `<video class="h-full max-h-[78vh] w-full rounded bg-black object-contain" src="${escapeCasesAttr(mediaSrc)}" controls playsinline preload="metadata"></video>`;
      } else {
        modalMedia.innerHTML = `<img class="h-full max-h-[78vh] w-full rounded object-contain" src="${escapeCasesAttr(mediaSrc)}" alt="${escapeCasesAttr(title)}">`;
      }
      setModalOpen(true);
    };

    // Same pager as the product archive: shared page numbers, arrows only on phones.
    const renderCasesPager = (totalPages) => {
      if (!pager) return;
      pager.innerHTML = '';
      pager.classList.toggle('hidden', totalPages <= 1);
      if (totalPages <= 1) return;
      const makeButton = (label, page, active = false, disabled = false, arrow = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.disabled = disabled;
        button.dataset.casesPage = String(page);
        if (arrow) {
          button.setAttribute('aria-label', label);
          const text = document.createElement('span');
          text.className = 'hidden sm:inline';
          text.textContent = label;
          const icon = document.createElement('span');
          icon.className = 'inline-block text-lg leading-none rtl:-scale-x-100 sm:hidden';
          icon.dir = 'ltr';
          icon.setAttribute('aria-hidden', 'true');
          icon.textContent = arrow;
          button.append(text, icon);
        } else {
          button.textContent = label;
        }
        button.className = active
          ? 'h-11 min-w-11 rounded-sm bg-[#0B1B33] px-4 text-sm font-black text-white transition'
          : (arrow
            ? 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-3 text-sm font-black text-[#0B1B33] transition hover:border-blue-300 disabled:cursor-not-allowed disabled:opacity-40 sm:px-5'
            : 'h-11 min-w-11 rounded-sm border border-[#D8DEE8] bg-white px-4 text-sm font-black text-[#0B1B33] transition hover:border-blue-300');
        if (active) button.setAttribute('aria-current', 'page');
        return button;
      };
      pager.appendChild(makeButton(pager.dataset.prevLabel || 'Previous', casesPage - 1, false, casesPage === 1, '\u2039'));
      pagerPageNumbers(totalPages, casesPage).forEach((page) => {
        if (page === null) {
          const ellipsis = document.createElement('span');
          ellipsis.className = 'inline-flex h-11 min-w-6 items-center justify-center text-sm font-black text-slate-400';
          ellipsis.setAttribute('aria-hidden', 'true');
          ellipsis.textContent = '\u2026';
          pager.appendChild(ellipsis);
          return;
        }
        pager.appendChild(makeButton(String(page), page, page === casesPage));
      });
      pager.appendChild(makeButton(pager.dataset.nextLabel || 'Next', casesPage + 1, false, casesPage === totalPages, '\u203A'));
    };

    const applyCasesFilter = () => {
      const country = countryFilter?.value || '';
      const product = productFilter?.value || '';
      const matches = cards.filter((card) => (card.dataset.caseKind || 'scene_case') === activeKind
        && (!country || card.dataset.country === country)
        && (!product || card.dataset.product === product));
      const visibleCount = matches.length;
      const totalPages = Math.max(1, Math.ceil(visibleCount / casesPerPage));
      casesPage = Math.min(Math.max(1, casesPage), totalPages);
      const from = (casesPage - 1) * casesPerPage;
      const pageCards = new Set(matches.slice(from, from + casesPerPage));
      cards.forEach((card) => card.classList.toggle('hidden', !pageCards.has(card)));
      root.dataset.activeKind = activeKind;
      if (summary) {
        summary.textContent = visibleCount > casesPerPage
          ? (summary.dataset.template || '{from}–{to} / {total}')
            .replace('{from}', String(from + 1))
            .replace('{to}', String(Math.min(from + casesPerPage, visibleCount)))
            .replace('{total}', String(visibleCount))
          : '';
      }
      renderCasesPager(totalPages);
      if (empty) empty.classList.toggle('hidden', visibleCount !== 0);
      if (grid) grid.classList.toggle('hidden', visibleCount === 0);
      tabButtons.forEach((button) => {
        const active = button.dataset.casesTabButton === activeKind;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
    };

    const setCasesSelectOpen = (selectRoot, open) => {
      const trigger = qs('[data-cases-custom-select-toggle]', selectRoot);
      const menu = qs('[data-cases-custom-select-menu]', selectRoot);
      if (!trigger || !menu) return;
      menu.classList.toggle('hidden', !open);
      selectRoot.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', String(open));
      if (open) {
        const search = qs('[data-cases-custom-search]', selectRoot);
        window.requestAnimationFrame(() => search?.focus());
      }
    };

    const closeCasesSelects = (exceptRoot = null) => {
      customSelects.forEach((selectRoot) => {
        if (selectRoot !== exceptRoot) setCasesSelectOpen(selectRoot, false);
      });
    };

    const syncCasesCustomSelect = (selectRoot) => {
      const nativeSelect = qs('[data-cases-filter]', selectRoot);
      const label = qs('[data-cases-custom-select-label]', selectRoot);
      const options = qsa('[data-cases-custom-option]', selectRoot);
      const value = nativeSelect?.value || '';
      const activeOption = options.find((option) => option.getAttribute('value') === value) || options[0];
      if (label && activeOption) label.textContent = activeOption.textContent.trim();
      options.forEach((option) => {
        const active = option === activeOption;
        option.classList.toggle('is-active', active);
        option.setAttribute('aria-selected', String(active));
      });
    };

    const filterCasesSelectOptions = (selectRoot) => {
      const search = qs('[data-cases-custom-search]', selectRoot);
      const empty = qs('[data-cases-custom-empty]', selectRoot);
      const options = qsa('[data-cases-custom-option]', selectRoot);
      const query = (search?.value || '').trim().toLowerCase();
      let matched = 0;
      options.forEach((option) => {
        const value = option.getAttribute('value') || '';
        const text = (option.dataset.optionText || option.textContent || '').toLowerCase();
        const visible = value === '' || query === '' || text.includes(query);
        option.classList.toggle('hidden', !visible);
        if (value !== '' && visible) matched += 1;
      });
      empty?.classList.toggle('hidden', query === '' || matched > 0);
    };

    customSelects.forEach((selectRoot) => {
      const trigger = qs('[data-cases-custom-select-toggle]', selectRoot);
      const nativeSelect = qs('[data-cases-filter]', selectRoot);
      const search = qs('[data-cases-custom-search]', selectRoot);
      const options = qsa('[data-cases-custom-option]', selectRoot);
      trigger?.addEventListener('click', () => {
        const nextOpen = qs('[data-cases-custom-select-menu]', selectRoot)?.classList.contains('hidden') ?? true;
        closeCasesSelects(selectRoot);
        if (nextOpen && search) {
          search.value = '';
          filterCasesSelectOptions(selectRoot);
        }
        setCasesSelectOpen(selectRoot, nextOpen);
      });
      search?.addEventListener('input', () => filterCasesSelectOptions(selectRoot));
      search?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          search.value = '';
          filterCasesSelectOptions(selectRoot);
          setCasesSelectOpen(selectRoot, false);
        }
      });
      options.forEach((option) => {
        option.addEventListener('click', () => {
          if (nativeSelect) {
            nativeSelect.value = option.getAttribute('value') || '';
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
          if (search) search.value = '';
          filterCasesSelectOptions(selectRoot);
          syncCasesCustomSelect(selectRoot);
          setCasesSelectOpen(selectRoot, false);
        });
      });
      nativeSelect?.addEventListener('change', () => syncCasesCustomSelect(selectRoot));
      syncCasesCustomSelect(selectRoot);
      filterCasesSelectOptions(selectRoot);
    });

    tabButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeKind = button.dataset.casesTabButton === 'customer_feedback' ? 'customer_feedback' : 'scene_case';
        casesPage = 1;
        applyCasesFilter();
      });
    });
    const resetCasesPage = () => {
      casesPage = 1;
      applyCasesFilter();
    };
    countryFilter?.addEventListener('change', resetCasesPage);
    productFilter?.addEventListener('change', resetCasesPage);
    pager?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-cases-page]');
      if (!button || button.disabled) return;
      casesPage = Number(button.dataset.casesPage) || 1;
      applyCasesFilter();
      // The pager is rebuilt, so keyboard focus moves to the new current page instead of being lost.
      pager.querySelector('[aria-current="page"]')?.focus({ preventScroll: true });
      // scrollIntoView honours html's scroll-padding-top (the fixed header height), so the controls land below the header.
      root.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });
    resets.forEach((button) => {
      button.addEventListener('click', () => {
        activeKind = 'scene_case';
        casesPage = 1;
        if (countryFilter) countryFilter.value = '';
        if (productFilter) productFilter.value = '';
        customSelects.forEach(syncCasesCustomSelect);
        closeCasesSelects();
        applyCasesFilter();
      });
    });
    mediaButtons.forEach((button) => button.addEventListener('click', () => openCasesModal(button)));
    closeButtons.forEach((button) => button.addEventListener('click', () => setModalOpen(false)));
    modalPrev?.addEventListener('click', () => showGalleryItem(galleryIndex - 1));
    modalNext?.addEventListener('click', () => showGalleryItem(galleryIndex + 1));
    document.addEventListener('pointerdown', (event) => {
      if (!customSelects.some((selectRoot) => selectRoot.contains(event.target))) closeCasesSelects();
    });
    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeCasesSelects();
      if (event.key === 'Escape' && modal && !modal.hidden) setModalOpen(false);
      if (modal && !modal.hidden && gallery.length > 1 && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
        showGalleryItem(galleryIndex + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
    applyCasesFilter();
  });
})();

// Customer feedback detail: one stage, thumbnails below, arrows, keys and swipe.
(() => {
  const init = () => document.querySelectorAll('[data-feedback-gallery]').forEach((root) => {
    let media = [];
    try {
      media = JSON.parse(root.dataset.feedbackMedia || '[]');
    } catch (error) {
      media = [];
    }
    if (media.length < 2) return;
    const stage = root.querySelector('[data-feedback-stage]');
    const holder = root.querySelector('[data-feedback-stage-media]');
    const indexLabel = root.querySelector('[data-feedback-index]');
    const backdrop = root.querySelector('[data-feedback-backdrop]');
    const thumbs = Array.from(root.querySelectorAll('[data-feedback-thumb]'));
    const title = root.dataset.feedbackTitle || '';
    let current = 0;

    const pad = (n) => String(n).padStart(2, '0');
    const preload = (index) => {
      const item = media[(index + media.length) % media.length];
      if (item.type === 'image') new Image().src = item.src;
    };
    const show = (index, autoplay) => {
      current = (index + media.length) % media.length;
      const item = media[current];
      holder.replaceChildren();
      if (item.type === 'video') {
        const video = document.createElement('video');
        video.className = 'h-full w-full object-contain';
        Object.assign(video, { src: item.src, poster: item.poster || '', controls: true, muted: true, playsInline: true, preload: 'metadata' });
        video.setAttribute('muted', '');
        video.setAttribute('playsinline', '');
        holder.append(video);
        if (autoplay) video.play().catch(() => {});
      } else {
        const image = document.createElement('img');
        image.className = 'h-full w-full object-contain';
        image.src = item.src;
        image.alt = title;
        image.decoding = 'async';
        holder.append(image);
      }
      if (indexLabel) indexLabel.textContent = pad(current + 1);
      const backdropSrc = item.thumb || (item.type === 'video' ? item.poster : item.src) || '';
      if (backdrop) backdrop.style.backgroundImage = backdropSrc ? `url("${backdropSrc.replace(/"/g, '%22')}")` : '';
      thumbs.forEach((thumb, i) => {
        const active = i === current;
        thumb.classList.toggle('is-active', active);
        thumb.setAttribute('aria-pressed', active ? 'true' : 'false');
        if (active) thumb.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
      });
      preload(current + 1);
    };

    thumbs.forEach((thumb) => thumb.addEventListener('click', () => show(Number(thumb.dataset.feedbackThumb), true)));
    root.querySelector('[data-feedback-prev]')?.addEventListener('click', () => show(current - 1, false));
    root.querySelector('[data-feedback-next]')?.addEventListener('click', () => show(current + 1, false));
    // Arrow keys page the gallery only while focus is on its own controls; a focused video keeps them for seeking.
    root.addEventListener('keydown', (event) => {
      if (event.target.closest?.('input, textarea, select, video, audio, [contenteditable]')) return;
      if (event.key === 'ArrowLeft') show(current - 1, false);
      if (event.key === 'ArrowRight') show(current + 1, false);
    });
    let touchX = null;
    stage?.addEventListener('touchstart', (event) => { touchX = event.touches[0].clientX; }, { passive: true });
    stage?.addEventListener('touchend', (event) => {
      if (touchX === null) return;
      const delta = event.changedTouches[0].clientX - touchX;
      touchX = null;
      if (Math.abs(delta) > 40 && !event.target.closest('video')) show(current + (delta < 0 ? 1 : -1), false);
    }, { passive: true });
    preload(1);
  });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

// Product detail images (详情图): thumbnail grid opens a full-screen viewer with arrows, keys and swipe.
(() => {
  const init = () => {
    document.querySelectorAll('[data-detail-viewer]').forEach((viewer) => {
      let images = [];
      try { images = JSON.parse(viewer.dataset.detailImages || '[]'); } catch (error) { images = []; }
      const box = viewer.querySelector('[data-detail-lightbox]');
      if (!images.length || !box) return;
      document.body.appendChild(box);
      const image = box.querySelector('[data-detail-image]');
      const stage = box.querySelector('[data-detail-stage]');
      const index = box.querySelector('[data-detail-index]');
      const alt = viewer.dataset.detailAlt || '';
      let captions = [];
      try { captions = JSON.parse(viewer.dataset.detailCaptions || '[]'); } catch (error) { captions = []; }
      const caption = box.querySelector('[data-detail-caption]');
      const pad = (n) => String(n).padStart(2, '0');
      let current = 0;
      let opener = null;
      let touchX = null;
      let background = [];
      let previousOverflow = '';
      const focusableControls = () => [...box.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')]
        .filter((control) => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length > 0);
      const show = (next) => {
        current = (next + images.length) % images.length;
        image.src = images[current];
        image.alt = captions[current] || `${alt} ${current + 1}`;
        if (index) index.textContent = pad(current + 1);
        if (caption) caption.textContent = captions[current] || '';
        stage.scrollTop = 0;
        [current + 1, current - 1].forEach((n) => { new Image().src = images[(n + images.length) % images.length]; });
      };
      const close = () => {
        box.classList.add('hidden');
        box.classList.remove('flex');
        box.setAttribute('aria-hidden', 'true');
        background.forEach(([element, wasInert]) => { element.inert = wasInert; });
        background = [];
        document.documentElement.style.overflow = previousOverflow;
        if (opener) opener.focus();
      };
      const open = (n, button) => {
        opener = button;
        show(n);
        box.classList.remove('hidden');
        box.classList.add('flex');
        box.setAttribute('aria-hidden', 'false');
        previousOverflow = document.documentElement.style.overflow;
        background = [...document.body.children].filter((element) => element !== box)
          .map((element) => [element, element.inert]);
        background.forEach(([element]) => { element.inert = true; });
        document.documentElement.style.overflow = 'hidden';
        box.querySelector('[data-detail-close]').focus();
      };
      viewer.querySelectorAll('[data-detail-open]').forEach((button) => {
        button.addEventListener('click', () => open(Number(button.dataset.detailOpen) || 0, button));
      });
      box.querySelector('[data-detail-close]').addEventListener('click', close);
      box.querySelector('[data-detail-prev]')?.addEventListener('click', () => show(current - 1));
      box.querySelector('[data-detail-next]')?.addEventListener('click', () => show(current + 1));
      stage.addEventListener('click', (event) => { if (event.target === stage) close(); });
      document.addEventListener('keydown', (event) => {
        if (box.getAttribute('aria-hidden') !== 'false') return;
        if (event.key === 'Escape') close();
        else if (event.key === 'ArrowLeft') show(current - 1);
        else if (event.key === 'ArrowRight') show(current + 1);
        else if (event.key === 'Tab') {
          const controls = focusableControls();
          const first = controls[0];
          const last = controls[controls.length - 1];
          if (!box.contains(document.activeElement) || (event.shiftKey ? document.activeElement === first : document.activeElement === last)) {
            event.preventDefault();
            (event.shiftKey ? last : first)?.focus();
          }
        }
      });
      document.addEventListener('focusin', (event) => {
        if (box.getAttribute('aria-hidden') === 'false' && !box.contains(event.target)) {
          box.querySelector('[data-detail-close]').focus();
        }
      });
      stage.addEventListener('touchstart', (event) => { touchX = event.touches[0].clientX; }, { passive: true });
      stage.addEventListener('touchend', (event) => {
        if (touchX === null) return;
        const delta = event.changedTouches[0].clientX - touchX;
        touchX = null;
        if (Math.abs(delta) > 50 && images.length > 1) show(current + (delta < 0 ? 1 : -1));
      }, { passive: true });
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

// News article contents: mark the section being read.
(() => {
  const watch = (toc) => {
    const links = [...toc.querySelectorAll('a[href^="#"]')];
    const headings = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1)))).filter(Boolean);
    if (!headings.length) return;
    const list = toc.querySelector('[data-news-toc-list]');
    const mark = (id) => {
      links.forEach((link) => link.setAttribute('aria-current', decodeURIComponent(link.hash.slice(1)) === id ? 'true' : 'false'));
      // A list that scrolls inside its card keeps the current entry in view (the list only, never the page).
      const active = links.find((link) => link.getAttribute('aria-current') === 'true');
      if (!list || !active || list.scrollHeight <= list.clientHeight) return;
      const top = active.offsetTop;
      if (top < list.scrollTop) list.scrollTop = top;
      else if (top + active.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = top + active.offsetHeight - list.clientHeight;
    };
    // The navigation stays over the top of the window (below an editor's toolbar), so reading starts below it: its sticky
    // offset plus its height, not its current position, which is lower while the announcement bar is still in view.
    const header = document.getElementById('top');
    const headerHeight = header ? Math.round((parseFloat(getComputedStyle(header).top) || 0) + header.getBoundingClientRect().height) : 0;
    // The reading band is the top 35% of the window below the navigation, and never less than 40px tall.
    const bandBottom = Math.max(0, Math.min(Math.round(window.innerHeight * 0.65), window.innerHeight - headerHeight - 40));
    const visible = new Set();
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
      const current = headings.filter((heading) => visible.has(heading))[0]
        || headings.filter((heading) => heading.getBoundingClientRect().top < headerHeight).pop();
      if (current) mark(current.id);
    }, { rootMargin: `-${headerHeight}px 0px -${bandBottom}px 0px` }); // in pixels: a percentage here may resolve against the width
    headings.forEach((heading) => observer.observe(heading));
    return observer;
  };
  const init = () => {
    if (!('IntersectionObserver' in window)) return;
    const lists = [...document.querySelectorAll('[data-news-toc]')];
    if (!lists.length) return;
    let observers = lists.map(watch);
    let height = window.innerHeight;
    let timer = 0;
    window.addEventListener('resize', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        if (window.innerHeight === height) return;
        height = window.innerHeight;
        observers.forEach((observer) => observer?.disconnect());
        observers = lists.map(watch);
      }, 200);
    }, { passive: true });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

// Product list, "how cranes are made": mark the last step whose photo has reached the top of the screen (just below
// where its link scrolls it: under the sticky navigation, plus scroll-mt-5). By position rather than intersection: the
// staggered photos sit 6rem apart.
(() => {
  const init = () => {
    document.querySelectorAll('[data-scroll-steps]').forEach((list) => {
      const links = [...list.querySelectorAll('a[href^="#"]')];
      const steps = links.map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1))));
      if (!links.length || steps.some((step) => !step)) return;
      let frame = 0;
      const update = () => {
        frame = 0;
        const header = document.getElementById('top');
        const headerBottom = header ? (parseFloat(getComputedStyle(header).top) || 0) + header.getBoundingClientRect().height : 0;
        const line = headerBottom + 68;
        let current = 0;
        steps.forEach((step, index) => { if (step.getBoundingClientRect().top <= line) current = index; });
        links.forEach((link, index) => link.setAttribute('aria-current', index === current ? 'true' : 'false'));
      };
      const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
      window.addEventListener('scroll', schedule, { passive: true });
      window.addEventListener('resize', schedule, { passive: true });
      update();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
