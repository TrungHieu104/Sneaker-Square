@extends('backend.index')

@section('title')
    Thêm sản phẩm mới
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
        @csrf {{ method_field('POST') }}
        <div class="card mb-4 card-border-top">
            <div class="card-body"> 
                <div class="row">
                    <div class="mb-3">
                        <label for="path" class="form-label">Đường dẫn</label>
                        <input type="text" class="form-control" id="path" name="pro_slug" readonly value="{{ old('pro_slug') }}" />
                        <div class="mb-1">
                            <small class="text-lowercase">{{route('product.detail', '')}}/<small class="fw-bold" id="url-slug"></small> </small>
                        </div>
                        @if ($errors->has("pro_slug")) 
                            @foreach ($errors->get("pro_slug") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3">
                        <label for="title" class="form-label">Tên sản phẩm</label>
                        <input type="text" class="form-control" onkeyup="ChangeToSlug();" name="pro_name" id="title" value="{{ old('pro_name') }}"/>
                        @if ($errors->has("pro_name")) 
                            @foreach ($errors->get("pro_name") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6 mb-3">
                        <label for="title" class="form-label">Mã sản phẩm</label>
                        <input type="text" class="form-control" name="pro_code" value="{{ old('pro_code') }}"/>
                        @if ($errors->has("pro_code")) 
                            @foreach ($errors->get("pro_code") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>  
                    <div class="col-lg-6 mb-3">
                        <label for="pro_date" class="form-label">Ngày bán</label>
                        <input class="form-control datepicker" id="start_coupon" autocomplete="off" type="text" name="pro_date"    value="{{ old('pro_date') ? old('pro_date'):date('d-m-Y', strtotime($today)) }}"/>
                        @if ($errors->has("pro_date")) 
                            @foreach ($errors->get("pro_date") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-8 col-md-6">
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label for="cate-product" class="form-label">Danh mục</label>
                                <div class="box">
                                    <select class="form-select" id="cate-product" name="cate_id">
                                        <option value="" selected>---Danh mục---</option>
                                        @foreach($allCates as $c)
                                            @if (!is_null($c->cate_parent_id))
                                                <option value="{{ $c->cate_id }}" {{ ($c->cate_id == old('cate_id'))? 'selected':'' }}>
                                                    {{ $c->cate_name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                @if ($errors->has("cate_id")) 
                                    @foreach ($errors->get("cate_id") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small> 
                                    @endforeach               
                                @endif
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label for="title" class="form-label">Giá nhập sản phẩm</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="capital_price" value="{{ old('capital_price') }}"
                                            min="1" step="1">
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                                @if ($errors->has("capital_price")) 
                                    @foreach ($errors->get("capital_price") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small> 
                                    @endforeach               
                                @endif
                            </div>   
        
                            <div class="col-lg-6 mb-3">
                                <label for="title" class="form-label">Giá bán</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="pro_price" value="{{ old('pro_price') }}"
                                        min="1" step="1">
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                                @if ($errors->has("pro_price")) 
                                    @foreach ($errors->get("pro_price") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small> 
                                    @endforeach               
                                @endif
                            </div>
        
                            <div class="col-lg-6 mb-3">
                                <label for="title" class="form-label">Giá giảm</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="pro_price_sale" value="{{ old('pro_price_sale') }}" min="0" step="1">
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                                @if ($errors->has("pro_price_sale")) 
                                    @foreach ($errors->get("pro_price_sale") as $error) 
                                        <small class="text-danger fst-italic">
                                            {{ $error }}
                                        </small> 
                                    @endforeach               
                                @endif
                            </div>
                            
                            <div class="col-lg-6 mb-3">
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <label for="title" class="form-label">Nổi bật</label><br>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" name="pro_hot" type="radio" value="1" id="hot-true" {{(old('pro_hot') == '1') ? 'checked' : 'checked'}}/>
                                                <label class="form-check-label" for="hot-true"> Có </label>
                                            </div>
                
                                            <div class="form-check">
                                                <input class="form-check-input" name="pro_hot" type="radio" value="0" id="hot-false" {{(old('pro_hot') == '0') ? 'checked' : ''}}/>
                                                <label class="form-check-label" for="hot-false"> Không </label>
                                            </div>
                                        </div>
                                    </div>
                
                                    <div class="col-lg-6 mb-3">
                                        <label for="title" class="form-label">Hiển thị</label><br>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" name="pro_hidden" type="radio" value="1" id="hidden-true" {{(old('pro_hidden') == '1') ? 'checked' : 'checked'}}/>
                                                <label class="form-check-label" for="hidden-true"> Hiện </label>
                                            </div>
                
                                            <div class="form-check">
                                                <input class="form-check-input" name="pro_hidden" type="radio" value="0" id="hidden-false" {{(old('pro_hidden') == '0') ? 'checked' : ''}}/>
                                                <label class="form-check-label" for="hidden-false"> Ẩn </label>
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex flex-column">
                                    <input type="file" class="form-control w-100 inputFile" name="pro_img" id="pro_img" style="display: none"/>
                                    <img src=""  onerror="this.src='/uploads/img_error.jpg'" id="previewImage" class="rounded mx-auto mb-3 border" alt="" width="100%" height="230px">
                                    <label for="pro_img" class="mx-auto btn-transition text-center text-white rounded" id="label-preview">
                                        <i class='bx bxs-camera'></i>
                                        Thêm ảnh
                                    </label>
                                    @if ($errors->has("pro_img")) 
                                        @foreach ($errors->get("pro_img") as $error) 
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

                <div class="row">
                    <div class="mb-3">
                        <label for="content" class="form-label">Mô tả</label>
                        <textarea class="ckeditor" id="content" name="pro_description" rows="30" cols="10">
                            {{ old('pro_description') }}
                        </textarea>
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
                    <label for="seo-title" class="form-label">SEO Title (vi):</label>
                    <input type="text" class="form-control" id="seo-title" name="pro_SEO_title" placeholder="SEO Title (vi)&#58;" value="{{ old('pro_SEO_title') }}"/>
                    @if ($errors->has("pro_SEO_title")) 
                        @foreach ($errors->get("pro_SEO_title") as $error) 
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small> 
                        @endforeach               
                    @endif
                </div>
                <div class="mb-3">
                    <label for="seo-keywords" class="form-label">SEO Keywords (vi):</label>
                    <input type="text" class="form-control" id="seo-keywords" name="pro_meta_keywords" placeholder="SEO Keywords (vi)&#58;" value="{{ old('pro_meta_keywords') }}" />
                    @if ($errors->has("pro_meta_keywords")) 
                        @foreach ($errors->get("pro_meta_keywords") as $error) 
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small> 
                        @endforeach               
                    @endif
                </div>
                <div class="mb-3">
                    <label for="seo-description" class="form-label">SEO Description (vi):</label>
                    <textarea class="form-control" id="seo-description" name="pro_meta_description" rows="6" placeholder="SEO Description (vi)&#58;">
                        {{ old('pro_meta_description') }}
                    </textarea>
                    @if ($errors->has("pro_meta_description")) 
                        @foreach ($errors->get("pro_meta_description") as $error) 
                            <small class="text-danger fst-italic">
                                {{ $error }}
                            </small> 
                        @endforeach               
                    @endif
                </div>
                <div class="mb-3 d-flex gap-3 justify-content-center">
                    <button type="submit" class="btn btn-success px-5">Lưu</button>
                    <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                </div>
            </div>
        </div>
    </form>
    <!--/ Bordered Table -->
@endsection

@push('script-backend')
    <script src="/backend/assets/js/previewImg.js"></script>
@endpush