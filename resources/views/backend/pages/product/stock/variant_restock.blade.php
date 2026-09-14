@php
    $restockErrors = $errors->getBag(
        \App\Http\Requests\Backend\StockRestockRequest::errorBagFor($quantity->quantity_id)
    );
@endphp

<div class="modal fade" id="restock-modal-{{ $quantity->quantity_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <h3 class="text-center">Nhập thêm — {{ $quantity->getProducts->pro_name }}</h3>
                <p class="text-center text-muted">
                    {{ $quantity->getColor?->color_vn ?? 'Không phân màu' }}
                    @if ($quantity->getSize)
                        &middot; Size {{ $quantity->getSize->size }}
                    @endif
                    &middot; đang tồn <b>{{ $quantity->quantity }}</b>
                </p>

                <form action="{{ route('stock.restock', $quantity->quantity_id) }}"
                      id="restock-form-{{ $quantity->quantity_id }}" method="POST">
                    @csrf {{ method_field('PUT') }}
                    <div class="card mb-4 card-border-top">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="restock-quantity-{{ $quantity->quantity_id }}">
                                    Số lượng nhập thêm
                                </label>
                                <input type="number" class="form-control"
                                       id="restock-quantity-{{ $quantity->quantity_id }}" name="quantity"
                                       value="{{ $restockErrors->isNotEmpty() ? old('quantity') : '' }}" />
                                @foreach ($restockErrors->get('quantity') as $error)
                                    <small class="text-danger fst-italic">{{ $error }}</small>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="restock-date-{{ $quantity->quantity_id }}">Ngày nhập</label>
                                <input type="date" class="form-control"
                                       id="restock-date-{{ $quantity->quantity_id }}" name="quantity_date"
                                       value="{{ $restockErrors->isNotEmpty() ? old('quantity_date', $today) : $today }}" />
                                @foreach ($restockErrors->get('quantity_date') as $error)
                                    <small class="text-danger fst-italic">{{ $error }}</small>
                                @endforeach
                            </div>

                            <p class="text-muted fst-italic mb-0">
                                Giá của biến thể này không thay đổi khi nhập thêm. Sửa giá ở nút giá của dòng.
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="restock-form-{{ $quantity->quantity_id }}" title="Nhập thêm">Nhập thêm</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>

@if ($restockErrors->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[data-bs-target="#restock-modal-{{ $quantity->quantity_id }}"]')?.click();
        });
    </script>
@endif
