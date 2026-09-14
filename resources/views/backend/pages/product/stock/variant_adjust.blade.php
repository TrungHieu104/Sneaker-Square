@php
    $adjustErrors = $errors->getBag(
        \App\Http\Requests\Backend\StockAdjustRequest::errorBagFor($quantity->quantity_id)
    );
    $adjustMode = $adjustErrors->isNotEmpty() ? old('mode', 'in') : 'in';
@endphp

<div class="modal fade" id="adjust-modal-{{ $quantity->quantity_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <h3 class="text-center">Điều chỉnh tồn kho — {{ $quantity->getProducts->pro_name }}</h3>
                <p class="text-center text-muted">
                    {{ $quantity->getColor?->color_vn ?? 'Không phân màu' }}
                    @if ($quantity->getSize)
                        &middot; Size {{ $quantity->getSize->size }}
                    @endif
                    &middot; đang tồn <b>{{ $quantity->quantity }}</b>
                </p>

                <form action="{{ route('stock.adjust', $quantity->quantity_id) }}"
                      id="adjust-form-{{ $quantity->quantity_id }}" method="POST"
                      data-in-stock="{{ $quantity->quantity }}">
                    @csrf {{ method_field('PUT') }}
                    <div class="card mb-4 card-border-top">
                        <div class="card-body">
                            <div class="row mb-3">
                                @foreach (['in' => 'Nhập thêm', 'out' => 'Giảm bớt'] as $value => $label)
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input adjust-mode" type="radio" name="mode"
                                                   value="{{ $value }}"
                                                   id="adjust-mode-{{ $value }}-{{ $quantity->quantity_id }}"
                                                   @checked($adjustMode === $value) />
                                            <label class="form-check-label"
                                                   for="adjust-mode-{{ $value }}-{{ $quantity->quantity_id }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @foreach ($adjustErrors->get('mode') as $error)
                                <small class="text-danger fst-italic d-block mb-2">{{ $error }}</small>
                            @endforeach

                            <div class="mb-3">
                                <label class="form-label" for="adjust-quantity-{{ $quantity->quantity_id }}">Số lượng</label>
                                <input type="number" class="form-control adjust-quantity"
                                       id="adjust-quantity-{{ $quantity->quantity_id }}" name="quantity"
                                       value="{{ $adjustErrors->isNotEmpty() ? old('quantity') : '' }}" />
                                @foreach ($adjustErrors->get('quantity') as $error)
                                    <small class="text-danger fst-italic">{{ $error }}</small>
                                @endforeach
                            </div>

                            <div class="mb-3 adjust-date-field">
                                <label class="form-label" for="adjust-date-{{ $quantity->quantity_id }}">Ngày nhập</label>
                                <input type="date" class="form-control"
                                       id="adjust-date-{{ $quantity->quantity_id }}" name="quantity_date"
                                       value="{{ $adjustErrors->isNotEmpty() ? old('quantity_date', $today) : $today }}" />
                                @foreach ($adjustErrors->get('quantity_date') as $error)
                                    <small class="text-danger fst-italic">{{ $error }}</small>
                                @endforeach
                            </div>

                            <p class="text-muted fst-italic mb-1 adjust-preview"></p>
                            <p class="text-muted fst-italic mb-0">
                                Giá của biến thể này không thay đổi. Sửa giá ở nút giá của dòng.
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="adjust-form-{{ $quantity->quantity_id }}" title="Lưu">Lưu</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('script-backend')
        <script>
            document.querySelectorAll('[id^="adjust-form-"]').forEach(function (form) {
                const inStock = parseInt(form.dataset.inStock, 10);
                const quantity = form.querySelector('.adjust-quantity');
                const dateField = form.querySelector('.adjust-date-field');
                const preview = form.querySelector('.adjust-preview');

                function reducing() {
                    return form.querySelector('.adjust-mode:checked')?.value === 'out';
                }

                function refresh() {
                    // A date belongs to a delivery: "ngày nhập hàng gần nhất" must not
                    // move because something was written off.
                    dateField.hidden = reducing();

                    const amount = parseInt(quantity.value, 10);

                    if (!amount || amount < 1) {
                        preview.textContent = '';
                        return;
                    }

                    const after = reducing() ? inStock - amount : inStock + amount;

                    preview.textContent = after < 0
                        ? 'Chỉ còn ' + inStock + ' trong kho.'
                        : 'Tồn sau điều chỉnh: ' + inStock + ' → ' + after;
                }

                form.querySelectorAll('.adjust-mode').forEach(function (radio) {
                    radio.addEventListener('change', refresh);
                });
                quantity.addEventListener('input', refresh);
                refresh();
            });
        </script>
    @endpush
@endonce

@if ($adjustErrors->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[data-bs-target="#adjust-modal-{{ $quantity->quantity_id }}"]')?.click();
        });
    </script>
@endif
