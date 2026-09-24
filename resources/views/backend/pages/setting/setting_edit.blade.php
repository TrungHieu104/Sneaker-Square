@extends('backend.index')

@section('title')
    Cấu hình hệ thống
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3">Cấu hình hệ thống</h4>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-don-hang" role="tab">
                <i class="bx bx-printer me-1"></i> Đơn hàng
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-chung" role="tab">
                <i class="bx bx-cog me-1"></i> Chung
            </button>
        </li>
    </ul>

    <div class="tab-content p-0">
        <div class="tab-pane fade show active" id="tab-don-hang" role="tabpanel">
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
                                    min="{{ \App\Services\ShopSettings::MIN_DAYS }}"
                                    max="{{ \App\Services\ShopSettings::MAX_DAYS }}"
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
                                    min="{{ \App\Services\ShopSettings::MIN_DAYS }}"
                                    max="{{ \App\Services\ShopSettings::MAX_DAYS }}"
                                    value="{{ old('return_days', $returnDays) }}" />
                                @error('return_days')
                                    <small class="text-danger fst-italic">{{ $message }}</small>
                                @enderror
                                <div class="form-text">
                                    Tính từ lúc đơn chuyển sang Thành công. Quá hạn này khách không gửi được yêu cầu trả hàng.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="tab-chung" role="tabpanel">
            <div class="card mb-4">
                <div class="card-body text-muted">
                    Chưa có cấu hình chung nào lưu trong hệ thống. Tên shop, địa chỉ lấy hàng, khoá GHN và khoá
                    thanh toán đang khai trong tệp <code>.env</code>, đổi thì phải sửa tệp rồi khởi động lại.
                </div>
            </div>
        </div>
    </div>
@endsection
