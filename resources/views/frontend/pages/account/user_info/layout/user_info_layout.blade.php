@extends('frontend.index')
<!-- Title -->
@section('title')
    Thông tin tài khoản
@endsection

<!-- Banner -->
@section('banner')
    <!-- Start banner Area -->

    <!-- End banner Area -->
@endsection

@push('css-access')
    <style>
        .account-nav {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .account-nav__me {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            border-bottom: 1px solid #EFEFEF;
            text-decoration: none;
        }

        .account-nav__avatar {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--light);
        }

        .account-nav__me-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .account-nav__name {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 15px;
            font-weight: 600;
            color: var(--color-secondary);
        }

        .account-nav__name span,
        .account-nav__email {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-nav__email {
            font-size: 12.5px;
            color: var(--color-text);
        }

        .account-nav__me:hover .account-nav__name,
        .account-nav__me.is-current .account-nav__name {
            color: var(--color-orange);
        }

        .account-nav__me.is-current {
            background: var(--light);
        }

        .account-nav__me.is-current::before {
            content: "";
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--color-orange);
        }

        .account-nav__me:focus-visible {
            outline: 2px solid var(--color-orange);
            outline-offset: -2px;
        }

        .account-nav__list {
            list-style: none;
            margin: 0;
            padding: 8px 0;
        }

        .account-nav__link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            font-size: 15px;
            color: var(--color-secondary);
            text-decoration: none;
            transition: background-color .15s ease-out, color .15s ease-out;
        }

        .account-nav__link i {
            font-size: 18px;
            color: var(--grey);
            transition: color .15s ease-out;
        }

        .account-nav__link:hover,
        .account-nav__link:hover i {
            color: var(--color-orange);
        }

        .account-nav__link:hover {
            background: var(--light);
        }

        .account-nav__link:focus-visible {
            outline: 2px solid var(--color-orange);
            outline-offset: -2px;
        }

        .account-nav__link:active {
            background: #EDEDED;
        }

        .account-nav__link.is-current,
        .account-nav__link.is-current i {
            color: var(--color-orange);
        }

        .account-nav__link.is-current {
            background: var(--light);
            font-weight: 600;
        }

        .account-nav__link.is-current::before {
            content: "";
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--color-orange);
        }

        /* The narrow column leaves room for the icon only, so the row centres
           on it once `.menu-info` is hidden by the site-wide breakpoint. */
        @media (max-width: 768px) {
            .account-nav__me,
            .account-nav__link {
                justify-content: center;
                gap: 0;
                padding-inline: 8px;
            }

            /* This block is pushed after main.css, so the site-wide
               `.menu-info { display: none }` loses on source order alone. */
            .account-nav__me-text {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .account-nav__link,
            .account-nav__link i {
                transition: none;
            }
        }
    </style>
@endpush

<!-- Content -->
@section('content')
    @php
        $me = auth()->user();
        $mucTaiKhoan = [
            ['route' => 'user.update_pass', 'icon' => 'bx bxs-key', 'label' => 'Mật khẩu'],
            ['route' => 'user.delivery', 'icon' => 'bx bx-current-location', 'label' => 'Địa chỉ giao hàng'],
            ['route' => 'user.order', 'icon' => 'bx bx-cart', 'label' => 'Đơn hàng'],
            ['route' => 'user.wallet', 'icon' => 'bx bx-wallet', 'label' => 'Ví của tôi'],
        ];
    @endphp
    <div class="container-fluid px-md-5 px-3 my-md-5 my-3">
        <div class="row">
            <div class="col-md-3 col-3">
                <nav class="account-nav" aria-label="Tài khoản của tôi">
                    <a class="account-nav__me {{ url()->current() === route('thong-tin-tai-khoan.index') ? 'is-current' : '' }}"
                        href="{{ route('thong-tin-tai-khoan.index') }}">
                        <img class="account-nav__avatar" src="{{ $me->user_img }}"
                            onerror="this.src='/uploads/img_error3.png'" alt="">
                        <span class="account-nav__me-text menu-info">
                            <span class="account-nav__name">
                                <span>{{ $me->name }}</span>
                                @if ($me->email_verified_at !== null)
                                    <svg width="15" height="15" viewBox="0 0 32 32" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" aria-label="Đã xác thực email">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z"
                                            fill="#4383FF"></path>
                                    </svg>
                                @endif
                            </span>
                            <span class="account-nav__email">{{ $me->email }}</span>
                        </span>
                    </a>
                    <ul class="account-nav__list">
                        @foreach ($mucTaiKhoan as $muc)
                            @php $dangMo = url()->current() === route($muc['route']); @endphp
                            <li>
                                <a class="account-nav__link {{ $dangMo ? 'is-current' : '' }}"
                                    href="{{ route($muc['route']) }}" @if ($dangMo) aria-current="page" @endif>
                                    <i class="{{ $muc['icon'] }}"></i>
                                    <span class="menu-info">{{ $muc['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
            <div class="col-md-9 col-9 ps-md-5 ps-2 pe-md-5 pe-0 py-md-5 py-3 page-user">
                <div class="tab-content" id="myTabContent">
                    @yield('content_user')
                </div>
            </div>
        </div>
    </div>
@endsection
