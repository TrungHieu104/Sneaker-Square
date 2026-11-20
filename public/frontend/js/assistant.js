// The support assistant widget. Every answer, button and order card comes
// from the server; this file only draws them. Text is set with textContent,
// never as HTML, so nothing a model or a customer writes can run here.
(function () {
    const root = document.getElementById('assistant');

    if (!root) {
        return;
    }

    const TIP_KEY = 'ss_assistant_tip';
    const SEEN_KEY = 'ss_assistant_seen';
    const TIP_MS = 5000;
    const POLL_MS = 4000;
    // With the panel closed, polling only lights the unread dot, so it can be slower.
    const POLL_CLOSED_MS = 15000;
    // Answers are written by a queue worker; while one is due the widget asks more often.
    const THINK_POLL_MS = 1500;
    // Longer than the job's own 75s limit, after which it leaves a busy notice:
    // giving up only happens when no worker is running at all.
    const THINK_GIVE_UP_MS = 80000;
    const WITH_STAFF = ['waiting_staff', 'answered'];
    // The bot writes the shop's own pages as full addresses; staff may paste any.
    const URL_PATTERN = 'https?:\\/\\/[^\\s<>"]+';
    const COPIED_MS = 2000;
    const STARTERS = [
        ['Tra đơn hàng', 'Đơn hàng gần nhất của mình tới đâu rồi?'],
        ['Tư vấn size', 'Mẫu này mình nên lấy size nào?'],
        ['Đổi trả, bảo hành', 'Chính sách đổi trả của shop thế nào?'],
    ];

    const panel = root.querySelector('.assistant-panel');
    const body = root.querySelector('.assistant-body');
    const form = root.querySelector('.assistant-form');
    const input = form.querySelector('input');
    const sendButton = form.querySelector('button');
    const tip = root.querySelector('.assistant-tip');
    const dot = root.querySelector('.assistant-dot');
    const token = document.querySelector('meta[name="csrf-token"]');
    const productMeta = document.querySelector('meta[name="assistant-product"]');

    const hotline = (root.dataset.hotline || '').replace(/\D/g, '');
    // However the number is spaced in the text: 0369 469 525, 0369.469.525, 0369469525.
    const linkable = new RegExp(URL_PATTERN + (hotline ? '|(?<!\\d)' + hotline.split('').join('[\\s.]?') + '(?!\\d)' : ''), 'g');
    const siteOrigin = originOf(root.dataset.site);

    let lastId = 0;
    let status = 'bot';
    let greeting = 'Chào bạn, mình có thể giúp gì cho bạn?';
    let started = false;
    let busy = false;
    let poll = null;
    let pollEvery = 0;
    let thinkingDots = null;
    let thinkingSince = 0;
    let thinkingTimer = null;

    function el(tag, className, text) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = text;
        }

        return node;
    }

    function originOf(address) {
        try {
            return new URL(address).origin;
        } catch (e) {
            return null;
        }
    }

    // The bot only links to this shop: a customer cannot talk it into handing
    // out a stranger's address. Its links carry the configured site address,
    // which may differ from the one in the address bar, so they open here.
    function hrefFor(found, sender) {
        if (!/^https?:/i.test(found)) {
            return 'tel:' + hotline;
        }

        const url = new URL(found);

        if (url.origin === window.location.origin || url.origin === siteOrigin) {
            return window.location.origin + url.pathname + url.search + url.hash;
        }

        return sender === 'staff' ? url.href : null;
    }

    function copyOnClick(link) {
        link.title = 'Bấm để gọi hoặc chép số';
        link.addEventListener('click', function () {
            if (!navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(hotline).then(function () {
                link.classList.add('is-copied');
                setTimeout(function () { link.classList.remove('is-copied'); }, COPIED_MS);
            }, function () {});
        });
    }

    function appendText(node, text, sender) {
        let from = 0;

        for (const match of text.matchAll(linkable)) {
            const found = match[0].replace(/[.,;:!?)\]]+$/, '');
            const href = hrefFor(found, sender);

            if (href === null) {
                continue;
            }

            node.appendChild(document.createTextNode(text.slice(from, match.index)));
            // A shop link shows where it really opens, not the configured address it was written with.
            const shown = href.startsWith(window.location.origin + '/') ? href.replace(/^https?:\/\//, '') : found;
            const link = el('a', 'assistant-link', shown);
            link.href = href;

            if (href.startsWith('tel:')) {
                copyOnClick(link);
            } else if (originOf(href) !== window.location.origin) {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            }

            node.appendChild(link);
            from = match.index + found.length;
        }

        node.appendChild(document.createTextNode(text.slice(from)));
    }

    function remember(key, value) {
        try {
            localStorage.setItem(key, String(value));
        } catch (e) {
            // Private windows may refuse storage; the widget works without it.
        }
    }

    function recall(key) {
        try {
            return Number(localStorage.getItem(key) || 0);
        } catch (e) {
            return 0;
        }
    }

    function scrollDown() {
        body.scrollTop = body.scrollHeight;
    }

    function showTipOnce() {
        if (recall(TIP_KEY)) {
            return;
        }

        remember(TIP_KEY, 1);

        tip.hidden = false;
        setTimeout(function () { tip.hidden = true; }, TIP_MS);
    }

    function drawStarters() {
        body.appendChild(el('div', 'assistant-msg is-bot', greeting));
        const chips = el('div', 'assistant-chips');

        STARTERS.forEach(function ([label, question]) {
            const chip = el('button', 'assistant-chip', label);
            chip.type = 'button';
            chip.addEventListener('click', function () { ask(question); });
            chips.appendChild(chip);
        });

        body.appendChild(chips);
    }

    function drawCard(card) {
        const box = el('div', 'assistant-card');
        const title = el('h4', null, card.title);
        title.appendChild(el('span', 'assistant-tag is-' + card.tone, card.status));
        box.appendChild(title);

        card.rows.forEach(function ([label, value, flag]) {
            const line = el('div', 'row-line');
            line.appendChild(el('span', null, label));
            const right = el('span', null, value);

            if (flag) {
                right.appendChild(document.createTextNode(' '));
                right.appendChild(el('span', 'assistant-tag is-late', flag));
            }

            line.appendChild(right);
            box.appendChild(line);
        });

        body.appendChild(box);
    }

    function runAction(action, row) {
        if (action.kind === 'link') {
            window.location.href = action.value;
        } else if (action.kind === 'handoff') {
            handOff();
        } else if (action.kind === 'select_size') {
            const onPage = productMeta && Number(productMeta.content) === action.product;
            const label = onPage && document.querySelector('.product-list_size label[data-size="' + action.value + '"]');

            if (label) {
                label.click();
                label.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                window.location.href = action.url;
            }
        } else if (action.kind === 'dismiss') {
            row.remove();
        }
    }

    function drawActions(actions) {
        const row = el('div', 'assistant-actions');

        actions.forEach(function (action) {
            const button = el('button', 'assistant-action' + (action.primary ? ' is-primary' : ''), action.label);
            button.type = 'button';
            button.dataset.kind = action.kind;
            button.disabled = action.kind === 'handoff' && WITH_STAFF.includes(status);
            button.addEventListener('click', function () { runAction(action, row); });
            row.appendChild(button);
        });

        body.appendChild(row);
    }

    function draw(message) {
        if (message.sender === 'system') {
            body.appendChild(el('div', 'assistant-system', message.body));
        } else if (message.sender === 'customer') {
            body.appendChild(el('div', 'assistant-msg is-customer', message.body));
        } else {
            if (message.card) {
                drawCard(message.card);
            }

            const bubble = el('div', 'assistant-msg is-' + message.sender);

            if (message.sender === 'staff') {
                bubble.appendChild(el('span', 'assistant-who', 'Nhân viên Sneaker Square'));
            }

            appendText(bubble, message.body, message.sender);
            body.appendChild(bubble);

            if (message.sources && message.sources.length) {
                const sources = el('div', 'assistant-sources');
                message.sources.forEach(function (source) {
                    const chip = el('a', null, source.title);
                    chip.href = hrefFor(source.url, 'bot') || source.url;
                    sources.appendChild(chip);
                });
                body.appendChild(sources);
            }

            if (message.actions && message.actions.length) {
                drawActions(message.actions);
            }
        }

        lastId = Math.max(lastId, message.id);
    }

    function setStatus(next) {
        status = next;
        const withStaff = WITH_STAFF.includes(status);
        root.querySelector('.assistant-note-bot').hidden = withStaff;
        root.querySelector('.assistant-note-staff').hidden = !withStaff;
        body.querySelectorAll('[data-kind="handoff"]').forEach(function (button) { button.disabled = withStaff; });

        let every = 0;

        if (withStaff) {
            every = panel.hidden ? POLL_CLOSED_MS : POLL_MS;
        }

        if (every !== pollEvery) {
            clearInterval(poll);
            poll = every ? setInterval(refresh, every) : null;
            pollEvery = every;
        }
    }

    function apply(state) {
        greeting = state.greeting || greeting;

        if (!started && !panel.hidden) {
            started = true;
            drawStarters();
        }

        const fresh = (state.messages || []).filter(function (m) { return m.id > lastId; });

        // A staff answer the customer has not opened the panel to read yet.
        if (panel.hidden && fresh.some(function (m) { return m.sender === 'staff' && m.id > recall(SEEN_KEY); })) {
            dot.hidden = false;
        }

        if (!panel.hidden) {
            fresh.forEach(draw);
            remember(SEEN_KEY, lastId);
            scrollDown();
        }

        setStatus(state.status);
        setThinking(Boolean(state.thinking));
    }

    function stopThinking() {
        clearTimeout(thinkingTimer);
        thinkingTimer = null;

        // Only a wait this function started is released here; a send in flight releases itself.
        if (thinkingDots) {
            thinkingDots.remove();
            thinkingDots = null;
            busy = false;
            sendButton.disabled = false;
        }
    }

    function setThinking(on) {
        if (!on || panel.hidden) {
            stopThinking();

            return;
        }

        if (!thinkingDots) {
            thinkingDots = typing();
            thinkingSince = Date.now();
        }

        // Kept under whatever was just drawn.
        body.appendChild(thinkingDots);
        scrollDown();
        busy = true;
        sendButton.disabled = true;
        clearTimeout(thinkingTimer);

        if (Date.now() - thinkingSince > THINK_GIVE_UP_MS) {
            stopThinking();
            body.appendChild(el('div', 'assistant-system', 'Trợ lý trả lời hơi lâu. Bạn mở lại khung chat sau ít phút, hoặc gặp nhân viên nhé.'));
            scrollDown();

            return;
        }

        thinkingTimer = setTimeout(refresh, THINK_POLL_MS);
    }

    function request(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }, options)).then(function (response) {
            if (!response.ok) {
                throw response;
            }

            return response.json();
        });
    }

    function refresh() {
        const open = panel.hidden ? 0 : 1;

        // A closed panel only needs what came after the last answer the customer saw.
        return request(root.dataset.show + '?sau=' + (panel.hidden ? recall(SEEN_KEY) : lastId) + '&mo=' + open, { method: 'GET' })
            .then(apply)
            .catch(function () {});
    }

    function typing() {
        const node = el('div', 'assistant-typing');
        node.append(el('i'), el('i'), el('i'));
        body.appendChild(node);
        scrollDown();

        return node;
    }

    function failed(response) {
        const text = response && response.status === 429
            ? 'Bạn hỏi hơi nhanh, đợi một chút rồi hỏi tiếp nhé.'
            : 'Chưa gửi được câu hỏi. Bạn kiểm tra kết nối rồi thử lại giúp mình.';
        body.appendChild(el('div', 'assistant-system', text));
        scrollDown();
    }

    function post(url, payload) {
        busy = true;
        sendButton.disabled = true;
        const dots = typing();

        return request(url, { method: 'POST', body: JSON.stringify(Object.assign({ sau: lastId }, payload)) })
            .then(function (state) {
                dots.remove();
                body.querySelectorAll('.is-pending').forEach(function (node) { node.remove(); });
                apply(state);
            })
            .catch(function (response) {
                dots.remove();

                // Not sent, so not shown as sent: the text goes back to the box to try again.
                body.querySelectorAll('.is-pending').forEach(function (node) { node.remove(); });

                if (payload.message && !input.value) {
                    input.value = payload.message;
                }

                failed(response);
            })
            .finally(function () {
                if (!thinkingDots) {
                    busy = false;
                    sendButton.disabled = false;
                }
            });
    }

    function ask(question) {
        const text = question.trim();

        if (!text || busy) {
            return;
        }

        body.appendChild(el('div', 'assistant-msg is-customer is-pending', text));
        scrollDown();
        input.value = '';
        post(root.dataset.send, { message: text, pro_id: productMeta ? productMeta.content : null });
    }

    function handOff() {
        if (!busy) {
            post(root.dataset.handoff, {});
        }
    }

    function open() {
        panel.hidden = false;
        tip.hidden = true;
        dot.hidden = true;
        root.classList.add('is-open');
        lastId = 0;
        body.textContent = '';
        started = false;
        refresh().then(function () { input.focus(); });
    }

    function close() {
        panel.hidden = true;
        root.classList.remove('is-open');
        stopThinking();
        setStatus(status);
    }

    root.querySelector('.assistant-open').addEventListener('click', open);
    root.querySelector('.assistant-close').addEventListener('click', close);
    root.querySelector('.assistant-staff').addEventListener('click', handOff);
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        ask(input.value);
    });

    showTipOnce();
    refresh();
})();
