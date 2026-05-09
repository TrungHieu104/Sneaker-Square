$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).ready(function() {
    $('input[name="status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            type: "POST",
            url: "/admin/status/"+id,
            data: {
                status: value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="n-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/blog-status/"+id,
            data: {
                'n-status': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="n-hot"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/blog-hot/"+id,
            data: {
                'n-hot': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="cate-new-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/blog-category-status/"+id,
            data: {
                'cate-new-status': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="tag-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/tag-status/"+id,
            data: {
                'tag-status': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="p-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            type: "POST",
            url: "/admin/promotion-status/"+id,
            data: {
                'p-status': value
            },
            success: function(response) {
                var statusElement = $('#status_' + id);
                statusElement.css('color', value == 0 ? 'red' : 'green');
                statusElement.text(value == 0 ? 'Vô hiệu' : 'Kích hoạt');
                location.reload();
            },
            error: function(xhr, pstatus, error) {
                // Xử lý lỗi
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="m-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/menu-status/"+id,
            data: {
                'm-status': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, pstatus, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="faq-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            type: "POST",
            url: "/admin/faq-status/" + id,
            data: {
                'faq-status': value
            },
            success: function(response) {
                var statusElement = $('#status_' + id);
                statusElement.css('color', value == 0 ? 'red' : 'green');
                statusElement.text(value == 0 ? 'Vô hiệu' : 'Kích hoạt');
                location.reload();
            },
            error: function(xhr, status, error) {
                // Xử lý lỗi
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="info-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            type: "POST",
            url: "/admin/info-status/"+id,
            data: {
                'info-status': value
            },
            success: function(response) {
                var statusElement = $('#status_' + id);
                statusElement.css('color', value == 0 ? 'red' : 'green');
                statusElement.text(value == 0 ? 'Vô hiệu' : 'Kích hoạt');
                location.reload();
            },
            error: function(xhr, pstatus, error) {
                // Xử lý lỗi
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    var currentTab = sessionStorage.getItem('currentTab');
    if (currentTab) {
        $('#' + currentTab).tab('show');
    }
    $('.nav-link').click(function () {
        var tabId = $(this).attr('id');
        sessionStorage.setItem('currentTab', tabId);
    });

    $('input[name="info-status"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            type: "POST",
            url: "/admin/info-status/"+id,
            data: {
                'info-status': value
            },
            success: function(response) {
                var statusElement = $('#status_' + id);
                statusElement.css('color', value == 0 ? 'red' : 'green');
                statusElement.text(value == 0 ? 'Vô hiệu' : 'Kích hoạt');
                location.reload();
            },
            error: function(xhr, pstatus, error) {
                // Xử lý lỗi
                // console.log(xhr.responseJSON.message);
            }
        });
    });


    var currentTab = sessionStorage.getItem('currentTab');
    if (currentTab) {
        $('#' + currentTab).tab('show');
    }
    $('.nav-link').click(function () {
        var tabId = $(this).attr('id');
        sessionStorage.setItem('currentTab', tabId);
    });

    $('input[name="cate_hidden"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/product-categories/update-status/"+id,
            data: {
                'cate_hidden': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });

        var $className = $('.cate-parent-id-' + id);
        $className.prop('checked', $(this).prop('checked'));
    });

    $('input[name="pro_hot"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/products/update-hot/"+id,
            data: {
                'pro_hot': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });


    $('input[name="pro_hidden"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "POST",
            url: "/admin/products/update-status/"+id,
            data: {
                'pro_hidden': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="comment_hidden"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "PUT",
            url: "/admin/comment/"+id,
            data: {
                'comment_hidden': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

    $('input[name="img_hidden"]').on('change', function() {
        var id = $(this).data('id');
        var value = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            type: "PUT",
            url: "/admin/image/"+id,
            data: {
                'img_hidden': value
            },
            success: function(response) {
                // console.log(response.message);
            },
            error: function(xhr, status, error) {
                // console.log(xhr.responseJSON.message);
            }
        });
    });

});