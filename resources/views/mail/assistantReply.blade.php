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
                    <a href="{{ $shopUrl }}" target="_blank">
                        <img width="80" src="{{ asset('frontend/img/images-email/image-1699430541307.jpg') }}"
                            alt="Sneaker Square" style="border:none;display:block;width:80px;height:auto">
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:24px 32px 8px 32px" class="text">
                    <p style="margin:0 0 12px 0">Chào {{ $name ?: 'bạn' }},</p>
                    <p style="margin:0">Nhân viên Sneaker Square đã trả lời câu hỏi bạn gửi trong khung chat.</p>
                </td>
            </tr>
            @if ($question)
                <tr>
                    <td style="padding:8px 32px" class="text">
                        <div class="muted">Bạn hỏi</div>
                        <div style="border-left:3px solid #dddddd;padding:4px 12px">{{ $question }}</div>
                    </td>
                </tr>
            @endif
            <tr>
                <td style="padding:8px 32px 16px 32px" class="text">
                    <div class="muted">Nhân viên trả lời</div>
                    <div style="border-left:3px solid #3DB2FF;padding:4px 12px;white-space:pre-line">{{ $answer }}</div>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:8px 32px 32px 32px">
                    <a href="{{ $shopUrl }}" target="_blank"
                        style="display:inline-block;background:#3DB2FF;color:#ffffff;text-decoration:none;font-weight:bold;padding:14px 32px;border-radius:4px">
                        Mở khung chat để hỏi tiếp
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:16px 32px 24px 32px;border-top:1px solid #eeeeee" class="muted">
                    Bạn nhận email này vì đã chuyển câu hỏi cho nhân viên khi đăng nhập tài khoản Sneaker Square
                    và đã rời trang trước khi có câu trả lời.
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
