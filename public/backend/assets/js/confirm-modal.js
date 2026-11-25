// Asks the admin's own dialog before a form carrying data-confirm is sent,
// such a link is followed or such a radio is chosen. See
// partials/confirm-modal.blade.php.
(function () {
    const dialog = document.getElementById('admin-confirm');

    if (!dialog || !window.bootstrap) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(dialog);
    const text = document.getElementById('admin-confirm-text');
    const ok = document.getElementById('admin-confirm-ok');
    let proceed = null;

    function ask(source, then) {
        text.textContent = source.dataset.confirm;
        ok.textContent = source.dataset.confirmOk || 'Đồng ý';
        proceed = then;
        modal.show();
    }

    ok.addEventListener('click', function () {
        const then = proceed;
        proceed = null;
        modal.hide();

        if (then) {
            then();
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) {
            return;
        }

        event.preventDefault();
        const submitter = event.submitter || null;

        ask(form, function () {
            // requestSubmit() fires the submit event right away, so the flag is
            // spent by then; dropping it keeps a page restored by Back asking again.
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter);
            delete form.dataset.confirmed;
        });
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-confirm]');

        if (!link) {
            return;
        }

        event.preventDefault();
        ask(link, function () {
            window.location.href = link.href;
        });
    });

    document.addEventListener('click', function (event) {
        const radio = event.target.closest('input[type="radio"][data-confirm]');

        if (!radio) {
            return;
        }

        // Cancelling the click puts the group back as it was; if the radio is
        // still checked afterwards it already was the choice, so nothing to ask.
        event.preventDefault();
        setTimeout(function () {
            if (radio.checked) {
                return;
            }

            ask(radio, function () {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
})();
