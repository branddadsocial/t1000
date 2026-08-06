/**
 * Restore Elementor footer DOM if custom chrome swapped it.
 */
(function () {
  'use strict';

  function restore() {
    var host = (location.hostname || '').replace(/^www\./, '').toLowerCase();
    if (host !== 'branddad.social') return;

    // Remove custom replacement footer(s).
    document.querySelectorAll('footer.bds-site-footer, .bds-site-footer').forEach(function (el) {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });

    // Ensure Elementor footer is visible.
    document.querySelectorAll('footer.elementor-location-footer, footer[data-elementor-type="footer"]').forEach(function (el) {
      el.style.setProperty('display', 'block', 'important');
      el.style.setProperty('visibility', 'visible', 'important');
      el.removeAttribute('hidden');
    });

    // Strip Directory / HostTech footer injects if present on Social.
    [
      '#bd-home-blog',
      '.bd-home-blog',
      '#bds-ai-local-pack',
      '.bds-ai-local-pack',
      '#bds-home-browse',
      '.bds-hbrowse'
    ].forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        if (el && el.parentNode) el.parentNode.removeChild(el);
      });
    });

    // Keep member strip just above the Elementor footer (original intent).
    var strip = document.querySelector('.bdsu-member-strip');
    var footer = document.querySelector('.elementor-location-footer, footer[data-elementor-type="footer"]');
    if (strip && footer && footer.parentNode && strip.nextElementSibling !== footer) {
      footer.parentNode.insertBefore(strip, footer);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restore);
  } else {
    restore();
  }
  // Chrome / deferred scripts may paint late.
  setTimeout(restore, 400);
  setTimeout(restore, 1200);
})();
