@extends('backend.index')

@section('title')
    Thêm mới danh mục bài viết
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Danh mục /</span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/blog-category')}}" method="post" enctype="multipart/form-data" >
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý danh mục bài viết</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" name="name" onkeyup="ChangeToSlug()" id="title" value="{{old('name')}}" placeholder="Nhập tại đây..."/>
                        @if ($errors->has("name")) 
                            @foreach ($errors->get("name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="path" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" name="slug" id="path" readonly value="{{old('slug')}}" placeholder="sneaker-square" />
                        <input type="hidden" value="0" class="form-control" name="url" id="path" readonly />
                        @if ($errors->has("slug")) 
                            @foreach ($errors->get("slug") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <label for="" class="form-label">Trạng thái</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hidden" type="radio" value="1" id="defaultCheck3" checked />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="hidden" type="radio" value="0" id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Thứ tự</label>
                        <input type="number" class="form-control w-100" min=0 name="sort" value="{{old('sort')}}"/>
                        @if ($errors->has("sort")) 
                            @foreach ($errors->get("sort") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>   
                    <div class="col-lg-6 mb-3">
                        <div class="d-flex flex-column">
                            <input type="file" class="form-control w-100 inputFile" name="img_blog" id="new_img" style="display: none"/>
                            <img src=""  onerror="this.src='/uploads/img_error.jpg'" id="previewImage" class="rounded mx-auto mb-3 border" alt="" width="100%" height="230px">
                            <label for="new_img" class="mx-auto btn-transition text-center text-white rounded" id="label-preview">
                                <i class='bx bxs-camera'></i>
                                Thêm ảnh
                            </label>
                            @if ($errors->has("img_blog")) 
                                @foreach ($errors->get("img_blog") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach 
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!--/ Bordered Table -->
@endsection

@push('script-backend')
    <script src="/backend/assets/js/previewImg.js"></script>
@endpush