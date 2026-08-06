/**
 * BDS Directory Connect — front-end wiring.
 * 1) Directory type tabs (Automotive, Beauty…) → real search-result URLs
 * 2) Directorist search bar → Ask BrandDad (hide duplicate AI hero chip)
 * 3) Replace hardcoded Popular In with live browse markup + bind location filters
 */
(function () {
  'use strict';

  var cfg = window.BDS_DC || {};

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function openAskBrandDad(prefill) {
    var launcher = document.getElementById('bds-ai-launcher');
    var chip = document.getElementById('bds-ai-header-chip');
    var panel = document.getElementById('bds-ai-panel');
    var input = document.getElementById('bds-ai-input');

    function fill() {
      if (input && prefill) {
        input.value = String(prefill);
        try {
          input.focus();
        } catch (e) {}
      }
    }

    if (launcher) {
      if (panel && panel.hidden) launcher.click();
      else if (panel && !panel.hidden) fill();
      else launcher.click();
      setTimeout(fill, 50);
      return true;
    }
    if (chip) {
      chip.click();
      setTimeout(fill, 50);
      return true;
    }
    if (panel) {
      panel.hidden = false;
      fill();
      return true;
    }
    return false;
  }

  /** Make Automotive / Beauty / … tabs navigate to real listing results. */
  function wireDirectoryTypes() {
    if (cfg.wireTypes === false) return;

    var typeMap = {};
    (cfg.browse && cfg.browse.directoryTypes ? cfg.browse.directoryTypes : []).forEach(function (t) {
      if (t && t.slug) typeMap[t.slug] = t.url;
    });

    qsa('a.search_listing_types, a.directorist-listing-type-selection__link').forEach(function (a) {
      var slug = a.getAttribute('data-listing_type') || '';
      if (!slug) return;

      var url = typeMap[slug];
      if (!url) {
        try {
          var u = new URL(cfg.searchUrl || '/search-result/', window.location.origin);
          u.searchParams.set('directory_type', slug);
          url = u.toString();
        } catch (e) {
          url = (cfg.home || '/') + 'search-result/?directory_type=' + encodeURIComponent(slug);
        }
      }

      a.setAttribute('href', url);
      a.setAttribute('data-bds-dc-type', slug);

      // Ensure click navigates (Directorist often preventDefault on href="#").
      a.addEventListener(
        'click',
        function (e) {
          // Let modifier clicks / new-tab behave normally.
          if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
          e.preventDefault();
          e.stopPropagation();
          // Keep Directorist hidden field in sync if present.
          qsa('input.listing_type[name="directory_type"], input[name="directory_type"]').forEach(function (inp) {
            inp.value = slug;
          });
          window.location.href = url;
        },
        true
      );
    });
  }

  /** Hide duplicate Ask BrandDad hero chip; wire Directorist search to AI. */
  function wireSearchToAi() {
    if (cfg.hideAiHero !== false) {
      var hero = document.getElementById('bds-ai-hero-entry');
      if (hero) {
        hero.setAttribute('hidden', 'hidden');
        hero.style.display = 'none';
      }
    }
    if (cfg.wireSearch === false) return;

    var forms = qsa(
      'form.directorist-search-form, .directorist-search-contents form, .directorist-search-form-box form, form[action*="search-result"]'
    );
    if (!forms.length) {
      // Directorist sometimes wraps without a <form>; catch the Search button.
      var box = qs('.directorist-search-form, .directorist-search-contents');
      if (box) {
        box.addEventListener('click', function (e) {
          var btn = e.target && e.target.closest
            ? e.target.closest('.directorist-btn-search, button[type="submit"], .directorist-search-form-action__submit button')
            : null;
          if (!btn) return;
          var input = box.querySelector('input[name="q"], input.directorist-search-field__input[type="text"]');
          var q = input ? String(input.value || '').trim() : '';
          if (!q) return; // empty → let normal / type navigation work
          e.preventDefault();
          e.stopPropagation();
          openAskBrandDad(q);
        }, true);
      }
      return;
    }

    forms.forEach(function (form) {
      if (form.getAttribute('data-bds-dc-wired')) return;
      form.setAttribute('data-bds-dc-wired', '1');

      // Soft label cue under type tabs.
      if (!form.querySelector('.bds-dc-search-hint')) {
        var hint = document.createElement('p');
        hint.className = 'bds-dc-search-hint';
        hint.textContent = 'Type a need and Search opens Ask BrandDad. Or tap a category tab to browse that directory.';
        var top = form.querySelector('.directorist-search-form__top, .directorist-listing-type-selection');
        if (top && top.parentNode) {
          top.parentNode.insertBefore(hint, top.nextSibling);
        } else {
          form.appendChild(hint);
        }
      }

      form.addEventListener(
        'submit',
        function (e) {
          var input = form.querySelector('input[name="q"], input.directorist-search-field__input[type="text"]');
          var q = input ? String(input.value || '').trim() : '';
          if (!q) {
            // No query: go to selected directory type results.
            var typeInp = form.querySelector('input.listing_type[name="directory_type"], input[name="directory_type"]');
            var slug = typeInp ? String(typeInp.value || '').trim() : '';
            if (slug) {
              e.preventDefault();
              try {
                var u = new URL(cfg.searchUrl || '/search-result/', window.location.origin);
                u.searchParams.set('directory_type', slug);
                window.location.href = u.toString();
              } catch (err) {
                window.location.href =
                  (cfg.home || '/') + 'search-result/?directory_type=' + encodeURIComponent(slug);
              }
            }
            return;
          }
          e.preventDefault();
          openAskBrandDad(q);
        },
        true
      );
    });
  }

  function searchResultUrl(params) {
    try {
      var u = new URL(cfg.searchUrl || '/search-result/', window.location.origin);
      Object.keys(params || {}).forEach(function (k) {
        if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
          u.searchParams.set(k, params[k]);
        }
      });
      return u.toString();
    } catch (e) {
      return (cfg.home || '/') + 'search-result/';
    }
  }

  /**
   * /single-category/* archives show "0 Items Found".
   * Rewrite Local/Digital chips to working /search-result/?in_cat=… links.
   */
  function patchLocalChips(sec) {
    if (!sec) return;
    var bySlug = {};
    var lists = [];
    if (cfg.browse && cfg.browse.local) lists = lists.concat(cfg.browse.local);
    if (cfg.browse && cfg.browse.digital) lists = lists.concat(cfg.browse.digital);
    lists.forEach(function (c) {
      if (c && c.slug) bySlug[c.slug] = c;
    });

    qsa('.bds-hbrowse__chip', sec).forEach(function (a) {
      var slug = a.getAttribute('data-slug') || '';
      var href = a.getAttribute('href') || '';
      var live = bySlug[slug];
      var brokenArchive = /\/single-category\//i.test(href);

      if (live && live.url) {
        a.setAttribute('href', live.url);
        a.setAttribute('data-base-url', live.url);
        if (live.id) a.setAttribute('data-cat-id', String(live.id));
        if (live.directory_type) a.setAttribute('data-directory-type', live.directory_type);
        if (live.combo) {
          try {
            a.setAttribute('data-combo', JSON.stringify(live.combo));
          } catch (e) {}
        }
        return;
      }

      // Fallback without REST payload: derive from data-combo or cat id.
      if (brokenArchive || !href) {
        var catId = a.getAttribute('data-cat-id');
        var dirType = a.getAttribute('data-directory-type') || '';
        var comboRaw = a.getAttribute('data-combo');
        if (!catId && comboRaw) {
          try {
            var map = JSON.parse(comboRaw);
            var first = map && map[Object.keys(map)[0]];
            if (first) {
              var tmp = new URL(first, window.location.origin);
              catId = tmp.searchParams.get('in_cat') || '';
              dirType = dirType || tmp.searchParams.get('directory_type') || '';
            }
          } catch (e) {}
        }
        if (catId) {
          var fixed = searchResultUrl({ in_cat: catId, directory_type: dirType });
          a.setAttribute('href', fixed);
          a.setAttribute('data-base-url', fixed);
        }
      }
    });

    // Patch Popular-in place links that still point at empty-feeling archives only.
    if (cfg.browse && cfg.browse.locations) {
      var locBySlug = {};
      cfg.browse.locations.forEach(function (l) {
        if (l && l.slug) locBySlug[l.slug] = l;
      });
      qsa('.bds-hbrowse__loc--link', sec).forEach(function (a) {
        var slug = a.getAttribute('data-loc') || '';
        var live = locBySlug[slug];
        if (!live) return;
        if (live.url) a.setAttribute('href', live.url);
        if (live.archive) a.setAttribute('data-archive', live.archive);
        var countEl = a.querySelector('.bds-hbrowse__loc-count');
        if (countEl && typeof live.count === 'number') countEl.textContent = String(live.count);
      });
    }
  }

  /** Prefer our live section over the old hardcoded #bds-home-browse. */
  function ensureBrowseSection() {
    var ours = qsa('#bds-home-browse[data-bds-dc="1"]');
    var all = qsa('#bds-home-browse');
    if (ours.length && all.length > 1) {
      all.forEach(function (sec) {
        if (sec.getAttribute('data-bds-dc') !== '1') {
          sec.parentNode && sec.parentNode.removeChild(sec);
        }
      });
    }
    var sec = qs('#bds-home-browse[data-bds-dc="1"]') || qs('#bds-home-browse');
    if (!sec) return null;

    // Place just under hero search / AI entry (same as previous place script).
    if (!sec.getAttribute('data-placed')) {
      var after =
        document.getElementById('bds-ai-hero-entry') ||
        qs('.directorist-search-contents') ||
        qs('.directorist-search-top') ||
        qs('.directorist-search-form') ||
        qs('#directorist');
      if (after && after.parentNode) {
        if (after.nextSibling) after.parentNode.insertBefore(sec, after.nextSibling);
        else after.parentNode.appendChild(sec);
        sec.setAttribute('data-placed', 'after-hero');
      }
    }

    patchLocalChips(sec);
    return sec;
  }

  /** Bind Popular In location filters → rewrite category chip hrefs. */
  function bindBrowseLocations(sec) {
    if (!sec || sec.getAttribute('data-bound')) return;
    sec.setAttribute('data-bound', '1');

    var hint = sec.querySelector('[data-loc-hint]');
    var chips = sec.querySelectorAll('.bds-hbrowse__block--local .bds-hbrowse__chip');
    var locs = sec.querySelectorAll('.bds-hbrowse__loc');
    var active = '';

    function setActive(slug, label) {
      active = slug || '';
      locs.forEach(function (el) {
        var on = (el.getAttribute('data-loc') || '') === active;
        el.classList.toggle('is-active', on);
        if (el.tagName === 'BUTTON') el.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      chips.forEach(function (a) {
        var base = a.getAttribute('data-base-url') || a.getAttribute('href');
        var comboRaw = a.getAttribute('data-combo');
        if (active && comboRaw) {
          try {
            var map = JSON.parse(comboRaw);
            if (map && map[active]) {
              a.setAttribute('href', map[active]);
              return;
            }
          } catch (e) {}
        }
        // Never leave chips on broken /single-category/ archives.
        if (base && /\/single-category\//i.test(base)) {
          var catId = a.getAttribute('data-cat-id');
          var dirType = a.getAttribute('data-directory-type') || '';
          if (catId) base = searchResultUrl({ in_cat: catId, directory_type: dirType });
        }
        if (base) a.setAttribute('href', base);
      });
      if (hint) {
        hint.textContent =
          active && label
            ? 'Showing local listings for ' + label + ' — tap a category to open them.'
            : 'Tap a category to see local listings. Prefer near-me or a zip? Use Ask BrandDad above.';
      }
    }

    locs.forEach(function (el) {
      el.addEventListener('click', function (e) {
        var slug = el.getAttribute('data-loc') || '';
        if (el.tagName === 'A' && (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1)) return;
        e.preventDefault();
        var label = el.getAttribute('data-label') || el.textContent.trim();
        if (el.classList.contains('is-active') && slug) {
          // Second click → open place listings (search-result?in_loc=…).
          var dest = el.getAttribute('href') || el.getAttribute('data-archive');
          if (dest) {
            window.location.href = dest;
            return;
          }
        }
        setActive(slug, label);
      });
    });

    var ask = sec.querySelector('[data-bds-ask]');
    if (ask) {
      ask.addEventListener('click', function () {
        openAskBrandDad('');
      });
    }

    // Default: All places — ensure chips already point at search-result URLs.
    setActive('', '');
  }

  function boot() {
    wireDirectoryTypes();
    wireSearchToAi();
    var sec = ensureBrowseSection();
    bindBrowseLocations(sec);
  }

  ready(function () {
    boot();
    // Directorist / Elementor late paint.
    setTimeout(boot, 400);
    setTimeout(boot, 1200);
  });
})();
