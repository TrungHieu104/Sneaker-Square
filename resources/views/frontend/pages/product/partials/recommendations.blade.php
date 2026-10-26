@if ($similar->isNotEmpty())
    <section class="product-list__relate" id="recommend-similar">
        <div class="container-fluid px-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="section-title">
                        <h1>Có thể bạn cũng thích</h1>
                    </div>
                </div>
            </div>
            <div class="product-relate_item mb-5">
                @foreach ($similar as $match)
                    @include('components.product_tile', [
                        'product' => $match['product'],
                        'reasons' => $match['reasons'],
                        'url' => route('product.detail', [$match['product']->pro_slug, 'tu' => 'goi-y']),
                    ])
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($recent->isNotEmpty())
    <section class="product-list__relate" id="recommend-recent">
        <div class="container-fluid px-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="section-title">
                        <h1>Sản phẩm bạn đã xem</h1>
                    </div>
                </div>
            </div>
            <div class="product-relate_item mb-5">
                @foreach ($recent as $product)
                    @include('components.product_tile', [
                        'product' => $product,
                        'reasons' => [],
                        'url' => route('product.detail', [$product->pro_slug, 'tu' => 'da-xem']),
                    ])
                @endforeach
            </div>
        </div>
    </section>
@endif
