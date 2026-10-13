{{--
    @param ?\App\Models\ProductModel $product    the product the customer came from, to go back to
    @param ?int                      $shoeSize   the size already on record
    @param array<int, int>           $sizeChart  size => longest foot it fits, in millimetres
--}}
@extends('frontend.index')

@section('title')
    Đo cỡ chân
@endsection

@section('banner')
    <section class="banner-area organic-breadcrumb">
        <div class="container-fluid px-5">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Đo cỡ chân</h1>
                    <nav class="d-flex align-items-center">
                        <a title="Quay về trang chủ" href="{{ route('home.page') }}">Trang chủ<span class="lnr lnr-arrow-right"></span></a>
                        <li class="breadcumb-link">Đo cỡ chân</li>
                    </nav>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <section class="foot-page">
        <div class="container-xxl px-3 my-5">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8 text-center">
                    <div class="section-title">
                        <h1>Chọn size giày của bạn</h1>
                        <p class="mb-0">
                            Chọn size bạn thường đi, hoặc đứng lên một tờ giấy A4 và chụp ảnh để hệ thống đo giúp.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-5 order-2 order-lg-1">
                    <div class="foot-guide">
                        <h4>Cách chụp để đo</h4>
                        <svg class="foot-guide__art" viewBox="0 0 300 330" role="img"
                            aria-label="Ảnh mẫu: bàn chân trên tờ A4, gót chạm mép dưới, thấy đủ 4 góc giấy">
                            <rect width="300" height="330" class="floor" />
                            <rect x="70" y="20" width="160" height="226" class="paper" />
                            <g class="corner">
                                <path d="M70 40 V20 H90" /><path d="M210 20 H230 V40" />
                                <path d="M230 226 V246 H210" /><path d="M90 246 H70 V226" />
                            </g>
                            <g class="foot">
                                <path d="M132 246 C116 246 112 222 113 196 C114 160 106 128 108 98 C110 70 122 56 146 54 C170 52 188 62 190 84 C193 110 186 142 182 176 C178 210 182 232 168 244 C160 247 142 247 132 246 Z" />
                                <ellipse cx="120" cy="58" rx="10" ry="12" /><ellipse cx="140" cy="48" rx="8" ry="10" />
                                <ellipse cx="157" cy="49" rx="7" ry="9" /><ellipse cx="172" cy="54" rx="6" ry="8" />
                                <ellipse cx="184" cy="64" rx="5.5" ry="7" />
                            </g>
                            <g class="ruler">
                                <line x1="250" y1="40" x2="250" y2="246" />
                                <line x1="243" y1="40" x2="257" y2="40" /><line x1="243" y1="246" x2="257" y2="246" />
                            </g>
                            <text x="150" y="272" class="note">gót chạm sát mép dưới tờ giấy</text>
                            <text x="150" y="292" class="note">thấy đủ 4 góc giấy</text>
                        </svg>

                        <ol class="foot-guide__steps">
                            <li>Đặt một tờ <b>giấy A4 trắng</b> nằm dọc trên sàn.</li>
                            <li>Đứng lên giấy bằng chân trần, <b>gót chạm sát mép dưới</b> tờ giấy, bàn chân thẳng dọc theo giấy, hai bên còn thấy giấy.</li>
                            <li>Dồn trọng lượng lên chân đang đo, rồi <b>chụp thẳng từ trên xuống</b>, thấy đủ <b>4 góc giấy</b>.</li>
                            <li>Chụp chỗ đủ sáng. Đo cả hai chân, lấy kết quả của chân dài hơn.</li>
                        </ol>
                    </div>
                </div>

                <div class="col-lg-5 order-1 order-lg-2">
                    <div class="foot-tool">
                        @if ($product)
                            <p class="foot-tool__for">
                                Đang chọn size cho
                                <a href="{{ route('product.detail', $product->pro_slug) }}">{{ $product->pro_name }}</a>
                            </p>
                        @endif

                        <form id="foot-form" novalidate>
                            <label for="foot-size-input" class="form-label fw-bold">Size bạn thường đi</label>
                            <select class="form-select" id="foot-size-input" name="size">
                                <option value="">Chọn size</option>
                                @foreach (array_keys($sizeChart) as $size)
                                    <option value="{{ $size }}" @selected($shoeSize === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <p class="text-danger small mb-0 mt-1" id="foot-error"></p>

                            <div class="foot-tool__or"><span>hoặc để hệ thống đo giúp</span></div>

                            <label class="btn btn-outline-dark w-100 foot-tool__pick" for="foot-photo">
                                <i class='bx bx-camera'></i> Chụp hoặc chọn ảnh bàn chân
                            </label>
                            <input type="file" id="foot-photo" accept="image/*" hidden>
                            <p class="foot-tool__status" id="foot-status" aria-live="polite"></p>
                            <p class="foot-tool__roomier d-none" id="foot-roomier">
                                Muốn đi thoải mái hơn, bạn có thể lên 1 size:
                                <button type="button" class="foot-tool__roomier-pick" id="foot-roomier-pick"></button>
                            </p>
                            <canvas id="foot-preview" class="foot-tool__preview d-none"></canvas>

                            <button type="submit" class="btn btn-cart_order text-white fw-bold w-100 mt-3">Lưu size</button>
                        </form>

                        <div class="foot-tool__result d-none" id="foot-result">
                            <span class="foot-tool__result-label">Size của bạn</span>
                            <span class="foot-tool__result-size" id="foot-size"></span>
                            @if ($product)
                                <a href="{{ route('product.detail', $product->pro_slug) }}" class="btn btn-outline-dark w-100 mt-2">
                                    Quay lại sản phẩm
                                </a>
                            @endif
                        </div>

                        @guest
                            <p class="small text-muted mt-3 mb-0">
                                Bạn chưa đăng nhập nên size chỉ được nhớ trong lần truy cập này.
                                <a href="{{ route('user.login') }}">Đăng nhập</a> để lưu vào tài khoản.
                            </p>
                        @endguest
                    </div>
                </div>
            </div>

            <div class="row g-3 justify-content-center mt-2">
                <div class="col-lg-10">
                    <h5 class="foot-mistakes__title">Những ảnh sẽ không đo được</h5>
                </div>
                <div class="col-lg-10">
                    <div class="foot-mistakes">
                        <figure class="foot-mistake">
                            <svg viewBox="0 0 200 230" role="img" aria-label="Gót chân không chạm mép giấy">
                                <rect width="200" height="230" class="floor" />
                                <rect x="40" y="15" width="120" height="170" class="paper" />
                                <path class="foot" d="M88 150 C78 150 74 136 75 118 C76 96 71 76 73 58 C75 42 83 34 98 33 C113 32 124 38 125 52 C127 68 122 88 120 108 C118 130 120 142 112 149 C107 151 96 151 88 150 Z" />
                                <path class="gap" d="M70 156 H130 M70 182 H130" />
                                <text x="100" y="173" class="gap-label">khoảng hở</text>
                            </svg>
                            <figcaption><b>Gót không chạm mép giấy.</b> Kết quả sẽ dài hơn chân thật.</figcaption>
                        </figure>
                        <figure class="foot-mistake">
                            <svg viewBox="0 0 200 230" role="img" aria-label="Bàn chân đặt cạnh tờ giấy">
                                <rect width="200" height="230" class="floor" />
                                <rect x="100" y="30" width="85" height="120" class="paper" />
                                <path class="foot" d="M38 190 C28 190 24 170 25 150 C26 120 21 92 23 70 C25 50 33 40 48 39 C63 38 74 46 75 62 C77 82 72 110 70 136 C68 162 70 180 62 188 C57 191 46 191 38 190 Z" />
                            </svg>
                            <figcaption><b>Chân đặt cạnh tờ giấy.</b> Phải đứng lên giấy.</figcaption>
                        </figure>
                        <figure class="foot-mistake">
                            <svg viewBox="0 0 200 230" role="img" aria-label="Tờ giấy bị cắt mất góc">
                                <rect width="200" height="230" class="floor" />
                                <rect x="40" y="-30" width="120" height="170" class="paper" />
                                <path class="foot" d="M88 138 C78 138 74 124 75 106 C76 84 71 62 73 44 C75 28 83 20 98 19 C113 18 124 24 125 38 C127 54 122 76 120 96 C118 118 120 130 112 137 C107 139 96 139 88 138 Z" />
                            </svg>
                            <figcaption><b>Mất góc giấy.</b> Phải thấy đủ 4 góc trong ảnh.</figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css-access')
    <style>
        .foot-guide__art,
        .foot-mistake svg {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .foot-guide__art {
            max-width: 300px;
            margin: 12px auto 20px;
        }

        .foot-guide svg .floor,
        .foot-mistake svg .floor {
            fill: #e4dccd;
        }

        .foot-guide svg .paper,
        .foot-mistake svg .paper {
            fill: #fff;
            stroke: #cfcfcf;
            stroke-width: 1.5;
        }

        .foot-guide svg .foot,
        .foot-mistake svg .foot {
            fill: #d9a07c;
        }

        .foot-guide__art .corner {
            fill: none;
            stroke: var(--color-green, #198754);
            stroke-width: 4;
        }

        .foot-guide__art .ruler {
            stroke: var(--color-orange);
            stroke-width: 2;
        }

        .foot-guide__art .note {
            fill: #333;
            font-size: 13px;
            font-weight: 600;
            text-anchor: middle;
        }

        .foot-mistake svg .gap {
            stroke: var(--color-red);
            stroke-width: 2;
            stroke-dasharray: 4 3;
        }

        .foot-mistake svg .gap-label {
            fill: var(--color-red);
            font-size: 12px;
            font-weight: 600;
            text-anchor: middle;
        }

        .foot-guide__steps {
            counter-reset: step;
            list-style: none;
            padding-left: 0;
        }

        .foot-guide__steps li {
            counter-increment: step;
            position: relative;
            padding-left: 34px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .foot-guide__steps li::before {
            content: counter(step);
            position: absolute;
            left: 0;
            top: 1px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff4ea;
            color: var(--color-orange);
            font-size: 13px;
            font-weight: bold;
            line-height: 24px;
            text-align: center;
        }

        .foot-mistakes__title {
            margin: 0;
        }

        .foot-mistakes {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .foot-mistake {
            margin: 0;
            border: 1px solid #f1c2c7;
            border-radius: 12px;
            padding: 10px;
        }

        .foot-mistake figcaption {
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.5;
        }

        .foot-mistake figcaption b {
            color: var(--color-red);
        }

        .foot-tool {
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            padding: 24px;
        }

        .foot-tool__for {
            font-size: 14px;
        }

        .foot-tool__for a {
            font-weight: bold;
            color: #222;
        }

        .foot-tool__or {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 18px 0 14px;
            color: #888;
            font-size: 13px;
        }

        .foot-tool__or::before,
        .foot-tool__or::after {
            content: "";
            flex: 1;
            border-top: 1px solid #e6e6e6;
        }

        .foot-tool__pick {
            cursor: pointer;
        }

        .foot-tool__status {
            font-size: 14px;
            min-height: 21px;
            margin: 10px 0 0;
        }

        .foot-tool__status.is-error {
            color: var(--color-red);
        }

        .foot-tool__roomier {
            font-size: 14px;
            margin: 6px 0 0;
            padding: 8px 12px;
            border-radius: 8px;
            background: #fff4ea;
        }

        .foot-tool__roomier-pick {
            border: 1px solid var(--color-orange);
            border-radius: 6px;
            background: #fff;
            color: var(--color-orange);
            font-weight: 600;
            padding: 1px 10px;
            margin-left: 4px;
        }

        .foot-tool__roomier-pick:hover,
        .foot-tool__roomier-pick.is-picked {
            background: var(--color-orange);
            color: #fff;
        }

        .foot-tool__preview {
            display: block;
            width: 100%;
            max-width: 280px;
            margin: 12px auto 0;
            border-radius: 6px;
            border: 1px solid #e6e6e6;
        }

        .foot-tool__result {
            margin-top: 20px;
            padding: 16px;
            border-radius: 10px;
            background: #fff4ea;
            text-align: center;
        }

        .foot-tool__result-label {
            display: block;
            font-size: 14px;
            color: #555;
        }

        .foot-tool__result-size {
            display: block;
            font-size: 40px;
            font-weight: bold;
            line-height: 1.2;
            color: var(--color-orange);
        }

        @media (max-width: 767.98px) {
            .foot-mistakes {
                grid-template-columns: minmax(0, 1fr);
            }

            .foot-mistake {
                display: grid;
                grid-template-columns: 110px minmax(0, 1fr);
                gap: 12px;
                align-items: center;
            }

            .foot-mistake figcaption {
                margin-top: 0;
            }
        }

        @media (max-width: 575.98px) {
            .foot-tool {
                padding: 16px;
            }
        }
    </style>
@endpush

@push('script-access')
    <script src="{{ asset_v('frontend/js/foot-measure.js') }}"></script>
    <script>
        (function () {
            const opencvUrl = @json(asset('frontend/vendor/opencv/opencv.js'));
            const heicUrl = @json(asset('frontend/vendor/heic-to/heic-to.js'));
            const sizeChart = @json($sizeChart);
            const savedSize = @json($shoeSize);
            const status = document.getElementById('foot-status');
            const preview = document.getElementById('foot-preview');
            const select = document.getElementById('foot-size-input');
            const error = document.getElementById('foot-error');
            const roomier = document.getElementById('foot-roomier');
            const roomierPick = document.getElementById('foot-roomier-pick');

            function say(text, isError) {
                status.textContent = text;
                status.classList.toggle('is-error', !!isError);
            }

            function sizeForLength(lengthMm) {
                const sizes = Object.keys(sizeChart).map(Number).sort(function (a, b) { return a - b; });
                for (const size of sizes) {
                    if (lengthMm <= sizeChart[size]) {
                        return size;
                    }
                }

                return null;
            }

            // Shoes run differently from brand to brand, and some people like
            // room for thick socks: one size up is offered, never chosen for them.
            function showRoomierPick() {
                const picked = select.value === roomierPick.dataset.size;
                roomierPick.classList.toggle('is-picked', picked);
                roomierPick.textContent = (picked ? 'Đã chọn size ' : 'Chọn size ') + roomierPick.dataset.size;
            }

            function offerRoomier(size) {
                const up = size + 1;
                if (!(up in sizeChart)) {
                    roomier.classList.add('d-none');
                    return;
                }
                roomierPick.dataset.size = up;
                showRoomierPick();
                roomier.classList.remove('d-none');
            }

            roomierPick.addEventListener('click', function () {
                select.value = this.dataset.size;
                showRoomierPick();
            });

            select.addEventListener('change', showRoomierPick);

            // Started on arrival: the library is about 10 MB, and by the time the
            // photo is taken it has usually finished loading.
            FootMeasure.ready(opencvUrl).catch(function (e) { say(e.message, true); });

            document.getElementById('foot-photo').addEventListener('change', function () {
                const file = this.files[0];
                this.value = '';

                if (!file) {
                    return;
                }

                say('Đang đo...');
                preview.classList.add('d-none');
                roomier.classList.add('d-none');
                // A size left over from an earlier photo must not be saved by
                // mistake when this one cannot be measured.
                select.value = savedSize === null ? '' : String(savedSize);

                FootMeasure.open(file, heicUrl)
                    .then(function (image) {
                        return FootMeasure.measure(opencvUrl, image, preview);
                    })
                    .then(function (result) {
                        const size = sizeForLength(result.lengthMm);
                        preview.classList.remove('d-none');

                        if (size === null) {
                            say('Bàn chân dài ' + result.lengthMm + ' mm, cửa hàng chưa có size phù hợp.', true);
                            return;
                        }

                        select.value = String(size);
                        offerRoomier(size);
                        say('Bàn chân dài ' + result.lengthMm + ' mm, hợp với size ' + size + '. Nếu khung xanh ôm đúng bàn chân, bấm Lưu size.');
                    })
                    .catch(function (e) {
                        say(e.isMeasureError ? e.message : 'Không đo được ảnh này, bạn thử chụp lại.', true);
                    });
            });

            document.getElementById('foot-form').addEventListener('submit', function (event) {
                event.preventDefault();
                error.textContent = '';

                $.ajax({
                    url: @json(route('foot.store')),
                    method: 'POST',
                    data: { size: select.value, _token: @json(csrf_token()) },
                    dataType: 'json',
                }).done(function (foot) {
                    document.getElementById('foot-size').textContent = foot.size;
                    document.getElementById('foot-result').classList.remove('d-none');
                }).fail(function (xhr) {
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    error.textContent = errors && errors.size
                        ? errors.size[0]
                        : 'Chưa lưu được, bạn thử lại.';
                });
            });
        })();
    </script>
@endpush
