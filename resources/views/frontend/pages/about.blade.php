@extends('frontend.index')
<!-- Title -->
@section('title')
    Về chúng tôi
@endsection

<!-- Banner -->
@section('banner')
    <!-- Start banner Area -->

    <!-- End banner Area -->
@endsection

<!-- Content -->
@section('content')
    <!-- Start Content Area -->
    <section class="about-page mt-5 mb-4">
        <div class="container-fluid px-5">
            @foreach ($about as $data)
                
           
            <div class="row justify-content-center overflow-hidden">
                <div class="col-lg-6 text-center" data-aos="fade-up" data-aos-duration="2000">
                    <div class="container-fluid px-0">
                        <div class="section-title">
                            <h1>{{$data->faq_name}}</h1>
                            <p>Nơi Trải Nghiệm Thế Giới Giày Thể Thao Chính Hãng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row my-4">
                <div class="col-lg-12">
                    <div class="w-100 box__shadow p-3 rounded">
                        <p>
                            {!!$data->faq_content!!}
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    <!-- Start Content Area -->
@endsection