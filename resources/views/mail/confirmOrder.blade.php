<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        * {
            font-family: 'Optimistic Text', Helvetica, sans-serif;
        }

        .tdcss {
            padding: 26px 32px 0px 32px;
            word-break: break-word;
            text-align: left;
        }

        table.table {
            border-collapse: collapse;
            width: 100%;
            text-align: center;
        }

        table.table th,
        table.table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table.table th {
            background-color: #eee;
        }

        table.table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .text-start {
            text-align: start;
        }

        .text-end {
            text-align: end;
        }

        .text-danger {
            color: red;
        }

        .py-5 {
            padding-top: 3rem !important;
            padding-bottom: 3rem !important;
        }

        .text-success {
            --bs-text-opacity: 1;
            color: rgba(25, 135, 84);
        }

        .tbd,
        .th,
        .tf tr:last-child {
            border-bottom: 1px dashed #000 !important;
        }

        .notif {
            font-size: 13px;
            margin-top: 15px;
            line-height: 16.5px;
        }

        .text {
            font-size: 16px;
            letter-spacing: none;
            line-height: 1.8;
            text-align: left;
            color: #414141
        }

        .m-0 {
            margin: 0 0 !important;
        }
    </style>
</head>

<body>
    <div align="center" style="background-color:#f5f5f5;width:100%">
        <div class="py-5" style="background:#ffffff;background-color:#ffffff;Margin:0px auto;max-width:800px ">
            <table align="center" style="background-color:#ffffff;width:100%">
                <tr>
                    <td style="padding:20px 32px 0px 32px;">
                        <div style="margin:0 auto">
                            <a href="{{route('home.page')}}" target="_blank" data-saferedirecturl="">
                                <img height="auto" width="100" src="{{asset('frontend/img/images-email/image-1699430541307.jpg')}}"
                                    style="border:none;outline:none;text-decoration:none;height:auto;width:10%;font-size:13px;display:block">
                            </a>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="tdcss">
                        <div class="text">

                            <p class="m-0">Chào {{ $lastName }},</p>
                            <p class="m-0">&nbsp;</p>
                            <p class="m-0">Lời đầu tiên xin được thay mặt
                                toàn bộ đội ngũ nhân viên gửi lời cảm ơn chân
                                thành và sâu sắc nhất tới Quý khách hàng đã đồng
                                hành, hợp tác cũng như ủng hộ cửa hàng. Sneaker
                                Square xin gửi bạn thông tin đơn hàng sau đây:
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="tdcss">
                        <div class="text">
                            <b>Mã đơn hàng:</b> <span>{{ $order_code }}</span> <br>
                            <b>Người nhận hàng:</b> <span>{{ $order_name }}</span><br>
                            <b>Email:</b> <span>{{ $order_email }}</span><br>
                            <b>Số điện thoại:</b> <span>{{ $order_phone }}</span><br>
                            <b>Địa chỉ giao hàng:</b> <span>{{ $order_address }}, {{ $order_local }}</span><br>
                            <b>Phương thức thanh toán:</b> <span>
                                @if ($order_payment == 'cod')
                                    Thanh toán khi nhận hàng
                                @else
                                    @if ($order_payment == 'payUrl')
                                        Thanh toán qua MoMo
                                    @else
                                        Thanh toán qua VNPay
                                    @endif
                                @endif
                                {{-- {{ $order_payment }} --}}
                            </span>
                            @if ($order_payment_status == 1)
                                <i class="text-success">(Đã thanh toán)</i><br>
                            @else
                                <i class="text-danger">(Chưa thanh toán)</i><br>
                            @endif
                            <b>Ghi chú:</b> <span>{{ $note_customer }}</span><br>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="tdcss">
                        <table class="table text-center">
                            <thead class="th">
                                <tr>
                                    <th scope="col">STT</th>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Màu</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Đơn giá</th>
                                    <th scope="col">Số lượng</th>
                                    <th scope="col">Tổng tiền</th>
                                </tr>
                            </thead>
                            <tbody class="tbd">
                                @php
                                    $giatri_donhang = 0;
                                    $i = 1;
                                @endphp
                                @foreach ($od as $item)
                                    <tr>
                                        <td>{{ $i }}</td>
                                        <td class="text-start">{{ $item->pro_name }}</td>
                                        <td>{{ $item->color ?? '-' }}</td>
                                        <td>{{ $item->size ?? '-' }}</td>
                                        <td>{{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td>{{ $item->quantity ?? 'x' }}</td>
                                        <td class="text-end">
                                            {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                                    </tr>
                                    @php
                                        $giatri_donhang += $item->price;
                                        $i++;
                                    @endphp
                                @endforeach

                            </tbody>
                            <tfoot class="tf">
                                <tr>
                                    <td colspan="6" class="text-end"><i>Phí vận chuyển</i> </td>
                                    <td class="text-end">{{ number_format($order_delivery_fee, 0, ',', '.') }}</td>
                                </tr>
                                @if ($coupon_id !== null)
                                    @php
                                        $coupon_data = DB::table('coupon')
                                            ->where('coupon_id', '=', $coupon_id)
                                            ->first();
                                        if ($coupon_data->coupon_condition == 0) {
                                            $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                        }
                                    @endphp
                                    <tr>
                                        <td colspan="6" class="text-end"><i>Mã giảm giá</i> </td>
                                        <td class="text-end">
                                            {{ $coupon_data->coupon_condition == 1
                                                ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.')
                                                : '- ' . number_format($discount, 0, ',', '.') . ' (' . $coupon_data->coupon_value . '%)' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="6" class="text-end"><i>Thành tiền <i class="text-danger">(*)</i></i>
                                    </td>
                                    <td class="text-end">{{ number_format($order_total, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="tdcss">
                        <div class="text">
                            <p class="m-0">Hy vọng Quý khách sẽ hài lòng với sản phẩm. Rất mong tiếp
                                tục nhận được sự ủng hộ của bạn trong thời gian tới. Chúng tôi sẽ không
                                ngừng phát triển, nâng cao chất lượng dịch vụ để có thể phục vụ Quý khách
                                hàng tốt hơn. Nếu bạn cần sự hỗ trợ hãy liên hệ với chúng tôi <a
                                    href="{{route('contact.page')}}" target="_blank">tại đây</a>
                            </p>
                        </div>
                        <p class="m-0">&nbsp;</p>
                        <div class="text">
                            <p class="m-0">Trân trọng,
                            </p>
                            <p class="m-0" style="font-weight: bold;">Đội ngũ Sneaker Square
                            </p>
                        </div>
                    </td>

                </tr>
            </table>
        </div>
        <div class="notif ">
            --------------------------------------- <br>
            <b><u>Lưu ý:</u></b> <i class="text-danger">(*)</i></i> Là số tiền cần phải thanh toán <br>
            Đây là email tự động. Vui lòng không trả lời email này. <br>
            <a href="{{ env('APP_URL') }}/in-don-hang/{{$order_code}}" target="_blank">Bấm vào đây</a> để xuất hóa đơn. <br>
            Hotline: 0123456789 <br>
            <a href="{{ env('APP_URL') }}/" target="_blank">{{ env('APP_URL') }}/</a>
        </div>

    </div>
</body>

</html>