/**
 * Mobile Device & Maintenance List — Load More (+3 Cards) & Collapse (Show Less) System
 * Stock Supply Theme
 */
(function () {
    'use strict';

    function initMobileLoadMore() {
        // Only run on small screens (<= 768px)
        if (window.innerWidth > 768) return;

        const containers = document.querySelectorAll('.mobile-only-container');
        containers.forEach(function (container) {
            const cards = container.querySelectorAll('.mobile-device-card, .mobile-maintenance-card');
            if (!cards.length) return;

            let btnWrap = container.querySelector('.mobile-load-more-wrapper');
            let visibleCount = parseInt(container.getAttribute('data-visible-count') || '3', 10);

            // Function to update card visibility and toggle state classes
            function updateCardVisibility() {
                cards.forEach(function (card, index) {
                    if (index < visibleCount) {
                        card.style.setProperty('display', 'flex', 'important');
                        card.style.setProperty('visibility', 'visible', 'important');
                        card.style.setProperty('opacity', '1', 'important');
                    } else {
                        card.style.setProperty('display', 'none', 'important');
                    }
                });

                if (!btnWrap) return;

                if (cards.length <= 3) {
                    btnWrap.style.setProperty('display', 'none', 'important');
                    return;
                }

                btnWrap.style.setProperty('display', 'flex', 'important');

                const hasMore = (visibleCount < cards.length);
                const hasLess = (visibleCount > 3);
                const isDual = (hasMore && hasLess);

                btnWrap.classList.toggle('has-more', hasMore);
                btnWrap.classList.toggle('has-less', hasLess);
                btnWrap.classList.toggle('is-dual', isDual);
            }

            // Create buttons wrapper if total cards > 3
            if (cards.length > 3 && !btnWrap) {
                btnWrap = document.createElement('div');
                btnWrap.className = 'mobile-load-more-wrapper mt-3 mb-4';

                // Show More Button (With Icon + Text)
                const moreBtn = document.createElement('button');
                moreBtn.type = 'button';
                moreBtn.className = 'mobile-load-more-btn';
                moreBtn.innerHTML = '<i class="fa-solid fa-chevron-down me-1"></i><span>Show More (+3)</span>';

                moreBtn.addEventListener('click', function () {
                    visibleCount += 3;
                    container.setAttribute('data-visible-count', visibleCount);
                    updateCardVisibility();

                    // Animate newly revealed cards
                    cards.forEach(function (card, index) {
                        if (index >= visibleCount - 3 && index < visibleCount) {
                            card.classList.add('slide-up');
                        }
                    });
                });

                // Show Less (Collapse) Button (With Icon + Text)
                const lessBtn = document.createElement('button');
                lessBtn.type = 'button';
                lessBtn.className = 'mobile-load-less-btn';
                lessBtn.innerHTML = '<i class="fa-solid fa-chevron-up me-1"></i><span>Show Less</span>';

                lessBtn.addEventListener('click', function () {
                    visibleCount = 3;
                    container.setAttribute('data-visible-count', visibleCount);
                    updateCardVisibility();

                    // Smooth scroll back to top of container
                    const topOffset = container.getBoundingClientRect().top + window.pageYOffset - 75;
                    window.scrollTo({ top: Math.max(0, topOffset), behavior: 'smooth' });
                });

                btnWrap.appendChild(moreBtn);
                btnWrap.appendChild(lessBtn);
                container.appendChild(btnWrap);
            }

            updateCardVisibility();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileLoadMore);
    } else {
        initMobileLoadMore();
    }

    window.initMobileLoadMore = initMobileLoadMore;
})();
