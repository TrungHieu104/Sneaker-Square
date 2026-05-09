$(document).ready(function () {
    $('#search-form').on('submit', function (e) {
        e.preventDefault();
        var keyword = $(this).find('input[name="keyword"]').val();
        var currentPath = window.location.pathname;
        window.location.href = currentPath + '?keyword=' + keyword;
    });
});