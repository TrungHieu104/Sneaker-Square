$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }

    });

    loadCount();

    $('.add-wishlist-btn').change(function (e) {
        e.preventDefault();

        var product_id = $(this).closest('.product-data').find('.product-id').val();

        $.ajax({
            method: "POST",
            url: "/them-san-pham-yeu-thich",
            data: {
                "product-id": product_id
            },
            success: function (response) {
                new Noty({
                    text: response.status,
                    layout: 'topRight',
                    theme: 'metroui',
                    type: response.icon,
                    timeout: 1000,
                    closeWith: ['click', 'button'],
                    animation: {
                        open : 'animated fadeInRight',
                        close: 'animated fadeOutRight'
                    }
                }).show();

                loadCount();
            }
        });

    });

    $('.delete-wish-list').click(function (e) {
        e.preventDefault();

        swal({
            title: "Bạn muốn xóa?",
            icon: "warning",
            buttons: ["Hủy", "Đồng ý"],
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                var product_id = $(this).closest('.pro-data-wish').find('.pro-id').val();
                $.ajax({
                    method: "POST",
                    url: "/xoa-yeu-thich",
                    data: {
                        "pro_id": product_id
                    },
                    success: function (response) {
                        new Noty({
                            text: response.status,
                            layout: 'topRight',
                            theme: 'metroui',
                            type: response.icon,
                            timeout: 1000,
                            closeWith: ['click', 'button'],
                            animation: {
                                open : 'animated fadeInRight',
                                close: 'animated fadeOutRight'
                            }
                        }).show();
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 2800);

                        loadCount();
                    }
                });
            }
        });
    });

    function loadCount() {
        $.ajax({
            method: "POST",
            url: "/yeu-thich-total",
            success: function (response) {
                if(response && response.count > 0) {
                    $('.count-heart').find('#quality').show();
                    $('.count-heart').find('#quality').text(response.count);
                }else {
                    $('.count-heart').find('#quality').hide();
                }
            }
        });
    }

});