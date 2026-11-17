$(document).ready(function () {
    $('#search-form').on('submit', function (e) {
        e.preventDefault();
        var keyword = $(this).find('input[name="keyword"]').val();
        var currentPath = window.location.pathname;
        window.location.href = currentPath + '?keyword=' + keyword;
    });

    // A list filter leaves out the fields left blank: the navbar script sends
    // any page whose URL holds an empty `keyword=` back to the dashboard.
    $('form[data-list-filter]').on('submit', function () {
        $(this).find('input, select').filter(function () {
            return this.name && this.value === '';
        }).prop('disabled', true);
    });
});