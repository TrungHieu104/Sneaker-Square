{{-- One product in a horizontal strip: the related and recommended blocks. --}}
<div class="rounded item__product border">
    <div class="overflow-hidden rounded-top border-bottom img_product-relate">
        <a href="{{ $url }}">
            <img class="img-fluid w-100 h-100 zoom" onerror="this.src='/uploads/img_error2.jpg'"
                src="{{ asset($product->pro_img) }}" alt="{{ $product->pro_name }}">
        </a>
    </div>
    <div class="product-details">
        <a href="{{ $url }}">
            <h6 class="px-2 title__truncate">
                {{ $product->pro_name }}
            </h6>
        </a>
        @if (! empty($reasons))
            <div class="product-reasons d-flex flex-wrap justify-content-center gap-1 px-2 mb-2">
                @foreach ($reasons as $reason)
                    <span class="product-reason">{{ $reason }}</span>
                @endforeach
            </div>
        @endif
        <div class="price d-flex gap-3 justify-content-center mb-3">
            @if ($product->isOnSale())
                <h6 class="price__product">
                    {{ $product->displaySellingPrice() }} VNĐ
                </h6>
                <h6 class="price__product l-through px-0">
                    {{ $product->displayListPrice() }} VNĐ
                </h6>
            @else
                <h6 class="price__product">
                    {{ $product->displayListPrice() }} VNĐ
                </h6>
            @endif
        </div>
        <div class="prd-bottom d-flex justify-content-center">
            <a href="{{ $url }}" class="btn btn-order mb-3 px-3 text-white">
                Mua ngay
            </a>
        </div>
    </div>
</div>
