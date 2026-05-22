<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Đơn hàng PDF</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            padding: 0;
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica;
        }

        @font-face {
            font-family: "source_sans_proregular";
            src: local("Source Sans Pro"), url("fonts/sourcesans/sourcesanspro-regular-webfont.ttf") format("truetype");
            font-weight: normal;
            font-style: normal;

        }

        body {
            margin-top: 15px;
            font-family: "source_sans_proregular", Calibri, Candara, Segoe, Segoe UI, Optima, Arial, sans-serif;
        }

        /* * {
            box-sizing: border-box;
        } */

        .BTN-VATTEMP {
            width: 780px;
            height: auto;
            margin: 0 auto;
            padding: 0;
            font-size: 12pt;
            color: #000;
            background: #fff;
            line-height: 1;
        }

        .BTN-VATTEMP .BTN-brand {
            font-size: 12px;
            font-style: italic;
            text-align: center;
        }

        .BTN-pl {
            padding-left: 5px;
        }

        .BTN-VATTEMP .BTN-VATBound {
            background-position: 0 0;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            border: 2px solid #333;
        }

        .BTN-VATTEMP .BTN-has-pagination {
            position: relative;
            padding-bottom: 15px;
        }

        .BTN-VATTEMP .BTN-pagination {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            z-index: 1;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-pagination label {
            padding-right: 4px;
        }

        .BTN-VATTEMP .BTN-header {
            padding: 10px;
        }

        .BTN-VATTEMP .BTN-header::after {
            display: table;
            clear: both;
            content: "";
        }

        .BTN-VATTEMP .BTN-header .BTN-logo {
            float: left;
            width: 26%;
            min-height: 80px;
            background-position: center;
            background-repeat: no-repeat;
        }

        .BTN-VATTEMP .BTN-header .BTN-heading {
            float: left;
            width: 48%;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-header .BTN-heading .BTN-title {
            font-size: 140%;
            font-weight: bold;
            text-transform: uppercase;
        }
        .BTN-price{
            font-size: 200%;
            font-weight: bold;
            text-transform: uppercase;
        }
        .BTN-VATTEMP .BTN-header .BTN-heading .BTN-title .BTN-en {
            font-size: 95%;
            font-weight: bold;
            text-transform: uppercase;
        }

        .BTN-VATTEMP .BTN-header .BTN-heading .BTN-title .BTN-en {
            display: block;
            margin-top: 3px;
        }



        .BTN-VATTEMP .BTN-header .BTN-info {
            float: right;
            width: 26%;
        }

        .BTN-VATTEMP .BTN-header .BTN-info .BTN-row {
            margin-top: 5px;
        }

        .BTN-VATTEMP .BTN-header .BTN-info .BTN-row::after {
            display: table;
            clear: both;
            content: "";
        }

        .BTN-VATTEMP .BTN-header .BTN-info .BTN-row:last-child {
            margin-top: 2px;
        }

        .BTN-VATTEMP .BTN-header .BTN-info .BTN-row:first-child {
            margin-top: 0;
        }

        .BTN-VATTEMP .BTN-header .BTN-info .BTN-row .BTN-text {
            font-weight: bold;
        }

        .BTN-invoice-no {
            font-size: 130%;
            color: #ff0000;
            font-weight: bold;
        }
        .BTN-des{
            font-size: 80%;
        }
        .BTN-VATTEMP .BTN-header .BTN-heading .BTN-title .BTN-note {
            margin-top: 3px;
            font-size: 10pt;
            font-weight: bold;
            font-style: italic;
            text-transform: none;
        }

        .BTN-VATTEMP .BTN-seller {
            padding: 10px;
            border-top: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row,
        .BTN-VATTEMP .BTN-buyer .BTN-row {
            margin-top: 5px;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row::after,
        .BTN-VATTEMP .BTN-buyer .BTN-row::after {
            display: table;
            clear: both;
            content: "";
        }

        .BTN-VATTEMP .BTN-seller .BTN-row:first-child,
        .BTN-VATTEMP .BTN-buyer .BTN-row:first-child {
            margin-top: 0;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-label,
        .BTN-VATTEMP .BTN-buyer .BTN-row .BTN-label {
            float: left;
            padding-right: 4px;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text,
        .BTN-VATTEMP .BTN-buyer .BTN-row .BTN-text {
            float: left;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text.BTN-company {
            font-size: 100%;
            font-weight: bold;
            text-transform: uppercase;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text.BTN-tax-code {
            font-size: 110%;
            font-weight: bold;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text.BTN-phone {
            min-width: 300px;
            min-height: 1px;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text.BTN-email {
            min-width: 300px;
            min-height: 1px;
        }

        .BTN-VATTEMP .BTN-seller .BTN-row .BTN-text.BTN-website {
            min-width: 300px;
            min-height: 1px;
        }

        .BTN-VATTEMP .BTN-buyer {
            padding: 10px;
            border-top: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-buyer .BTN-row .BTN-text.BTN-payment-method {
            min-width: 200px;
            min-height: 1px;
        }
        .BTN-infos{
            font-weight: bold;
        }
        .BTN-VATTEMP .BTN-statistics {
            padding: 0 10px 10px 10px;
            position: relative;
        }

        .BTN-VATTEMP .BTN-statistics table {
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .BTN-VATTEMP .BTN-statistics table tr td {
            height: 25px;
            font-size: 100%;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-empty td {
            height: 20px;
            font-size: 11pt;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-statistics table tr td:first-child {
            border-left: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-statistics table tr:first-child td {
            border-top: 1px solid #000;
        }

        .BTN-VATTEMP .BTN-statistics table tr td div {
            padding: 0 3px;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-title td {
            font-weight: bold;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-title td div {
            padding: 3px;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-title td .BTN-en {
            display: block;
            font-weight: normal;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-title-index td {
            font-style: italic;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-line td.BTN-text-left {
            text-align: left;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-line td.BTN-text-center {
            text-align: center;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-line td.BTN-text-right {
            text-align: right;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-no-line-top td {
            border-top: 0;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-no-line-bottom td {
            border-bottom: 0;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-no-line-left td {
            border-left: 0;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-no-line-right td {
            border-right: 0;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-total .BTN-text {
            text-align: right;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-vat .BTN-text {
            text-align: right;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-amount .BTN-text {
            text-align: right;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-amount-word td {
            height: 52px;
            padding: 3px 0;
            vertical-align: top;
        }

        .BTN-VATTEMP .BTN-statistics table tr.BTN-amount-word td .BTN-text {
            font-style: italic;
            line-height: 1;
        }

        .BTN-VATTEMP .BTN-statistics-background {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1;
            opacity: 0.2;
            background-position: center;
            background-repeat: no-repeat;
        }

        .BTN-VATTEMP .BTN-footer {
            padding: 0 10px 10px 10px;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature {
            margin: 0;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature::after {
            display: table;
            clear: both;
            content: "";
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer {
            float: right;
            width: 29%;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-title,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-title {
            font-weight: bold;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-description,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-description {
            font-style: italic;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-description .BTN-en,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-description .BTN-en {
            display: block;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-text,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-text {
            margin-top: 10px;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-text div,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-text div {
            margin: 0 auto;
            padding: 2px;
            color: #ff0000;
            text-align: center;
            border: 1px solid #ff0000;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-seller .BTN-text div p,
        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer .BTN-text div p {
            color: #ff0000;
        }

        .BTN-VATTEMP .BTN-footer .BTN-signature .BTN-signature-buyer {
            float: left;
        }

        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container {
            float: left;
            width: 42%;
            min-height: 1px;
            text-align: center;
            padding: 0px 10px 0px 10px;
        }

        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container .BTN-title {
            font-weight: bold;
        }

        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container .BTN-description {
            font-style: italic;
        }
        .BTN-description{
            font-size: 80%;

        }
        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container .BTN-text {
            margin-top: 10px;
        }

        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container .BTN-text div {
            margin: 0 auto;
            padding: 2px;
            color: #ff0000;
            text-align: center;
            border: 1px solid #ff0000;
            width: 300px;
            word-break: break-all;
        }

        .BTN-VATTEMP .BTN-footer .BTN-has-note-einvoice .BTN-note-einvoice-container .BTN-text div p {
            color: #ff0000;
        }

        .BTN-VATTEMP .BTN-footer .BTN-note-einvoice {
            margin-top: 10px;
            font-size: 14px;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-footer .BTN-note-einvoice span {
            display: block;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 95%;
        }

        .BTN-VATTEMP .BTN-footer .BTN-note-einvoice p {
            margin: 0;
        }

        .BTN-VATTEMP .BTN-footer .BTN-note-einvoice p:last-child {
            margin-top: 60px;
        }

        .BTN-VATTEMP .BTN-look-up {
            margin-top: 10px;
        }

        .BTN-VATTEMP .BTN-look-up p {
            margin: 0;
            font-size: 92%;
            font-style: italic;
        }

        .BTN-VATTEMP .BTN-look-up p:first-child {
            margin-top: 0;
        }

        .BTN-VATTEMP .BTN-look-up .BTN-label {
            padding-right: 3px;
        }

        .BTN-VATTEMP .BTN-look-up .BTN-text {
            font-weight: bold;
        }

        .BTN-VATTEMP .BTN-note-receive-einvoice {
            margin-top: 10px;
            font-size: 80%;
            font-style: italic;
            text-align: center;
        }

        .BTN-VATTEMP .BTN-footer .BTN-replace-info,
        .BTN-VATTEMP .BTN-footer .BTN-replace-adjust-TT78-info,
        .BTN-VATTEMP .BTN-footer .BTN-adjust-info {
            margin-top: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .BTN-en {
            font-style: italic;
        }

        .BTN-BUTTON-TOOL {
            margin-top: 5px;
            text-align: center;
        }

        @media print {
            .BTN-VATTEMP:not(:first-child) .BTN-VATBound {
                page-break-before: always;
            }
        }

        #VTU-TienTruocThue-Label {
            text-align: right;
        }

        #VTU-ThueSuat {
            border-right: none;
        }

        #VTU-TienThue {
            text-align: right;
        }

        #VTU-TienSauThue {
            text-align: right;
        }
        .pro-name{
            max-width: 570px;
            white-space: normal;
            overflow: hidden;
        }
                       

        /* #BTN-logo {
            background-image: url('') !important;
            //width:  248px !important;
            margin-top: px !important;
            margin-bottom: px !important;
            margin-left: px !important;
            margin-right: px !important;
            background-position: center !important;
            background-repeat: no-repeat !important;

            background-size: 150px 150px !important;
            height: 150px;
        } */

        #BTN-border {

            background-position: 0 0 !important;
            background-repeat: no-repeat !important;
            background-size: 100% 100% !important;

            border-width: px !important;
        }

        #BTN-body {
            color: !important;
        }

        #BTN-body .BTN-ID-font-color {
            border-color: !important;
        }

        #BTN-body table td {
            color: !important;
            border-color: !important;
        }


        #BTN-body {}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script> 
</head>

<body>

    <div id="printView" style="position: relative;">
        <div id="BTN-body" class="BTN-VATTEMP VATTEMP" style="display: block;">
            <div id="quantitypages">
                <div class="pagecurrent" id="page_1">
                    <div id="BTN-border" class="BTN-VATBound VATBound">
                        <div class="BTN-header">
                            <div id="BTN-logo" class="BTN-logo">
                                <div class="BTN-row">
                                    {{-- <span class="BTN-label">
                                        Mã đơn hàng:
                                    </span>
                                    <b>{{ $order->order_code }}</b> <br> --}}
                                    {{-- <span class="BTN-label">
                                        Số:
                                    </span>
                                    <span class="BTN-text BTN-invoice-no">{{ $order->order_id }}</span> --}}
                                </div>
                            </div>
                            {{-- <img src="{{asset('/uploads/img_error.jpg')}}" alt=""> --}}
                            <div class="BTN-heading">
                                <div class="BTN-title">
                                    HÓA ĐƠN <span id="BTN-name"></span>Giao hàng
                                </div>
                                
                            </div>
                            {{-- <div class="BTN-info">
                                <div class="BTN-row">
                                    <span class="BTN-label">
                                        Mã đơn hàng:
                                    </span>
                                  <b>{{ $order->order_code }}</b> <br>
                                </div>
                            </div> --}}
                            <div class="BTN-info">
                                <div class="BTN-row">
                                    <span class="BTN-label">
                                        Mã đơn hàng:
                                    </span>
                                    <b id="orderCode">{{ $order->order_code }}</b> <br>
                                </div>
                               
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6"></div>
                        </div>
                        <div class="BTN-ID-font-color BTN-seller">
                            <p class="BTN-infos">Thông tin người gửi</p>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Đơn vị bán hàng:
                                </div>
                                <div id="BTN-ComName" class="BTN-text BTN-company">Sneaker Square </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Địa chỉ:
                                </div>
                                <div id="BTN-ComAddress" class="BTN-text">99 Nguyễn Du, Bến Thành, Quận 1, TP.Hồ Chí Minh</div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Điện thoại:
                                </div>
                                <div id="BTN-ComPhone" class="BTN-text BTN-phone">0369 469 525</div>
                                &nbsp;
                            </div>
                        </div>
                        
                        <div class="BTN-ID-font-color BTN-buyer">
                            <p class="BTN-infos">Thông tin người nhận</p>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Họ tên: 
                                </div>
                                <div class="BTN-text">
                                    {{ $order->order_name }}
                                </div>
                            </div>

                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Số điện thoại: 
                                </div>
                                <div class="BTN-text BTN-account-no">
                                    {{ $order->order_phone }}

                                </div>
                            </div>

                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Địa chỉ nhận hàng: 
                                </div>
                                <div class="BTN-text pro-name">
                                    {{ $order->order_address }}, {{$order->order_local}}
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Ngày đặt hàng:
                                </div>
                                <div class="BTN-text">
                                    {{ date('d-m-Y', strtotime($order->order_date))}}
                                </div>
                            </div>
                        </div>
                        <div class="BTN-statistics statistics">
                            <div>
                                <table cellpadding="0" cellspacing="0">
                                    <tbody>
                                        <tr class="BTN-title">
                                            <td style="width: 50px;">
                                                <div>
                                                    STT
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    Tên hàng hóa, dịch vụ
                                                </div>
                                            </td>
                                            <td style="width: 110px;">
                                                <div>
                                                    Đơn vị tính
                                                </div>
                                            </td>
                                            <td style="width: 110px;">
                                                <div>
                                                    Số lượng
                                                </div>
                                            </td>
                                        </tr>
                                        @php
                                            $giatri_donhang = 0;
                                            $i = 1;
                                        @endphp
                                        @foreach ($od as $item)
                                            <tr class="BTN-line">
                                                <td class="BTN-text-center">{{ $i }}</td>
                                                <td class="BTN-pl">
                                                    {{ $item->pro_name }}&nbsp;
                                                    @if (isset($item->color) && isset($item->size))
                                                        (<i>{{ $item->color ?? '' }}</i>
                                                        <i>, Size: {{ $item->size ?? '' }}</i>)
                                                    @endif
                                                </td>
                                                <td class="BTN-text-center">-</td>
                                                <td class="BTN-text-center">{{ $item->quantity ?? 'x' }}</td>
                                            </tr>
                                            @php
                                                $giatri_donhang += $item->price*$item->quantity;
                                                $i++;
                                            @endphp
                                        @endforeach
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-amount-word">
                                            <td colspan="4">
                                                <div>
                                                    <i class="BTN-des">
                                                        Kiểm tra tên sản phẩm và đối chiếu Mã đơn hàng trên website Sneaker Square trước khi nhận hàng. (Lưu ý: Một số sản phẩm có thể bị ẩn do danh sách quá dài.)
                                                    </i>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div id="BTN-background" class="BTN-statistics-background" />
                            </div>
                        </div>
                        <div class="BTN-footer">
                            <div class="BTN-signature BTN-has-note-einvoice">
                                <div class="BTN-signature-buyer">
                                    <div class="BTN-title">
                                        Tiền thu người nhận
                                    </div>
                                    
                                    <div class="BTN-price">
                                        @if ($order->order_payment == 'cod')
                                            {{ number_format($order->order_total, 0, ',', '.') }} VNĐ
                                        @else
                                            @if ($order->order_payment == 'payUrl')
                                                0 VNĐ
                                            @else
                                                0 VNĐ
                                            @endif
                                        @endif
                                    </div>
                                    
                                    <div class="BTN-text">
                                        &nbsp;
                                    </div>
                                </div>
                                <div class="BTN-note-einvoice-container">
                                    <div class="BTN-row">
                                        &nbsp;
                                    </div>
                                </div>
                                <div class="BTN-signature-seller">
                                    <div class="BTN-title">
                                        Chữ ký người nhận
                                    </div>
                                    <div class="BTN-description">
                                        (Xác nhận hàng nguyên vẹn, không móp méo, bể vỡ)
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="BTN-footer">
                            <div>
                                <span class="BTN-des">
                                    Hướng dẫn giao hàng: Được đồng kiểm hàng. Chuyển hoàn sau 3 lần phát: Lưu kho tối đa 5 ngày.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        JsBarcode("#barcode", "{{ $order->order_code }}", {
            format: "CODE128", // Loại mã vạch
            displayValue: false, // Ẩn giá trị mã đơn hàng
        });
    </script>
</body>
</html>