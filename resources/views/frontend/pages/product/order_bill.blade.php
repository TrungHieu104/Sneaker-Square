@extends('frontend.index')

@section('title')
    Thông tin đơn hàng {{ $order->order_code }}
@endsection

@section('banner')
    <!-- Start banner Area -->
    
    <!-- End banner Area -->
@endsection

@section('content')
    <style>
        .h-100 {
            height: 100% !important;
        }

        /* A form button, because paying again opens a fresh gateway attempt
           and that must not happen on a plain GET; drawn as the link it was. */
        .pay-now-link {
            padding: 0;
            border: 0;
            background: none;
            font-size: inherit;
            color: var(--bs-link-color, #0d6efd);
        }

        .pay-now-link:hover {
            text-decoration: underline;
        }

        .payment-due {
            color: #6B6867;
        }

        /* A fixed height: a percentage follows the row, and the wallet's
           balance line makes that row taller than the logos were drawn for. */
        .payment-icon {
            height: 32px;
            width: auto;
        }

        /* Tall enough for the wallet's two lines, so every option is the same size. */
        .payment-option {
            min-height: 64px;
        }

        @media screen and (max-width:768px) {
            .mb-rps-dfl {
                display: block !important;
            }

            .mb-rps-dfl .form-floating {
                margin-bottom: 20px !important;
            }
        }

        @media screen and (max-width:680px) {
            .mx-5 {
                margin-left: 2rem !important;
                margin-right: 2rem !important;
            }

            .mb-rps-dfl {
                display: block !important;
            }

            .mb-rps-dfl .form-floating {
                margin-bottom: 20px !important;
            }

            .rps-back {
                left: 8%;
                top: 0% !important;
            }
        }

        @media screen and (max-width:376px) {
            .mx-5 {
                margin-left: .5rem !important;
                margin-right: .5rem !important;
            }
        }
    </style>
    <!-- Start Content Area -->
    <section class="container-fluid p-0 overflow-hidden">
        <div class="container-fluid px-5 my-5">
            <div class="row justify-content-center position-relative">
                <div class="col-lg-6 text-center" data-aos="fade-right" data-aos-duration="2000">
                    <div class="section-title">
                        <h1>Thông tin đơn hàng</h1>
                        <p>
                            Cảm ơn vì đã tin tưởng lựa chọn sản phẩm tại cửa hàng.
                            Sản phẩm sẽ được chúng tôi giao tới cho bạn sớm nhất!
                        </p>
                    </div>
                </div>
            </div>
            <div class="container-fluid px-0">
                <div class="container-xxl box__shadow p-3 rounded">
                    <div class="row">
                        <div class="container-fluid mt-3 d-flex justify-content-center">
                            @if ($order->hasStatus(\App\Enums\OrderStatus::Cancelled))
                                <div class="text-center mb-3">
                                    <h4 class="fw-bold mb-1">Đã hủy đơn</h4>
                                    @if ($order->order_cancel_reason)
                                        <div class="text-muted small">Lý do: {{ $order->order_cancel_reason }}</div>
                                    @endif
                                </div>
                            @else
                                @if ($order->orderReturn)
                                    <div class="text-center mb-3">
                                        <h4 class="fw-bold mb-1">Yêu cầu trả hàng: {{ $order->orderReturn->statusLabel() }}</h4>
                                        <div class="text-muted small">
                                            @switch($order->orderReturn->status)
                                                @case(\App\Models\OrderReturnModel::REQUESTED)
                                                    Cửa hàng đang xem xét yêu cầu của bạn.
                                                    @break
                                                @case(\App\Models\OrderReturnModel::APPROVED)
                                                    Vui lòng đóng gói sản phẩm, nhân viên GHN sẽ đến lấy hàng tại địa chỉ nhận hàng của đơn.
                                                    @if ($order->orderReturn->return_shipping_code)
                                                        Mã vận đơn trả hàng: <b>{{ $order->orderReturn->return_shipping_code }}</b>.
                                                    @endif
                                                    @break
                                                @case(\App\Models\OrderReturnModel::RECEIVED)
                                                    Cửa hàng đã nhận được hàng trả và sẽ hoàn tiền cho bạn.
                                                    @break
                                                @case(\App\Models\OrderReturnModel::REJECTED)
                                                    Cửa hàng đã từ chối yêu cầu này. Còn trong thời hạn thì bạn vẫn gửi lại được.
                                                    @break

                                                @case(\App\Models\OrderReturnModel::CANCELLED)
                                                    Bạn đã huỷ yêu cầu này. Còn trong thời hạn thì bạn vẫn gửi lại được.
                                                    @break

                                                @case(\App\Models\OrderReturnModel::REFUNDED)
                                                    Đã hoàn {{ number_format((int) $order->orderReturn->refund_amount, 0, ',', '.') }} VNĐ.
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                @elseif ($order->hasStatus(\App\Enums\OrderStatus::Returned))
                                    <h4 class="fw-bold mb-3">Giao hàng không thành công, đơn đã được hoàn về cửa hàng</h4>
                                @elseif ($order->order_status->isComingBack())
                                    <h4 class="fw-bold mb-3">Đã gửi yêu cầu trả hàng</h4>
                                @else
                                    @include('frontend.pages.product.partials.order_progress')
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="vtrWey"></div>
                            <h4 class="text-center fw-bold mt-3 mb-4 text-uppercase">Đơn hàng của bạn</h4>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="d-flex gap-1">
                                        <h6 class="text-uppercase">Mã đơn hàng: </h6>
                                        <h6 class="fw-normal">{{ $order->order_code }}</h6>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="d-flex gap-1">
                                        <h6 class="text-uppercase">Ngày đặt hàng: </h6>
                                        <h6 class="fw-normal">{{ date('d/m/Y', strtotime($order->order_date)) }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="bt_hr my-2"></div>
                            <div class="row">
                                <h6 class="fw-bold text-uppercase my-3">Địa chỉ nhận hàng</h6>
                                <div class="col-lg-12">
                                    <div class="container-fluid px-0">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 mb-3 bt_hr">
                                                <div class="bg-light p-3 rounded mb-3 flex-wrap">
                                                    <div class="d-flex flex-wrap">
                                                        <strong>{{ $order->order_name }}</strong> |
                                                        <b>{{ $order->order_phone }}</b> |
                                                        <b>{{ $order->order_email }}</b>
                                                    </div>
                                                    <div class="d-flex">
                                                        <span>{{ $order->order_address }},</span>
                                                        <span>{{ $order->order_local }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-8 mb-3">
                                                <label for="floatingTextarea2" class="form-label">Lưu ý dành cho người bán</label>
                                                <textarea class="form-control" cols="5" rows="4" disabled placeholder="Nhập nội dung tại đây..." id="floatingTextarea2">{{ $order->note_customer == null ? 'Không có ghi chú' : $order->note_customer }}</textarea>
                                            </div>
                                            <div class="col-lg-4 mb-3 h-100">
                                                <label for="" class="form-label fw-bold">Đơn vị vận chuyển</label>
                                                <div class="bg-light p-3 h-100 rounded bd_hr d-flex flex-column">
                                                    <span class="text-dvvc fw-bold">Giao hàng nhanh</span>
                                                    <small>
                                                        @if ($order->order_expected_delivery)
                                                            Dự kiến nhận hàng: {{ \Carbon\Carbon::parse($order->order_expected_delivery)->format('d/m/Y') }}
                                                        @else
                                                            Đang cập nhật thời gian giao
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <a href="{{ route('user.order') }}"
                                                    class="btn-del__cart btn-add__pro">
                                                    <span class="button__text">Quay lại</span>
                                                    <span class="button__icon">
                                                        <i class='bx bx-left-arrow-alt fs-5'></i>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <h4 class="text-center fw-bold mt-3 mb-4 text-uppercase">Sản phẩm đã mua</h4>
                            @php
                                $giatri_donhang = 0;
                                $sosanpham = 0;
                            @endphp
                            @foreach ($orderDetail as $oD)
                                @php
                                    $pro = DB::table('products')
                                        ->where('pro_id', '=', $oD->pro_id)
                                        ->first();
                                @endphp
                                <div class="col-12">
                                    <div class="d-flex gap-3 mb-3">
                                        <img src="{{ asset($pro->pro_img) }}" onerror="this.src='/uploads/img_error.jpg'" class="img-payment__new rounded" alt="{{ $pro->pro_name }}">
                                        <div>
                                            <h6 class="text__truncate mb-1 fw-bold">{{ $oD->pro_name }}</h6>
                                            <div class="d-flex gap-3 flex-wrap">
                                                @if ($oD->size !== null)
                                                    <span class="fw-bold">Size: <span class="fw-normal">{{ $oD->size }}</span></span>
                                                @endif
                                                @if ($oD->color !== null)
                                                    <span class="fw-bold">Màu: <span class="fw-normal">{{ $oD->color }}</span></span>
                                                @endif
                                                <span class="fw-bold">SL: <span class="fw-normal">{{ $oD->quantity }}</span></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <h5 class="fw-bold mb-0 color-price__pay">{{ number_format($oD->price*$oD->quantity, 0, ',', '.') }} VNĐ</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $giatri_donhang += $oD->price*$oD->quantity;
                                    $sosanpham++;
                                @endphp
                            @endforeach
                            <div class="cart__bottom mt-4">
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase">Giá trị sản phẩm <small class="fw-normal">({{ $sosanpham }} sản phẩm):</small></h6>
                                    <label class="font__size">{{ number_format($giatri_donhang, 0, ',', '.') }} VNĐ</label>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase">Phí vận chuyển:</h6>
                                    <label class="font__size">{{ number_format($order->order_delivery_fee, 0, ',', '.') }} VNĐ</label>
                                </div>
        
                                @isset($coupon_data)
                                    <div class="mb-3 d-flex justify-content-between align-items-center gap-1">
                                        <label class="fw-bold text-black font__size">Mã giảm giá:
                                            @if ($coupon_data->coupon_condition == 0)
                                                {{ '(' . $coupon_data->coupon_value . '%)' }}
                                            @endif
                                        </label>
                                        <label class="font__size">
                                            @php
                                                if ($coupon_data->coupon_condition == 0) {
                                                    $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                                }
                                            @endphp
                                            {{ $coupon_data->coupon_condition == 1
                                                ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.') . '     VNĐ'
                                                : '- ' . number_format($discount, 0, ',', '.') . ' VNĐ' }}
                                        </label>
                                    </div>
                                @endisset
        
                                <hr class="mb-3">
                                <div class="mb-3 d-flex justify-content-between align-items-center gap-1">
                                    <h6 class="fw-bold text-uppercase mb-0">Thành tiền: </h6>
                                    <h4 class="fw-bold mb-0">
                                        {{ number_format($order->order_total, 0, ',', '.') }} VNĐ
                                    </h4>
                                </div>
                            </div>

                            @php
                                $thanhToan = app(\App\Services\Payment\OrderPayments::class);
                                $hanThanhToan = $thanhToan->deadlineFor($order);
                                $traLaiDuoc = $thanhToan->canPay($order);
                                $doiDuoc = $thanhToan->canChangeMethod($order);
                            @endphp
                            <div class="mb-3">
                                <h6 class="fw-bold text-uppercase mb-2">HÌNH THỨC THANH TOÁN</h6>
                                <div class="bg-light p-3 rounded" style="border: 1px dashed rgba(0, 0, 0, .09);">
                                    <span class="fw-bold">
                                        {{ $order->paymentLabel() }}
                                    </span>
                                    @if ($hanThanhToan)
                                        <small class="d-block mt-1 payment-due">
                                            @if ($traLaiDuoc)
                                                Chưa thanh toán · hạn {{ $hanThanhToan->format('H:i d/m/Y') }}
                                            @else
                                                Đã quá hạn thanh toán, đơn sẽ được huỷ
                                            @endif
                                        </small>
                                    @endif
                                </div>
                            </div>
                            {{--  --}}
                            <div class="row">
                                @if ($order->orderReturn)
                                    <div class="col-lg-12 mb-3">
                                        <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                            data-bs-toggle="modal" data-bs-target="#returnDetail">
                                            Lịch sử trả hàng ({{ $order->orderReturns->count() }})
                                        </button>
                                    </div>
                                @endif
                                @if (! $order->hasStatus(\App\Enums\OrderStatus::Cancelled) && ! $order->order_status->isComingBack())
                                    {{-- Confirming receipt is the customer's word, and it is only theirs to
                                        give once the parcel has left the shop. --}}
                                    @if ($order->order_status->isOnTheRoad())
                                        <div class="col-lg-12 mb-3">
                                            @if (! $order->isAwaitingReceipt())
                                                <button disabled
                                                    class="w-100 custom-btn bgc-o text-white bgc-o-disabled p-2 rounded btn-order">Đã nhận
                                                    hàng</button>
                                            @else
                                                <form action="{{ route('success.order') }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="order_code" value="{{ $order->order_code }}">
                                                    <button class="w-100 custom-btn bgc-o text-white p-2 rounded btn-order">Đã nhận
                                                        hàng</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($traLaiDuoc)
                                        <div class="col-lg-12 mb-3">
                                            <form action="{{ route('order.pay', $order->order_code) }}" method="post">
                                                @csrf
                                                <button type="submit" class="w-100 custom-btn bgc-o text-white p-2 rounded btn-order">Thanh toán ngay</button>
                                            </form>
                                        </div>
                                    @endif
                                    @if ($doiDuoc)
                                        <div class="col-lg-12 mb-3">
                                            <button type="button" class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                data-bs-toggle="modal" data-bs-target="#changePayment">Đổi phương thức thanh toán</button>
                                        </div>
                                    @endif
                                    @if ($order->hasStatus(\App\Enums\OrderStatus::New) || $order->canRequestCancel())
                                        <div class="col-lg-12 mb-3">
                                            <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                                data-bs-toggle="modal" data-bs-target="#cancelOrder">Hủy đơn / Hoàn
                                                tiền</button>
                                        </div>
                                    @endif
                                @endif
                                {{-- Outside the block above: an order that came partly back
                                     counts as "coming back", yet the rest of it can still be
                                     sent home. canRequestReturn() already knows when. --}}
                                @if ($order->canRequestReturn())
                                    <div class="col-lg-12 mb-3">
                                        <button class="w-100 border grey-hover border-1 p-2 custom-btn text-dark rounded btn-order"
                                            data-bs-toggle="modal" data-bs-target="#returnOrder">Yêu cầu trả hàng / Hoàn
                                            tiền</button>
                                    </div>
                                @endif
                            </div>
                            {{--  --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- return Order --}}
    @if ($order->canRequestReturn() || $errors->hasAny(['reason', 'items', 'description', 'images', 'images.*']))
    <div class="modal fade" id="returnOrder" tabindex="-1" aria-labelledby="returnOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('return.order', $order->order_code) }}" method="post" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="returnOrderLabel">Yêu cầu trả hàng / Hoàn tiền</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    @php
                        // Only what is still at the customer's house can be sent
                        // back: a line returned last week is not on offer again.
                        $conTraDuoc = $order->returnableQuantities();
                        $dongConLai = $orderDetail->filter(fn ($dong) => isset($conTraDuoc[$dong->order_details_id]));
                    @endphp
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Bạn có thể gửi yêu cầu đến hết
                            {{ optional($order->returnDeadline())->format('H:i d/m/Y') }}.
                            Sau khi cửa hàng duyệt, nhân viên GHN sẽ đến địa chỉ nhận hàng của đơn để lấy hàng trả.
                            <a href="{{ route('policy.return') }}" target="_blank" rel="noopener">Tìm hiểu thêm</a>
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Sản phẩm muốn trả <span class="text-danger">*</span></label>
                            <div class="border rounded p-2">
                                @foreach ($dongConLai as $dong)
                                    @php $conLai = $conTraDuoc[$dong->order_details_id]; @endphp
                                    <div class="d-flex justify-content-between align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                        <div>
                                            <div>{{ $dong->pro_name }}</div>
                                            <div class="text-muted small">
                                                Size {{ $dong->size }} &middot; {{ $dong->color }} &middot;
                                                đã mua {{ $dong->quantity }} &middot;
                                                {{ number_format((int) $dong->price, 0, ',', '.') }}đ / sản phẩm
                                            </div>
                                        </div>
                                        <div style="width: 150px;">
                                            <select class="form-select form-select-sm" name="items[{{ $dong->order_details_id }}]">
                                                @for ($sl = 0; $sl <= $conLai; $sl++)
                                                    <option value="{{ $sl }}" @selected((int) old('items.'.$dong->order_details_id, 0) === $sl)>
                                                        {{ $sl === 0 ? 'Không trả' : 'Trả '.$sl }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('items') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="return-reason" class="form-label">Lý do trả hàng <span class="text-danger">*</span></label>
                            <select class="form-select" id="return-reason" name="reason">
                                <option value="">Chọn lý do</option>
                                @foreach (\App\Models\OrderReturnModel::REASONS as $value => $label)
                                    <option value="{{ $value }}"
                                        data-fault="{{ in_array($value, \App\Models\OrderReturnModel::SHOP_AT_FAULT, true) ? 'shop' : 'khach' }}"
                                        @selected(old('reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reason') <small class="text-danger">{{ $message }}</small> @enderror
                            {{-- Who pays the carriage is decided by this field, so it is
                                 answered here rather than in the small print. --}}
                            <div class="return-hint mt-2" id="return-fault-shop" hidden>
                                Lỗi thuộc về cửa hàng nên <b>cửa hàng chịu phí gửi trả</b>. Bạn được hoàn đủ tiền hàng.
                            </div>
                            <div class="return-hint mt-2" id="return-fault-khach" hidden>
                                Lý do này thuộc về người mua nên <b>phí gửi trả sẽ được trừ vào tiền hoàn</b>.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="return-description" class="form-label">Mô tả thêm</label>
                            <textarea class="form-control" id="return-description" name="description" rows="3"
                                placeholder="Ví dụ: giày size 42 bị chật, muốn trả lại">{{ old('description') }}</textarea>
                            @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="return-images" class="form-label">Ảnh sản phẩm (tối đa {{ \App\Http\Requests\Frontend\ReturnOrderRequest::MAX_IMAGES }} ảnh)</label>
                            <input class="form-control" type="file" id="return-images" name="images[]" accept="image/*" multiple>
                            @error('images') <small class="text-danger">{{ $message }}</small> @enderror
                            @error('images.*') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="border grey-hover border-1 custom-btn text-dark" data-bs-dismiss="modal">Không trả hàng</button>
                        <button type="submit" class="custom-btn bgc-o text-white">Gửi yêu cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($errors->hasAny(['reason', 'items', 'description', 'images', 'images.*']))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('returnOrder')).show();
            });
        </script>
    @endif
    @endif
    @if ($order->orderReturn)
        @include('components.return_request', ['order' => $order->load('orderReturns.items.line')])
    @endif

    @if ($doiDuoc)
        @php
            try {
                $soDuVi = app(\App\Services\Wallet\WalletService::class)->for(auth()->user())->balance;
            } catch (\Throwable) {
                $soDuVi = null;
            }
            $duTienVi = $soDuVi !== null && $soDuVi >= (int) $order->order_total;
            $cachTra = [
                'cod' => ['Khi nhận hàng', '<img src="/frontend/img/delivery-bike.png" alt="" class="payment-icon">'],
                'redirect' => ['VNPay', '<img src="/frontend/img/VNPAY.png" alt="" class="payment-icon">'],
                'payUrl' => ['Momo', '<img src="/frontend/img/momo.png" alt="" class="payment-icon">'],
                'wallet' => ['SPay', "<i class='bx bx-wallet fs-3'></i>"],
            ];
        @endphp
        <div class="modal fade" id="changePayment" tabindex="-1" aria-labelledby="changePaymentLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('order.change_payment', $order->order_code) }}" method="post">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="changePaymentLabel">Đổi phương thức thanh toán</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Đơn {{ $order->order_code }} · {{ number_format((int) $order->order_total, 0, ',', '.') }}đ
                                @if ($hanThanhToan)
                                    · giữ hàng đến {{ $hanThanhToan->format('H:i d/m/Y') }}
                                @endif
                            </p>
                            <div class="row">
                                @foreach ($cachTra as $ma => [$ten, $bieuTuong])
                                    @php $hetTien = $ma === 'wallet' && ! $duTienVi; @endphp
                                    <div class="col-6 mb-3">
                                        <input type="radio" class="btn-check btn-tst" name="payment" value="{{ $ma }}"
                                            id="doi-{{ $ma }}" autocomplete="off" @checked($order->order_payment === $ma) @disabled($hetTien)>
                                        <label class="btn border p-2 d-flex justify-content-center gap-2 gap-md-3 align-items-center h-100 payment-option" for="doi-{{ $ma }}">
                                            <span>
                                                {{ $ten }}
                                                @if ($ma === 'wallet' && $soDuVi !== null)
                                                    <small class="d-block text-nowrap {{ $hetTien ? 'text-danger' : 'text-muted' }}">Số dư {{ number_format($soDuVi, 0, ',', '.') }}đ</small>
                                                @endif
                                            </span>
                                            {!! $bieuTuong !!}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="border grey-hover border-1 custom-btn text-dark" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="custom-btn bgc-o text-white">Xác nhận</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- cancel Order --}}
    <div class="modal fade" id="cancelOrder" tabindex="-1" aria-labelledby="cancelOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="cancelOrderLabel">Hủy đơn / Hoàn tiền</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="d-flex justify-content-center my-3">
                            <img src="/frontend/img/giphyno.gif" class="rounded-circle" alt="" width="150px" height="150px">
                        </div>
                        <div>
                            <div class="mb-3">
                                <label for="reasonCancelOrder" class="form-label">Hãy cho chúng tôi biết lí do bạn muốn hủy?</label>
                                <textarea class="form-control" placeholder="Nhập nội dung tại đây..." id="reasonCancelOrder" rows="4"
                                    onkeyup="updateNote()"></textarea>
                                <i id="errorSpan" class="error" style="color:red;display: none;">Vui lòng nhập lý do hủy
                                    đơn hàng.</i>
                            </div>
                            <div class="d-flex justify-content-center m-auto my-3">
                                <form action="{{ route('cancelOrder', $order->order_code) }}" method="post">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="inputCancelOrder" value="" id="inputCancelOrder">
                                    <button type="submit" class="custom-btn bgc-o text-white"
                                        onclick="validateForm(event)">Xác nhận hủy</button>
                                </form>
                                <button type="button" class="border grey-hover border-1 mx-2 custom-btn text-dark "
                                    data-bs-toggle="modal">Không hủy</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        /* A line the customer must read before they choose, not small print
           under the button: it decides who pays for the parcel coming back. */
        .return-hint {
            padding: 10px 12px;
            background-color: var(--light);
            border-left: 3px solid var(--color-orange);
            font-size: 13px;
            line-height: 1.55;
        }
    </style>
    <script>
        (function () {
            const reason = document.getElementById('return-reason');

            if (!reason) {
                return;
            }

            function showFault() {
                const chon = reason.options[reason.selectedIndex];
                const fault = chon ? chon.dataset.fault : '';

                document.getElementById('return-fault-shop').hidden = fault !== 'shop';
                document.getElementById('return-fault-khach').hidden = fault !== 'khach';
            }

            reason.addEventListener('change', showFault);
            showFault();
        })();

        function updateNote() {
            var noteValue = document.getElementById("reasonCancelOrder").value;
            document.getElementById("inputCancelOrder").value = noteValue;
        }

        function validateForm(event) {
            var noteValue = document.getElementById("reasonCancelOrder").value;
            var errorSpan = document.getElementById("errorSpan");

            if (noteValue.trim() === "") {
                errorSpan.style.display = "block";
                event.preventDefault(); // Ngăn chặn việc submit form
            } else {
                errorSpan.style.display = "none";
                // Form được submit tự động nếu đã nhập lý do
            }
        }
    </script>
    <div class="modal fade" id="HanhTrinhModal" tabindex="-1" aria-labelledby="HanhTrinhModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="HanhTrinhModalLabel">Hành trình đơn hàng</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @include('components.shipment_timeline', ['order' => $order])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection
