@extends('backend.index')

@section('title')
    Chỉnh sửa tài khoản
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.index')}}" class="tab-back">Danh sách / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/account/{{$userAdmin->user_id}}" method="post" enctype="multipart/form-data">
        @csrf {{method_field('PUT')}}
        <input type="hidden" name="id" value="{{$userAdmin->user_id}}">
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý tài khoản</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 text-center mb-4">
                        <div class="img-user position-relative px-3">               
                            <img id="previewImage" onerror="this.src='/uploads/img_error3.png'" src="{{asset($userAdmin->user_img)}}" alt="" class="border rounded-circle" width="150px" height="150px">
                            <a class="edit-button">
                                <i class='bx bxs-edit-alt'></i>
                            </a>
                            <input type="file" name="img" id="fileInput" class="file-input" style="display: none;">
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" name="name" value="{{$userAdmin->name}}"/>
                        @if ($errors->has("name")) 
                            @foreach ($errors->get("name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" autocomplete="off" value="{{$userAdmin->username}}"/>
                        @if ($errors->has("username")) 
                            @foreach ($errors->get("username") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" autocomplete="off" value="{{$userAdmin->email}}"/>
                        @if ($errors->has("email")) 
                            @foreach ($errors->get("email") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div> 
                    <div class="col-lg-6 mb-3 pl-3">
                        <label for="title" class="form-label">Trạng thái</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck3" {{$userAdmin->user_status == 1? "checked":""}} />
                                <label class="form-check-label" for="defaultCheck3"> Kích hoạt </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" onclick="return confirm('Bạn có chắc muốn vô hiệu hóa user này?')" name="hid" type="radio" value="0" id="defaultCheck4" {{$userAdmin->user_status == 0? "checked":""}}/>
                                <label class="form-check-label" for="defaultCheck4"> Vô hiệu </label>
                            </div>
                        </div>
                    </div>   
                </div>
                
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header card-border-top">
            Các quyền hiện tại của người dùng
        </div>
        <hr class="m-0">
        <div class="card-body">
            @if ($userAdmin->roles->isNotEmpty())
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <label for="image-product" class="form-label">Vai trò</label>
                    @foreach ($userAdmin->roles as $role)
                        <input type="text" class="form-control" readonly value=" {{ $role->name }}"/>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-12 mb-3">
                <label for="cate-product" class="form-label">Quyền</label>
                <div class="demo-inline-spacing">
                    @if ($role->permissions->isNotEmpty())
                        @foreach ($role->permissions as $permission)
                            <span class="badge btn-primary">{{ $permission->name }}</span>
                        @endforeach
                    @else
                        <span class="badge btn-primary">Không có quyền</span>
                    @endif
                </div>
            </div>
            @else
                <div class="row">
                    <span class="text-center">Người dùng này chưa được cấp quyền!</span>
                </div>
            @endif
        </div>
    </div>
@endsection
