@extends('backend.index')
@section('title')
    Chỉnh sửa thẻ tag
@endsection
@section('content') 
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Thẻ tag /</span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/tags/{{$tags->tag_id}}" method="post" enctype="multipart/form-data">
        @csrf {{method_field('PUT')}}
        <input type="hidden" name="id" value="{{$tags->tag_id}}">
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý thẻ tag</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Nội dung thẻ tag</label>
                        <input type="text" class="form-control" name="contentTg" id="title" onkeyup="ChangeToSlug()" value="{{$tags->tag_content}}"/>
                        @if ($errors->has("contentTg")) 
                            @foreach ($errors->get("contentTg") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="path" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" name="slug" id="path" readonly value="{{$tags->tag_slug}}"/>
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
                    <div class="col-lg-6 mb-3">
                        <label for="" class="form-label">Trạng thái</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hidden" type="radio" value="1" {{$tags->tag_hidden == 1? "checked":""}}  id="defaultCheck3" />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="hidden" type="radio" value="0" {{$tags->tag_hidden == 0? "checked":""}}  id="defaultCheck4"/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </form>
@endsection
