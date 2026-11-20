// Asks the admin's own dialog before a form carrying data-confirm is sent or
// such a link is followed. See partials/confirm-modal.blade.php.
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
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter);
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
})();
