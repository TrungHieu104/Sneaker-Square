@extends('backend.index')

@section('title')
    Kho
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Kho</h4>
    <!-- Bordered Table -->
    <div class="card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý sản phẩm trong kho</h5>
            <button type="button" class="btn btn-primary me-4" data-bs-toggle="modal" data-bs-target="#add-variant-modal">
                <i class="fas fa-plus me-1"></i> Thêm biến thể
            </button>
        </div>
        <div class="card-body">
            @php
                $isFiltered = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            @endphp

            <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end mb-4">
                @if ($sizeOptions->isNotEmpty())
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label" for="filter-size">Size</label>
                        <select class="form-select" id="filter-size" name="size_id" onchange="this.form.submit()">
                            <option value="">Tất cả size</option>
                            @foreach ($sizeOptions as $size)
                                <option value="{{ $size->size_id }}" @selected($filters['size_id'] == $size->size_id)>
                                    {{ $size->size }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($colorOptions->isNotEmpty())
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label" for="filter-color">Màu sắc</label>
                        <select class="form-select" id="filter-color" name="color_id" onchange="this.form.submit()">
                            <option value="">Tất cả màu</option>
                            @foreach ($colorOptions as $color)
                                <option value="{{ $color->color_id }}" @selected($filters['color_id'] == $color->color_id)>
                                    {{ $color->color_vn }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="filter-price">Giá</label>
                    <select class="form-select" id="filter-price" name="price" onchange="this.form.submit()">
                        <option value="">Mọi loại giá</option>
                        <option value="own" @selected($filters['price'] === 'own')>Có giá riêng</option>
                        <option value="inherited" @selected($filters['price'] === 'inherited')>Theo sản phẩm</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="filter-stock">Tồn kho</label>
                    <select class="form-select" id="filter-stock" name="stock" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <option value="in" @selected($filters['stock'] === 'in')>Còn hàng</option>
                        <option value="out" @selected($filters['stock'] === 'out')>Hết hàng</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2 d-flex align-items-center gap-3">
                    <span class="text-muted">{{ $allQuantity->total() }} dòng</span>
                    @if ($isFiltered)
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
                    @endif
                </div>

                {{-- Filtering starts over rather than landing on page 7 of 2 results. --}}
                <input type="hidden" name="page" value="1" />
            </form>

            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Tên sản phẩm</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            @if ($hasSize)
                                <th class="text-center">Size</th>
                            @endif
                            @if ($hasColor)
                                <th class="text-center">Màu sắc</th>
                            @endif
                            <th class="text-center">Ngày nhập hàng gần nhất</th>
                            <th class="text-center">Số lượng trong kho</th>
                            <th class="text-center">Giá bán</th>
                            <th class="text-center">Giá vốn</th>
                            <th class="text-center" style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($allQuantity->isEmpty())
                            <tr>
                                <td class="text-center text-muted py-4" colspan="{{ 7 + ($hasSize ? 1 : 0) + ($hasColor ? 1 : 0) }}">
                                    Không có dòng nào khớp với bộ lọc.
                                </td>
                            </tr>
                        @endif

                        @foreach ($allQuantity as $quantity)
                            <tr>
                                <td class="text-center">
                                    {{ $quantity->getProducts->pro_name }}
                                </td>

                                <td class="px-3">
                                    <div class="img-container_admin mx-auto border p-1 rounded">
                                        <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($quantity->getProducts->pro_img) }}" class="img-list_admin rounded"
                                            alt="{{ $quantity->getProducts->pro_name }}">
                                    </div>
                                </td>

                                @if ($hasSize)
                                    <td class="text-center">
                                        {{ $quantity->getSize?->size ?? '—' }}
                                    </td>
                                @endif

                                @if ($hasColor)
                                    <td class="text-center">
                                        @if ($quantity->getColor)
                                            <div class="">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    value=""
                                                    id="color-swatch-{{ $quantity->quantity_id }}"
                                                    disabled
                                                    style="width: 80%; background-color: {{ $quantity->getColor->color }}; opacity: 1; border: 2px solid black;"
                                                />
                                                <label class="form-check-label" for="color-swatch-{{ $quantity->quantity_id }}"></label>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                
                                <td class="text-center">
                                    {{ date('d-m-Y', strtotime($quantity->quantity_date)) }}
                                </td>

                                <td class="text-center">
                                    @if ($quantity->quantity == 0)
                                        <span class="text-danger">Hết hàng</span>
                                    @else
                                        {{ $quantity->quantity }}
                                    @endif
                                </td>

                                <td class="text-center">
                                    @php
                                        $listPrice = $quantity->listPrice();
                                        $sellingPrice = $quantity->sellingPrice();
                                    @endphp
                                    {{ number_format($sellingPrice, 0, ',', '.') }} VNĐ
                                    @if ($sellingPrice < $listPrice)
                                        <br><del class="text-muted">{{ number_format($listPrice, 0, ',', '.') }} VNĐ</del>
                                    @endif
                                    {{-- Both columns: pro_price alone called a row "theo sản phẩm"
                                         while it sold at a sale price of its own. --}}
                                    @if ($quantity->pro_price === null && $quantity->pro_price_sale === null)
                                        <br><small class="text-muted fst-italic">theo sản phẩm</small>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ number_format($quantity->capitalPrice(), 0, ',', '.') }} VNĐ
                                    @if ($quantity->capital_price === null)
                                        <br><small class="text-muted fst-italic">theo sản phẩm</small>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div>
                                        <button type="button" class="btn btn-success btn-sm m-1" title="Nhập thêm"
                                                data-bs-toggle="modal" data-bs-target="#restock-modal-{{ $quantity->quantity_id }}">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm m-1" title="Sửa giá"
                                                data-bs-toggle="modal" data-bs-target="#price-modal-{{ $quantity->quantity_id }}">
                                            <i class="fas fa-tag"></i>
                                        </button>
                                        <form class="d-inline" action="{{ route('stock.destroy', $quantity->quantity_id) }}" method="POST">
                                            @csrf 
                                            @method('DELETE')
                                            <button type='submit' onclick="return confirm('Bạn muốn xóa mục này trong kho?')"
                                                    class="btn btn-danger btn-sm ms-1"
                                                    {{ $quantity->quantity != 0 ? 'disabled':'' }}>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $allQuantity->onEachSide(1)->links('backend.layouts.partials.pagination') }}

            {{-- Outside the table on purpose: a <div> in <tbody> gets lifted out to
                 just before the table, still inside .text-nowrap, which then runs
                 the modal's text off the side of the card. --}}
            @foreach ($allQuantity as $quantity)
                @include('backend.pages.product.stock.variant_price_edit')
                @include('backend.pages.product.stock.variant_restock')
            @endforeach

            @include('backend.pages.product.stock.variant_add')
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
