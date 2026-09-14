# Quy ước dự án Sneaker-Square

Laravel 12 · PHP 8.2 · MySQL (dev) + SQLite (test) · Blade · template admin Sneat

---

## A. Comment & ngôn ngữ

1. Comment và docblock **luôn viết tiếng Anh**. Tiếng Việt chỉ dùng cho chữ người
   dùng đọc: text UI, thông báo validation, flash message, và **tên method test**.

2. **File migration không có comment**, kể cả docblock scaffold của Laravel.

3. Comment phải nói điều code không nói được. Ba loại bị cấm:
   - đọc lại chữ ký hàm (`// The variant for the colour and size` trên `variantFor()`)
   - kể code trước đây thế nào — git giữ chuyện đó rồi
   - giải thích cái đã hiển nhiên

   Nếu lời cảnh báo trong một đoạn sử ký còn giá trị, nén nó thành một câu luật:

   ```php
   // Never recompute revenue or profit from the product's current price: that is
   // neither what the customer paid nor what the variant cost.
   ```

## B. Database

4. Đổi schema thì **sửa thẳng file migration gốc**, không tạo migration vá.

## C. Test

5. Mọi thay đổi hành vi phải có feature test. Tên method giữ kiểu hiện có:
   `test_nhap_kho_hop_le_cong_them_vao_ton`.

6. Chạy `php artisan test` trước khi báo xong, và **dán đúng con số thật** — kể cả
   khi fail.

7. File mới phải qua `./vendor/bin/pint`. **Không format lại file cũ** như tác dụng
   phụ: repo chưa pint-clean, làm vậy chỉ khiến diff phình lên vô nghĩa.

## D. Thay đổi giao diện

8. Sửa UI thì phải **render thật và xem ảnh** trước khi báo xong, không suy luận từ
   code.

9. Dọn sạch sau khi kiểm thử: file tạm trong `public/`, dev server, dữ liệu demo.

## E. Cách báo cáo

10. Trả lời bằng **tiếng Việt**.

11. Nói cái đã đổi và lý do. **Không dẫn tour qua code**, không kể lại việc vừa được
    yêu cầu.

12. Phát hiện ngoài phạm vi: **nêu đúng một lần**, ghi vào mục "việc treo" ở cuối câu
    trả lời. **Không lặp lại ở những lượt sau.**

13. Không tự làm việc ngoài yêu cầu. Nêu ra và chờ quyết định.

14. Sửa lỗi mình gây ra thì sửa gọn rồi đi tiếp, không tự kiểm điểm dài dòng.
