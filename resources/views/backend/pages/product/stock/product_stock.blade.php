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
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Tên sản phẩm</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            @if (!is_null($allQuantity[0]->size_id))
                                <th class="text-center">Size</th>
                            @endif
                            @if (!is_null($allQuantity[0]->color_id))
                                <th class="text-center">Màu sắc</th>
                            @endif
                            <th class="text-center">Ngày nhập hàng gần nhất</th>
                            <th class="text-center">Số lượng trong kho</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
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

                                @if (!is_null($quantity->getSize))
                                    <td class="text-center">
                                        {{ $quantity->getSize->size }}
                                    </td>
                                @endif
                                
                                @if (!is_null($quantity->getColor))
                                    <td class="text-center">
                                        <div class="">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value=""
                                                id="disabledCheck2"
                                                disabled
                                                style="width: 80%; background-color: {{ $quantity->getColor->color }}; opacity: 1; border: 2px solid black;"
                                            />
                                            <label class="form-check-label" for="disabledCheck2"></label>
                                        </div>
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
                                    <div>
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
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
