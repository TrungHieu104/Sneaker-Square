@extends('backend.index')
@section('title')
    Chỉnh sửa danh mục Slide
@endsection
@section('content') 
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('cate-slide.index')}}" class="tab-back">Slide / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/cate-slide/{{$cates->cate_slide_id}}" method="post">
        @csrf {{method_field('PUT')}}
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý danh mục Slide</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <input type="hidden" value="{{$cates->cate_slide_id}}" name="id">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" name="name" value="{{$cates->cate_slide_name}}"/>
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
                        <input type="text" class="form-control" readonly name="slug" id="path" value="{{$cates->cate_slide_slug}}" />
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
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="1" id="defaultCheck3" {{$cates->cate_slide_hidden == 1? "checked":""}} />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="0" id="defaultCheck4" {{$cates->cate_slide_hidden == 0? "checked":""}}/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </form>
@endsection
