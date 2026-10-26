<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            font-family: Helvetica, Arial, sans-serif;
        }

        .text {
            font-size: 16px;
            line-height: 1.7;
            color: #414141;
        }

        .muted {
            font-size: 13px;
            line-height: 1.6;
            color: #888888;
        }
    </style>
</head>

<body style="margin:0;padding:0">
    <div align="center" style="background-color:#f5f5f5;width:100%;padding:24px 0">
        <table align="center" cellpadding="0" cellspacing="0"
            style="background-color:#ffffff;width:100%;max-width:640px;border-collapse:collapse">
            <tr>
                <td style="padding:24px 32px 0 32px">
                    <a href="{{ route('home.page') }}" target="_blank">
                        <img width="80" src="{{ asset('frontend/img/images-email/image-1699430541307.jpg') }}"
                            alt="Sneaker Square" style="border:none;display:block;width:80px;height:auto">
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:24px 32px 8px 32px" class="text">
                    <p style="margin:0 0 12px 0">Chào {{ $name ?: 'bạn' }},</p>
                    <p style="margin:0">
                        Bạn đã để lại {{ count($lines) }} sản phẩm trong giỏ hàng tại Sneaker Square.
                        Hàng vẫn còn, và giỏ của bạn đã được giữ nguyên để bạn đặt tiếp khi sẵn sàng.
                    </p>
                </td>
            </tr>
            <tr>
                <td style="padding:16px 32px">
                    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">
                        @foreach ($lines as $line)
                            <tr>
                                <td style="padding:12px 0;border-top:1px solid #eeeeee;width:88px;vertical-align:top">
                                    <img width="72" src="{{ asset($line['product']->pro_img) }}" alt="{{ $line['product']->pro_name }}"
                                        style="display:block;width:72px;height:72px;object-fit:cover;border:1px solid #eeeeee">
                                </td>
                                <td style="padding:12px 0;border-top:1px solid #eeeeee;vertical-align:top" class="text">
                                    <div style="font-weight:bold">{{ $line['product']->pro_name }}</div>
                                    <div class="muted">
                                        @if ($line['color']) Màu {{ $line['color'] }} @endif
                                        @if ($line['size']) · Size {{ $line['size'] }} @endif
                                        · Số lượng {{ $line['quantity'] }}
                                    </div>
                                </td>
                                <td style="padding:12px 0;border-top:1px solid #eeeeee;vertical-align:top;text-align:right;white-space:nowrap" class="text">
                                    {{ number_format($line['price'] * $line['quantity'], 0, ',', '.') }} đ
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" style="padding:12px 0;border-top:1px solid #dddddd;font-weight:bold" class="text">Tạm tính</td>
                            <td style="padding:12px 0;border-top:1px solid #dddddd;text-align:right;font-weight:bold" class="text">
                                {{ number_format($total, 0, ',', '.') }} đ
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @if ($coupon)
                <tr>
                    <td style="padding:0 32px 8px 32px">
                        <div style="border:1px dashed #f57c00;padding:16px;text-align:center" class="text">
                            Tặng riêng bạn mã giảm <b>{{ $coupon->coupon_value }}%</b>:
                            <span style="font-size:20px;font-weight:bold;letter-spacing:2px;color:#f57c00">{{ $coupon->coupon_code }}</span>
                            @if ($terms = app(\App\Services\CartPricingService::class)->couponTerms($coupon))
                                <div class="muted">Áp dụng cho {{ $terms }}, tính trên tiền hàng.</div>
                            @endif
                            <div class="muted">Dùng được một lần, hết hạn ngày {{ \Illuminate\Support\Carbon::parse($coupon->coupon_end)->format('d/m/Y') }}.
                                Mã được áp sẵn khi bạn bấm nút bên dưới.</div>
                        </div>
                    </td>
                </tr>
            @endif
            <tr>
                <td align="center" style="padding:16px 32px 32px 32px">
                    <a href="{{ $restoreUrl }}" target="_blank"
                        style="display:inline-block;background:#f57c00;color:#ffffff;text-decoration:none;font-weight:bold;padding:14px 32px;border-radius:4px">
                        Quay lại giỏ hàng
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:16px 32px 24px 32px;border-top:1px solid #eeeeee" class="muted">
                    Bạn nhận email này vì đã thêm sản phẩm vào giỏ hàng khi đăng nhập tài khoản Sneaker Square.
                    Mỗi giỏ hàng chỉ được nhắc tự động tối đa hai lần.
                    <a href="{{ $unsubscribeUrl }}" style="color:#888888">Không nhận email nhắc giỏ hàng nữa</a>.
                </td>
            </tr>
        </table>
    </div>
    <img src="{{ $pixelUrl }}" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0">
</body>

</html>
