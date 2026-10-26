// Reports what happens on a page without a request of its own. Fire and
// forget: the page never waits on it, and a lost beacon costs one analytics
// row, nothing the shopper sees.
window.trackEvent = function (type, data) {
    const endpoint = document.querySelector('meta[name="event-beacon"]');
    const token = document.querySelector('meta[name="csrf-token"]');

    if (!endpoint || !token || !navigator.sendBeacon) {
        return;
    }

    const form = new FormData();
    form.append('_token', token.content);
    form.append('type', type);

    Object.entries(data).forEach(function ([key, value]) {
        if (value !== null && value !== undefined && value !== '') {
            form.append(key, value);
        }
    });

    navigator.sendBeacon(endpoint.content, form);
};
