@extends('frontend.index')

<!-- Title -->
@section('title')
    Sản phẩm yêu thích
@endsection

<!-- Banner -->
@section('banner')
    <!-- Start banner Area -->

    <!-- End banner Area -->
@endsection

<!-- Content -->
@section('content')
    <!-- Start Content Area -->
    <section class="wish-list__page">
        <div class="container-fluid px-5 mt-5">
            @if ($wishList->count() > 0)
                <div class="row justify-content-center overflow-hidden">
                    <div class="col-lg-6 text-center">
                        <div class="container-fluid px-0">
                            <div class="section-title">
                                <h1>Sản phẩm yêu thích</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col">
                        @foreach ($wishList as $item)
                            <div class="card mb-3 px-0 border-0 box__shadow">
                                <div class="row g-0">
                                    <div class="col-md-2 p-3">
                                        <a href="{{route('product.detail', $item->products->pro_slug)}}">
                                            <img onerror="this.src='/uploads/img_error2.jpg'" src="{{$item->products->pro_img}}" class="img-fluid rounded border img-wish__page" alt="{{$item->products->pro_name}}">
                                        </a>
                                    </div>
                                    <div class="col-md-10 d-flex align-items-center pro-data-wish">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-4 p-3 d-flex align-items-center">
                                                    <a href="{{route('product.detail', $item->products->pro_slug)}}">
                                                        <h4 class="mb-0 title__truncate fw-bold">{{$item->products->pro_name}}</h4>
                                                    </a>
                                                </div>
                                                <div class="col-lg-4 p-3 d-flex align-items-center">
                                                    @if ($item->products->pro_price_sale != 0)
                                                        <div class="d-flex align-items-center gap-4">
                                                            <h4 class="fw-bold price-text__wishlist mb-0">
                                                                {{ number_format($item->products->pro_price_sale, 0, ',', '.') }} VNĐ
                                                            </h4>
                                                            <h5 class="text-decoration-line-through price-old__text mb-0">
                                                                {{ number_format($item->products->pro_price, 0, ',', '.') }} VNĐ
                                                            </h5>
                                                        </div>
                                                    @else
                                                        <h4 class="fw-bold price-text__wishlist mb-0">
                                                            {{ number_format($item->products->pro_price, 0, ',', '.') }} VNĐ
                                                        </h4>
                                                    @endif
                                                </div>
                                                <div class="col-lg-4 p-3">
                                                    <div class="d-flex gap-3">
                                                        <input type="hidden" name="product-id" class="pro-id" value="{{$item->pro_id}}">
                                                        <div class="flex-fill">
                                                            <button class="btn delete-wish-list w-100 btn-order">
                                                                <i class='bx bx-trash'></i>
                                                            </button>
                                                        </div>
                                                        <div class="flex-fill">
                                                            <a href="{{route('product.detail', $item->products->pro_slug)}}" class="btn shopping-wish-list btn-order"><i class="fas fa-eye"></i> Xem sản phẩm</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 box__shadow d-flex mb-5 justify-content-center">
                            <h4 class="text-center py-3 mb-0">Chưa có sản phẩm...</h4>
                            <img src="{{asset('uploads/9318694.jpg')}}" alt="" class="img-fluid mx-auto" width="280px" height="280px">
                            <div class="mb-3 mx-auto">
                                <a href="{{route('product.page')}}"
                                    class="cssbuttons-io-button"> Xem thêm
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            width="24" height="24">
                                            <path fill="none" d="M0 0h24v24H0z"></path>
                                            <path fill="currentColor"
                                                d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z">
                                            </path>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!-- Start Content Area -->
@endsection