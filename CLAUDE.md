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

6. Trước khi báo xong phải chạy đủ ba lệnh mà CI chạy, và **dán đúng con số thật**
   — kể cả khi fail:
   - `php artisan test`
   - `./vendor/bin/pint --test` (repo đã pint-clean: file nào sửa thì chạy
     `./vendor/bin/pint` cho file đó)
   - `./vendor/bin/phpstan analyse` (Larastan): **0 lỗi**. Không baseline, không
     `@phpstan-ignore`, không ép kiểu chỉ để tắt lỗi. Sửa gốc.

7. Refactor đoạn code chưa có test thì **viết test đặc tả trước** trên code cũ, thấy
   pass, rồi mới sửa.

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

## F. Chất lượng mã nguồn

Ngưỡng hiện tại và nợ kỹ thuật còn lại nằm ở `docs/technical/technical-debt.md`. Thay đổi
không được làm tụt các số đó.

15. **Không viết secret vào code.** Key, token, mật khẩu đọc từ `.env`. Biến mới thì thêm
    vào `.env.example` với giá trị rỗng và một dòng giải thích. File khoá đặt trong
    `storage/`, **không bao giờ trong `public/`**: mọi thứ ở đó đều tải được qua web.

16. **Dùng lại khối có sẵn, không chép lại:**
    - sắp xếp danh sách admin → `$this->listingSort()` trong `Controller`
    - header, menu, footer storefront → trait `SharesStorefrontLayout`
    - file Excel → kế thừa `App\Exports\Sheets\ListingSheet`
    - chuỗi lặp từ 3 lần trong một class → `private const`

17. **Controller mỏng.** Nghiệp vụ đặt trong `app/Actions` hoặc `app/Services`. Một hàm
    không vượt cognitive complexity 15 (ngưỡng của SonarQube). Dài hơn thì tách hàm.

18. **Quan hệ Eloquent khai báo kiểu trả về và generic**:
    `/** @return BelongsTo<OrderModel, $this> */ public function order(): BelongsTo`.

19. Không để lại code đã comment, biến hay method không dùng. Git giữ lịch sử rồi.

20. Commit message và tên nhánh **viết bằng tiếng Anh**. Commit theo Conventional Commits
    (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`, `style:`), một commit một
    việc. Nhánh dạng `<type>/<kebab-case>` (`refactor/code-quality`), không commit thẳng lên
    `main`. Chỉ commit khi được yêu cầu, không push khi chưa được yêu cầu.
