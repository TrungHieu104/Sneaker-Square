@extends('frontend.index')

@section('title') {{$detailNews->news_title}} @stop

@section('meta_title') {{$meta_title}}  @endsection

@section('description') {{$meta_desc}} @endsection

@section('keywords') {{$meta_keywords}} @endsection

@section('img') {{asset($img)}} @endsection

@section('banner')
    <!-- Start banner Area -->
   
    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start Content Area -->
    <section class="blog_area single-post-area my-5">
        <div class="container-fluid px-5">
            <div class="row overflow-hidden">
                <div class="col-lg-8 posts-list">
                    <div class="single-post row">
                        <div class="col-lg-12">
                            <div class="feature-img">
                                <img class="img-fluid w-100 h-100" src="{{ asset($detailNews->news_img) }}" 
                                onerror="this.src='/uploads/img_error2.jpg'"  alt="{{$detailNews->news_title}}">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="blog_info text-right">
                                <div class="post_tag">
                                    <a class="active" href="{{route('cate.news', $detailNews->getCateNews->cate_news_slug)}}">{{$detailNews->getCateNews->cate_news_name}}</a>
                                </div>
                                <h1 class="title-blog__post fs-3">{{$detailNews->news_title}}</h1>
                                <ul class="blog_meta list row">
                                    <li class="col-lg-4 col-md-6 ps-0 d-flex gap-2 align-items-center"><i class="lnr lnr-user"></i>{{$detailNews->news_created_by}}</li>
                                    <li class="col-lg-4 col-md-6 ps-0 d-flex gap-2 align-items-center"><i class="lnr lnr-calendar-full"></i>Ngày {{ date('d/m/Y', strtotime($detailNews->post_date)) }}</li>
                                    <li class="col-lg-4 col-md-6 ps-0 d-flex gap-2 align-items-center"><i class="lnr lnr-eye"></i>{{$detailNews->views}} lượt xem</li>
                                </ul>
                            </div>
                            <hr class="my-3">
                        </div>
                        <div class="col-lg-12">
                            <p class="text-justify">
                                {!!  $detailNews->news_content  !!}
                            </p>
                        </div>
                        <div class="col-lg-12 mb-4">
                            <div class="br"></div>
                            <h6 class="mb-0">Chia sẻ</h6>
                            <ul class="d-flex gap-3 ms-3 pt-2 d-flex align-items-center item_share-social">
                                <div class="fb-share-button" data-layout="button_count">
                                    <a href="#" class="fb-xfbml-parse-ignore" target="_blank" title="Facebook"></a>
                                </div>
                                <a href="#" class="twitter-share-button item-social btn-order btn-share-twitter" title="Twitter">
                                </a>
                                <div class="copy-link">
                                    <input type="hidden" class="copy-link-input" value="{{ $url_canonical }}">
                                    <button class="btn-link-copy copy-link-button btn-order d-flex justify-content-center align-items-center" title="Copy link">
                                        <i class="bi bi-link-45deg fs-5"></i>
                                    </button>
                                </div>
                            </ul>
                            <div class="fb-comments" data-href="{{url()->current()}}" data-width="100%" data-numposts="5"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="blog_right_sidebar">
                        <aside class="single_sidebar_widget search_widget">
                            <form action="{{route('news.search')}}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="keywords" class="form-control" placeholder="Tìm bài viết..."
                                        onfocus="this.placeholder = ''" onblur="this.placeholder = 'Tìm bài viết...'"/> 
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit">
                                            <i class="lnr lnr-magnifier"></i>
                                        </button>
                                    </span>
                                </div>
                            </form>
                            <div class="br"></div>
                        </aside>
                        <aside class="single_sidebar_widget author_widget">
                            <div class="avatar_author mx-auto">
                                <img src="{{asset($detailNews->getUser->user_img)}}" 
                                onerror="this.src='/frontend/img/author_2.jpg'"
                                alt="" class="w-100 h-100 rounded-circle object-fit-fill">
                            </div>
                            <h4>{{$detailNews->news_created_by}}</h4>
                            <p>Người viết</p>
                            <div class="social_icon">
                                <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                                <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                                <a href="#" target="_blank"><i class="fab fa-github"></i></a>
                                <a href="#" target="_blank"><i class="fab fa-behance"></i></a>
                            </div>
                            <p>
                               {{  $detailNews->news_summarize  }}
                            </p>
                            <div class="br"></div>
                        </aside>
                        <aside class="single_sidebar_widget popular_post_widget">
                            <h3 class="widget_title">Bài viết cùng chủ đề</h3>
                            <div id="scroller" style="margin-bottom: 20px;">
                                <!-- Blog Item -->
                                @foreach ($relatedNews as $rN)
                                    <div class="card mb-3 border-0">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <a href="{{route('news.detail', $rN->news_slug) }}">
                                                    <img onerror="this.src='/uploads/img_error2.jpg'" src="{{ asset($rN->news_img ) }}" class="img-fluid h-100 object-fit-fill rounded-start" alt="{{$rN->news_title}}">
                                                </a>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <a href="{{route('news.detail', $rN->news_slug) }}">
                                                        <h5 class="card-title title__truncate">{{$rN->news_title}}</h5>
                                                    </a>
                                                    <p class="card-text text__truncate mb-2">
                                                        {{$rN->news_summarize}}
                                                    </p>
                                                    <div class="d-flex gap-4">
                                                        <p class="mb-0 fst-italic">{{ date('d/m/Y', strtotime($rN->post_date)) }} </p>
                                                        <p class="fst-italic mb-0">
                                                            <i class="far fa-eye"></i> &#8226; {{$rN->views}}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach    
                            </div>
                            <div class="br"></div>
                        </aside>
                        <aside class="single_sidebar_widget post_category_widget">
                        <h4 class="widget_title">Danh mục bài viết</h4>
                        <ul class="list cat-list">
                            @foreach ($cateNews as $cN)
                                @if (count($cN->getNewsInCate) != 0) 
                                    <li>
                                        <a href="{{route('cate.news', $cN->cate_news_slug)}}" class="d-flex justify-content-between">
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
                        <h4 class="widget_title">Tag bài viết</h4>
                        <ul class="list">
                            @foreach ($getTags as $t)
                                <li>
                                    <a href="{{route('tags.news', $t->tag_slug)}}"><span>{{$t->tag_content}}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    </aside>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Start Content Area -->
@endsection

@push('css-access')
    <style>
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
@endpush

@push('script-access')
    <script>
        document.querySelectorAll(".copy-link").forEach((copyLinkParent) => {
            const inputField = copyLinkParent.querySelector(".copy-link-input");
            const copyButton = copyLinkParent.querySelector(".copy-link-button");
            const statusCopy = copyLinkParent.querySelector(".bi-link-45deg");
            const text = inputField.value;
            inputField.addEventListener("focus", () => inputField.select());

            copyButton.addEventListener("click", () => {
                inputField.select();
                navigator.clipboard.writeText(text);
                statusCopy.className = "bi bi-check2";
                setTimeout(() => {
                    statusCopy.className = "bi bi-link-45deg fw-bold fs-5";
                }, 1800);
            });
        });
    </script>
@endpush