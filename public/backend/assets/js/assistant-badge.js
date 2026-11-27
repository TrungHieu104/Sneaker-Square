// Keeps the "Trợ lý AI" menu badge current on every admin page. The inbox
// refreshes the menu along with everything else, so it is left to that.
(function () {
    const item = document.getElementById('assistant-menu');

    if (!item || document.getElementById('assistant-inbox')) {
        return;
    }

    const every = Number(item.dataset.pollMs) || 15000;

    function paint(count) {
        const link = item.querySelector('.menu-link');
        let badge = link.querySelector('.badge');

        if (count <= 0) {
            if (badge) {
                badge.remove();
            }

            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge rounded-pill bg-danger ms-auto';
            link.appendChild(badge);
        }

        badge.textContent = count;
    }

    // A tab in the background is not polled; the next tick after it is shown catches up.
    setInterval(function () {
        if (document.hidden) {
            return;
        }

        fetch(item.dataset.countUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                return response.ok && !response.redirected ? response.json() : Promise.reject(response);
            })
            .then(function (data) {
                paint(Number(data.waiting) || 0);
            })
            .catch(function () {});
    }, every);
})();
