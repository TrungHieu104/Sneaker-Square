@extends('backend.index')

@section('title')
    Nhập hàng
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Kho</h4>
    <!-- Bordered Table -->
    <form id="store-new-color" action="{{ route('stock.new.color') }}" method="POST" enctype="multipart/form-data">
        @csrf {{ method_field('POST') }}
    </form>
    <form id="stock" action="{{ route('stock.store') }}" method="POST" enctype="multipart/form-data">
        @csrf {{ method_field('POST') }}
    </form>
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <label for="cate-product" class="form-label">Sản phẩm</label>
                        <div class="box">
                            <select class="form-select" id="pro_id" name="pro_id" form="stock">
                                <option value="">---Sản phẩm---</option>
                                @foreach ($allProducts as $product)
                                    <option value="{{ $product->pro_id }}"
                                        {{ $product->pro_id == old('pro_id') ? 'selected' : '' }}>
                                        {{ $product->pro_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($errors->has('pro_id'))
                            @foreach ($errors->get('pro_id') as $error)
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small>
                            @endforeach
                        @endif
                    </div>

                    <div class="col">
                        <label for="pro_date" class="form-label">Ngày nhập</label>
                        <input class="form-control" id="quantity_date" type="date" name="quantity_date"
                            value="{{ old('quantity_date') }}" form="stock"/>
                        @if ($errors->has('quantity_date'))
                            @foreach ($errors->get('quantity_date') as $error)
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="row mt-3">
                    <label for="title" class="form-label">Loại sản phẩm</label><br>
                    <div class="nav-align-top">
                        <ul class="nav nav-pills nav-fill" role="tablist">
                            <li class="col nav-item">
                                <label class="nav-link active" for="pro-type-1" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-justified-home" aria-controls="navs-pills-justified-home"
                                    aria-selected="true">Giày/dép/phụ kiện (vớ, mũ,...)</label>
                                <div class="form-check">
                                    <input class="form-check-input d-none" name="pro_type" type="radio" value="0"
                                        id="pro-type-1" checked form="stock"/>
                                </div>
                            </li>
                            <li class="nav-item">
                                <label class="nav-link" for="pro-type-3" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-justified-messages"
                                    aria-controls="navs-pills-justified-messages" aria-selected="false">Khác (dụng cụ vệ
                                    sinh giày,...)</label>
                                <div class="form-check">
                                    <input class="form-check-input d-none" name="pro_type" type="radio" value="1"
                                        id="pro-type-3" form="stock"/>
                                </div>
                            </li>
                        </ul>

                        <div class="tab-content p-0">
                            {{-- Size and color start --}}
                            <div class="tab-pane fade show active" id="navs-pills-justified-home" role="tabpanel">
                                <div class="table-responsive text-nowrap">
                                    <table class="table table-bordered w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-left" colspan="3">Size</th>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <input class="btn border py-1 px-2" type="button" id="toggle"
                                                        value="Tất cả" onClick="selectAll();" />
                                                    @foreach ($allSize as $size)
                                                        <div class="form-check form-check-inline ml-2">
                                                            <input class="form-check-input" type="checkbox" name="size_id[]"
                                                                id="size-{{ $size->size_id }}"
                                                                value="{{ $size->size_id }}"
                                                                {{ is_array(old('size_id')) && in_array($size->size_id, old('size_id')) ? 'checked' : '' }} 
                                                                form="stock" />
                                                            <label class="form-check-label"
                                                                for="size-{{ $size->size_id }}">{{ $size->size }}</label>
                                                        </div>
                                                    @endforeach
                                                </td>
                                            </tr>

                                            <th class="text-center align-middle">Thao tác</th>

                                            <th class="text-center align-middle">Màu sắc</th>

                                            <th class="text-center align-middle w-50">
                                                Số lượng <br>
                                                @if ($errors->has('quantityColorAndSize'))
                                                    @foreach ($errors->get('quantityColorAndSize') as $error)
                                                        <small class="text-danger fst-italic">
                                                            ({{ $error }})
                                                            <br>
                                                        </small>
                                                    @endforeach
                                                @endif
                                                @if ($errors->has('quantityColorAndSize.*'))
                                                    @foreach ($errors->all() as $error)
                                                        <small class="text-danger fst-italic">
                                                            {{ $error }}
                                                        </small>
                                                    @endforeach
                                                @endif
                                            </th>
                                        </thead>

                                        <tbody>
                                            @foreach ($allColor as $color)
                                                <tr>
                                                    <td class="text-center">
                                                        <div>
                                                            <button type="button" class="btn btn-success btn-sm m-1" title="Xem"
                                                                    data-bs-toggle="modal" data-bs-target="#color-modal-{{$color->color_id}}"
                                                                    >
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <form id="delete-color" class="d-inline" action="{{ route('stock.delete.color', $color->color_id) }}" method="POST">
                                                                @csrf 
                                                                @method('DELETE')
                                                                <button type='submit' onclick="return confirm('Bạn muốn xóa màu này?')"
                                                                        class="btn btn-danger btn-sm ms-1">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>

                                                    <td class="text-left">
                                                        <input class="form-check-input" type="checkbox" name="color_id[]"
                                                            id="color-{{ $color->color_id }}"
                                                            value="{{ $color->color_id }}"
                                                            onclick="checkColor({{ $color->color_id }})"
                                                            {{ is_array(old('color_id')) && in_array($color->color_id, old('color_id')) ? 'checked' : '' }} 
                                                            form="stock"/>
                                                        <label class="form-check-label"
                                                            for="color-{{ $color->color_id }}">{{ $color->color_vn }}</label>
                                                    </td>

                                                    <td class="text-center">
                                                        <input class="form-control quantityColorAndSize" type="number"
                                                            id="quantityColorAndSize{{ $color->color_id }}"
                                                            value="{{ old('quantityColorAndSize.' . $color->color_id) }}"
                                                            name="quantityColorAndSize[{{ $color->color_id }}]"
                                                            {{ is_array(old('color_id')) && in_array($color->color_id, old('color_id')) ? '' : 'disabled' }}
                                                            required
                                                            oninvalid="this.setCustomValidity('Vui lòng nhập số lượng!')"
                                                            oninput="this.setCustomValidity('')" 
                                                            form="stock"/>
                                                    </td>
                                                </tr>

                                                @include('backend.pages.product.stock.color_edit')
                                            @endforeach

                                            <tr>
                                                <th colspan="3">Màu khác</th>
                                            </tr>

                                            <tr>
                                                <td colspan="3" class="text-left">
                                                    <div class="form-check-inline p-0">
                                                        <input form="store-new-color" class="form-control w-100" type="text"
                                                            id="color" value="{{ old('color') }}" name="color"
                                                            placeholder="Tên màu bằng tiếng Anh" />
                                                        @if ($errors->has('color'))
                                                            @foreach ($errors->get('color') as $error)
                                                                <small class="text-danger fst-italic">
                                                                    ({{ $error }})
                                                                    <br>
                                                                </small>
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                    <div class="form-check-inline">
                                                        <input form="store-new-color" class="form-control w-100" id="color-vn"
                                                            type="text" value="{{ old('color_vn') }}" name="color_vn"
                                                            placeholder="Tên màu bằng tiếng Việt" />
                                                        @if ($errors->has('color_vn'))
                                                            @foreach ($errors->get('color_vn') as $error)
                                                                <small class="text-danger fst-italic">
                                                                    ({{ $error }})
                                                                    <br>
                                                                </small>
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                    <div class="form-check-inline align-top">
                                                        <button form="store-new-color" type="submit" class="btn btn-success"
                                                            title="Thêm màu">Thêm màu &nbsp;<i
                                                                class="bi bi-plus-circle"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {{-- Size and color end --}}

                            {{-- Others start --}}
                            <div class="tab-pane fade" id="navs-pills-justified-messages" role="tabpanel">
                                <div class="table-responsive text-nowrap">
                                    <table class="table table-bordered w-50 mx-auto">
                                        <thead>
                                            <th class="text-center align-middle">
                                                Số lượng <br>
                                                @if ($errors->has('quantityOthers'))
                                                    @foreach ($errors->get('quantityOthers') as $error)
                                                        <small class="text-danger fst-italic">
                                                            ({{ $error }})
                                                            <br>
                                                        </small>
                                                    @endforeach
                                                @endif
                                            </th>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="number" class="form-control" name="quantityOthers"
                                                        value="{{ old('quantityOthers') }}" 
                                                        form="stock" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 d-flex gap-3 justify-content-center">
                <button type="submit" class="btn btn-success px-5" form="stock">Lưu</button>
                <button type="reset" class="btn btn-primary px-5" form="stock">Làm lại</button>
            </div>
        </div>
    <!--/ Bordered Table -->
@endsection

<script type="text/javascript">
    function selectAll() {

        var checkboxes = document.getElementsByName('size_id[]');
        var button = document.getElementById('toggle');

        if (button.value == 'Tất cả') {
            for (var i in checkboxes) {
                checkboxes[i].checked = 'FALSE';
            }
            button.value = 'Bỏ chọn'
        } else {
            for (var i in checkboxes) {
                checkboxes[i].checked = '';
            }
            button.value = 'Tất cả';
        }
    }

    function checkColor(i) {
        var checkBox = document.getElementById("color-" + i);
        var quantity = document.getElementById("quantityColorAndSize" + i);
        checkBox.onchange = function() {
            quantity.disabled = !this.checked;
            quantity.focus();
        }
    }

    function newColor() {
        var checkNewColor = document.getElementById("add-color");
        var color = document.getElementById("color");
        var colorVn = document.getElementById("color-vn");
        var newColorQuantity = document.getElementById("new-color-quantity");
        checkNewColor.onchange = function() {
            color.disabled = !this.checked;
            colorVn.disabled = !this.checked;
            newColorQuantity.disabled = !this.checked;
            color.focus();
        }
    }
</script>
