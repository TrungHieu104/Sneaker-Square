// One Reverb connection per admin page, shared by the parts of the page that
// show live data (the bell, the shipment tracking). Each part registers how to
// refresh itself: it runs when its channel signals, when the socket comes back,
// and on a timer while there is no socket. A hidden tab is not polled, nor is a
// page nobody has touched for a while; either catches up once someone is back.
window.AdminLive = (function () {
    const settings = document.getElementById('admin-live');
    const live = settings ? JSON.parse(settings.textContent) : {};
    const POLL_MS = Number(live.poll) || 15000;
    const IDLE_MS = Number(live.idle) || 600000;
    // Several writes often land together (an order and its status log).
    const SETTLE_MS = 300;

    const resumes = [];
    let echo = null;
    let lastActive = Date.now();

    if (live.socket && window.Echo) {
        echo = new window.Echo.default({
            broadcaster: 'reverb',
            key: live.socket.key,
            wsHost: live.socket.host,
            wsPort: live.socket.port,
            wssPort: live.socket.port,
            forceTLS: live.socket.tls,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: live.auth,
        });
    }

    function connected() {
        return Boolean(echo) && echo.connector.pusher.connection.state === 'connected';
    }

    function idle() {
        return Date.now() - lastActive >= IDLE_MS;
    }

    function active() {
        const wasIdle = idle();

        lastActive = Date.now();

        // Polling stopped while nobody was here, so catch up at once.
        if (wasIdle && !connected() && !document.hidden) {
            resumes.forEach(function (refresh) {
                refresh(false);
            });
        }
    }

    ['pointerdown', 'pointermove', 'keydown', 'wheel', 'scroll', 'touchstart'].forEach(function (type) {
        window.addEventListener(type, active, { capture: true, passive: true });
    });

    // A tab brought back to the front counts as someone looking at it; each
    // part refreshes itself on that below.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            lastActive = Date.now();
        }
    });

    // refresh(true) after a signal: the change is committed, so fetch it in full.
    // refresh(false) on the timer: the part may answer "unchanged" cheaply.
    function watch(channel, event, refresh) {
        let timer = null;

        function soon() {
            clearTimeout(timer);
            timer = setTimeout(function () {
                refresh(true);
            }, SETTLE_MS);
        }

        if (echo) {
            echo.private(channel).listen('.' + event, soon);
            // Whatever changed while the connection was down is fetched on its return.
            echo.connector.pusher.connection.bind('connected', soon);
        }

        resumes.push(refresh);

        setInterval(function () {
            if (!connected() && !document.hidden && !idle()) {
                refresh(false);
            }
        }, POLL_MS);

        document.addEventListener('visibilitychange', function () {
            if (!connected() && !document.hidden) {
                refresh(false);
            }
        });
    }

    return { watch: watch };
})();
