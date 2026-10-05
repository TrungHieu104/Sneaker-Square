# Quy trình đóng góp

## Nhánh và Pull Request

1. Không commit thẳng lên `main`. Mỗi việc làm trên một nhánh riêng tách từ `main`, tên nhánh
   **bằng tiếng Anh**: `feat/foot-measure`, `fix/trash-sorting`, `refactor/excel-exports`,
   `docs/deployment-diagram`.
2. Đẩy nhánh lên và mở Pull Request vào `main`, điền theo mẫu có sẵn.
3. Cần ít nhất **một thành viên khác review và approve**, và CI phải xanh, thì mới merge.
4. Merge bằng **Squash and merge** để mỗi PR là một commit gọn trên `main`.
5. Commit đều đặn, tuần nào làm thì tuần đó có commit. Không dồn cả tuần vào một commit.

Nên bật branch protection cho `main` trên GitHub (Settings → Branches): bắt buộc PR, bắt buộc
1 review, bắt buộc check CI pass.

## Commit message

Theo [Conventional Commits](https://www.conventionalcommits.org/), viết **bằng tiếng Anh**, một
commit một việc:

```
feat: measure foot size from a photo
fix: trash pages ignore the selected sort column
refactor: move Excel exports onto a shared base sheet
test: cover sending coupons to customer groups
docs: add deployment diagram
chore: add CI workflow
style: format codebase with Laravel Pint
```

Không dùng message kiểu `fix`, `update`, hay một câu dài liệt kê năm việc khác nhau.

## Tự kiểm tra trước khi mở PR

CI chạy đúng các lệnh dưới đây. Chạy ở máy trước để khỏi chờ CI báo đỏ.

```bash
php artisan config:clear
./vendor/bin/pint --test        # code style, phải sạch
./vendor/bin/phpstan analyse    # Larastan, phải 0 lỗi
php artisan test                # feature test, phải pass hết
```

Nếu Pint báo lỗi thì chạy `./vendor/bin/pint` để tự sửa.

## Secret

Khoá, token, mật khẩu chỉ để trong `.env`. Thêm biến mới thì ghi vào `.env.example` với giá trị
rỗng và một dòng giải thích. File khoá (JSON, PEM) để trong `storage/`, không bao giờ để trong
`public/`, vì mọi thứ trong `public/` đều tải được qua web. CI quét secret bằng gitleaks trên
mỗi PR.

## Đo chất lượng bằng SonarQube

Không bắt buộc ở mỗi PR, nhưng nên chạy trước mỗi mốc báo cáo:

```bash
docker run -d --name sonarqube -p 9000:9000 sonarqube:community
# Mở http://localhost:9000, đăng nhập admin/admin, đổi mật khẩu, tạo token
docker run --rm --network host -e SONAR_HOST_URL=http://localhost:9000 \
  -e SONAR_TOKEN=<token> -v "$PWD:/usr/src" sonarsource/sonar-scanner-cli
```

Phạm vi phân tích nằm trong `sonar-project.properties`. Kết quả gần nhất và danh mục nợ kỹ thuật
xem ở [`docs/ky-thuat/no-ky-thuat.md`](docs/ky-thuat/no-ky-thuat.md).
