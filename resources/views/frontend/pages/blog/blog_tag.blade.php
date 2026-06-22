@extends('frontend.index')
@if (is_null($getOneTag))
    @section('title')
        Tất cả blog tại Sneaker Square
    @endsection
@else
    @section('title')
        {{ $getOneTag->tag_content }}
    @endsection
    @section('banner')
        <!-- Start banner Area -->
        <section class="banner-area organic-breadcrumb">
            <div class="container-fluid px-5">
                <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                    <div class="col-first">
                        <h1>Bài viết</h1>
                        <nav class="d-flex align-items-center">
                            <a href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                            <a href="{{ route('blog.page') }}">Blog<span class="lnr lnr-arrow-right"></span></a>
                            <a href="{{ route('tags.news', $getOneTag->tag_slug) }}">{{ $getOneTag->tag_content }}</a>
                        </nav>
                    </div>
                </div>
            </div>
        </section>
        <!-- End banner Area -->
    @endsection
    
@endif

@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Bài viết</h1>
                    <nav class="d-flex align-items-center">
                        <a href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <a href="{{ route('blog.page') }}">Blog</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- End banner Area -->
@endsection
@section('content')
    <!-- Start Content Area -->
    <section class="container-fluid px-5 my-5 section-blog__page">
        <div class="row">
            <div class="col-xl-8 col-lg-6">
                <div class="container-fluid px-0">
                    @if (!is_null($getOneTag))
                        @foreach ($getOneTag->getNews as $lN)
                            <div class="card mb-3 border-0 box__shadow">
                                <div class="row g-0 h-100">
                                    <div class="col-md-5 img-blog_item__page h-100">
                                        <a href="{{ route('news.detail', $lN->news_slug) }}">
                                            <img src="{{ asset($lN->news_img) }}" onerror="this.src='/uploads/img_error2.jpg'"
                                                class="h-100 w-100 rounded-start" alt="{{ $lN->news_title }}">
                                        </a>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="card-body">
                                            <a href="{{ route('news.detail', $lN->news_slug) }}">
                                                <h4 class="card-title mb-3 title__truncate">{{ $lN->news_title }}</h5>
                                            </a>
                                            <p class="card-text text__truncate">
                                                {{ $lN->news_summarize }}
                                            </p>
                                            </p>
                                            <div class="d-flex gap-4 align-items-center mb-3">
                                                <p class="fst-italic mb-0">
                                                    Ngày {{ date('d/m/Y', strtotime($lN->post_date)) }} &#8722;
                                                    {{ date('H:i', strtotime($lN->created_at)) }}
                                                </p>
                                                <p class="fst-italic mb-0">
                                                    <i class="far fa-eye"></i> &#8226;
                                                    {{ $lN->views }} Lượt xem
                                                </p>
                                            </div>
                                            <a href="{{ route('news.detail', $lN->news_slug) }}"
                                                class="cssbuttons-io-button"> Xem thêm
                                                <div class="icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                        width="24" height="24">
                                                        <path fill="none" d="M0 0h24v24H0z"></path>
                                                        <path fill="currentColor"
                                                            d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="paginate d-flex justify-content-center align-items-center gap-3 url-pagination my-3">
                            {{$getOneTag->getNews->links('frontend.layouts.partials.pagination')}}
                        </div>
                    @else
                        @foreach ($listNews as $allNews)
                            <div class="card mb-3 border-0 box__shadow">
                                <div class="row g-0 h-100">
                                    <div class="col-md-5 img-blog_item__page h-100">
                                        <a href="{{ route('news.detail', $allNews->news_slug) }}">
                                            <img src="{{ asset($allNews->news_img) }}"
                                                onerror="this.src='/uploads/img_error2.jpg'"
                                                class="h-100 w-100 rounded-start" alt="{{ $allNews->news_title }}">
                                        </a>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="card-body">
                                            <a href="{{ route('news.detail', $allNews->news_slug) }}">
                                                <h4 class="card-title mb-3 title__truncate">{{ $allNews->news_title }}</h5>
                                            </a>
                                            <p class="card-text text__truncate">
                                                {{ $allNews->news_summarize }}
                                            </p>
                                            </p>
                                            <div class="d-flex gap-3 align-items-center mb-3">
                                                <p class="fst-italic mb-0">
                                                    Ngày {{ date('d/m/Y', strtotime($allNews->post_date)) }} &#8722;
                                                    {{ date('H:i', strtotime($allNews->created_at)) }}
                                                </p>
                                                <p class="fst-italic mb-0">
                                                    <i class="far fa-eye"></i> &#8226;
                                                    {{ $allNews->views }} Lượt xem
                                                </p>
                                            </div>
                                            <a href="{{ route('news.detail', $allNews->news_slug) }}"
                                                class="cssbuttons-io-button"> Xem thêm
                                                <div class="icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                        width="24" height="24">
                                                        <path fill="none" d="M0 0h24v24H0z"></path>
                                                        <path fill="currentColor"
                                                            d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Start Pagination Bar -->
                        <div class="paginate d-flex justify-content-center align-items-center gap-3 url-pagination my-3">
                            {{$listNews->links('frontend.layouts.partials.pagination')}}
                        </div>
                        <!-- End Pagination Bar -->
                    @endif
                </div>
            </div>
            <div class="col-xl-4 col-lg-6">
                <!-- Start Filter Bar -->
                <div class="blog_right_sidebar" id="blog_right_sidebar">
                    <aside class="single_sidebar_widget search_widget mb-4">
                        <form action="{{ route('news.search') }}" method="GET">
                            <div class="input-group">
                                <input type="text" name="keywords" class="form-control" placeholder="Tìm bài viết..."
                                    onfocus="this.placeholder = ''" onblur="this.placeholder = 'Tìm bài viết...'">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="submit">
                                        <i class="lnr lnr-magnifier"></i>
                                    </button>
                                </span>
                            </div>
                        </form>
                    </aside>
                    <aside class="single_sidebar_widget popular_post_widget">
                        <h4>Bài viết phổ biến</h4>
                        <div class="br mt-2 mb-3"></div>
                        @foreach ($hotNews as $hn)
                            <div class="card mb-3 border-0 box__shadow">
                                <div class="row g-0 h-100">
                                    <div class="col-md-4 h-100">
                                        <a href="{{route('news.detail', $hn->news_slug) }}">
                                            <img onerror="this.src='/uploads/img_error2.jpg'" src="{{ asset($hn->news_img) }}" class="img-fluid h-100 object-fit-fill rounded-start" alt="{{$hn->news_title}}">
                                        </a>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <a href="{{route('news.detail', $hn->news_slug) }}">
                                                <h5 class="card-title title__truncate">{{$hn->news_title}}</h5>
                                            </a>
                                            <p class="card-text text__truncate mb-2">
                                                {{$hn->news_summarize}}
                                            </p>
                                            <div class="d-flex gap-4">
                                                <p class="mb-0 fst-italic">{{ date('d/m/Y', strtotime($hn->post_date)) }} </p>
                                                <p class="fst-italic">
                                                    <i class="far fa-eye"></i> &#8226; {{$hn->views}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="br"></div>
                    </aside>
                    <aside class="single_sidebar_widget post_category_widget">
                        <h4 class="widget_title">Danh mục bài viết</h4>
                        <ul class="list cat-list">
                            @foreach ($cateNews as $cN)
                                @if (count($cN->getNewsInCate) != 0)
                                    <li>
                                        <a href="{{ route('cate.news', $cN->cate_news_slug) }}"
                                            class="d-flex justify-content-between">
                                            <p>{{ $cN->cate_news_name }}</p>
                                            <p>{{ count($cN->getNewsInCate) }}</p>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <br>
                    </aside>
                    <aside class="single-sidebar-widget tag_cloud_widget">
                        <h4 class="widget_title">Thẻ tag</h4>
                        <ul class="list">
                            @foreach ($tags as $tg)
                                @if (count($tg->getNews) != 0)
                                    <li><a href="{{ route('tags.news', $tg->tag_slug) }}">{{ $tg->tag_content }}</a></li>
                                @endif
                        @endforeach
                        </ul>
                    </aside>
                </div>
                <!-- End Filter Bar -->
            </div>
        </div>
    </section>
    <!-- Start Content Area -->
@endsection