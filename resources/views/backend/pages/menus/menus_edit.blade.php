@extends('backend.index')

@section('title')
    Menu
@endsection

@section('content') 
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('menus.index')}}" class="tab-back">Menu / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table -->
    <form action="/admin/menus/{{$menus->menu_id}}" method="post">
        @csrf {{method_field('PUT')}}
        <input type="hidden" name="id" value="{{$menus->menu_id}}">
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý Menu</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" name="name" value="{{$menus->menu_name}}"/>
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
                        <input type="text" class="form-control" name="slug" id="path" value="{{$menus->menu_link}}" />
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
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="title" class="form-label">Vị trí</label>
                        <input type="number" class="form-control w-100" name="position" value="{{$menus->menu_position}}" />
                        @if ($errors->has("position")) 
                            @foreach ($errors->get("position") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>  
                    <div class="col-lg-3 mb-3 pl-3">
                        <label for="cate-product" class="form-label">Thuộc nhóm menu</label>
                        <div class="box">
                            <select class="form-select" id="cate-product" name="parent_id">
                                <option value="">---Danh mục cha---</option>
                                @foreach($menu as $data)
                                    @php
                                    $indentation = str_repeat('---', $data->level);
                                    $selected = $menus->menu_parent_id == $data->menu_id ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $data->menu_id }}" {{ $selected }}>
                                        {{ $indentation . $data->menu_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>   
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="1" id="defaultCheck3" {{$menus->menu_hidden == 1? "checked":""}} />
                                <label class="form-check-label" for="defaultCheck3"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="status" type="radio" value="0" id="defaultCheck4" {{$menus->menu_hidden == 0? "checked":""}}/>
                                <label class="form-check-label" for="defaultCheck4"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </form>
@endsection
