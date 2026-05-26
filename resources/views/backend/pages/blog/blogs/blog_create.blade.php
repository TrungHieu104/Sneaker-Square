@extends('backend.index')

@section('title')
Thêm mới bài viết
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Bài viết /</span> Thêm mới</h4>
    <!-- Bordered Table -->
    <form action="{{asset('admin/blog')}}" method="post" enctype="multipart/form-data" >
        @csrf 
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="mb-3">
                        <label for="path" class="form-label">Đường dẫn</label>
                        <div class="mb-1">
                            <small class="text-lowercase">{{route('blog.page')}}/<small class="fw-bold" id="url-slug"></small> </small>
                            <input type="text" class="form-control" name="slug" id="path" placeholder="shop-giay-sneaker-square"  value="{{old('slug')}}" readonly />
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
                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề</label>
                        <input type="text" onkeyup="ChangeToSlug()" name="title" class="form-control" id="title" placeholder="Shop giày Sneaker Square" value="{{old('title')}}"/>
                        @if ($errors->has("title")) 
                            @foreach ($errors->get("title") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach               
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8 col-md-6">
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label for="cate-product" class="form-label">Ngày đăng</label>
                                <div class="col-md-12">
                                    <input class="form-control datepicker" id="start_coupon" autocomplete="off" type="text" name="post_date" value="{{old('post_date') ? old('post_date'):date('d-m-Y', strtotime($today))}}"/>
                                    @if ($errors->has("post_date")) 
                                        @foreach ($errors->get("post_date") as $error) 
                                            <small class="text-danger fst-italic">
                                                {{ $error }}
                                            </small> 
                                        @endforeach                  
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label for="cate-product" class="form-label">Danh mục</label>
                                <div class="box">
                                    <select class="form-select" id="cate-news" name="cate">
                                        @foreach ($cateN as $cate)
                                            @if (old('cate') == $cate->news_id)
                                                <option value="{{$cate->cate_news_id}}" selected>{{$cate->cate_news_name}}</option>
                                            @else
                                                <option value="{{$cate->cate_news_id}}">{{$cate->cate_news_name}}</option>
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
                            <div class="col-lg-6 mb-3">
                                <label for="title" class="form-label">Người đăng</label>
                                {{-- <input type="text" name="user_id" value="{{Auth::user()->user_id}}" class="form-control" readonly /> --}}
                                <input type="text" name="created_by" value="{{Auth::user()->name}}" class="form-control" readonly />
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
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
                                    <div class="col-lg-6 mb-3">
                                        <label for="" class="form-label">Nổi bật</label><br>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" name="hot" type="radio" value="1" id="defaultCheck5" checked />
                                                <label class="form-check-label" for="defaultCheck5"> Có </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" name="hot" type="radio" value="0" id="defaultCheck6"/>
                                                <label class="form-check-label" for="defaultCheck6"> Không </label>
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
                                    <input type="file" class="form-control w-100 inputFile" name="img_blog" id="new_img" style="display: none"  value="{{old('img_blog')}}"/>
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
                <div class="col-lg-12 mb-3">
                    <label for="cate-product" class="form-label">Thẻ tag</label>
                    <div class="gap-3">
                        @foreach ($tags as $tag)
                            <div class="form-check form-check-inline ml-2">
                                <input class="form-check-input" name="tags[]" id="tag{{$tag->tag_id}}" value="{{$tag->tag_id}}" type="checkbox">
                                <label for="tag{{$tag->tag_id}}">{{$tag->tag_content}}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="summary" class="form-label">Tóm tắt</label>
                        <textarea class="form-control" id="summary" rows="5" name="summarize">{{old('summarize')}}</textarea>
                        @if ($errors->has("summarize"))                           
                            @foreach ($errors->get("summarize") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach                 
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Nội dung</label><br>
                        <textarea class="form-control" name="content" id="content" cols="30" rows="10">{{old('content')}}</textarea>
                        @if ($errors->has("content"))                           
                            @foreach ($errors->get("content") as $error) 
                                <small class="text-danger fst-italic">
                                    {{ $error }}
                                </small> 
                            @endforeach                 
                        @endif
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
                    <input type="text" class="form-control" name="seo_title" id="seo-title" value="{{old('seo_title')}}" placeholder="SEO Title (vi)&#58;" />
                </div>
                <div class="mb-3">
                    <label for="seo-keywords" class="form-label">SEO Keywords (vi):</label>
                    <input type="text" class="form-control" name="seo_keywords" id="seo-keywords" value="{{old('seo_keywords')}}" placeholder="SEO Keywords (vi)&#58;" />
                </div>
                <div class="mb-3">
                    <label for="seo-description" class="form-label">SEO Description (vi):</label>
                    <textarea class="form-control" name="seo_description" id="seo-description" rows="6" placeholder="SEO Description (vi)&#58;">{{old('seo_description')}}</textarea>
                </div>
                <div class="d-flex justify-content-center gap-3">
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