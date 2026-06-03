@foreach ($getAllProduct as $pro)
    <!-- single product -->
    <div class="col-lg-4 col-md-6">
        <div class="single-product rounded">
            <div class="overflow-hidden rounded-top" id="img_product">
                <a href="{{ route('product.detail', $pro->pro_slug) }}">
                    <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                        src="{{ asset($pro->pro_img) }}" alt="{{ asset($pro->pro_name) }}">
                </a>
            </div>
            <div class="product-details">
                <a href="{{ route('product.detail', $pro->pro_slug) }}">
                    <div class="product-name-box">
                        <h6 class="px-2 text__truncate">
                            {{ $pro->pro_name }}
                        </h6>
                    </div>
                </a>
                <div class="price d-flex gap-3 justify-content-center">
                    @if ($pro->pro_price_sale != 0)
                        <h6 class="price__product">
                            {{ number_format($pro->pro_price_sale, 0, ',', '.') }} VNĐ
                        </h6>
                        <h6 class="price__product l-through px-0">
                            {{ number_format($pro->pro_price, 0, ',', '.') }} VNĐ
                        </h6>
                    @else
                        <h6 class="price__product">
                            {{ number_format($pro->pro_price, 0, ',', '.') }} VNĐ
                        </h6>
                    @endif
                </div>
                <div class="prd-bottom d-flex justify-content-center">
                    <a href="{{ route('product.detail', $pro->pro_slug) }}" class="btn btn-order my-3 px-3 text-white">
                        Mua ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
@endforeach
<!-- Start Pagination Bar -->
<div class="paginate d-flex justify-content-center align-items-center gap-3 url-pagination">
    @if (isset($reqSort) || isset($keyword))
        {{ $getAllProduct->appends(['sort' => $reqSort, 'keyword' => $keyword])->links('frontend.layouts.partials.pagination') }}
    @else
        {{ $getAllProduct->links('frontend.layouts.partials.pagination') }}
    @endif
</div>
<!-- End Pagination Bar -->