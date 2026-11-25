// Keeps the staff inbox current. When the socket server says a chat changed,
// the page is fetched again for the chat on screen and its parts are swapped
// in, so the server still renders every line and checks every read. A reply
// being typed and a save form being filled in are left alone.
(function () {
    const page = document.getElementById('assistant-inbox');
    const settings = document.getElementById('assistant-live');

    if (!page || !settings || !window.Echo) {
        return;
    }

    const live = JSON.parse(settings.textContent);
    // Several lines often land together (a question, then the hand-off notice).
    const SETTLE_MS = 300;
    const NEAR_BOTTOM_PX = 40;
    const ALWAYS = ['assistant-tabs', 'assistant-chat-list', 'assistant-customer', 'assistant-menu'];

    const echo = new window.Echo.default({
        broadcaster: 'reverb',
        key: live.key,
        wsHost: live.host,
        wsPort: live.port,
        wssPort: live.port,
        forceTLS: live.tls,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: live.auth,
    });

    let timer = null;

    function later() {
        clearTimeout(timer);
        timer = setTimeout(reload, SETTLE_MS);
    }

    echo.private(live.channel).listen('.changed', later);
    // Whatever changed while the connection was down is fetched on its return.
    echo.connector.pusher.connection.bind('connected', later);

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

    function replace(id, fresh) {
        const now = document.getElementById(id);
        const next = fresh.getElementById(id);

        if (now && next) {
            now.replaceWith(document.importNode(next, true));
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
