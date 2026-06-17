@extends('frontend.index')

@section('title')
    {{ $detailProduct->pro_name }}
@endsection

@section('banner')
    <!-- Start banner Area -->

    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start List Product  -->
    <section class="product-page_detail mt-5">
        <div class="container-fluid px-5">
            <div class="mb-3 navigation-breadcumb">
                <ul aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{route('home.page')}}">Trang chủ</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{route('product.page')}}">Giày</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a
                                href="{{ route('product.by.cate', $detailProduct->getCate->cate_slug) }}">{{ $detailProduct->getCate->cate_name }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $detailProduct->pro_name }}</li>
                    </ol>
                </ul>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="product-detail_thumb mb-3">
                        <!-- Image thumb image  -->
                        <div class="product-detail_image border rounded">
                            <img class="img-fluid rounded" onerror="this.src='/uploads/img_error2.jpg'"
                                src="{{ asset($detailProduct->pro_img) }}" alt="{{ $detailProduct->pro_name }}">
                        </div>

                        @foreach ($detailProduct->getImages->where('img_hidden', 1) as $image)
                            <div class="product-detail_image border rounded">
                                <img class="img-fluid rounded" onerror="this.src='/uploads/img_error2.jpg'"
                                    src="{{ asset($image->img_name) }}" alt="{{ $detailProduct->pro_name }}">
                            </div>
                        @endforeach
                    </div>

                    <div class="product-detail_gallery w-75 mx-auto">
                        <!-- Image thumb click slide  -->
                        <div class="product-gallery_image">
                            <img class="img-fluid" onerror="this.src='/uploads/img_error2.jpg'"
                                src="{{ asset($detailProduct->pro_img) }}" alt="{{ $detailProduct->pro_name }}">
                        </div>

                        @foreach ($detailProduct->getImages->where('img_hidden', 1) as $image)
                            <div class="product-gallery_image">
                                <img class="img-fluid" onerror="this.src='/uploads/img_error2.jpg'"
                                    src="{{ asset($image->img_name) }}" alt="{{ $detailProduct->pro_name }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-6">
                    <form id="product-form" action="{{ route('addProduct.cart', $detailProduct->pro_slug) }}"
                        method="POST">
                        @csrf
                        <input type="text" name="pro_name" value="{{ $detailProduct->pro_name }}" hidden>
                        
                        <div class="product-detail_info mt-3">
                            <h2 class="fw-bold">{{ $detailProduct->pro_name }}</h2>
                            <h6 class="fw-bold">SKU: <span class="fw-normal">{{ $detailProduct->pro_code }}</span></h6>
                        </div>
                        <div class="product-detail_price mt-4">
                            @if ($detailProduct->pro_price_sale != 0)
                                <div class="d-flex align-items-center gap-4">
                                    <h3 class="fw-bold price-new">
                                        {{ number_format($detailProduct->pro_price_sale, 0, ',', '.') }} VNĐ
                                    </h3>
                                    <h4 class="text-decoration-line-through price-old">
                                        {{ number_format($detailProduct->pro_price, 0, ',', '.') }} VNĐ
                                    </h4>
                                </div>
                                <input type="text" name="pro_price" value="{{ $detailProduct->pro_price_sale }}" hidden>
                            @else
                                <h3 class="fw-bold price-new">
                                    {{ number_format($detailProduct->pro_price, 0, ',', '.') }} VNĐ
                                </h3>
                                <input type="text" name="pro_price" value="{{ $detailProduct->pro_price }}" hidden>
                            @endif
                        </div>
                        {{-- Display color --}}
                        @if ($getColor->isNotEmpty())
                            <div class="product-detail_color mt-4 d-flex gap-3 align-items-center">
                                <label class="fw-bold fs-6">Màu sắc:</label>
                                <div class="product-list_color d-flex gap-2">
                                    @php $i = 1; @endphp
                                    @foreach ($getColor as $color)
                                        <input type="radio" class="btn-check btn-radio" name="options-color"
                                            id="color{{ $i }}" value="{{ $color->color_id }}"
                                            autocomplete="off">
                                        <label class="btn btn-style" id="color-style"
                                            for="color{{ $i }}"style="border-radius:50%; background-color:{{ $color->color }} "></label>
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <i><span id="color-error" style="color: red;"></span></i>

                        {{-- Display size --}}
                        @if ($getSize->isNotEmpty())
                            <div class="product-detail_size mt-3">
                                <label class="fw-bold fs-6 mb-2">Size:</label>
                                <div class="product-list_size d-flex flex-wrap gap-3">
                                    @php $i = 1; @endphp
                                    @foreach ($getSize as $size)
                                        <input type="radio" class="btn-check btn-radio" name="options-size"
                                            id="size{{ $i }}" value="{{ $size->size_id }}" autocomplete="off">
                                        <label class="btn btn-style px-4"
                                            for="size{{ $i }}">{{ $size->size }}</label>
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <i><span id="size-error" style="color: red;"></span></i>

                        <div class="product-detail_quality mt-3">
                            <label class="fw-bold fs-6 mb-2">Số lượng:</label>
                            <div class="d-flex">
                                <button type="button" class="minus rounded-start-2">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantity" min="1" max="20" value="1"
                                    class="btn_quality">
                                <button type="button"    class="plus rounded-end-2">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-add_cart mt-3">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-12 px-0 d-flex gap-3 mb-3 flex-wrap product-data">
                                        <div class="flex-fill">
                                            <a href="{{ route('product.page') }}"
                                                class="w-100 h-100 py-2 d-flex align-items-center justify-content-center btn text-white btn-order gap-2 fw-bold btn-add-pro__detail">
                                                 <i class='bx bx-shopping-bag fs-5'></i> Tiếp tục mua hàng
                                            </a>
                                        </div>
                                        <div class="flex-fill">
                                            <input type="hidden" class="product-id" name="" value="{{ $detailProduct->pro_id }}">
                                        @if (Auth::check())
                                            <input type="checkbox" id="favorite" name="favorite-checkbox"
                                            value="favorite-button" class="add-wishlist-btn" {{ (isset($likeStatus) && (($likeStatus->like_status) == 1)) ? 'checked' : '' }}>
                                        @else
                                            <input type="checkbox" id="favorite" name="favorite-checkbox"
                                                value="favorite-button" class="add-wishlist-btn">
                                        @endif
                                        <label for="favorite" class="favorite py-2 rounded">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-heart">
                                                    <path
                                                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                                                    </path>
                                                </svg>
                                                <div class="action">
                                                    <span class="option-1">Thêm mục yêu thích</span>
                                                    <span class="option-2">Đã thêm yêu thích</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 px-0">
                                        <button class="btn btn-order w-100 py-2 btn-cart_order text-white fw-bold"
                                            type="submit"> Mua hàng</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion accordion-flush mt-3" id="accordionFlushExample">
                            @foreach ($faq as $item)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#flush-{{$item->faq_id}}" aria-expanded="false"
                                            aria-controls="flush-{{$item->faq_id}}">
                                            {{$item->faq_name}}
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </h2>
                                    <div id="flush-{{$item->faq_id}}" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionFlushExample">
                                        <div class="accordion-body">
                                            {{$item->faq_description}}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>
    <!-- End List Product  -->

    <!-- Start Product Description  -->
    <section class="product-detail_des my-5">
        <div class="container-fluid px-5">
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <div class="box__shadow py-3">
                        <div class="container">
                            <div class="row nav nav-pills justify-content-center gap-2">
                                <li class="nav-item col-lg-3 col-md-6 col-sm-6">
                                    <a class="w-100 btn btn-outline-primary tab-comment__detail active" data-bs-toggle="pill"
                                        href="#tab-1">Mô
                                        tả</a>
                                </li>
                                <li class="nav-item col-lg-3 col-md-6 col-sm-6">
                                    <a class="w-100 btn btn-outline-primary tab-comment__detail" data-bs-toggle="pill" href="#tab-2">Bình
                                        luận ({{ count($detailProduct->getComments->where('comment_hidden', 1)) }})</a>
                                </li>
                            </div>
                        </div>
                        <hr>
                        <div class="tab-content">
                            <div id="tab-1" class="tab-pane fade show active px-4">
                                <div class="description"> 
                                    {!! $detailProduct->pro_description !!}
                                </div>
                            </div>
                            
                            <div id="tab-2" class="tab-pane fade show px-4">
                                <div class="comment-form">
                                    <h4>Bình luận</h4>
                                    <div class="container-fluid px-0">
                                        @if (Auth::check())
                                            <form action="{{ route('comments.store', $detailProduct->pro_id) }}" method="POST" id="form-comment">
                                                @csrf {{ method_field('POST') }}
                                                <div class="row">
                                                    <div class="col-lg-6 mb-4 position-relative">
                                                        <input type="text" class="form-control" placeholder="Họ tên" name="comment_name" id="comment_name"
                                                            onfocus="this.placeholder=''" onblur="this.placeholder='Họ tên'" value="{{ Auth::guard('web')->user()->name }}" readonly>
                                                        <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                    <div class="col-lg-6 mb-4 position-relative">
                                                        <input type="text" class="form-control" placeholder="Email" name="comment_email" id="comment_email"
                                                            onfocus="this.placeholder=''" onblur="this.placeholder='Email'" value="{{ Auth::guard('web')->user()->email }}" readonly>
                                                        <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                    <div class="col-lg-12 mb-4 position-relative">
                                                        <textarea  id="comment_content" cols="" rows="2" class="form-control" name="comment_content" placeholder="Nội dung"
                                                            onfocus="this.placeholder=''" onblur="this.placeholder='Nội dung'"></textarea>
                                                        <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn primary-btn submit_btn mt-2 px-5">Bình luận</button>
                                            </form>
                                        @else
                                            <form action="{{ route('comments.store', $detailProduct->pro_id) }}" method="POST" id="form-comment">
                                                @csrf {{ method_field('POST') }}
                                                <div class="row">
                                                    <div class="col-lg-6 mb-4 position-relative">
                                                        <input type="text" class="form-control" placeholder="Họ tên" name="comment_name"
                                                            onfocus="this.placeholder=''" onblur="this.placeholder='Họ tên'" id="comment_name">
                                                        <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                    <div class="col-lg-6 mb-4 position-relative">
                                                        <input type="text" class="form-control" placeholder="Email" name="comment_email" id="comment_email"
                                                            onfocus="this.placeholder=''" onblur="this.placeholder='Email'">
                                                        <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                    <div class="col-lg-12 mb-4 position-relative">
                                                        <textarea cols="" rows="2" class="form-control" placeholder="Nội dung" name="comment_content"
                                                            onfocus="this.placeholder=''" id="comment_content" onblur="this.placeholder='Nội dung'">{{ old('comment_content') }}</textarea>
                                                            <small class="error-text position-absolute text-danger fst-italic"></small>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn primary-btn submit_btn mt-2 px-5">Bình luận</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="comments-area">
                                    <h4> {{ count($detailProduct->getComments->where('comment_hidden', 1)) }} bình luận</h4>
                                    @foreach ($detailProduct->getComments->where('comment_hidden', 1)->sortByDesc('comment_date') as $comment)
                                        <div class="comment-list">
                                            <div class="single-comment justify-content-between d-flex">
                                                <div class="user justify-content-between d-flex">
                                                    <div class="desc">
                                                        <h5>
                                                            <a>
                                                                @if ($comment->user_id != null)
                                                                    {{ $comment->getUsers->name }}
                                                                @elseif ($comment->comment_name != null)
                                                                    {{ $comment->comment_name }}
                                                                @else
                                                                    {{ 'Người dùng ẩn danh' }}
                                                                @endif
                                                            </a>
                                                        </h5>
                                                        <p class="date">
                                                            {{ date('d-m-Y', strtotime($comment->comment_date)) }} lúc
                                                            {{ date('H:i', strtotime($comment->comment_date)) }}
                                                        </p>
                                                        <p class="comment">
                                                            {{ $comment->comment_content }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="container-fluid px-0">
                        <div class="row" id="scroller">
                            <!-- Product Item  -->
                            @foreach ($hotProduct as $hotPro)
                                <a href="{{ route('product.detail', $hotPro->pro_slug) }}">
                                    <div class="d-flex mb-3 gap-3">
                                        <img onerror="this.src='/uploads/img_error2.jpg'" class="card_border rounded" width="100px" height="100px"
                                        src="{{ asset($hotPro->pro_img) }}" alt="{{ $hotPro->pro_name }}">
                                        <div>
                                            <h6 class="text__truncate fw-bold">{{ $hotPro->pro_name }}</h6>
                                            <div class="d-flex mt-2">
                                                @if ($hotPro->pro_price_sale != 0)
                                                    <div class="d-flex gap-3 align-items-center flex-wrap">
                                                        <h5 class="fw-bold mb-0 color__price">
                                                            {{ number_format($hotPro->pro_price_sale, 0, ',', '.') }} VNĐ
                                                        </h5>
                                                        <del class="text-decoration-line-through price-old">
                                                            {{ number_format($hotPro->pro_price, 0, ',', '.') }} VNĐ
                                                        </del>
                                                    </div>
                                                @else
                                                    <h5 class="fw-bold mb-0 color__price">
                                                        {{ number_format($hotPro->pro_price, 0, ',', '.') }} VNĐ
                                                    </h5>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Product Description  -->

    <!-- Start Product new  -->
    @if ($relatedProduct->isNotEmpty())
        <section class="product-list__relate">
            <div class="container-fluid px-5">
                <div class="row justify-content-center">
                    <div class="col-lg-6 text-center">
                        <div class="section-title">
                            <h1>Sản phẩm liên quan</h1>
                        </div>
                    </div>
                </div>
                <div class="product-relate_item mb-5">
                    <!-- Product Item  -->
                    @foreach ($relatedProduct as $relatedPro)
                        <div class="rounded item__product border">
                            <div class="overflow-hidden rounded-top border-bottom img_product-relate">
                                <a href="{{ route('product.detail', $relatedPro->pro_slug) }}">
                                    <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                                        src="{{ asset($relatedPro->pro_img) }}" alt="{{ $relatedPro->pro_name }}">
                                </a>
                            </div>
                            <div class="product-details">
                                <a href="{{ route('product.detail', $relatedPro->pro_slug) }}">
                                    <h6 class="px-2 title__truncate">
                                        {{ $relatedPro->pro_name }}
                                    </h6>
                                </a>
                                <div class="price d-flex gap-3 justify-content-center mb-3">
                                    @if ($relatedPro->pro_price_sale != 0)
                                        <h6 class="price__product">
                                            {{ number_format($relatedPro->pro_price_sale, 0, ',', '.') }} VNĐ
                                        </h6>
                                        <h6 class="price__product l-through px-0">
                                            {{ number_format($relatedPro->pro_price, 0, ',', '.') }} VNĐ
                                        </h6>
                                    @else
                                        <h6 class="price__product">
                                            {{ number_format($relatedPro->pro_price, 0, ',', '.') }} VNĐ
                                        </h6>
                                    @endif
                                </div>
                                <div class="prd-bottom d-flex justify-content-center">
                                    <a href="{{ route('product.detail', $relatedPro->pro_slug) }}"
                                        class="btn btn-order mb-3 px-3 text-white">
                                        Mua ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- End Product new  -->

@endsection

@push('css-access')
    <style>
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
@endpush

@push('script-access')
    <script src="{{ asset('frontend/js/pro-detail.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#product-form').submit(function(event) {
                let colorError = false;
                let sizeError = false;

                // Check if a color is selected
                if ($("input[name='options-color']").length > 0 && $("input[name='options-size']").length > 0) {
                    if (!$("input[name='options-color']:checked").val()) {
                        $('#color-error').text('Vui lòng chọn màu.');
                        colorError = true;
                    } else {
                        $('#color-error').text('');
                    }

                    // Check if a size is selected
                    if (!$("input[name='options-size']:checked").val()) {
                        $('#size-error').text('Vui lòng chọn size.');
                        sizeError = true;
                    } else {
                        $('#size-error').text('');
                    }
                }
                if (colorError || sizeError) {
                    event.preventDefault();
                }
            });
            $('#product-form').submit(function(event) {
                let colorError = false;
                if ($("input[name='options-color']").length > 0) {
                    if (!$("input[name='options-color']:checked").val()) {
                        $('#color-error').text('Vui lòng chọn màu.');
                        colorError = true;
                    } else {
                        $('#color-error').text('');
                    }
                }
                if (colorError) {
                    event.preventDefault();
                }
            });

            $('.nav-pills a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
                var tabId = e.target.href.split('#')[1];
                localStorage.setItem('currentTab', tabId);
            });
            
            var currentTab = localStorage.getItem('currentTab');
            if (currentTab) {
                $('.nav-pills a[href="#' + currentTab + '"]').tab('show');
            }
        });
    </script>
    <script src="{{ asset('frontend/js/validate_comment.js') }}"></script>
@endpush