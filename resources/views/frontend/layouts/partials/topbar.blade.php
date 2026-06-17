<!-- Start topbar Area -->
<div class="d-flex align-items-center" id="top-bar">
    <div class="top-bar_left">
        @foreach($contact as $data)
            <marquee class="text-white text-uppercase fw-bold">{{$data->contact_address}}</marquee>
        @endforeach
    </div>
    <div class="top-bar_right">
        <div class="top-right_info d-flex align-items-center gap-3">
            @if (Auth::check())
                <span class="text-white top-bar__user">
                    <i class='bx bx-user fs-6' id="top-bar_icon__user"></i>
                    <div class="top-bar_info box__shadow" id="top-bar_form__user">
                        <ul>
                            <li>
                                <span class="title__truncate">
                                    {{Auth::guard('web')->user()->name }}
                                </span>
                            </li>
                            <li>
                            <a href="{{route('thong-tin-tai-khoan.index')}}">Thông tin</a>
                            </li>
                            <li>
                                <a href="{{route('user.logout')}}">Đăng xuất</a>
                            </li>
                        </ul>
                    </div>
                </span>
            @else
                <a href="{{route('user.login')}}">Đăng nhập</a>
            @endif
            <span class="top-right_line"></span>
            @foreach($contact as $data)
                <a href="tel:0369469525">Hotline: {{ ''.substr($data->contact_phone, 0, 4).' '.substr($data->contact_phone, 4, 3).' '.substr($data->contact_phone, 7) }}</a>
            @endforeach
        </div>
    </div>
</div>
<!-- End topbar Area -->

@if (Auth::check())
    @push('script-access')
        <script>
            const iconUser = document.getElementById('top-bar_icon__user');
            const formInfo = document.getElementById('top-bar_form__user');

            iconUser.addEventListener('click', () => {
                if (formInfo.classList.contains('active')) {
                    formInfo.classList.remove('active');
                } else {
                    formInfo.classList.add('active');
                }
            })
            document.addEventListener("click", function (event) {
                if (event.target !== iconUser && !formInfo.contains(event.target)) {
                    formInfo.classList.remove('active');
                }
            })
        </script>
    @endpush
@endif