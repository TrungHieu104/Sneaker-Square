@extends('backend.index')

@section('title')
    Thêm mã giảm giá
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('coupon.index')}}" class="tab-back">Mã giảm giá / </a></span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/coupon')}}" method="post">
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý mã giảm giá</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Tên mã giảm</label>
                        <input type="text" class="form-control" name="name" value="{{old('name')}}"/>
                        @if ($errors->has("name")) 
                            @foreach ($errors->get("name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Mã code</label>
                        <input type="text" class="form-control" name="code" value="{{old('code')}}"/>
                        @if ($errors->has("code")) 
                            @foreach ($errors->get("code") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach                 
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <label for="title" class="form-label">Ngày bắt đầu</label>
                        <div class="col-md-12">
                            <input class="form-control datepicker" id="start_coupon" autocomplete="off" type="text" name="start_coupon" value="{{old('start_coupon')}}"/>
                            @if ($errors->has("start_coupon")) 
                                @foreach ($errors->get("start_coupon") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach                  
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="image-product" class="form-label">Ngày kết thúc</label>
                        <div class="col-md-12">
                            <input type="text" id="end_coupon" class="form-control datepicker" autocomplete="off" name="end_coupon" value="{{old('end_coupon')}}"/>
                            @if ($errors->has("end_coupon"))                           
                                @foreach ($errors->get("end_coupon") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach                 
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3 pl-3">
                        <label for="title" class="form-label">Số lượng</label>
                        <input type="number" class="form-control w-100" id="quantity" name="quantity" value="{{old('quantity')}}" min=0/>
                        @if ($errors->has("quantity")) 
                            @foreach ($errors->get("quantity") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>   
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Điều kiện giảm giá</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="condition" type="radio" value="1" id="defaultCheck3" checked/>
                                <label class="form-check-label" for="defaultCheck3"> Giảm theo tiền </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="condition" type="radio" value="0" id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Giảm theo % </label>
                            </div>
                        </div>
                    </div> 
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Nhập số % giảm hoặc số tiền giảm</label>
                        <div class="input-group">
                            <input type="number" id="price-sale-product" class="form-control" name="money" value="{{old('money')}}"
                                aria-label="Dollar amount (with dot and two decimal places)" min=0/>
                            <span class="input-group-text">VNĐ / %</span> 
                        </div>
                        @if ($errors->has("money")) 
                            @foreach ($errors->get("money") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[name="condition"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var conditionValue = this.value;
                var moneyInput = document.getElementById('price-sale-product');
                if (conditionValue === '0') {
                    moneyInput.max = 100;
                } else {
                    moneyInput.max = '';
                }
            });
        });
    });
</script>

