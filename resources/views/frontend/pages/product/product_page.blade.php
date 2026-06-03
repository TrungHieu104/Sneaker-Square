@extends('frontend.index')

@if ($getOneCate)
    @section('title')
        {{$getOneCate->cate_name}}
    @endsection
@elseif (url()->current() == route('product.hot'))
    @section('title')
        Sản phẩm nổi bật tại Sneaker Square
    @endsection
@elseif (url()->current() == route('product.sale'))
    @section('title')
        Sản phẩm đang sale tại Sneaker Square
    @endsection
@else
    @section('title')
        Tất cả sản phẩm tại Sneaker Square
    @endsection
@endif

@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Sản phẩm</h1>
                    <nav class="d-flex align-items-center">
                        <a href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Sản phẩm</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start Product Area -->
    <div class="container-fluid px-5 my-5">
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-5">
                <div class="container-fluid px-0">
                    <div class="accordion mb-3" id="accordionExample">
                        <div class="accordion-item rounded-0 border-0">
                            <div class="sidebar-categories">
                                <div class="accordion-header">
                                    @if (count($getAllCate) > 0)
                                        @foreach ($getAllCate as $item)
                                            @if ($item->cate_id == 1)
                                                <button class="accordion-button head rounded-0" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                        {{$item->cate_name}}
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show"
                                    data-bs-parent="#accordionExample">
                                    <ul class="main-categories">
                                        @if (count($getAllCate) > 0)
                                            @foreach ($getAllCate as $itemCate)
                                                @if ($itemCate->cate_parent_id == 1)
                                                    @if (count($itemCate->getProductsInCate) != 0)
                                                        <li class="main-nav-list">
                                                            <a data-toggle="collapse" class="{{( (url()->current()) == (route('product.by.cate', $itemCate->cate_slug)) ) ? 'active' : ''}}" href="{{route('product.by.cate', $itemCate->cate_slug)}}" aria-expanded="false"
                                                                aria-controls="fruitsVegetable">
                                                                <span class="lnr lnr-arrow-right"></span>{{$itemCate->cate_name}}<span
                                                                    class="number">({{ count($itemCate->getProductsInCate) }})</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion mb-3" id="accordionExample1">
                        <div class="accordion-item rounded-0 border-0">
                            <div class="sidebar-categories">
                                <div class="accordion-header">
                                    <button class="accordion-button head rounded-0" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                                        Đặc điểm
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div id="collapseTwo" class="accordion-collapse collapse show"
                                    data-bs-parent="#accordionExample2">
                                    <ul class="main-categories">
                                        <li class="main-nav-list">
                                            <a data-toggle="collapse" 
                                                class="{{( (url()->current()) == (route('product.hot')) ) ? 'active' : ''}}" 
                                                href="{{route('product.hot')}}" 
                                                aria-expanded="false"
                                                aria-controls="fruitsVegetable">
                                                <span class="lnr lnr-arrow-right"></span>Sản phẩm hot<span
                                                    class="number">
                                                    ({{ count($getHotProduct) }})
                                                </span>
                                            </a>
                                        </li>
                                        <li class="main-nav-list">
                                            <a data-toggle="collapse" 
                                                class="{{( (url()->current()) == (route('product.sale')) ) ? 'active' : ''}}" 
                                                href="{{route('product.sale')}}" 
                                                aria-expanded="false"
                                                aria-controls="fruitsVegetable">
                                                <span class="lnr lnr-arrow-right"></span>Sản phẩm sale<span
                                                    class="number">
                                                    ({{ count($getSaleProduct) }})
                                                </span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion mb-3" id="accordionExample2">
                        <div class="accordion-item rounded-0 border-0">
                            <div class="sidebar-categories">
                                <div class="accordion-header">
                                    <button class="accordion-button head rounded-0" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true"
                                        aria-controls="collapseThree">
                                        Phụ kiện
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div id="collapseThree" class="accordion-collapse collapse show"
                                    data-bs-parent="#accordionExample2">
                                    <ul class="main-categories">
                                        @if (count($getAllCate) > 0)
                                            @foreach ($getAllCate as $itemCate)
                                                @if ($itemCate->cate_parent_id == 6)
                                                    @if (count($itemCate->getProductsInCate) != 0)
                                                        <li class="main-nav-list">
                                                            <a data-toggle="collapse" class="{{( (url()->current()) == (route('product.by.cate', $itemCate->cate_slug)) ) ? 'active' : ''}}" href="{{route('product.by.cate', $itemCate->cate_slug)}}" aria-expanded="false"
                                                                aria-controls="fruitsVegetable">
                                                                <span class="lnr lnr-arrow-right"></span>{{$itemCate->cate_name}}<span
                                                                    class="number">({{ count($itemCate->getProductsInCate) }})</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-lg-8 col-md-7">
                <!-- Start Filter Bar -->
                <div class="container-fluid px-0">
                    <div class="filter-bar d-flex flex-wrap align-items-center justify-content-end" id="filter-bar">
                        <div class="box">
                            <form name="sortProduct" id="sortProduct">
                                <input type="hidden" name="url" id="url" value="{{$url}}">
                                <select class="form-select getsort" name="sort" id="sort">
                                    <option value="">Sắp xếp theo</option>
                                    <option value="gia-giam"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "gia-giam")
                                            selected
                                        @endif
                                    >Giá giảm dần</option>
                                    <option value="gia-tang"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "gia-tang")
                                            selected
                                        @endif
                                    >Giá tăng dần</option>
                                    <option value="moi-nhat"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "moi-nhat")
                                            selected
                                        @endif
                                    >Mới nhất</option>
                                    <option value="cu-nhat"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "cu-nhat")
                                            selected
                                        @endif
                                    >Cũ nhất</option>
                                    <option value="a-z"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "a-z")
                                            selected
                                        @endif
                                    >A-Z</option>
                                    <option value="z-a"
                                        @if (isset($_GET['sort']) && $_GET['sort'] == "z-a")
                                            selected
                                        @endif
                                    >Z-A</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Filter Bar -->

                <!-- Start Best Seller -->
                <section class="lattest-product-area pb-40 category-list">
                    <div class="container-fluid px-0">
                        <div class="row" id="appendProducts">
                            @include('frontend.pages.product.product_filter')
                        </div>
                    </div>
                </section>
                <!-- End Best Seller -->
            </div>
        </div>
    </div>
    <!-- End Product Area -->

    <!-- Start Product new  -->
    @foreach ($getAllCate as $cates)
        @if ($cates->cate_id == 6) 
            <section class="product-list__relate">
                <div class="container-fluid px-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 text-center" data-aos="fade-right" data-aos-duration="2000">
                            <div class="section-title">
                                <h1>{{$cates->cate_name}}</h1>
                                <p>
                                    Phụ kiện hoàn hảo cho đôi giày của bạn - cùng shop khám phá ngay
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="product-relate_item mb-5">
                        <!-- Product Area Item-->
                        @foreach ($getAccessories as $accessories)
                            @foreach ($accessories->getProductsInCate->where('pro_hidden', 1) as $accessories)
                                <!-- Product Area Item-->
                                <div class="rounded item__product border">
                                    <div class="overflow-hidden rounded-top img_product-relate w-100" id="img_product">
                                        <a href="{{ route('product.detail', $accessories->pro_slug) }}">
                                            <img class="img-fluid w-100 h-100 zoom border-bottom" onerror="this.src='/uploads/img_error2.jpg'"
                                                src="{{ asset($accessories->pro_img) }}" alt="{{ route('product.detail', $accessories->pro_name) }}">
                                        </a>
                                    </div>
                                    <div class="product-details">
                                        <a href="{{ route('product.detail', $accessories->pro_slug) }}">
                                            <h6 class="px-2 title__truncate">
                                                {{ $accessories->pro_name }}
                                            </h6>
                                        </a>
                                        <div class="price d-flex gap-3 justify-content-center mb-3">
                                            @if ($accessories->pro_price_sale != 0)
                                                <h6 class="price__product">
                                                    {{ number_format($accessories->pro_price_sale, 0, ',', '.') }} VNĐ
                                                </h6>
                                                <h6 class="price__product l-through px-0">
                                                    {{ number_format($accessories->pro_price, 0, ',', '.') }} VNĐ
                                                </h6>
                                            @else
                                                <h6 class="price__product">
                                                    {{ number_format($accessories->pro_price, 0, ',', '.') }} VNĐ
                                                </h6>
                                            @endif
                                        </div>
                                        <div class="prd-bottom d-flex justify-content-center">
                                            <a href="{{ route('product.detail', $accessories->pro_slug) }}" class="btn btn-order mb-3 px-3 text-white">
                                                Mua ngay
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach
    <!-- End Product new  -->
@endsection
