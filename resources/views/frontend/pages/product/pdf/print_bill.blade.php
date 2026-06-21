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
            font-size: 110%;
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

        #BTN-background {
            background-image: url('data:image/jpeg;base64,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') !important;
            background-position: !important;
            background-repeat: no-repeat !important;
            background-size: 350px 350px !important;
            position: fixed;
            position: absolute;
            top: -90%;
            left: 28%;
            opacity: .2;
        }

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
        .BTN-address{
            width:500px !important;
            word-wrap:break-word !important;
        }


        #BTN-body {}
    </style>
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
                                    <span class="BTN-label">
                                        Mã đơn hàng
                                        <span class="BTN-en">(Code)</span>:
                                    </span>
                                    <b>{{ $order->order_code }}</b> <br>
                                    <span class="BTN-label">
                                        Số
                                        <span class="BTN-en">(No)</span>:
                                    </span>
                                    <span class="BTN-text BTN-invoice-no">{{ $order->order_id }}</span>
                                </div>
                            </div>
                            <div class="BTN-heading">
                                <div class="BTN-title">
                                    HÓA ĐƠN <span id="BTN-name">Giá trị gia tăng</span>
                                    <div class="BTN-en">(VAT INVOICE)</div>
                                </div>
                                <div class="BTN-row">
                                    Bản thể hiện của hóa đơn điện tử
                                    <br>
                                    <i>(Electronic Invoice Display)</i>
                                </div>
                            </div>
                            <div class="BTN-info">
                                <center>

                                    <img src="{{ env('APP_URL') }}/frontend/img/logo_sn.png" alt="" style="width: 50%;">
                                </center>
                            </div>
                        </div>
                        <div class="BTN-ID-font-color BTN-seller">
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Đơn vị bán hàng
                                    <span class="BTN-en">(Seller)</span>:
                                </div>
                                <div id="BTN-ComName" class="BTN-text BTN-company">CÔNG TY TNHH MTV Sneaker Square Việt
                                    Nam</div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Địa chỉ
                                    <span class="BTN-en">(Address)</span>:
                                </div>
                                <div id="BTN-ComAddress" class="BTN-text">Quận 12, TP. Hồ Chí Minh</div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Điện thoại
                                    <span class="BTN-en">(Tel)</span>:
                                </div>
                                <div id="BTN-ComPhone" class="BTN-text BTN-phone">0917403833</div>
                                &nbsp;
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Số tài khoản
                                    <span class="BTN-en">(Account No)</span>:
                                </div>
                                <div class="BTN-text">
                                    <span id="BTN-ComBankNo">9704198526191432198</span>
                                    tại <span id="BTN-ComBankName">Ngân hàng Quốc Dân - CN Quận 12</span>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="BTN-ID-font-color BTN-buyer">
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Họ tên người mua hàng
                                    <span class="BTN-en">(Buyer)</span>:
                                </div>
                                <div class="BTN-text">
                                    &nbsp;
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Tên đơn vị
                                    <span class="BTN-en">(Company)</span>:
                                </div>
                                <div class="BTN-text">
                                    &nbsp;
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Mã số thuế
                                    <span class="BTN-en">(Tax code)</span>:
                                </div>
                                <div class="BTN-text">
                                    &nbsp;
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Địa chỉ
                                    <span class="BTN-en">(Address)</span>:
                                </div>
                                <div class="BTN-text">
                                    &nbsp;
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Hình thức thanh toán
                                    <span class="BTN-en">(Payment method)</span>:
                                </div>
                                <div class="BTN-text BTN-payment-method">
                                    &nbsp;
                                </div>
                                <div class="BTN-label">
                                    Số tài khoản
                                    <span class="BTN-en">(Account No)</span>:
                                </div>
                                <div class="BTN-text BTN-account-no">
                                    &nbsp;
                                </div>
                            </div>
                        </div> -->
                        <div class="BTN-ID-font-color BTN-buyer">
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Họ tên người mua hàng
                                    <span class="BTN-en">(Buyer)</span>:
                                </div>
                                <div class="BTN-text">
                                    {{ Auth::guard('web')->user()->name }}
                                </div>
                            </div>

                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Email
                                    <span class="BTN-en">(Gmail)</span>:
                                </div>
                                <div class="BTN-text BTN-payment-method">
                                    {{ Auth::guard('web')->user()->email }}
                                    &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp;
                                    &nbsp; &nbsp; &nbsp; &nbsp;
                                </div>
                                <div class="BTN-label">
                                    Số điện thoại
                                    <span class="BTN-en">(Tel)</span>:
                                </div>
                                <div class="BTN-text BTN-account-no">
                                    {{ $order->order_phone }}

                                </div>
                            </div>

                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Địa chỉ nhận hàng
                                    <span class="BTN-en">(Address)</span>:
                                </div>
                                <div class="BTN-text BTN-address">
                                    {{ $order->order_address }}, {{ $order->order_local }}
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Hình thức thanh toán
                                    <span class="BTN-en">(Payment method)</span>:
                                </div>
                                <div class="BTN-text BTN-payment-method">
                                    @if ($order->order_payment == 'cod')
                                        Thanh toán khi nhận hàng
                                    @else
                                        @if ($order->order_payment == 'payUrl')
                                            Thanh toán qua MoMo
                                        @else
                                            Thanh toán qua VNPay
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="BTN-row">
                                <div class="BTN-label">
                                    Ghi chú đơn hàng
                                    <span class="BTN-en">(Note)</span>:
                                </div>
                                <div class="BTN-text">
                                    {{$order->note_customer}}
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
                                                    <span class="BTN-en">(No)</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    Tên hàng hóa, dịch vụ
                                                    <span class="BTN-en">(Name of goods, services)</span>
                                                </div>
                                            </td>
                                            <td style="width: 110px;">
                                                <div>
                                                    Đơn vị tính
                                                    <span class="BTN-en">(Unit)</span>
                                                </div>
                                            </td>
                                            <td style="width: 110px;">
                                                <div>
                                                    Số lượng
                                                    <span class="BTN-en">(Quantity)</span>
                                                </div>
                                            </td>
                                            <td style="width: 110px;">
                                                <div>
                                                    Đơn giá
                                                    <span class="BTN-en">(Price)</span>
                                                </div>
                                            </td>
                                            <td style="width: 130px;">
                                                <div>
                                                    Thành tiền
                                                    <span class="BTN-en">(Amount)</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="BTN-title-index">
                                            <td>
                                                <div>1</div>
                                            </td>
                                            <td>
                                                <div>2</div>
                                            </td>
                                            <td>
                                                <div>3</div>
                                            </td>
                                            <td>
                                                <div>4</div>
                                            </td>
                                            <td>
                                                <div>5</div>
                                            </td>
                                            <td>
                                                <div>6=4x5</div>
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
                                                        (<i>{{ $item->color ?? '' }}</i>,
                                                        <i>{{ $item->size ?? '' }}</i>)
                                                    @endif
                                                </td>
                                                <td class="BTN-text-center">-</td>
                                                {{-- <td>{{ $item->size ?? '-' }}</td> --}}
                                                <td class="BTN-text-center">{{ $item->quantity ?? 'x' }}</td>
                                                <td class="BTN-text-center">
                                                    {{ number_format($item->price, 0, ',', '.') }}</td>
                                                <td class="BTN-text-right">
                                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                            @php
                                                $giatri_donhang += ($item->price * $item->quantity);
                                                $i++;
                                            @endphp
                                        @endforeach
                                        {{-- <tr >
                                            <td >
                                                <div />
                                            </td>
                                            <td>
                                                <div />
                                            </td>
                                            <td class="BTN-text-center">
                                                <div>
                                                    &nbsp;
                                                </div>
                                            </td>
                                            <td class="BTN-text-center">
                                                <div>
                                                    &nbsp;
                                                </div>
                                            </td>
                                            <td >
                                                <div>
                                                    &nbsp;
                                                </div>
                                            </td>
                                            <td class="BTN-text-right">
                                                <div>
                                                    &nbsp;
                                                </div>
                                            </td>
                                        </tr> --}}
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
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
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
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
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
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
                                            <td />
                                            <td />
                                        </tr>
                                        <tr class="BTN-line BTN-empty">
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                            <td />
                                        </tr>
                                        

                                        <tr id="BTN-amount" class="BTN-line BTN-amount">
                                            <td colspan="5" class="BTN-label" id="VTU-TienSauThue">
                                                <div>
                                                    Cộng tiền hàng hóa, dịch vụ
                                                    <span class="BTN-en">(Total amount)</span>:
                                                </div>
                                            </td>
                                            <td class="BTN-text">
                                                <div>
                                                    {{ number_format($giatri_donhang, 0, ',', '.') }}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="BTN-line BTN-total">
                                            <td colspan="2" class="BTN-label" style="border-right:none;">
                                                &nbsp;
                                            </td>
                                            <td colspan="3" class="BTN-label" id="VTU-TienTruocThue-Label">
                                                <div>
                                                    Phí vận chuyển
                                                    <span class="BTN-en">(Transport fee)</span>:
                                                </div>
                                            </td>
                                            <td class="BTN-text" id="VTU-TienTruocThue-Text">
                                                <div>
                                                    {{ number_format($order->order_delivery_fee, 0, ',', '.') }}
                                                </div>
                                            </td>
                                        </tr>
                                        @if ($order->coupon_id !== null)
                                            @php
                                                $coupon_data = DB::table('coupon')
                                                    ->where('coupon_id', '=', $order->coupon_id)
                                                    ->first();
                                                if ($coupon_data->coupon_condition == 0) {
                                                    $discount = $giatri_donhang * ($coupon_data->coupon_value / 100);
                                                }
                                            @endphp
                                            <tr class="BTN-line BTN-total">
                                                <td colspan="2" class="BTN-label" style="border-right:none;">
                                                    &nbsp;
                                                </td>
                                                <td colspan="3" class="BTN-label" id="VTU-TienTruocThue-Label">
                                                    <div>
                                                        Giảm giá
                                                        <span class="BTN-en">(Discount)</span>:
                                                    </div>
                                                </td>
                                                <td class="BTN-text" id="VTU-TienTruocThue-Text">
                                                    <div>
                                                        {{ $coupon_data->coupon_condition == 1
                                                            ? '- ' . number_format($coupon_data->coupon_value, 0, ',', '.')
                                                            : '- ' . number_format($discount, 0, ',', '.') . ' (' . $coupon_data->coupon_value . '%)' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        

                                        <tr id="BTN-amount" class="BTN-line BTN-amount">
                                            <td colspan="5" class="BTN-label" id="VTU-TienSauThue">
                                                <div>
                                                    Tổng cộng tiền thanh toán
                                                    <span class="BTN-en">(Total payment)</span>:
                                                </div>
                                            </td>
                                            <td class="BTN-text">
                                                <div>
                                                    {{ number_format($order->order_total, 0, ',', '.') }}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="BTN-line BTN-amount-word">
                                            <td colspan="6">
                                                <div>
                                                    <span class="BTN-label">
                                                        Số tiền viết bằng chữ
                                                        <span class="BTN-en">(Amount in words)</span>:
                                                    </span>
                                                    <span class="BTN-text" />
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
                                        Người mua hàng
                                        <span class="BTN-en">(Buyer)</span>
                                    </div>
                                    <div class="BTN-description">
                                        (Ký, ghi rõ họ tên)
                                        <span class="BTN-en">(Sign, fullname)</span>
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
                                        Người bán hàng
                                        <span class="BTN-en">(Seller)</span>
                                    </div>
                                    <div class="BTN-description">
                                        (Ký, ghi rõ họ tên)
                                        <span class="BTN-en">(Sign, fullname)</span>
                                    </div>
                                    <div class="BTN-text">
                                        <div style="background:url(data:image/png;base64,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) no-repeat center left;"
                                            onclick="showDialog('dialogServer','','','',0,'messSer','is')">
                                            <p style="margin: 2px; font-size: 13px;">Signature Valid</p>
                                            <p style="margin: 2px; font-size: 13px;">
                                                Ký bởi:
                                                <span id="BTN-sign-seller">CÔNG TY TNHH MTV SNEAKER SQUARE VIỆT
                                                    NAM</span>
                                                <span id="BTN-sign-date-seller">
                                                    <br>
                                                    Ký ngày: {{date('d / m / Y', strtotime($order->order_date))}}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="BTN-note-receive-einvoice">(Cần kiểm tra, đối chiếu khi lập, giao nhận hóa đơn)
                                <br>
                                <sub>
                                    Giá sản phẩm đã bao gồm 8% VAT
                                </sub>
                            </div>
                            <div id="dialogServer" style="background-color:white;display:none">
                                <div style="color:blue" id="messSer">Unknown!</div>
                                <br>
                                <br>
                                <a href="#" onclick="displayCert('')">
                                    <div style="color:#184D4E">Xem thông tin chứng thư</div>
                                </a>
                            </div>
                            <div id="dialogClient" style="background-color:white;display:none">
                                <div style="color:blue" id="messClt">Unknown!</div>
                                <br>
                                <br>
                                <a href="#" onclick="displayCert('')">
                                    <div style="color:#184D4E">Xem thông tin chứng thư</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>