@extends('backend.index')

@section('title')
    Menu
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('menus.index')}}" class="tab-back">Menu / </a></span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/menus')}}" method="post">
        @csrf
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
                        <label for="image-product" class="form-label">Tiêu đề</label>
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
                        <label for="path" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" name="slug" placeholder="&#47;san-pham" value="{{old('slug')}}"/>
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
                        <label for="title" class="form-label">Vị trí</label>
                        <input type="number" class="form-control w-100" name="position" min=0 value="{{old('position')}}"/>
                        @if ($errors->has("position")) 
                            @foreach ($errors->get("position") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach 
                        @endif
                    </div>  
                    <div class="col-lg-3 mb-3">
                        <label for="cate-product" class="form-label">Danh mục</label>
                        <div class="box">
                            <select class="form-select" id="cate-product" name="parent_id">
                                <option value="">---Danh mục cha---</option>
                                @foreach($menu as $data)
                                    <option value="{{$data->menu_id}}" class="mb-3">
                                        @php
                                        $str = '';
                                        for($i=0;$i< $data->level;$i++){
                                            echo $str;
                                            $str.='-- ';
                                        }
                                        @endphp
                                        {{$str.$data->menu_name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label for="" class="form-label">Hiển thị</label><br>
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
            </div>
        </div>
    </form>
@endsection
