@extends('frontend.index')

@section('title')
    Trang chủ
@endsection

@section('content')
    <div class="container-fluid p-0 overflow-hidden">
        <!-- Start features Area -->
        <section>
            <div class="container-fluid px-5 section_gap">
                <div class="container-fluid box__shadow rounded">
                    <div class="row">
                        <div
                            class="col-lg-3 col-md-6 col-sm-6 p-3 d-flex justify-content-center align-items-center flex-column single-features">
                            <div class="f-icon">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-giao-hang.html">
                                    <img src="/frontend/img/features/f-icon1.png" alt="" class="w-100 h-100">
                                </a>
                            </div>
                            <div class="text-center mt-2">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-giao-hang.html">
                                    <h6 class="fw-bold">Chính sách giao hàng</h6>
                                </a>
                                <p class="mt-2">Phí vận chuyển ưu đãi</p>
                            </div>
                        </div>
                        <div
                            class="col-lg-3 col-md-6 col-sm-6 p-3 d-flex justify-content-center align-items-center flex-column single-features">
                            <div class="f-icon">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-bao-hanh-va-doi-tra.html">
                                    <img src="/frontend/img/features/f-icon3.png" alt="" class="w-100 h-100">
                                </a>
                            </div>
                            <div class="text-center mt-2">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-bao-hanh-va-doi-tra.html">
                                    <h6 class="fw-bold">Chính sách đổi trả</h6>
                                </a>
                                <p class="mt-2">Đổi trả & quyền lợi khách hàng</p>
                            </div>
                        </div>
                        <div
                            class="col-lg-3 col-md-6 col-sm-6 p-3 d-flex justify-content-center align-items-center flex-column single-features">
                            <div class="f-icon">
                                <a href="{{route('contact.page')}}">
                                    <img src="/frontend/img/features/f-icon2.png" alt="" class="w-100 h-100">
                                </a>
                            </div>
                            <div class="text-center mt-2">
                                <a href="{{route('contact.page')}}">
                                    <h6 class="fw-bold">Hỗ trợ 24/7</h6>
                                </a>
                                <p class="mt-2">Không có giới hạn thời gian khi bạn cần</p>
                            </div>
                        </div>
                        <div
                            class="col-lg-3 col-md-6 col-sm-6 p-3 d-flex justify-content-center align-items-center flex-column single-features">
                            <div class="f-icon">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-bao-mat.html">
                                    <img src="/frontend/img/features/f-icon4.png" alt="" class="w-100 h-100">
                                </a>
                            </div>
                            <div class="text-center mt-2">
                                <a href="{{ env('APP_URL') }}/chinh-sach/chinh-sach-bao-mat.html">
                                    <h6 class="fw-bold">Bảo mật thông tin</h6> 
                                </a>
                                <p class="mt-2">An tâm trải nghiệm dịch vụ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- end features Area -->

        <!-- start product Area -->
        <section class="section_gap pt-0">
            <!-- single product slide -->
            <div class="single-product-slider">
                <div class="container-fluid px-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 text-center" data-aos="fade-right" data-aos-duration="2000">
                            <div class="section-title">
                                <h1>Bộ sưu tập</h1>
                                <p>
                                    Bộ sưu tập độc đáo dành riêng cho những người yêu thời trang và đôi giày.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- single product -->
                        @foreach ($hotProduct as $hotPro)
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="single-product rounded">
                                    <div class="overflow-hidden rounded-top" id="img_product">
                                        <a href="{{ route('product.detail', $hotPro->pro_slug) }}">
                                            <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                                                src="{{ $hotPro->pro_img }}"
                                                alt="{{ $hotPro->pro_name }}">
                                        </a>
                                    </div>
                                    <div class="product-details">
                                        <a href="{{ route('product.detail', $hotPro->pro_slug) }}">
                                            <div class="product-name-box">
                                                <h6 class="px-2 text__truncate py-1">
                                                    {{ $hotPro->pro_name }}
                                                </h6>
                                            </div>
                                        </a>
                                        
                                        @if ($hotPro->pro_price_sale != 0 )
                                            <div class="price d-flex gap-3 justify-content-center">
                                                <h6 class="price__product">
                                                    {{ number_format($hotPro->pro_price_sale, 0, ',', '.') }} VNĐ
                                                </h6>
                                                <h6 class="price__product l-through px-0">
                                                    {{ number_format($hotPro->pro_price, 0, ',', '.') }} VNĐ
                                                </h6>
                                            </div>
                                        @else
                                            <div class="price d-flex gap-3 justify-content-center">
                                                <h6 class="price__product px-0">
                                                    {{ number_format($hotPro->pro_price, 0, ',', '.') }} VNĐ
                                                </h6>
                                            </div>
                                        @endif

                                        <div class="prd-bottom d-flex justify-content-center">
                                            <a href="{{ route('product.detail', $hotPro->pro_slug) }}"
                                                class="btn btn-order my-3 px-3 text-white">
                                                Mua ngay
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center">
                        <a href="{{route('product.page')}}" class="btn-next__pro">
                            <div class="svg-wrapper-1">
                              <div class="svg-wrapper">
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  viewBox="0 0 24 24"
                                  width="24"
                                  height="24"
                                >
                                  <path fill="none" d="M0 0h24v24H0z"></path>
                                  <path
                                    fill="currentColor"
                                    d="M1.946 9.315c-.522-.174-.527-.455.01-.634l19.087-6.362c.529-.176.832.12.684.638l-5.454 19.086c-.15.529-.455.547-.679.045L12 14l6-8-8 6-8.054-2.685z"
                                  ></path>
                                </svg>
                              </div>
                            </div>
                            <span>Xem thêm</span>
                        </a>
                    </div>
                </div>
            </div>
            <!-- single product slide -->
        </section>
        <!-- end product Area -->

        <!-- Start brand Area -->
        <section class="brand-area" id="brand-area">
            <div class="container-fluid px-5">
                <div class="row">
                    @foreach ($catePro as $item)
                        <a class="col single-img" href="{{route('product.by.cate', $item->cate_slug)}}">
                            <img class="img-fluid d-block mx-auto" src="{{asset($item->cate_img)}}" alt="{{$item->cate_name}}" width="108px" height="70px">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        <!-- End brand Area -->

        <!-- Start exclusive deal Area -->
        <section class="exclusive-deal-area">
            <div class="container-fluid">
                <div class="row justify-content-center align-items-center overflow-x-hidden">
                    <div class="col-lg-6 no-padding exclusive-left">
                        <div class="container-fluid px-5">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="box-product__sale bg-white p-3 rounded">
                                        <h5>Đa dạng</h5>
                                        <img src="/frontend/img/brand.png" alt="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="box-product__sale bg-white p-3 rounded">
                                        <h5>Phong cách</h5>
                                        <img src="/frontend/img/hand.png" alt="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="box-product__sale bg-white p-3 rounded">
                                        <h5>Chính hãng</h5>
                                        <img src="/frontend/img/badge.png" alt="">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="box-product__sale bg-white p-3 rounded">
                                        <h5>Ưu đãi</h5>
                                        <img src="/frontend/img/price-tag.png" alt="">
                                    </div>
                                </div>
                            </div>
                            <a href="{{route('product.page')}}" class="primary-btn">Xem ngay</a>
                        </div>
                    </div>
                    <div class="col-lg-6 no-padding exclusive-right">
                        <div class="active-exclusive-product-slider">
                            <!-- single exclusive carousel -->
                            @foreach ($saleProduct as $salePro)
                                <div class="single-exclusive-slider">
                                    <img class="img-fluid mb-3" onerror="this.src='/uploads/img_error2.jpg'"
                                        src="{{ $salePro->pro_img }}" 
                                        alt="{{ $salePro->pro_name }}">
                                    <div class="product-details">
                                        <div class="price">
                                            <h6>
                                                {{ number_format($salePro->pro_price_sale, 0, ',', '.') }} VNĐ
                                            </h6>
                                            <h6 class="l-through">
                                                {{ number_format($salePro->pro_price, 0, ',', '.') }} VNĐ
                                            </h6>
                                        </div>
                                        <a href="{{ route('product.detail', $salePro->pro_slug) }}">
                                            <h4> {{ $salePro->pro_name }} </h4>
                                        </a>
                                        <div class="add-bag d-flex align-items-center justify-content-center">
                                            <a href="{{ route('product.detail', $salePro->pro_slug) }}">
                                                <span class="ti-bag bag-home"></span>
                                                <span class="add-text text-uppercase">Thêm vào giỏ</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End exclusive deal Area -->

        <!-- Start related-product Area -->
        <section class="related-product-area section_gap">
            <div class="container-fluid px-5">
                <div class="row justify-content-center">
                    <div class="col-lg-6 text-center">
                        <div class="section-title">
                            <h1>Sản phẩm xem nhiều nhất</h1>
                            <p>Top sản phẩm nhiều lượt xem. Đề xuất bạn thuận tiện tham khảo các khuyến nghị từ các khách hàng khác.</p>
                        </div>
                    </div>
                </div>
                <div class="row overflow-x-hidden">
                    <div class="col-lg-9">
                        <div class="row">
                            <!-- single product -->
                            @foreach ($mostViewProduct as $mostViewPro)
                                <div class="col-lg-4 col-md-4 col-sm-6">
                                    <div class="single-related-product d-flex">
                                        <a href="{{ route('product.detail', $mostViewPro->pro_slug) }}">
                                            <img onerror="this.src='/uploads/img_error2.jpg'"
                                                src="{{ $mostViewPro->pro_img }}" 
                                                alt="{{ $mostViewPro->pro_name }}" class="rounded image-related border">
                                        </a>
                                        <div class="desc">
                                            <a href="{{ route('product.detail', $mostViewPro->pro_slug) }}" class="title__truncate">
                                                {{ $mostViewPro->pro_name }}
                                            </a>
                                            <div class="price">
                                                @if ($mostViewPro->pro_price_sale != 0)
                                                    <h6> {{ number_format($mostViewPro->pro_price_sale, 0, ',', '.') }} VNĐ </h6>
                                                    <h6 class="l-through"> {{ number_format($mostViewPro->pro_price, 0, ',', '.') }} VNĐ </h6>
                                                @else
                                                    <h6> {{ number_format($mostViewPro->pro_price, 0, ',', '.') }} VNĐ </h6>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="ctg-right w-100 h-100">
                            <img class="d-block mx-auto w-100 h-100 rounded object-fit-fill"
                                src="https://img.freepik.com/free-photo/blue-sports-shoe-untied-ready-action-generated-by-ai_188544-25546.jpg"
                                alt="">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End related-product Area -->

        <!-- Start blog -->
        @if ($hotNews->isEmpty())
            
        @else
            <section class="section__social_bottom" id="section__social">
                <div class="container-fluid px-5 overflow-hidden">
                    <div class="row justify-content-center overflow-hidden">
                        <div class="col-lg-6 text-center">
                            <div class="section-title">
                                <h1>Sneaker Square Blog</h1>
                                <p>Những thông tin chia sẻ hữu ích đến cho các bạn. </p>
                            </div>
                        </div>
                    </div>
                    <div class="row overflow-x-hidden">
                        <div class="col-lg-6 mb-5 rounded-3 social__info">
                            <a href="{{route('news.detail', $hotNews[0]->news_slug) }}">
                            <img class="w-100 h-100 rounded-3"
                                src="{{ $hotNews[0]->news_img }}" onerror="this.src='/uploads/img_error2.jpg'"  alt="{{$hotNews[0]->news_title}}">
                            </a>
                        </div>
                        <div class="col-lg-6 mb-5">
                            <div class="container-fluid px-0" id="item__blog">
                                <div class="row g-4" id="section__blog">
                                    @for ($i = 1; $i < count($hotNews); $i++)
                                        <div class="col-lg-6 col-md-6">
                                            <div class="card box__shadow rounded-3 border-0 ">
                                                <div class="img__blog rounded-3 w-100">
                                                    <a href="{{route('news.detail', $hotNews[$i]->news_slug)}}">
                                                        <img class="img-fluid w-100 h-100 zoom" src="{{$hotNews[$i]->news_img}}" onerror="this.src='/uploads/img_error2.jpg'"  alt="{{$hotNews[$i]->news_title}}">
                                                    </a>
                                                </div>
                                                <div class="card-body mt-3">
                                                    <a href="{{route('news.detail', $hotNews[$i]->news_slug)}}">
                                                        <h5 class="card-title title__truncate">{{$hotNews[$i]->news_title}}</h5>
                                                    </a>
                                                    <p class="card-text text__truncate">{{$hotNews[$i]->news_summarize}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
        <!-- Start blog -->
    </div>
@endsection