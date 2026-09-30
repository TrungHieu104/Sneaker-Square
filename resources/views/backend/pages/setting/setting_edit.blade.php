@extends('backend.index')

@section('title')
    Cấu hình hệ thống
@endsection

@section('content')
    @php
        use App\Services\ShopSettings;

        $quanLyDon = auth()->user()->can('Quản trị Đơn hàng');
        $quanLyThongTin = auth()->user()->can('Quản trị Thông tin');
        // The shop tab leads, unless the admin cannot edit it or has just
        // failed to save the order form, whose errors would be out of sight.
        $loiDonHang = $errors->hasAny(['auto_complete_days', 'return_days', 'payment_window_minutes']);
        $moTabChung = $quanLyThongTin && ! ($quanLyDon && $loiDonHang);
    @endphp

    <h4 class="fw-bold py-3 mb-3">Cấu hình hệ thống</h4>

    <ul class="nav nav-tabs" role="tablist">
        @if ($quanLyThongTin)
            <li class="nav-item">
                <button type="button" class="nav-link {{ $moTabChung ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#tab-chung" role="tab">
                    <i class="bx bx-store me-1"></i> Thông tin cửa hàng
                </button>
            </li>
        @endif
        @if ($quanLyDon)
            <li class="nav-item">
                <button type="button" class="nav-link {{ $moTabChung ? '' : 'active' }}" data-bs-toggle="tab"
                    data-bs-target="#tab-don-hang" role="tab">
                    <i class="bx bx-printer me-1"></i> Đơn hàng
                </button>
            </li>
        @endif
    </ul>

    <div class="tab-content p-0">
        @if ($quanLyThongTin)
            <div class="tab-pane fade {{ $moTabChung ? 'show active' : '' }}" id="tab-chung" role="tabpanel">
                <form action="{{ route('setting.update_general') }}" method="post">
                    @csrf
                    @method('PUT')
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-3 d-flex align-items-center">
                                    <h5 class="fs-4 text-primary my-0">Liên hệ</h5>
                                </div>
                                <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                                    <button type="submit" class="btn btn-success px-5">Lưu</button>
                                </div>
                            </div>
                            <div class="form-text mb-3 mt-0">
                                Hiện trên website, trong email gửi khách và trên hoá đơn in kèm đơn hàng.
                            </div>
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <label for="shop-address" class="form-label">Địa chỉ</label>
                                    <input type="text" class="form-control" id="shop-address" name="address"
                                        value="{{ old('address', $shopInfo['address']) }}" />
                                    @error('address')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="shop-phone" class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-control" id="shop-phone" name="phone" inputmode="numeric"
                                        value="{{ old('phone', $shopInfo['phone']) }}" />
                                    @error('phone')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="shop-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="shop-email" name="email"
                                        value="{{ old('email', $shopInfo['email']) }}" />
                                    @error('email')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="fs-4 text-primary mb-1">Mã nhúng</h5>
                            <div class="form-text mb-3 mt-0">
                                Dán nguyên đoạn mã các dịch vụ cung cấp. Mã được chèn thẳng vào trang, nên chỉ dán mã
                                lấy từ chính Google Maps, Facebook hoặc Tawk.to.
                            </div>
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <label for="shop-map" class="form-label">Bản đồ Google Maps</label>
                                    <textarea class="form-control font-monospace small" id="shop-map" name="map_embed" rows="4">{{ old('map_embed', $shopInfo['map_embed']) }}</textarea>
                                    @error('map_embed')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">Google Maps → Chia sẻ → Nhúng bản đồ → Sao chép HTML. Hiện ở trang Liên hệ.</div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="shop-fanpage" class="form-label">Fanpage Facebook</label>
                                    <textarea class="form-control font-monospace small" id="shop-fanpage" name="fanpage_embed" rows="5">{{ old('fanpage_embed', $shopInfo['fanpage_embed']) }}</textarea>
                                    @error('fanpage_embed')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">Hiện ở chân trang.</div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="shop-chat" class="form-label">Khung chat Tawk.to</label>
                                    <textarea class="form-control font-monospace small" id="shop-chat" name="chat_embed" rows="5">{{ old('chat_embed', $shopInfo['chat_embed']) }}</textarea>
                                    @error('chat_embed')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">Để trống thì không hiện khung chat.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if ($quanLyDon)
            <div class="tab-pane fade {{ $moTabChung ? '' : 'show active' }}" id="tab-don-hang" role="tabpanel">
                <form action="{{ route('setting.update') }}" method="post">
                    @csrf
                    @method('PUT')
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-3 d-flex align-items-center">
                                    <h5 class="fs-4 text-primary my-0">Hoàn thành và trả hàng</h5>
                                </div>
                                <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                                    <button type="submit" class="btn btn-success px-5">Lưu</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label for="auto-complete-days" class="form-label">Tự động hoàn thành đơn sau (ngày)</label>
                                    <input type="number" class="form-control" id="auto-complete-days" name="auto_complete_days"
                                        min="{{ ShopSettings::MIN_DAYS }}" max="{{ ShopSettings::MAX_DAYS }}"
                                        value="{{ old('auto_complete_days', $autoCompleteDays) }}" />
                                    @error('auto_complete_days')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">
                                        Tính từ lúc GHN báo giao hàng thành công. Nếu khách không bấm "Đã nhận được hàng"
                                        trong khoảng này, đơn tự chuyển sang Thành công. Áp dụng cả cho các đơn đang chờ.
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="return-days" class="form-label">Cho phép yêu cầu trả hàng trong (ngày)</label>
                                    <input type="number" class="form-control" id="return-days" name="return_days"
                                        min="{{ ShopSettings::MIN_DAYS }}" max="{{ ShopSettings::MAX_DAYS }}"
                                        value="{{ old('return_days', $returnDays) }}" />
                                    @error('return_days')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">
                                        Tính từ lúc đơn chuyển sang Thành công. Quá hạn này khách không gửi được yêu cầu trả hàng.
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="fs-4 text-primary mb-3">Thanh toán</h5>
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label for="payment-window" class="form-label">Giữ hàng chờ thanh toán qua VNPay / MoMo (phút)</label>
                                    <input type="number" class="form-control" id="payment-window" name="payment_window_minutes"
                                        min="{{ ShopSettings::MIN_PAYMENT_WINDOW }}" max="{{ ShopSettings::MAX_PAYMENT_WINDOW }}"
                                        value="{{ old('payment_window_minutes', $paymentWindow) }}" />
                                    @error('payment_window_minutes')
                                        <small class="text-danger fst-italic">{{ $message }}</small>
                                    @enderror
                                    <div class="form-text">
                                        Tính từ lúc khách đặt hàng. Hết thời gian mà chưa trả tiền thì đơn tự huỷ, hàng và
                                        mã giảm giá được trả lại. Chỉ áp dụng cho đơn đặt sau khi lưu; đơn đang chờ giữ hạn cũ.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection

@push('script-backend')
    <script>
        // Each form comes back with its own tab in the address, so saving the
        // order rules does not land the admin on the shop tab that leads.
        if (location.hash && window.bootstrap) {
            const nut = document.querySelector('[data-bs-target="' + CSS.escape(location.hash) + '"]');
            if (nut) bootstrap.Tab.getOrCreateInstance(nut).show();
        }
    </script>
@endpush
