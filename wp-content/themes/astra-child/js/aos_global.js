/**
 * Animate On Scroll — Global (Lightweight IntersectionObserver)
 * Targets common UI elements across all pages automatically.
 */
(function () {
    'use strict';

    // ─── Elements to animate (selector → animation class) ───────────────────
    var TARGETS = [
        // Cards & Panels
        { sel: '.vd-info-card',          cls: 'aos-fade-up',    delay: true },
        { sel: '.stat-card',             cls: 'aos-fade-up',    delay: true },
        { sel: '.dashboard-card',        cls: 'aos-fade-up',    delay: true },
        { sel: '.md-card',               cls: 'aos-fade-up',    delay: true },
        { sel: '.card',                  cls: 'aos-fade-up',    delay: true },

        // Tables
        { sel: '.table-wrapper',         cls: 'aos-fade-up',    delay: false },
        { sel: '.next-table-row',        cls: 'aos-fade-left',  delay: true  },

        // Page Headers / Titles
        { sel: '.vd-header',             cls: 'aos-fade-down',  delay: false },
        { sel: '.history-header',        cls: 'aos-fade-down',  delay: false },
        { sel: '.section-header',        cls: 'aos-fade-down',  delay: false },
        { sel: '.page-title-wrap',       cls: 'aos-fade-down',  delay: false },
        { sel: 'h2.entry-title',         cls: 'aos-fade-down',  delay: false },

        // Filter / Search Bars
        { sel: '.filter-bar',            cls: 'aos-fade-up',    delay: false },
        { sel: '.search-bar-wrap',       cls: 'aos-fade-up',    delay: false },
        { sel: '.shadcn-filter-row',     cls: 'aos-fade-up',    delay: false },

        // Stat Numbers / Badges
        { sel: '.dtl-stats',             cls: 'aos-fade-up',    delay: false },
        { sel: '.dtl-date-header',       cls: 'aos-fade-left',  delay: false },
        { sel: '.donut-wrap',            cls: 'aos-zoom-in',    delay: false },

        // Forms
        { sel: '.vd-info-grid',          cls: 'aos-fade-up',    delay: false },
        { sel: '.form-section',          cls: 'aos-fade-up',    delay: false },
    ];

    var CSS_ADDED = false;

    // ─── Inject CSS once ─────────────────────────────────────────────────────
    function injectCSS() {
        if (CSS_ADDED) return;
        CSS_ADDED = true;
        var style = document.createElement('style');
        style.textContent = [
            /* Base hidden state */
            '.aos-hidden{opacity:0 !important;will-change:opacity,transform;}',
            /* Fade Up */
            '.aos-fade-up.aos-hidden{transform:translateY(28px) !important;}',
            '.aos-fade-up.aos-in{opacity:1 !important;transform:translateY(0) !important;transition:opacity .6s cubic-bezier(.4,0,.2,1) !important,transform .6s cubic-bezier(.4,0,.2,1) !important;}',
            /* Fade Down */
            '.aos-fade-down.aos-hidden{transform:translateY(-22px) !important;}',
            '.aos-fade-down.aos-in{opacity:1 !important;transform:translateY(0) !important;transition:opacity .6s cubic-bezier(.4,0,.2,1) !important,transform .6s cubic-bezier(.4,0,.2,1) !important;}',
            /* Fade Left */
            '.aos-fade-left.aos-hidden{transform:translateX(-24px) !important;}',
            '.aos-fade-left.aos-in{opacity:1 !important;transform:translateX(0) !important;transition:opacity .6s cubic-bezier(.4,0,.2,1) !important,transform .6s cubic-bezier(.4,0,.2,1) !important;}',
            /* Fade Right */
            '.aos-fade-right.aos-hidden{transform:translateX(24px) !important;}',
            '.aos-fade-right.aos-in{opacity:1 !important;transform:translateX(0) !important;transition:opacity .6s cubic-bezier(.4,0,.2,1) !important,transform .6s cubic-bezier(.4,0,.2,1) !important;}',
            /* Zoom In */
            '.aos-zoom-in.aos-hidden{transform:scale(.92) !important;}',
            '.aos-zoom-in.aos-in{opacity:1 !important;transform:scale(1) !important;transition:opacity .6s cubic-bezier(.4,0,.2,1) !important,transform .6s cubic-bezier(.4,0,.2,1) !important;}',
            /* Delay helpers */
            '.aos-delay-1{transition-delay:.08s!important;}',
            '.aos-delay-2{transition-delay:.16s!important;}',
            '.aos-delay-3{transition-delay:.24s!important;}',
            '.aos-delay-4{transition-delay:.32s!important;}',
            '.aos-delay-5{transition-delay:.40s!important;}',
            '.aos-delay-6{transition-delay:.48s!important;}',
            /* Respect reduced motion */
            '@media(prefers-reduced-motion:reduce){.aos-hidden{opacity:1 !important;transform:none !important;}.aos-in{transition:none!important;}}',
        ].join('');
        document.head.appendChild(style);
    }

    // ─── Mark & observe elements ─────────────────────────────────────────────
    function observe() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: show everything
            document.querySelectorAll('.aos-hidden').forEach(function (el) {
                el.classList.remove('aos-hidden');
                el.classList.add('aos-in');
            });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('aos-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

        TARGETS.forEach(function (target) {
            var els = document.querySelectorAll(target.sel);
            els.forEach(function (el, i) {
                // Skip if already processed
                if (el.classList.contains('aos-hidden') || el.classList.contains('aos-in')) return;
                el.classList.add(target.cls, 'aos-hidden');
                // Stagger delay for sibling groups
                if (target.delay && i < 6) {
                    el.classList.add('aos-delay-' + (i + 1));
                }
                io.observe(el);
            });
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────
    function init() {
        injectCSS();
        observe();
    }

    // Re-run on AJAX content loads (for SPA-style navigation)
    window.aosGlobalRefresh = function () { observe(); };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
