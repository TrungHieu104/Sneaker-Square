<?php

namespace Database\Seeders;

use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * The storefront FAQ, written from the rules the shop runs on. The return
     * window, the time an unpaid online order is held and the days before a
     * delivered order completes itself repeat the default settings: change
     * them here too if those change. New questions are appended to a group,
     * so a database seeded before keeps its order and gains only the new rows.
     */
    public function run(): void
    {
        $groups = [
            'Đặt hàng & thanh toán' => [
                'Shop nhận những hình thức thanh toán nào?' => '<p>Bạn có thể thanh toán khi nhận hàng (COD), qua MoMo, qua VNPay hoặc bằng số dư ví SPay.</p>',
                'Tôi muốn huỷ đơn đã đặt thì làm sao?' => '<p>Vào <b>Đơn hàng của tôi</b>. Đơn shop chưa xác nhận thì bạn tự huỷ được ngay. Đơn đã xác nhận hoặc đang đóng gói thì bạn gửi yêu cầu huỷ để shop duyệt. Đơn đã giao cho đơn vị vận chuyển thì không huỷ được nữa: bạn nhận hàng rồi gửi yêu cầu trả hàng. Đơn đã thanh toán được hoàn tiền vào ví SPay.</p>',
                'Tôi có cần tài khoản để đặt hàng không?' => '<p>Có. Bạn xem và thêm sản phẩm vào <a href="/gio-hang">giỏ hàng</a> mà không cần đăng nhập, nhưng phải đăng nhập và có địa chỉ giao hàng mặc định mới đặt được đơn.</p>',
                'Đặt hàng xong có đổi được địa chỉ giao hàng không?' => '<p>Không, địa chỉ được ghi vào đơn lúc bạn đặt. Nếu đơn còn <b>Chờ xác nhận</b>, bạn huỷ đơn rồi đặt lại với địa chỉ đúng.</p>',
                'Thanh toán MoMo hoặc VNPay bị lỗi thì đơn có bị huỷ không?' => '<p>Chưa. Shop giữ hàng cho đơn trong 30 phút. Trong thời gian đó bạn mở đơn ở <b>Đơn hàng của tôi</b>, bấm <b>Thanh toán ngay</b> để thử lại hoặc đổi sang cách thanh toán khác. Hết 30 phút mà đơn chưa được thanh toán thì đơn tự huỷ.</p>',
                'Tôi có thể trả một phần bằng ví SPay, phần còn lại bằng cách khác không?' => '<p>Không. Mỗi đơn chỉ dùng một cách thanh toán. Muốn trả bằng ví SPay thì số dư phải đủ cho cả đơn; nếu chưa đủ, bạn nạp thêm vào ví hoặc chọn cách thanh toán khác.</p>',
            ],
            'Giao hàng' => [
                'Shop giao hàng bằng đơn vị nào?' => '<p>Shop giao qua Giao Hàng Nhanh (GHN). Phí giao hàng tính theo địa chỉ nhận lúc bạn đặt hàng.</p>',
                'Làm sao biết đơn của tôi đang ở đâu?' => '<p>Vào <b>Đơn hàng của tôi</b> để xem trạng thái và mã vận đơn của từng đơn.</p>',
                'Phí giao hàng tính thế nào? Có miễn phí giao hàng không?' => '<p>Phí do GHN tính theo địa chỉ nhận và cân nặng của đơn, hiện ở trang thanh toán trước khi bạn đặt. Shop hiện chưa có chương trình miễn phí giao hàng.</p>',
                'Bao lâu thì tôi nhận được hàng?' => '<p>Trang thanh toán hiện ngày dự kiến nhận hàng mà GHN báo cho địa chỉ của bạn. Ngày này cũng hiện ở trang chi tiết đơn hàng.</p>',
                'Nhận hàng rồi tôi cần làm gì?' => '<p>Bấm <b>Đã nhận hàng</b> ở trang đơn để đơn chuyển sang <b>Thành công</b>. Nếu bạn không bấm, đơn tự hoàn tất sau 7 ngày kể từ khi GHN báo đã giao. Hạn trả hàng được tính từ lúc đơn hoàn tất.</p>',
            ],
            'Đổi trả & hoàn tiền' => [
                'Tôi được trả hàng trong bao lâu?' => '<p>Trong 7 ngày kể từ khi đơn hoàn tất. Bạn gửi yêu cầu trả hàng ở trang <b>Đơn hàng của tôi</b>. Xem thêm <a href="/chinh-sach/tra-hang-hoan-tien">chính sách trả hàng</a>.</p>',
                'Shop có đổi size không?' => '<p>Shop chưa có luồng đổi hàng riêng. Muốn đổi size hay đổi mẫu, bạn gửi yêu cầu trả hàng trong hạn, tiền được hoàn vào ví SPay, rồi đặt đơn mới với size hoặc mẫu muốn đổi.</p>',
                'Tiền trả hàng được hoàn về đâu?' => '<p>Về ví SPay trong tài khoản của bạn. Bạn dùng số dư này cho đơn sau, hoặc gửi yêu cầu rút về tài khoản ngân hàng ở trang <b>Ví của tôi</b>.</p>',
                'Gửi yêu cầu trả hàng cần những gì?' => '<p>Chọn sản phẩm, số lượng muốn trả và lý do. Chọn <b>Lý do khác</b> thì ghi thêm mô tả. Bạn có thể đính kèm tối đa 3 ảnh (jpg, png hoặc webp, mỗi ảnh tối đa 5 MB).</p>',
                'Tôi có trả một phần đơn hàng được không?' => '<p>Được, bạn chọn từng sản phẩm và số lượng muốn trả. Mỗi đơn chỉ được duyệt trả hàng một lần, nên hãy chọn đủ những gì muốn trả trong cùng một yêu cầu.</p>',
                'Ai trả phí gửi hàng trả lại?' => '<p>Shop đặt GHN đến lấy hàng. Nếu bạn trả vì sai size, phí gửi trả được trừ vào tiền hoàn. Với các lý do còn lại (sản phẩm lỗi, giao nhầm, không giống mô tả), shop trả phí.</p>',
                'Tôi được hoàn bao nhiêu tiền?' => '<p>Tiền các sản phẩm trả lại theo giá bạn đã trả; nếu đơn có dùng mã giảm giá thì trừ phần giảm của các sản phẩm đó. Phí giao hàng ban đầu chỉ được hoàn khi bạn trả cả đơn. Tiền vào ví SPay sau khi shop nhận lại hàng.</p>',
                'Gửi yêu cầu trả hàng rồi có huỷ được không?' => '<p>Được, khi yêu cầu còn <b>Chờ duyệt</b>. Huỷ xong, nếu còn trong hạn trả hàng, bạn vẫn gửi lại được yêu cầu mới.</p>',
            ],
            'Size & sản phẩm' => [
                'Làm sao chọn đúng size?' => '<p>Shop dùng size EU theo chiều dài bàn chân. Bạn có thể đo chân bằng ảnh chụp ở trang <a href="/do-co-chan">Đo cỡ chân</a> để được gợi ý size.</p>',
                'Shop có nhớ size của tôi không?' => '<p>Có. Size bạn đo ở trang <a href="/do-co-chan">Đo cỡ chân</a>, hoặc size của lần mua gần nhất, được chọn sẵn khi bạn mở trang sản phẩm. Bạn vẫn đổi được trước khi thêm vào giỏ.</p>',
                'Ai được đánh giá sản phẩm?' => '<p>Bạn cần đăng nhập và có một đơn <b>Thành công</b> có sản phẩm đó. Đánh giá gồm số sao từ 1 đến 5 và nhận xét ít nhất 5 ký tự.</p>',
                'Tôi lưu sản phẩm yêu thích thế nào?' => '<p>Bấm <b>Thêm mục yêu thích</b> ở trang sản phẩm. Bạn cần đăng nhập; danh sách nằm ở trang <a href="/san-pham-yeu-thich">Sản phẩm yêu thích</a>.</p>',
            ],
            'Tài khoản & ví SPay' => [
                'Tôi đăng ký tài khoản bằng email nào?' => '<p>Shop nhận email Gmail (đuôi @gmail.com) khi đăng ký tài khoản.</p>',
                'Nạp tiền vào ví SPay thế nào?' => '<p>Vào trang <b>Ví của tôi</b>, chọn nạp qua MoMo hoặc VNPay. Mỗi lần nạp từ 10.000đ đến 50.000.000đ.</p>',
                'Tôi quên mật khẩu thì làm sao?' => '<p>Vào trang <a href="/quen-mat-khau">Quên mật khẩu</a>, nhập email tài khoản. Shop gửi link để bạn đặt mật khẩu mới.</p>',
                'Tôi đăng nhập bằng Google hoặc Facebook được không?' => '<p>Được. Nếu email đó đã đăng ký tài khoản bằng mật khẩu, bạn đăng nhập bằng email và mật khẩu như trước.</p>',
                'Rút tiền từ ví SPay về ngân hàng thế nào?' => '<p>Ở trang <a href="/vi-cua-toi">Ví của tôi</a>, gửi yêu cầu rút từ 50.000đ trở lên. Số tiền được trừ khỏi ví ngay khi bạn gửi; shop chuyển khoản rồi xác nhận. Nếu yêu cầu bị từ chối, tiền quay lại ví.</p>',
            ],
            'Mã giảm giá & ưu đãi' => [
                'Nhập mã giảm giá ở đâu?' => '<p>Ở trang <a href="/gio-hang">Giỏ hàng</a>, ô <b>Mã giảm giá</b>. Mỗi đơn dùng một mã; nhập mã mới thì mã cũ được thay. Mã giảm vào tiền hàng, không giảm phí giao hàng.</p>',
                'Vì sao mã giảm giá của tôi không dùng được?' => '<p>Mã có thể đã hết hạn, đã hết lượt dùng, đơn chưa đạt giá trị tối thiểu của mã, hoặc mã dành riêng cho tài khoản khác. Trang giỏ hàng ghi rõ lý do khi mã không áp được.</p>',
                'Huỷ đơn thì mã giảm giá có được trả lại không?' => '<p>Có, huỷ đơn thì lượt dùng mã được trả lại. Trả hàng thì không.</p>',
                'Làm sao để nhận mã ưu đãi?' => '<p>Shop gửi mã ưu đãi riêng qua email cho khách đã mua hàng. Mã đó chỉ dùng được một lần với tài khoản của bạn; bấm link trong email là mã được áp vào giỏ nếu giỏ đủ điều kiện.</p>',
                'Tôi không muốn nhận email nhắc giỏ hàng nữa thì làm sao?' => '<p>Bấm <b>Không nhận email nhắc giỏ hàng nữa</b> ở cuối email. Tắt rồi bạn cũng không nhận email ưu đãi của shop nữa.</p>',
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
