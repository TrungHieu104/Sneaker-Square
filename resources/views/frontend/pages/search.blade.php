@extends('frontend.index')

@section('title')
    Kết quả tìm kiếm
@endsection

@section('banner')
    
@endsection

@section('content')
    <section class="container-fluid px-5 my-5 section-blog__page">
        <div class="row">
            @if (count($results) > 0)
                @foreach ($results as $item)
                    @if (class_basename($item) == 'ProductModel')
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="single-product rounded">
                                <div class="overflow-hidden rounded-top" id="img_product">
                                    <a href="{{route('product.detail', $item->pro_slug)}}">
                                        <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                                            src="{{asset($item->pro_img)}}"
                                            alt="{{$item->pro_name}}">
                                    </a>
                                </div>
                                <div class="product-details">
                                    <a href="{{route('product.detail', $item->pro_slug)}}">
                                        <div class="product-name-box">
                                            <h6 class="px-2 text__truncate">
                                                {{$item->pro_name}}
                                            </h6>
                                        </div>
                                    </a>
                                    @php
                                        $avgRating = $item->getAverageRating();
                                        $reviewCount = $item->getReviewCount();
                                    @endphp
                                    <div class="rating-box d-flex align-items-center justify-content-center gap-1 mb-2">
                                        @if ($reviewCount > 0)
                                            <div class="stars text-warning" style="font-size: 13px;">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= round($avgRating))
                                                        <i class="fas fa-star"></i>
                                                    @else
                                                        <i class="far fa-star"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">({{ $avgRating }})</small>
                                        @else
                                            <div class="stars text-muted" style="font-size: 13px;">
                                                <i class="far fa-star"></i>
                                                <i class="far fa-star"></i>
                                                <i class="far fa-star"></i>
                                                <i class="far fa-star"></i>
                                                <i class="far fa-star"></i>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">(0)</small>
                                        @endif
                                    </div>
                                    @if ($item->pro_price_sale != 0 )
                                        <div class="price d-flex gap-3 justify-content-center">
                                            <h6 class="price__product">
                                                {{ number_format($item->pro_price_sale, 0, ',', '.') }} VNĐ
                                            </h6>
                                            <h6 class="price__product l-through px-0">
                                                {{ number_format($item->pro_price, 0, ',', '.') }} VNĐ
                                            </h6>
                                        </div>
                                    @else
                                        <div class="price d-flex gap-3 justify-content-center">
                                            <h6 class="price__product px-0">
                                                {{ number_format($item->pro_price, 0, ',', '.') }} VNĐ
                                            </h6>
                                        </div>
                                    @endif
                                    <div class="prd-bottom d-flex justify-content-center">
                                        <a href="{{route('product.detail', $item->pro_slug)}}"
                                            class="btn btn-order my-3 px-3 text-white">
                                            Mua ngay
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(class_basename($item) == 'NewsModel')
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="single-product rounded">
                                <div class="overflow-hidden rounded-top" id="img_product">
                                    <a href="{{route('news.detail', $item->news_slug)}}">
                                        <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                                            src="{{asset($item->news_img)}}"
                                            alt="{{$item->news_title}}">
                                    </a>
                                </div>
                                <div class="product-details">
                                    <a href="{{route('news.detail', $item->news_slug)}}">
                                        <div class="product-name-box">
                                            <h6 class="px-2 text__truncate">
                                                {{$item->news_title}}
                                            </h6>
                                        </div>
                                    </a>
                                    <div class="price d-flex gap-3 justify-content-center">
                                        <span>
                                            {{date('d/m/Y', strtotime($item->post_date))}} 
                                        </span>
                                        &#8722;
                                        <span>
                                            <i class="far fa-eye"></i> &#8226; {{ $item->views }} 
                                        </span>
                                    </div>
                                    <div class="prd-bottom d-flex justify-content-center">
                                        <a href="{{route('news.detail', $item->news_slug)}}"
                                            class="btn btn-order my-3 px-3 text-white">
                                            Xem ngay
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                <div class="paginate d-flex justify-content-center align-items-center gap-3 url-pagination">
                    @if (isset($keyword))
                        {{ $results->appends(['keyword' => $keyword])->links('frontend.layouts.partials.pagination') }}
                    @else
                        {{ $results->links('frontend.layouts.partials.pagination') }}
                    @endif
                </div>
            @else
                <div class="col-12">
                    <div class="card border-0 box__shadow d-flex justify-content-center">
                        <h4 class="text-center py-3 mb-0">Không tìm thấy dữ liệu...</h4>
                        <img src="{{asset('uploads/9318694.jpg')}}" alt="" class="img-fluid mx-auto" width="280px" height="280px">
                    </div>
                </div>
            @endif
            
        </div>
    </section>
@endsection