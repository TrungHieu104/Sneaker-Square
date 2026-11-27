// Keeps the staff inbox current. When the socket server says a chat changed,
// or on a timer while there is no socket, the page is fetched again for the
// chat on screen and its parts are swapped in, so the server still renders
// every line and checks every read. A reply being typed and a save form being
// filled in are left alone.
(function () {
    const page = document.getElementById('assistant-inbox');

    if (!page) {
        return;
    }

    const settings = document.getElementById('assistant-live');
    // Several lines often land together (a question, then the hand-off notice).
    const SETTLE_MS = 300;
    const NEAR_BOTTOM_PX = 40;
    const POLL_MS = Number(page.dataset.pollMs) || 15000;
    const ALWAYS = ['assistant-tabs', 'assistant-chat-list', 'assistant-customer', 'assistant-menu'];

    let echo = null;
    let timer = null;

    function later() {
        clearTimeout(timer);
        timer = setTimeout(reload, SETTLE_MS);
    }

    function connected() {
        return Boolean(echo) && echo.connector.pusher.connection.state === 'connected';
    }

    if (settings && window.Echo) {
        const live = JSON.parse(settings.textContent);

        echo = new window.Echo.default({
            broadcaster: 'reverb',
            key: live.key,
            wsHost: live.host,
            wsPort: live.port,
            wssPort: live.port,
            forceTLS: live.tls,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: live.auth,
        });

        echo.private(live.channel).listen('.changed', later);
        // Whatever changed while the connection was down is fetched on its return.
        echo.connector.pusher.connection.bind('connected', later);
    }

    // A tab in the background is not polled; it catches up once shown again.
    setInterval(function () {
        if (!connected() && !document.hidden) {
            reload();
        }
    }, POLL_MS);

    document.addEventListener('visibilitychange', function () {
        if (!connected() && !document.hidden) {
            later();
        }
    });

    function reload() {
        const url = new URL(window.location.href);

        // The chat on screen stays on screen, even when a newer one tops the list.
        if (page.dataset.chat) {
            url.searchParams.set('chat', page.dataset.chat);
        }

        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
            .then(function (response) {
                return response.ok && !response.redirected ? response.text() : Promise.reject(response);
            })
            .then(function (html) {
                swap(new DOMParser().parseFromString(html, 'text/html'));
            })
            .catch(function () {});
    }

    // What the server last sent for each part. Compared with that rather than
    // with the page, which tooltips have already written into.
    const served = {};

    // A part the server sends unchanged is left in place, so a timed poll does
    // not drop a text selection or an open tooltip every few seconds.
    function replace(id, fresh) {
        const now = document.getElementById(id);
        const next = fresh.getElementById(id);

        if (!now || !next || served[id] === next.innerHTML) {
            return;
        }

        served[id] = next.innerHTML;

        const Tooltip = window.bootstrap ? window.bootstrap.Tooltip : null;
        const added = document.importNode(next, true);

        if (Tooltip) {
            now.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                Tooltip.getInstance(el)?.dispose();
            });
        }

        now.replaceWith(added);

        if (Tooltip) {
            added.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new Tooltip(el);
            });
        }
    }

    function typing() {
        const box = document.querySelector('#assistant-reply textarea');

        return Boolean(box) && (box.value.trim() !== '' || document.activeElement === box);
    }

    function swap(fresh) {
        const freshPage = fresh.getElementById('assistant-inbox');

        if (!freshPage) {
            return;
        }

        ALWAYS.forEach(function (id) { replace(id, fresh); });

        const thread = document.getElementById('assistant-thread');
        const freshThread = fresh.getElementById('assistant-thread');
        const followed = !thread || thread.scrollHeight - thread.scrollTop - thread.clientHeight < NEAR_BOTTOM_PX;

        if (!typing()) {
            replace('assistant-chat', fresh);
        } else if (thread && freshThread) {
            replace('assistant-chat-head', fresh);
            replace('assistant-thread', fresh);
        }

        page.dataset.chat = freshPage.dataset.chat || '';

        const shown = document.getElementById('assistant-thread');

        if (shown) {
            shown.scrollTop = followed ? shown.scrollHeight : (thread ? thread.scrollTop : 0);
        }

        // An open save form would lose what is being written into it.
        if (!document.querySelector('#assistant-save-forms .offcanvas.show')) {
            replace('assistant-save-forms', fresh);
        }
    }
})();
