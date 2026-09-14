{{-- A blank box here clears the variant's own price, unlike the intake form. --}}
@php
    $priceErrors = $errors->getBag(
        \App\Http\Requests\Backend\ProductVariantPriceRequest::errorBagFor($quantity->quantity_id)
    );
@endphp
<div class="modal fade" id="price-modal-{{ $quantity->quantity_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <h3 class="text-center">Giá — {{ $quantity->getProducts->pro_name }}</h3>
                <p class="text-center text-muted">
                    {{ $quantity->getColor?->color_vn ?? 'Không phân màu' }}
                    @if ($quantity->getSize)
                        &middot; Size {{ $quantity->getSize->size }}
                    @endif
                </p>

                <form action="{{ route('stock.update.price', $quantity->quantity_id) }}"
                      id="price-form-{{ $quantity->quantity_id }}" method="POST">
                    @csrf {{ method_field('PUT') }}
                    <div class="card mb-4 card-border-top">
                        <div class="card-body">
                            <p class="text-muted fst-italic">
                                Để trống ô nào thì biến thể này lấy đúng giá của sản phẩm cho ô đó.
                                Giá sản phẩm hiện tại: bán
                                {{ number_format($quantity->getProducts->sellingPrice(), 0, ',', '.') }} VNĐ,
                                vốn {{ number_format($quantity->getProducts->capital_price, 0, ',', '.') }} VNĐ.
                            </p>

                            @foreach (['pro_price' => 'Giá bán', 'pro_price_sale' => 'Giá giảm', 'capital_price' => 'Giá vốn'] as $field => $label)
                                <div class="row">
                                    <div class="mb-3">
                                        <label class="form-label" for="{{ $field }}-{{ $quantity->quantity_id }}">{{ $label }}</label>
                                        <input type="number" class="form-control"
                                               id="{{ $field }}-{{ $quantity->quantity_id }}"
                                               name="{{ $field }}"
                                               value="{{ $priceErrors->isNotEmpty() ? old($field, $quantity->{$field}) : $quantity->{$field} }}"
                                               placeholder="Theo sản phẩm" />
                                        @if ($priceErrors->has($field))
                                            @foreach ($priceErrors->get($field) as $error)
                                                <small class="text-danger fst-italic">{{ $error }}</small>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="price-form-{{ $quantity->quantity_id }}" title="Cập nhật">Cập nhật</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>

@if ($priceErrors->isNotEmpty())
    {{-- Reopen the row that failed, so the messages are not hidden behind a closed modal. --}}
    <script>
        // Through the trigger the page already has: this template's bootstrap bundle
        // does not publish a `bootstrap` global to construct a Modal from.
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[data-bs-target="#price-modal-{{ $quantity->quantity_id }}"]')?.click();
        });
    </script>
@endif
