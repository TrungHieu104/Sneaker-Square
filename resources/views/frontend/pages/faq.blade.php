@extends('frontend.index')

@section('title')
{{$detailFaq->faq_name}}
@endsection

@section('banner')
    <!-- Start banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Chính sách</h1>
                    <nav class="d-flex align-items-center">
                        <a title="Quay về trang chủ" href="{{route('home.page')}}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">{{$detailFaq->faq_name}}</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- End banner Area -->
@endsection

@section('content')
    <!-- Start Content Area -->
    <section class="contact-page">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center"  data-aos="fade-up" data-aos-duration="2000">
                    <div class="section-title">
                        <h1>{{$detailFaq->faq_name}}</h1>
                        <p>
                            Bạn có thắc mắc, đọc dưới này nhé!
                        </p>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col">
                    <div class="w-100 box__shadow p-4">
                        {!!$detailFaq->faq_content!!}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Start Content Area -->

@endsection