/**
 * Stock Monitor — Real-Time AJAX Polling
 *
 * Polls the stock_realtime_data endpoint every 20 seconds.
 * Updates DOM numbers with animated transitions.
 * Pauses when tab is not visible (Page Visibility API).
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 20000; // 20 seconds
    let timerId = null;
    let ajaxUrl = '';

    // Status color map (matches existing dashboard palette)
    const STATUS_COLORS = {
        'Available':   '#6ABF57',
        'In Use':      '#F05353',
        'Maintenance': '#FDB840',
        'Retired':     '#919191'
    };

    // Category config
    const CATEGORY_ICONS = {
        'Monitor':     'fa-desktop',
        'Laptop':      'fa-laptop',
        'Accessories': 'fa-plug'
    };

    const CATEGORY_COLORS = {
        'Monitor':     '#FDB840',
        'Laptop':      '#15A5DA',
        'Accessories': '#6ABF57'
    };

    /**
     * Initialize stock monitor
     */
    function init() {
        var urlEl = document.getElementById('stock-monitor-ajax-url');
        if (!urlEl) return;

        ajaxUrl = urlEl.value;
        if (!ajaxUrl) return;

        // Bind manual refresh
        var refreshBtn = document.getElementById('stock-refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                fetchData(true);
            });
        }

        // Initial fetch
        fetchData(false);

        // Start polling
        startPolling();

        // Visibility API — pause when tab hidden
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopPolling();
            } else {
                fetchData(false);
                startPolling();
            }
        });
    }

    function startPolling() {
        stopPolling();
        timerId = setInterval(function () {
            fetchData(false);
        }, POLL_INTERVAL);
    }

    function stopPolling() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    /**
     * Fetch data from AJAX endpoint
     */
    function fetchData(isManual) {
        var refreshBtn = document.getElementById('stock-refresh-btn');
        if (isManual && refreshBtn) {
            refreshBtn.classList.add('refreshing');
        }

        var formData = new FormData();
        formData.append('action', 'stock_realtime_data');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function (response) { return response.json(); })
        .then(function (json) {
            if (json.success && json.data) {
                renderData(json.data);
            }
        })
        .catch(function (error) {
            console.error('Stock monitor fetch error:', error);
        })
        .finally(function () {
            if (refreshBtn) {
                refreshBtn.classList.remove('refreshing');
            }
        });
    }

    /**
     * Render data into the DOM
     */
    function renderData(data) {
        // Update timestamp
        var tsEl = document.getElementById('stock-live-timestamp');
        if (tsEl && data.timestamp) {
            var d = new Date(data.timestamp);
            tsEl.textContent = 'Updated: ' + formatTime(d);
        }

        // Update category cards
        var categories = ['Monitor', 'Laptop', 'Accessories'];
        var statuses = ['Available', 'In Use', 'Maintenance', 'Retired'];

        categories.forEach(function (cat) {
            var catData = data.by_category_status[cat];
            if (!catData) return;

            var totalInCat = 0;
            statuses.forEach(function (s) { totalInCat += (catData[s] || 0); });

            // Total number
            var totalEl = document.getElementById('stock-total-' + slugify(cat));
            if (totalEl) {
                animateNumber(totalEl, totalInCat);
            }

            // Status bars
            statuses.forEach(function (s) {
                var count = catData[s] || 0;
                var pct = totalInCat > 0 ? (count / totalInCat) * 100 : 0;

                var fillEl = document.getElementById('stock-bar-' + slugify(cat) + '-' + slugify(s));
                if (fillEl) {
                    fillEl.style.width = pct + '%';
                }

                var countEl = document.getElementById('stock-count-' + slugify(cat) + '-' + slugify(s));
                if (countEl) {
                    animateNumber(countEl, count);
                }
            });

            // Low-stock card state
            var cardEl = document.getElementById('stock-card-' + slugify(cat));
            if (cardEl) {
                if ((catData['Available'] || 0) < 3) {
                    cardEl.classList.add('low-stock');
                } else {
                    cardEl.classList.remove('low-stock');
                }
            }
        });

        // Low-stock alert banner
        renderAlerts(data.low_stock_alerts || []);

        // Total footer
        var footerTotal = document.getElementById('stock-footer-total');
        if (footerTotal) {
            animateNumber(footerTotal, data.total);
        }

        var footerAvail = document.getElementById('stock-footer-available');
        if (footerAvail && data.by_status) {
            animateNumber(footerAvail, data.by_status['Available'] || 0);
        }
    }

    /**
     * Render low-stock alerts
     */
    function renderAlerts(alerts) {
        var alertEl = document.getElementById('stock-low-alert');
        var listEl = document.getElementById('stock-low-alert-list');
        if (!alertEl || !listEl) return;

        if (alerts.length === 0) {
            alertEl.classList.remove('visible');
            return;
        }

        var html = '';
        alerts.forEach(function (a) {
            html += '<li class="stock-low-alert-item">' +
                '<strong>' + escHtml(a.category) + '</strong> — Available: ' +
                a.available + ' (threshold: ' + a.threshold + ')' +
                '</li>';
        });
        listEl.innerHTML = html;
        alertEl.classList.add('visible');
    }

    /**
     * Animate number change with flash effect
     */
    function animateNumber(el, newVal) {
        var current = parseInt(el.textContent, 10);
        if (current === newVal) return;

        el.textContent = newVal;
        el.classList.add('stock-number-updating');
        setTimeout(function () {
            el.classList.remove('stock-number-updating');
        }, 400);
    }

    /**
     * Slugify string for IDs
     */
    function slugify(str) {
        return str.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    }

    /**
     * Format time as HH:MM:SS
     */
    function formatTime(d) {
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        var s = String(d.getSeconds()).padStart(2, '0');
        return h + ':' + m + ':' + s;
    }

    /**
     * Escape HTML
     */
    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
