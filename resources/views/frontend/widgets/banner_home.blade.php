<section id="homepage_slider">
    <div class="slideshow">
        <p class="control-slideshow prev-slideshow transition"><i class="fas fa-chevron-left"></i></p>
        <div class="owl-carousel owl-theme owl-slideshow">
            @foreach($slide as $data)
                <div>
                    <div class="item">
                        <div class="bg" style="background-image: url({{$data->promotion_img}});"></div>
                        <div class="content_slide">
                            <div class="maxwidth">
                                <div class="slide_btn_content">
                                    <h2 class="first-fade text__truncate">{{$data->promotion_name}}</h2>
                                    <p class="second-fade text__truncate">{{$data->promotion_content}}</p>
                                    <a class="button_content_box" href="{{$data->promotion_link}}">Xem thêm</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="control-slideshow next-slideshow transition"><i class="fas fa-chevron-right"></i></p>
    </div>
</section>

@push('script-access')
    <script src="{{asset('frontend/js/slider_home.js')}}"></script>
@endpush
