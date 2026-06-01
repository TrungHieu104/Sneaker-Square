<!-- start footer Area -->
<footer class="footer-area">
    <div class="container-fluid px-5">
        <div class="row justify-content-start">
            <div class="col-lg-2 col-md-6 mb-3">
                <h6>Về chúng tôi</h6>
                <ul class="footer-link">
                    <li class="mb-2"><a href="{{route('about.page')}}">Giới thiệu</a></li>
                    <li class="mb-2"><a href="{{route('contact.page')}}">Liên hệ</a></li>
                    <li class="mb-2"><a href="{{route('blog.page')}}">Bài viết</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <h6>Chính sách & điều khoản</h6>
                <ul class="footer-link">
                    @foreach ($faq as $data)
                        <li class="mb-2"><a href="{{ route('faq.detail', $data->faq_slug) }}">{{$data->faq_name}}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <h6>Thông tin liên hệ</h6>
                @foreach($contact as $data)
                <div class="d-flex justify-content-start gap-2 mb-2">
                    <i class="bi bi-telephone-fill"></i>
                    <span>{{ ''.substr($data->contact_phone, 0, 4).' '.substr($data->contact_phone, 4, 3).' '.substr($data->contact_phone, 7) }}</span>
                </div>
                <div class="d-flex justify-content-start gap-2 mb-2">
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:{{$data->contact_email}}" target="_blank" style="color: #777777;">{{$data->contact_email}}</a>
                </div>
                <div class="d-flex justify-content-start gap-2 mb-2">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>{{$data->contact_address}}</span>
                </div>
                @endforeach
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <h6>Theo dõi chúng tôi</h6>
                <div class="col-lg-12 d-flex gap-3 mb-3 social-footer justify-content-start">
                    <a href="#" target="_blank">
                        <div class="social-item">
                            <i class="bi bi-facebook"></i>
                        </div>
                    </a>
                    <a href="#" target="_blank">
                        <div class="social-item">
                            <i class="bi bi-youtube"></i>
                        </div>
                    </a>
                    <a href="#" target="_blank">
                        <div class="social-item">
                            <i class="bi bi-instagram"></i>
                        </div>
                    </a>
                </div>
                <div class="col-lg-12 overflow-hidden">
                    @foreach($contact as $data)
                        {!!$data->fanpage_link!!}
                    @endforeach
                </div>
            </div>
            <hr class="w-100 mt-3 px-0">
        </div>
        <div class="footer-bottom d-flex justify-content-center align-items-center flex-wrap">
            <p class="mb-3 text-center">
                &copy;
                <script>document.write(new Date().getFullYear());</script> -
                All rights reserved. Designed by <a href="#" target="_blank" class="link_team">Sneaker Square</a>
            </p>
        </div>
    </div>
</footer>
@foreach($contact as $data)
{!!$data->tawk_link!!}
@endforeach
<!-- End footer Area -->