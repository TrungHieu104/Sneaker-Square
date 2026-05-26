@extends('backend.index')

@section('title')
    Thêm liên hệ
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('info-contact.index')}}" class="tab-back">Thông tin / </a></span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/info-contact')}}" method="post">
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý thông tin</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{old('phone')}}"/>
                        @if ($errors->has("phone")) 
                            @foreach ($errors->get("phone") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" value="{{old('email')}}"/>
                        @if ($errors->has("email")) 
                            @foreach ($errors->get("email") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }} <br>
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="address" name="address" value="{{old('address')}}"/>
                        @if ($errors->has("address")) 
                            @foreach ($errors->get("address") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>  
                    <div class="col-lg-3 mb-3">
                        <label for="image-product" class="form-label">Được tạo bởi</label>
                        <input type="text" class="form-control" id="author" name="author" value="{{Auth::user()->name}}" readonly/>
                    </div>
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck3" checked />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="0" id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div>   
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Google Map</label>
                    <textarea class="form-control" name="gg" id="" cols="30" rows="5">{{old('gg')}}</textarea>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Fanpage facebook</label>
                    <textarea name="fb" id="" class="form-control" cols="30" rows="5">{{old('fb')}}</textarea>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Live chat</label>
                    <textarea name="tt" id="" class="form-control" cols="30" rows="5">{{old('tt')}}</textarea>
                </div>
                <div class="row">
                    <div class="col-lg-12 mb-3 d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
