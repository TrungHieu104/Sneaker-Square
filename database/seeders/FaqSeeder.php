<?php

namespace Database\Seeders;

use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * The storefront FAQ, written from the rules the shop runs on. The return
     * window repeats the default setting: change it here too if that changes.
     */
    public function run(): void
    {
        $groups = [
            'Đặt hàng & thanh toán' => [
                'Shop nhận những hình thức thanh toán nào?' => '<p>Bạn có thể thanh toán khi nhận hàng (COD), qua MoMo, qua VNPay hoặc bằng số dư ví SPay.</p>',
                'Tôi muốn huỷ đơn đã đặt thì làm sao?' => '<p>Vào <b>Đơn hàng của tôi</b>. Đơn shop chưa xác nhận thì bạn tự huỷ được ngay. Đơn đã xác nhận hoặc đang đóng gói thì bạn gửi yêu cầu huỷ để shop duyệt. Đơn đã giao cho đơn vị vận chuyển thì không huỷ được nữa: bạn nhận hàng rồi gửi yêu cầu trả hàng. Đơn đã thanh toán được hoàn tiền vào ví SPay.</p>',
            ],
            'Giao hàng' => [
                'Shop giao hàng bằng đơn vị nào?' => '<p>Shop giao qua Giao Hàng Nhanh (GHN). Phí giao hàng tính theo địa chỉ nhận lúc bạn đặt hàng.</p>',
                'Làm sao biết đơn của tôi đang ở đâu?' => '<p>Vào <b>Đơn hàng của tôi</b> để xem trạng thái và mã vận đơn của từng đơn.</p>',
            ],
            'Đổi trả & hoàn tiền' => [
                'Tôi được trả hàng trong bao lâu?' => '<p>Trong 7 ngày kể từ khi đơn hoàn tất. Bạn gửi yêu cầu trả hàng ở trang <b>Đơn hàng của tôi</b>. Xem thêm <a href="/chinh-sach/tra-hang-hoan-tien">chính sách trả hàng</a>.</p>',
                'Shop có đổi size không?' => '<p>Shop chưa có luồng đổi hàng riêng. Muốn đổi size hay đổi mẫu, bạn gửi yêu cầu trả hàng trong hạn, tiền được hoàn vào ví SPay, rồi đặt đơn mới với size hoặc mẫu muốn đổi.</p>',
                'Tiền trả hàng được hoàn về đâu?' => '<p>Về ví SPay trong tài khoản của bạn. Bạn dùng số dư này cho đơn sau, hoặc gửi yêu cầu rút về tài khoản ngân hàng ở trang <b>Ví của tôi</b>.</p>',
            ],
            'Size & sản phẩm' => [
                'Làm sao chọn đúng size?' => '<p>Shop dùng size EU theo chiều dài bàn chân. Bạn có thể đo chân bằng ảnh chụp ở trang <a href="/do-co-chan">Đo cỡ chân</a> để được gợi ý size.</p>',
            ],
            'Tài khoản & ví SPay' => [
                'Tôi đăng ký tài khoản bằng email nào?' => '<p>Shop nhận email Gmail (đuôi @gmail.com) khi đăng ký tài khoản.</p>',
                'Nạp tiền vào ví SPay thế nào?' => '<p>Vào trang <b>Ví của tôi</b>, chọn nạp qua MoMo hoặc VNPay. Mỗi lần nạp từ 10.000đ đến 50.000.000đ.</p>',
            ],
        ];

        $groupPosition = 0;

        foreach ($groups as $name => $questions) {
            $group = FaqGroupModel::firstOrCreate(['group_name' => $name], ['group_position' => ++$groupPosition]);
            $position = 0;

            foreach ($questions as $question => $answer) {
                FaqModel::firstOrCreate(
                    ['group_id' => $group->group_id, 'faq_question' => $question],
                    ['faq_answer' => $answer, 'faq_position' => ++$position, 'faq_created_by' => 'Admin'],
                );
            }
        }
    }
}
