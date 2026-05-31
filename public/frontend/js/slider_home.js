$('.owl-slideshow').owlCarousel({
    items: 1,
    rewind: true,
    autoplay: true,
    loop: true,
    lazyLoad: false,
    mouseDrag: false,
    touchDrag: false,
    animateIn: 'animate__animated animate__fadeIn',
    animateOut: 'animate__animated animate__fadeOut',
    margin: 0,
    smartSpeed: 500,
    autoplaySpeed: 1500,
    nav: false,
    dots: false
});

$('.prev-slideshow').click(function () {
    $('.owl-slideshow').trigger('prev.owl.carousel');
});

$('.next-slideshow').click(function () {
    $('.owl-slideshow').trigger('next.owl.carousel');
});