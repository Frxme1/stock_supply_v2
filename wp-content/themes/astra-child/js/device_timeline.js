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
        initStatChipFilters();
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

    /** Interactive Action Chip Filters */
    function initStatChipFilters() {
        const statsContainers = document.querySelectorAll('.dtl-stats');
        if (!statsContainers.length) return;

        statsContainers.forEach(function (statsWrap) {
            // Skip client-side filtering if container uses server-side filtered links
            if (statsWrap.closest('.dtl-server-filtered') || statsWrap.querySelector('a.dtl-stat-chip')) {
                return;
            }

            const timelineWrap = statsWrap.closest('.dtl-timeline-wrap') || statsWrap.parentElement;
            if (!timelineWrap) return;

            const chips = statsWrap.querySelectorAll('.dtl-stat-chip');
            if (!chips.length) return;

            // Set first chip (Total) as active by default if none active
            if (!statsWrap.querySelector('.dtl-stat-chip.active')) {
                chips[0].classList.add('active');
            }

            chips.forEach(function (chip) {
                chip.setAttribute('role', 'button');
                chip.setAttribute('tabindex', '0');

                function handleChipClick() {
                    const targetAction = (chip.dataset.filterAction || chip.innerText.replace(/[0-9]/g, '').trim()).toLowerCase();
                    const isTotal = (targetAction === 'all' || targetAction.indexOf('total') !== -1);
                    const wasActive = chip.classList.contains('active');

                    // If clicking already active chip (and not total), reset to total
                    let finalFilter = targetAction;
                    if (wasActive && !isTotal) {
                        finalFilter = 'all';
                    }

                    // Update active state on chips
                    chips.forEach(function (c) {
                        const cAction = (c.dataset.filterAction || c.innerText.replace(/[0-9]/g, '').trim()).toLowerCase();
                        const cIsTotal = (cAction === 'all' || cAction.indexOf('total') !== -1);
                        if (finalFilter === 'all') {
                            if (cIsTotal) c.classList.add('active');
                            else c.classList.remove('active');
                        } else {
                            if (cAction === finalFilter) c.classList.add('active');
                            else c.classList.remove('active');
                        }
                    });

                    // Filter nodes
                    const nodes = timelineWrap.querySelectorAll('.dtl-node');
                    let visibleCount = 0;

                    nodes.forEach(function (node) {
                        const nodeAction = (node.dataset.action || (node.querySelector('.dtl-action-badge') ? node.querySelector('.dtl-action-badge').innerText : '')).toLowerCase().trim();
                        
                        let matches = (finalFilter === 'all');
                        if (!matches) {
                            matches = (nodeAction === finalFilter || nodeAction.indexOf(finalFilter) !== -1 || finalFilter.indexOf(nodeAction) !== -1);
                        }

                        if (matches) {
                            node.style.display = '';
                            node.classList.add('dtl-visible');
                            visibleCount++;
                        } else {
                            node.style.display = 'none';
                        }
                    });

                    // Manage date groups visibility
                    const dateGroups = timelineWrap.querySelectorAll('.dtl-date-group');
                    dateGroups.forEach(function (group) {
                        const visibleInGroup = group.querySelectorAll('.dtl-node:not([style*="display: none"])');
                        if (visibleInGroup.length > 0) {
                            group.style.display = '';
                        } else {
                            group.style.display = 'none';
                        }
                    });
                }

                chip.addEventListener('click', handleChipClick);
                chip.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleChipClick();
                    }
                });
            });
        });
    }
})();
