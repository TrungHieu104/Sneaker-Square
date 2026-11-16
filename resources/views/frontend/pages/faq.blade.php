@extends('frontend.index')

@section('title')
Câu hỏi thường gặp
@endsection

@section('description', 'Câu hỏi thường gặp khi mua giày tại Sneaker Square: thanh toán, giao hàng, đổi trả, size và ví SPay.')

@push('css-access')
    <link rel="stylesheet" href="{{ asset_v('frontend/css/faq.css') }}">
@endpush

@section('banner')
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Câu hỏi thường gặp</h1>
                    <nav class="d-flex align-items-center">
                        <a title="Quay về trang chủ" href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Câu hỏi thường gặp</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="contact-page faq-page">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <div class="section-title">
                        <h1>Câu hỏi thường gặp</h1>
                        <p>Bạn có thắc mắc, đọc dưới này nhé!</p>
                    </div>
                    @if ($groups->isNotEmpty())
                        <input type="search" class="form-control faq-search" id="faq-search" placeholder="Tìm câu hỏi…" aria-label="Tìm câu hỏi">
                    @endif
                </div>
            </div>

            @if ($groups->isEmpty())
                <p class="text-center text-muted my-5">Chưa có câu hỏi nào.</p>
            @else
                <div class="row justify-content-center mt-4 g-4">
                    <div class="col-lg-3">
                        <nav class="faq-groups" aria-label="Nhóm câu hỏi">
                            @foreach ($groups as $group)
                                <a href="#nhom-{{ $group->group_id }}" @class(['is-current' => $loop->first])>
                                    <span>{{ $group->group_name }}</span><small>{{ $group->faqs->count() }}</small>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                    <div class="col-lg-7">
                        @foreach ($groups as $group)
                            <section class="faq-group" id="nhom-{{ $group->group_id }}" data-faq-group>
                                <h2>{{ $group->group_name }}</h2>
                                @foreach ($group->faqs as $faq)
                                    <details class="faq-item" id="cau-hoi-{{ $faq->faq_id }}" data-faq-item @if ($loop->parent->first && $loop->first) open @endif>
                                        <summary>{{ $faq->faq_question }}</summary>
                                        <div class="faq-answer">{!! $faq->faq_answer !!}</div>
                                    </details>
                                @endforeach
                            </section>
                        @endforeach
                        <p class="text-muted faq-empty" id="faq-empty" hidden>Không có câu hỏi nào khớp. Thử từ khác, hoặc hỏi shop ở dưới.</p>

                        <div class="faq-help">
                            <h3>Chưa thấy câu trả lời?</h3>
                            <p>Xem các chính sách của shop, gửi tin ở trang Liên hệ hoặc gọi hotline.</p>
                            <div class="faq-help-links">
                                @foreach ($policies as $policy)
                                    <a href="{{ route('policy.detail', $policy->page_slug) }}">{{ $policy->page_name }}</a>
                                @endforeach
                                <a href="{{ route('contact.page') }}">Liên hệ</a>
                                @if ($hotline['tel'] !== '')
                                    <a href="tel:{{ $hotline['tel'] }}" class="is-call">Gọi {{ $hotline['display'] }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('script-access')
    <script>
        // A link to #cau-hoi-12 opens that question; the search box filters as you type.
        (function () {
            const items = Array.from(document.querySelectorAll('[data-faq-item]'));
            const groups = Array.from(document.querySelectorAll('[data-faq-group]'));
            const search = document.getElementById('faq-search');
            const empty = document.getElementById('faq-empty');
            const fold = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();

            function openFromHash() {
                const target = location.hash && document.getElementById(location.hash.slice(1));

                if (target && target.matches('[data-faq-item]')) {
                    target.open = true;
                    target.scrollIntoView({ block: 'center' });
                }
            }

            if (search) {
                search.addEventListener('input', function () {
                    const wanted = fold(search.value.trim());
                    let shown = 0;

                    items.forEach(function (item) {
                        const match = wanted === '' || fold(item.textContent).includes(wanted);
                        item.hidden = !match;
                        shown += match ? 1 : 0;
                    });
                    groups.forEach(function (group) {
                        group.hidden = !group.querySelector('[data-faq-item]:not([hidden])');
                    });
                    empty.hidden = shown > 0;
                });
            }

            // The group list marks the group being read.
            const links = Array.from(document.querySelectorAll('.faq-groups a'));

            if ('IntersectionObserver' in window) {
                const watcher = new IntersectionObserver(function (entries) {
                    entries.filter((entry) => entry.isIntersecting).forEach(function (entry) {
                        links.forEach((link) => link.classList.toggle('is-current', link.hash === '#' + entry.target.id));
                    });
                }, { rootMargin: '-120px 0px -60% 0px' });

                groups.forEach((group) => watcher.observe(group));
            }

            window.addEventListener('hashchange', openFromHash);
            openFromHash();
        })();
    </script>
@endpush
