// Drag-and-drop order for an admin table. A <tbody data-sortable-url> posts the
// data-id of its rows in their new order as soon as a row is dropped, renumbers
// the [data-row-number] cells, and puts the rows back if the save fails.
document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let toast;
    let toastTimer;

    function notify(message, ok) {
        if (!toast) {
            toast = document.createElement('div');
            toast.setAttribute('role', 'status');
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 260px; box-shadow: 0 4px 12px rgba(0,0,0,.15);';
            document.body.appendChild(toast);
        }
        toast.className = 'alert position-fixed ' + (ok ? 'alert-success' : 'alert-danger');
        toast.textContent = message;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.add('d-none'); }, 2500);
    }

    function renumber(body) {
        body.querySelectorAll('[data-row-number]').forEach(function (cell, index) {
            cell.textContent = index + 1;
        });
    }

    document.querySelectorAll('tbody[data-sortable-url]').forEach(function (body) {
        let before = [];

        new Sortable(body, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onStart: function () {
                before = Array.from(body.children);
            },
            onEnd: function (event) {
                if (event.oldIndex === event.newIndex) {
                    return;
                }

                const ids = Array.from(body.querySelectorAll('tr[data-id]')).map(function (row) {
                    return Number(row.dataset.id);
                });

                renumber(body);
                fetch(body.dataset.sortableUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ ids: ids })
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw new Error(data.message || 'Không lưu được thứ tự, thử lại.');
                            }
                            notify(data.message, true);
                        });
                    })
                    .catch(function (error) {
                        before.forEach(function (row) { body.appendChild(row); });
                        renumber(body);
                        notify(error.message || 'Không lưu được thứ tự, thử lại.', false);
                    });
            }
        });
    });
});
