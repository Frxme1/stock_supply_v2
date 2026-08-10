/**
 * Mobile Device List — Load More (+3 Cards) System
 * Stock Supply Theme
 */
(function () {
    'use strict';

    function initMobileLoadMore() {
        // Only run on small screens (<= 768px)
        if (window.innerWidth > 768) return;

        const containers = document.querySelectorAll('.mobile-only-container');
        containers.forEach(function (container) {
            const cards = container.querySelectorAll('.mobile-device-card');
            if (!cards.length) return;

            let btnWrap = container.querySelector('.mobile-load-more-wrapper');
            let visibleCount = parseInt(container.getAttribute('data-visible-count') || '3', 10);

            // Update visibility of cards
            cards.forEach(function (card, index) {
                if (index < visibleCount) {
                    card.style.setProperty('display', 'flex', 'important');
                    card.style.setProperty('visibility', 'visible', 'important');
                    card.style.setProperty('opacity', '1', 'important');
                } else {
                    card.style.setProperty('display', 'none', 'important');
                }
            });

            // Add or update "Show More (+3)" button if total cards > 3
            if (cards.length > 3) {
                if (!btnWrap) {
                    btnWrap = document.createElement('div');
                    btnWrap.className = 'mobile-load-more-wrapper mt-3 mb-3 text-center';
                    
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'mobile-load-more-btn';
                    btn.innerHTML = '<i class="fa-solid fa-chevron-down me-2"></i> แสดงเพิ่มเติม (+3)';
                    
                    btn.addEventListener('click', function () {
                        let count = parseInt(container.getAttribute('data-visible-count') || '3', 10);
                        count += 3;
                        container.setAttribute('data-visible-count', count);
                        
                        cards.forEach(function (card, index) {
                            if (index < count) {
                                card.style.setProperty('display', 'flex', 'important');
                                card.style.setProperty('visibility', 'visible', 'important');
                                card.style.setProperty('opacity', '1', 'important');
                                card.classList.add('slide-up');
                            }
                        });

                        if (count >= cards.length) {
                            btnWrap.style.display = 'none';
                        }
                    });

                    btnWrap.appendChild(btn);
                    container.appendChild(btnWrap);
                }

                if (visibleCount >= cards.length) {
                    btnWrap.style.display = 'none';
                } else {
                    btnWrap.style.display = 'block';
                }
            } else if (btnWrap) {
                btnWrap.style.display = 'none';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileLoadMore);
    } else {
        initMobileLoadMore();
    }

    window.initMobileLoadMore = initMobileLoadMore;
})();
