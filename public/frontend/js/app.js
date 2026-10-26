// The horizontal product strips. A function so a strip that arrives after the
// page has loaded, like the product page's recommendations, gets the same one.
window.initProductStrip = function (strip) {
    $(strip).slick({
        slidesToShow: 4,
        arrows: true,
        autoplay: true,
        rewind: true,
        loop: true,
        lazyLoad: false,
        autoplaySpeed: 2000,
        centerMode: true,
        centerPadding: '40px',
        prevArrow: "<button type='button' class='arrow_prev slick-prev pull-left'><i class='fas fa-angle-left' aria-hidden='true'></i></button>",
        nextArrow: "<button type='button' class='arrow_next slick-next pull-right'><i class='fas fa-angle-right' aria-hidden='true'></i></button>",
        responsive: [
            {
                breakpoint: 1000,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 2
                }
            },
            {
                breakpoint: 768,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: false,
                    centerMode: true,
                    centerPadding: '20px',
                    slidesToShow: 1
                }
            }
        ]
    });
};

$(document).ready(function () {
    $('.product-detail_thumb').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.product-detail_gallery',
    });
    $('.product-detail_gallery').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: '.product-detail_thumb',
        dots: false,
        centerMode: true,
        focusOnSelect: true,
        prevArrow: "<button type='button' class='slick-prev pull-left'><i class='fas fa-angle-left' aria-hidden='true'></i></button>",
        nextArrow: "<button type='button' class='slick-next pull-right'><i class='fas fa-angle-right' aria-hidden='true'></i></button>",
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
            }
        ]
    });
    initProductStrip('.product-relate_item');
})
