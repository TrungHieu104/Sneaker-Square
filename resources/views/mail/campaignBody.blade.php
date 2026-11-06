{{-- Inline styles only: this is both the email and the admin's preview of it. --}}
<div align="center" style="background-color:#f5f5f5;width:100%;padding:24px 0;font-family:Helvetica,Arial,sans-serif">
    <table align="center" cellpadding="0" cellspacing="0"
        style="background-color:#ffffff;width:100%;max-width:640px;border-collapse:collapse;text-align:left">
        <tr>
            <td style="padding:24px 32px 0 32px">
                <a href="{{ route('home.page') }}" target="_blank">
                    <img width="80" src="{{ asset('frontend/img/images-email/image-1699430541307.jpg') }}"
                        alt="Sneaker Square" style="border:none;display:block;width:80px;height:auto">
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:24px 32px 8px 32px;font-size:16px;line-height:1.7;color:#414141">
                <p style="margin:0 0 12px 0">Chào {{ $name ?: 'bạn' }},</p>
                <p style="margin:0;white-space:pre-line" data-preview="message">{{ $lead }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 32px">
                <div style="border:1px dashed #f57c00;padding:16px;text-align:center;font-size:16px;line-height:1.7;color:#414141">
                    Tặng riêng bạn mã giảm <b data-preview="percent">{{ $percent }}%</b>:
                    <span style="font-size:20px;font-weight:bold;letter-spacing:2px;color:#f57c00">{{ $code }}</span>
                    <div style="font-size:13px;line-height:1.6;color:#888888" data-preview="terms">
                        @php
                            $dieuKien = array_filter([
                                $minOrder ? 'Đơn từ '.number_format($minOrder, 0, ',', '.').' VNĐ' : null,
                                $maxDiscount ? ($minOrder ? 'giảm' : 'Giảm').' tối đa '.number_format($maxDiscount, 0, ',', '.').' VNĐ' : null,
                            ]);
                        @endphp
                        {{ $dieuKien ? implode(', ', $dieuKien).', tính trên tiền hàng.' : 'Tính trên tiền hàng.' }}
                    </div>
                    <div style="font-size:13px;line-height:1.6;color:#888888">
                        Dùng được một lần, hết hạn ngày <span data-preview="expires">{{ $expires?->format('d/m/Y') }}</span>.
                        <span data-preview="how">{{ $minOrder ? 'Nhập mã ở giỏ hàng khi đơn đủ điều kiện.' : 'Mã được áp sẵn khi bạn bấm nút bên dưới.' }}</span>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:16px 32px 32px 32px">
                <a href="{{ $visitUrl }}" target="_blank"
                    style="display:inline-block;background:#f57c00;color:#ffffff;text-decoration:none;font-weight:bold;padding:14px 32px;border-radius:4px">
                    Xem sản phẩm
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 32px 24px 32px;border-top:1px solid #eeeeee;font-size:13px;line-height:1.6;color:#888888">
                Bạn nhận email này vì đã mua hàng tại Sneaker Square.
                <a href="{{ $unsubscribeUrl }}" style="color:#888888">Không nhận email ưu đãi nữa</a>.
            </td>
        </tr>
    </table>
</div>
