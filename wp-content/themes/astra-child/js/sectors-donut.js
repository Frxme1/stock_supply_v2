/**
 * Sectors Donut Component Interactivity (Vanilla JS)
 */

(function () {
    'use strict';

    function setupSectorsDonut(container) {
        if (container.classList.contains('sd-initialized')) return;
        container.classList.add('sd-initialized');

        const arcs = container.querySelectorAll('.sectors-donut-arc');
        const legendItems = container.querySelectorAll('.sectors-donut-legend-item');

        // Initial arc entry animation (staggered draw)
        setTimeout(() => {
            arcs.forEach((arc) => {
                const targetDash = arc.getAttribute('data-target-dash');
                if (targetDash) {
                    arc.style.strokeDasharray = targetDash;
                }
            });
        }, 50);

        function setHot(index) {
            if (index === null || index === undefined) {
                container.classList.remove('has-hot');
                arcs.forEach(a => a.classList.remove('is-hot'));
                legendItems.forEach(l => l.classList.remove('is-hot'));
            } else {
                container.classList.add('has-hot');
                arcs.forEach(a => {
                    if (parseInt(a.getAttribute('data-index'), 10) === index) {
                        a.classList.add('is-hot');
                    } else {
                        a.classList.remove('is-hot');
                    }
                });
                legendItems.forEach(l => {
                    if (parseInt(l.getAttribute('data-index'), 10) === index) {
                        l.classList.add('is-hot');
                    } else {
                        l.classList.remove('is-hot');
                    }
                });
            }
        }

        // Attach listeners to SVG arcs
        arcs.forEach(arc => {
            const idx = parseInt(arc.getAttribute('data-index'), 10);
            arc.addEventListener('mouseenter', () => setHot(idx));
            arc.addEventListener('mouseleave', () => setHot(null));
        });

        // Attach listeners to legend items
        legendItems.forEach(item => {
            const idx = parseInt(item.getAttribute('data-index'), 10);
            item.addEventListener('mouseenter', () => setHot(idx));
            item.addEventListener('mouseleave', () => setHot(null));
            item.addEventListener('focus', () => setHot(idx));
            item.addEventListener('blur', () => setHot(null));
        });
    }

    function initAllSectorsDonuts() {
        const containers = document.querySelectorAll('.sectors-donut-container:not(.sd-initialized)');
        containers.forEach(setupSectorsDonut);
    }

    window.initSectorsDonut = initAllSectorsDonuts;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllSectorsDonuts);
    } else {
        initAllSectorsDonuts();
    }
})();
