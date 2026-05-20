@extends('backend.index')

@section('title')
    Slide
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('promotion.index')}}" class="tab-back">Nội dung Slide / </a></span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/promotion')}}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý nội dung Slide</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="image-product" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" onkeyup="ChangeToSlug()" name="name" value="{{old('name')}}"/>
                        @if ($errors->has("name")) 
                            @foreach ($errors->get("name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Thể loại</label>
                        <input type="text" class="form-control" name="type" value="{{old('type')}}"/>
                        @if ($errors->has("type")) 
                            @foreach ($errors->get("type") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" name="url" value="{{old('url')}}"/>
                        @if ($errors->has("url")) 
                            @foreach ($errors->get("url") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach                 
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Hình ảnh</label>
                        <div class="img-user position-relative px-3 d-flex">               
                            <img id="previewImage" onerror="this.src='/uploads/img_error2.jpg'" src="" alt="" class="border form-control" height="180px" value="{{old('img')}}">
                            <a class="edit-button mt-5">
                                <i class='bx bxs-edit-alt'></i>
                            </a>
                            <input type="file" name="img" id="fileInput" class="file-input" style="display: none;">
                        </div>
                        {{-- <input class="form-control" name="img" type="file" class="file-input" id="fileInput" value="{{old('img')}}"/> --}}
                        @if ($errors->has("img")) 
                            @foreach ($errors->get("img") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Thứ tự</label>
                        <input type="number" class="form-control w-100" min=0 name="position" value="{{old('position')}}"/>
                        @if ($errors->has("position")) 
                            @foreach ($errors->get("position") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>   
                    <div class="col-lg-3 mb-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="1" id="defaultCheck3" checked />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="0" id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <label for="title" class="form-label">Ngày bắt đầu</label>
                        <div class="col-md-12">
                            <input class="form-control datepicker" id="start_coupon" autocomplete="off" type="text" name="start_pro" value="{{old('start_pro')}}"/>
                            @if ($errors->has("start_pro")) 
                                @foreach ($errors->get("start_pro") as $error) 
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
                            <input type="text" id="end_coupon" class="form-control datepicker" autocomplete="off" name="end_pro" value="{{old('end_pro')}}"/>
                            @if ($errors->has("end_pro"))                           
                                @foreach ($errors->get("end_pro") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach                 
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="color-product" class="form-label">Danh mục Slide</label>
                        <div class="box">
                            <select class="form-select" id="color-product" name="cate">
                                <?php $slideCate = \App\Models\CateSlideModel::where('cate_slide_hidden',1)->get();?>
                                @foreach ($slideCate as $cate)
                                    @if (old('cate') == $cate->promotion_id)
                                        <option value="{{$cate->cate_slide_id}}" selected>{{$cate->cate_slide_name}}</option>
                                    @else
                                        <option value="{{$cate->cate_slide_id}}">{{$cate->cate_slide_name}}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has("cate"))                           
                                @foreach ($errors->get("cate") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach                 
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="title" class="form-label">Nội dung</label><br>
                        <textarea class="form-control" name="contents" id="" cols="30" rows="5">{{old('contents')}}</textarea>
                        @if ($errors->has("contents"))                           
                            @foreach ($errors->get("contents") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach                 
                        @endif
                    </div> 
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="title" class="form-label">Ghi chú</label><br>
                        <textarea class="form-control" name="note" id="" cols="30" rows="5"></textarea>
                    </div> 
                </div>
            </div>
        </div>
    </form>
@endsection
