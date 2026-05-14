@extends('backend.index')

@section('title')
    Thêm mới danh mục sản phẩm
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Danh mục /</span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{ route('product-category.store') }}" method="POST" enctype="multipart/form-data">
        @csrf {{ method_field('POST') }}
        <div class="card mb-4 card-border-top">
            <div class="card-body"> 
                <div class="row">
                    <div class="col mb-3">
                        <label for="path" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" id="path" name="cate_slug" readonly value="{{ old('cate_slug') }}" />
                        <div class="mb-1">
                            <small class="text-lowercase">{{route('product.by.cate', '')}}/<small class="fw-bold" id="url-slug"></small> </small>
                        </div>
                    </div>
                {{-- </div>
                
                <div class="row"> --}}
                    <div class="col mb-3">
                        <label for="title" class="form-label">Tên danh mục</label>
                        <input type="text" onkeyup="ChangeToSlug();" class="form-control" name="cate_name" id="title" placeholder="Tên danh mục" value="{{ old('cate_name')}} "/>
                        @if ($errors->has("cate_name")) 
                            @foreach ($errors->get("cate_name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                {{-- </div>

                <div class="row"> --}}
                    <div class="col mb-3">
                        <label for="title" class="form-label">Số thứ tự</label>
                        <input type="text" class="form-control w-100" id="quantity" name="cate_sort" value="{{ old('cate_sort') }}"/>
                        @if ($errors->has("cate_sort")) 
                            @foreach ($errors->get("cate_sort") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>   
                </div>

                <div class="row">
                    <div class="col mb-3">
                        <label for="title" class="form-label">Hình ảnh</label>
                        <input type="file" class="form-control w-100" id="quantity" name="cate_img" />
                        @if ($errors->has("cate_img")) 
                            @foreach ($errors->get("cate_img") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>   
                {{-- </div>

                <div class="row"> --}}
                    <div class="col mb-3">
                        <label for="title" class="form-label">Hiển thị</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="cate_hidden" type="radio" value="1" id="hidden-false" {{(old('cate_hidden') == '1') ? 'checked' : 'checked'}} />
                                <label class="form-check-label" for="hidden-false"> Hiện </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="cate_hidden" type="radio" value="0" id="hidden-true" {{(old('cate_hidden') == '0') ? 'checked' : ''}}/>
                                <label class="form-check-label" for="hidden-true"> Ẩn </label>
                            </div>
                        </div>
                    </div> 
                {{-- </div>

                <div class="row"> --}}
                    <div class="col mb-3">
                        <label for="cate-product" class="form-label">Danh mục gốc</label>
                        <div class="box">
                            <select class="form-select" id="cate-product" name="cate_parent_id">
                                <option value="">---Danh mục gốc---</option>
                                @foreach($cates as $data)
                                @if (is_null($data->cate_parent_id))
                                    <option value="{{$data->cate_id}}" class="mb-3">
                                        @php
                                        $str = '';
                                        for($i = 0; $i< $data->level; $i++){
                                            echo $str;
                                            $str.='-- ';
                                        }
                                        @endphp
                                        {{ $str.$data->cate_name }}
                                    </option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>  
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header card-border-top">
                Nội dung SEO
            </div>

            <hr class="m-0">

            <div class="card-body">
                <div class="mb-3">
                    <label for="seo-keywords" class="form-label">SEO Keywords (vi):</label>
                    <input type="text" class="form-control" name="cate_meta_keywords" id="seo-keywords" placeholder="SEO Keywords (vi)&#58;" value="{{ old('cate_meta_keywords') }}"/>
                </div>
            </div>

            <div class="mb-3 d-flex gap-3 justify-content-center">
                <button type="submit" class="btn btn-success px-5">Lưu</button>
                <button type="reset" class="btn btn-primary px-5">Làm lại</button>
            </div>
        </div>
    </form>
    <!--/ Bordered Table -->
@endsection