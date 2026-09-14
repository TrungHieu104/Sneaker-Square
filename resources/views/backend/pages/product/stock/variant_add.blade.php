@php
    $addErrors = $errors->getBag(\App\Http\Requests\Backend\StockVariantRequest::ERROR_BAG);
@endphp

<div class="modal fade" id="add-variant-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <h3 class="text-center">Thêm biến thể — {{ $product->pro_name }}</h3>

                <form action="{{ route('stock.variant.store', $product->pro_slug) }}" id="add-variant-form" method="POST">
                    @csrf
                    <div class="card mb-4 card-border-top">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="add-variant-size">Size</label>
                                    <select class="form-select" id="add-variant-size" name="size_id">
                                        <option value="">Không phân size</option>
                                        @foreach ($allSize as $size)
                                            <option value="{{ $size->size_id }}" @selected($addErrors->isNotEmpty() && old('size_id') == $size->size_id)>
                                                {{ $size->size }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @foreach ($addErrors->get('size_id') as $error)
                                        <small class="text-danger fst-italic">{{ $error }}</small>
                                    @endforeach
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="add-variant-color">Màu sắc</label>
                                    <select class="form-select" id="add-variant-color" name="color_id">
                                        <option value="">Không phân màu</option>
                                        @foreach ($allColor as $color)
                                            <option value="{{ $color->color_id }}" @selected($addErrors->isNotEmpty() && old('color_id') == $color->color_id)>
                                                {{ $color->color_vn }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @foreach ($addErrors->get('color_id') as $error)
                                        <small class="text-danger fst-italic">{{ $error }}</small>
                                    @endforeach
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="add-variant-quantity">Số lượng</label>
                                    <input type="number" class="form-control" id="add-variant-quantity" name="quantity"
                                           value="{{ $addErrors->isNotEmpty() ? old('quantity') : '' }}" />
                                    @foreach ($addErrors->get('quantity') as $error)
                                        <small class="text-danger fst-italic">{{ $error }}</small>
                                    @endforeach
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="add-variant-date">Ngày nhập</label>
                                    <input type="date" class="form-control" id="add-variant-date" name="quantity_date"
                                           value="{{ $addErrors->isNotEmpty() ? old('quantity_date', $today) : $today }}" />
                                    @foreach ($addErrors->get('quantity_date') as $error)
                                        <small class="text-danger fst-italic">{{ $error }}</small>
                                    @endforeach
                                </div>
                            </div>

                            <p class="text-muted fst-italic">
                                Để trống ô giá nào thì biến thể này lấy đúng giá của sản phẩm cho ô đó.
                                Giá sản phẩm hiện tại: bán
                                {{ number_format($product->sellingPrice(), 0, ',', '.') }} VNĐ,
                                vốn {{ number_format($product->capital_price, 0, ',', '.') }} VNĐ.
                            </p>

                            <div class="row">
                                @foreach (['pro_price' => 'Giá bán', 'pro_price_sale' => 'Giá giảm', 'capital_price' => 'Giá vốn'] as $field => $label)
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label" for="add-variant-{{ $field }}">{{ $label }}</label>
                                        <input type="number" class="form-control" id="add-variant-{{ $field }}"
                                               name="{{ $field }}"
                                               value="{{ $addErrors->isNotEmpty() ? old($field) : '' }}"
                                               placeholder="Theo sản phẩm" />
                                        @foreach ($addErrors->get($field) as $error)
                                            <small class="text-danger fst-italic">{{ $error }}</small>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="add-variant-form" title="Thêm">Thêm</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>

@if ($addErrors->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[data-bs-target="#add-variant-modal"]')?.click();
        });
    </script>
@endif
