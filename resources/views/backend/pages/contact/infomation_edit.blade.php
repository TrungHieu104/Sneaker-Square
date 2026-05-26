@extends('backend.index')

@section('title')
    Chỉnh sửa liên hệ
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('info-contact.index')}}" class="tab-back">Thông tin / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/info-contact/{{$contact->contact_id}}" method="post">
        @csrf {{method_field('PUT')}}
        <input type="hidden" name="id" value="{{$contact->contact_id}}">
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
                        <input type="text" class="form-control" id="phone" name="phone" value="{{$contact->contact_phone}}"/>
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
                        <input type="text" class="form-control" id="email" name="email" value="{{$contact->contact_email}}"/>
                        @if ($errors->has("email")) 
                            @foreach ($errors->get("email") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" id="address" name="address" value="{{$contact->contact_address}}"/>
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
                        <input type="text" class="form-control" id="author" name="author" value="{{$contact->contact_created_by}}" readonly/>
                    </div>
                    @if($contact->contact_hidden==1)
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <input type="hidden" name="hid" value="1">
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input disabled class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck3" {{$contact->contact_hidden == 1? "checked":""}} />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input disabled class="form-check-input" name="hid" type="radio" value="0" id="defaultCheck4" {{$contact->contact_hidden == 0? "checked":""}}/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div>   
                    @else
                        <div class="col-lg-3 mb-3 pl-3">
                            <label for="title" class="form-label">Hiển thị</label><br>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck5" {{$contact->contact_hidden == 1? "checked":""}} />
                                    <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" name="hid" type="radio" value="0" id="defaultCheck6" {{$contact->contact_hidden == 0? "checked":""}}/>
                                    <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                                </div>
                            </div>
                        </div>   
                    @endif
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Google Map</label>
                    <textarea class="form-control" id="gg" name="gg"  cols="30" rows="5">{{$contact->map_link}}</textarea>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Fanpage facebook</label>
                    <textarea class="form-control" id="fb" name="fb" cols="30" rows="5">{{$contact->fanpage_link}}</textarea>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Live chat</label>
                    <textarea class="form-control" id="tt" name="tt" cols="30" rows="5">{{$contact->tawk_link}}</textarea>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Chỉnh sửa bởi</label>
                        <input required type="text" class="form-control" name="editby" value="{{Auth::user()->name}}" readonly/>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-3 d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
