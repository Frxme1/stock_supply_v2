/**
 * Device Timeline — Toggle, Expand, Scroll Animation
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initViewToggle();
        initCardExpand();
        initScrollReveal();
    });

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

            // Don't toggle if clicking photo
            if (e.target.closest('.dtl-photo-thumb')) return;

            const body = card.querySelector('.dtl-card-body');
            if (!body) return;

            const isOpen = card.classList.contains('dtl-open');

            // Close all others
            document.querySelectorAll('.dtl-card.dtl-open').forEach(function (c) {
                if (c !== card) {
                    c.classList.remove('dtl-open');
                    var b = c.querySelector('.dtl-card-body');
                    if (b) b.classList.remove('dtl-expanded');
                }
            });

            if (isOpen) {
                card.classList.remove('dtl-open');
                body.classList.remove('dtl-expanded');
            } else {
                card.classList.add('dtl-open');
                body.classList.add('dtl-expanded');
            }
        });
    }

    /** Animate nodes on scroll into view */
    function initScrollReveal() {
        var nodes = document.querySelectorAll('.dtl-node');
        if (!nodes.length) return;

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry, i) {
                    if (entry.isIntersecting) {
                        // Stagger animation
                        setTimeout(function () {
                            entry.target.classList.add('dtl-visible');
                        }, i * 60);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            nodes.forEach(function (node) {
                node.classList.remove('dtl-visible');
                observer.observe(node);
            });
        } else {
            // Fallback: show all
            nodes.forEach(function (node) {
                node.classList.add('dtl-visible');
            });
        }
    }
})();
