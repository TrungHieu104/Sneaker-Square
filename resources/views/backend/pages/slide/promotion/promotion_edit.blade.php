@extends('backend.index')

@section('title')
    Slide
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('promotion.index')}}" class="tab-back">Nội dung Slide / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/promotion/{{$pro->promotion_id}}" method="post" enctype="multipart/form-data">
        @csrf {{method_field('PUT')}}
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
                <input type="hidden" name="id" value="{{$pro->promotion_id}}">
                <div class="row">
                    <div class="mb-3">
                        <label for="image-product" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" name="name" value="{{$pro->promotion_name}}"/>
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
                        <input type="text" class="form-control" name="type" value="{{$pro->type}}"/>
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
                        <input type="text" class="form-control" name="url" value="{{$pro->promotion_link}}"/>
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
                            <img id="previewImage" onerror="this.src='/uploads/img_error2.jpg'" src="{{asset($pro->promotion_img)}}" alt="" class="border form-control" height="180px">
                            <a class="edit-button mt-5 pl-2">
                                <i class='bx bxs-edit-alt'></i>
                            </a>
                            <input type="file" name="img" id="fileInput" class="file-input" style="display: none;">
                        </div>
                        {{-- <input class="form-control" name="img" type="file" id="image-product" value="{{$pro->promotion_img}}"/> --}}
                    </div>
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Thứ tự</label>
                        <input type="number" class="form-control w-100" min=0 name="position" value="{{$pro->promotion_sort}}"/>
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
                                <input class="form-check-input" name="status" type="radio" value="1" {{$pro->promotion_hidden == 1? "checked":""}} id="defaultCheck3" />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="0" {{$pro->promotion_hidden == 0? "checked":""}} id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <label for="title" class="form-label">Ngày bắt đầu</label>
                        <div class="col-md-12">
                            <input class="form-control datepicker" id="start_coupon" autocomplete="off" type="text" name="start_pro" value="{{date('d-m-Y', strtotime($pro->promotion_start))}}"/>
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
                            <input type="text" id="end_coupon" class="form-control datepicker" autocomplete="off" name="end_pro" value="{{date('d-m-Y', strtotime($pro->promotion_end))}}"/>
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
                                <?php $slideCate = \App\Models\CateSlideModel::all();?>
                                @foreach ($slideCate as $cate)
                                    @if ($pro->cate_slide_id == $cate->cate_slide_id)
                                        <option value="{{$cate->cate_slide_id}}" selected>{{$cate->cate_slide_name}}</option>
                                    @else
                                        <option value="{{$cate->cate_slide_id}}">{{$cate->cate_slide_name}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="title" class="form-label">Nội dung</label><br>
                        <textarea class="form-control" name="contents" id="" cols="30" rows="5">{{$pro->promotion_content}}</textarea>
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
                        <textarea class="form-control" name="note" id="" cols="30" rows="5">{{$pro->promotion_note}}</textarea>
                    </div> 
                </div>
            </div>
        </div>
    </form>
@endsection

