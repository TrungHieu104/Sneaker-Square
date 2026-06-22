@extends('frontend.index')

@section('title')
    Tìm kiếm bài viết
@endsection

@section('banner')
    <!-- Start banner Area -->
    
    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start Content Area -->
    <section class="container-fluid px-5 my-5 section-blog__page">
        <div class="row">
            @if ($listNews->isEmpty())
                <div class="col-12">
                    <div class="card border-0 box__shadow d-flex justify-content-center">
                        <h4 class="text-center py-3 mb-0">Không tìm thấy bài viết...</h4>
                        <img src="{{asset('uploads/9318694.jpg')}}" alt="" class="img-fluid mx-auto" width="280px" height="280px">
                        <div class="mb-3 mx-auto">
                            <a href="{{route('blog.page')}}"
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
            @else
                @foreach ($listNews as $lN)
                <div class="col-xl-6 col-lg-6">
                    <div class="container-fluid px-0">
                        <div class="card mb-3 border-0 box__shadow">
                            <div class="row g-0">
                                <div class="col-md-5 img-blog_item__page">
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
                    </div>
                </div>
                @endforeach
            @endif
        </div>
        {{$listNews->links('frontend.layouts.partials.pagination')}}
    </section>
@endsection