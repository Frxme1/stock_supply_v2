/**
 * Device Timeline — Toggle, Expand, Scroll Animation
 */
(function () {
    'use strict';

    if (window.__device_timeline_initialized) {
        return;
    }
    window.__device_timeline_initialized = true;

    function init() {
        initViewToggle();
        initCardExpand();
        initScrollReveal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /** Toggle between Timeline and Table view */
    function initViewToggle() {
        const btns = document.querySelectorAll('.dtl-toggle-btn');
        const timelineWrap = document.querySelector('.dtl-timeline-wrap');
        const tableWrap = document.querySelector('.dtl-table-wrap');
        const historySec = document.querySelector('.vd-history-section');

        if (!btns.length || !timelineWrap || !tableWrap) return;

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = this.dataset.view;
                btns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');

                if (target === 'timeline') {
                    timelineWrap.classList.add('active');
                    tableWrap.classList.remove('active');
                    if (historySec) historySec.classList.remove('dtl-show-search');
                    // Re-trigger scroll reveal
                    initScrollReveal();
                } else {
                    tableWrap.classList.add('active');
                    timelineWrap.classList.remove('active');
                    if (historySec) historySec.classList.add('dtl-show-search');
                }
            });
        });
    }

    /** Expand / Collapse card details */
    function initCardExpand() {
        document.addEventListener('click', function (e) {
            const card = e.target.closest('.dtl-card');
            if (!card) return;

            // Don't toggle if clicking photo, links, or inputs
            if (e.target.closest('.dtl-photo-thumb, a, button, input, select, textarea')) return;

            const body = card.querySelector('.dtl-card-body');
            
            const isOpen = card.classList.contains('dtl-open');
            if (isOpen) {
                card.classList.remove('dtl-open');
                if (body) body.classList.remove('dtl-expanded');
            } else {
                card.classList.add('dtl-open');
                if (body) body.classList.add('dtl-expanded');
            }
        });
    }

    /** Animate nodes on scroll into view */
    function initScrollReveal() {
        var nodes = document.querySelectorAll('.dtl-node');
        if (!nodes.length) return;

        // Ensure nodes are immediately visible on mobile / small screens
        if (window.innerWidth <= 768) {
            nodes.forEach(function (node) {
                node.classList.add('dtl-visible');
            });
            return;
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry, i) {
                    if (entry.isIntersecting) {
                        setTimeout(function () {
                            entry.target.classList.add('dtl-visible');
                        }, i * 60);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.05, rootMargin: '50px 0px' });

            nodes.forEach(function (node) {
                observer.observe(node);
            });

            // Failsafe timeout for all nodes
            setTimeout(function () {
                nodes.forEach(function (node) {
                    node.classList.add('dtl-visible');
                });
            }, 300);
        } else {
            nodes.forEach(function (node) {
                node.classList.add('dtl-visible');
            });
        }
    }
})();
