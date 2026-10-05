# Danh mục nợ kỹ thuật

Cập nhật: 05/10/2026.

## Số đo hiện tại

| Công cụ | Chỉ số | Kết quả |
|---|---|---|
| Laravel Pint (chuẩn `laravel`) | File chưa đúng chuẩn | 0 |
| Larastan (PHPStan), level 2 | Lỗi | 0 |
| SonarQube Community | Blocker / Critical | 0 / 0 |
| SonarQube | Bug / Vulnerability | 0 / 0 |
| SonarQube | Trùng lặp mã | 2,5% |
| SonarQube | Xếp hạng Reliability / Security / Maintainability | A / A / A |
| SonarQube | Code smell còn lại | 34 Major, 10 Minor |
| gitleaks | Secret trong mã nguồn hiện tại | 0 |
| PHPUnit | Feature test | 610 pass |

Phạm vi SonarQube và lý do loại trừ từng phần nằm trong `sonar-project.properties`.

## Nợ còn lại và kế hoạch

| # | Nợ | Ảnh hưởng | Kế hoạch xử lý | Trạng thái |
|---|---|---|---|---|
| 1 | Secret cũ còn trong lịch sử Git: một khoá Google service account (`private_key_id` bắt đầu bằng `379b57`, commit 25/06/2026) và một token GHN (commit 03/06/2026) | Ai đọc được repo đều lấy được nếu khoá còn hiệu lực | Thu hồi khoá trên Google Cloud Console (IAM → Service accounts → Keys) và tạo lại token trên GHN. Sau khi thu hồi, thêm dấu vân tay của hai phát hiện vào `.gitleaksignore` kèm ghi chú "đã thu hồi". Không viết lại lịch sử vì repo dùng chung | Chờ thu hồi |
| 2 | Khoá Google service account hiện tại (`d95764…`) từng nằm trong `public/`, tức tải được qua web | Nếu đã deploy bản có file đó thì khoá coi như lộ | Đã chuyển vào `storage/app/google/`, có test chặn. Nếu từng deploy: tạo khoá mới, xoá khoá cũ | Đã sửa, cần xoay khoá nếu đã deploy |
| 3 | Larastan mới đạt level 2. Level 3–5 còn khoảng 70 lỗi, đa số là gán `0/1` vào cột `boolean` | Kiểu dữ liệu không chặt | Thêm cast `boolean` cho các cột cờ (`*_hidden`, `*_status`), nâng mỗi lần một level, không dùng baseline | Kế hoạch |
| 4 | Trùng lặp trong Blade khoảng 23% (đo bằng jscpd, SonarQube không phân tích Blade) | Sửa giao diện admin phải sửa nhiều nơi | Tách form thêm/sửa và bảng danh sách admin thành Blade component | Kế hoạch |
| 5 | Controller admin lớn: `OrderAdminController` (662 dòng), `ProductQuantityController` (495), `AuthAdminController` (492) | Khó đọc, khó test | Tách theo nhóm việc như đã làm với `ProductController` (đã tách thành Product/Cart/Checkout/CustomerOrder) | Kế hoạch |
| 6 | Code smell Major còn lại: hàm nhiều `return`, tên biến snake_case, tham số không dùng ở chữ ký bắt buộc của route | Thấp | Sửa dần khi đụng vào file | Kế hoạch |
| 7 | `database/sneaker_square.sql` lệch so với migration (thiếu `facebook_id`) | Cài theo cách 1 trong README sẽ thiếu cột | Xuất lại dump từ DB đã `migrate --seed` | Kế hoạch |
| 8 | Lịch sử Git cũ commit thẳng lên `main`, không qua Pull Request | Thiếu dấu vết review | Từ 05/10/2026 làm theo `CONTRIBUTING.md`: nhánh riêng, PR, review, CI xanh mới merge | Đang áp dụng |

## Đã xử lý trong đợt 05/10/2026

- Gom 11 file xuất Excel thành 4 file trên một lớp nền chung (Template Method).
- Gộp đăng nhập Google và Facebook vào một controller.
- Tách `ProductController` 745 dòng thành 4 controller theo trách nhiệm.
- Dùng một trait cho header/menu/footer thay cho 6 bản sao.
- Gom 23 đoạn đọc tham số sắp xếp thành một hàm. Hàm mới chặn tên cột không có thật: trước đây tên cột sai làm trang lỗi 500, và các trang thùng rác không sắp xếp được.
- Sửa redirect `/admin` trỏ sai và route `/dang-ky` khai báo hai lần.
- Đưa license CKFinder sang `.env`. Chuyển khoá Google Analytics ra khỏi `public/`.
- Siết CORS về `APP_URL`, bỏ `md5`, bỏ code bị comment và biến không dùng.
