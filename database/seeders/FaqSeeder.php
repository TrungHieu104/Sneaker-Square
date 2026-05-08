<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('faq')->insert([
            [
                'faq_name' => 'Chính sách bảo hành và đổi trả',
                'faq_content' => '<p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Ch&iacute;nh s&aacute;ch bảo h&agrave;nh v&agrave; đổi trả của Sneaker Square được x&acirc;y dựng nhằm đảm bảo sự h&agrave;i l&ograve;ng của bạn v&agrave; tăng cường niềm tin v&agrave;o c&aacute;c sản phẩm. Dưới đ&acirc;y l&agrave; một số nguy&ecirc;n tắc chung trong ch&iacute;nh s&aacute;ch bảo h&agrave;nh v&agrave; đổi trả:</span></span></span></span></p>

                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><strong><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Bảo h&agrave;nh:</span></span></span></strong></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Đối với sản phẩm mới, Sneaker Square cam kết sẽ bảo h&agrave;nh sản phẩm trong một th&aacute;ng kể từ khi mua h&agrave;ng. </span></span></span></span></p>
                
                <p><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="color:#081c36">- Trong thời gian bảo h&agrave;nh, </span><span style="background-color:#ffffff"><span style="color:#081c36">Sneaker Square&nbsp;</span></span><span style="color:#081c36">sẽ thay thế miễn ph&iacute; sản phẩm mới, nếu sản phẩm bị lỗi kỹ thuật do nh&agrave; sản xuất </span></span>(bung keo, đứt chỉ,&hellip;)</span></p>
                
                <p><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Kh&aacute;ch h&agrave;ng cần cung cấp bi&ecirc;n lai hoặc chứng từ chứng minh&nbsp;chứng minh mua h&agrave;ng để y&ecirc;u cầu bảo h&agrave;nh.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><strong><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Đổi trả:</span></span></span></strong></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Ch&uacute;ng t&ocirc;i cho ph&eacute;p kh&aacute;ch h&agrave;ng đổi trả sản phẩm từ 7-14 ng&agrave;y sau khi mua h&agrave;ng. </span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Sản phẩm cần được giữ trong trạng th&aacute;i mới, kh&ocirc;ng sử dụng hoặc hư hỏng. Bao b&igrave; v&agrave; tag sản phẩm đi k&egrave;m cũng cần được bảo quản nguy&ecirc;n vẹn.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- C&aacute;c bạn cần cung bi&ecirc;n lai hoặc chứng từ chứng minh&nbsp;&nbsp;mua h&agrave;ng để y&ecirc;u cầu đổi trả.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><strong><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Điều kiện &aacute;p dụng:</span></span></span></strong></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Ch&iacute;nh s&aacute;ch bảo h&agrave;nh v&agrave; đổi trả chỉ &aacute;p dụng cho sản phẩm được mua từ sneakersquare.com. Sản phẩm mua từ c&aacute;c nguồn kh&aacute;c c&oacute; thể kh&ocirc;ng được &aacute;p dụng ch&iacute;nh s&aacute;ch n&agrave;y.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">- Sản phẩm đ&atilde; qua sử dụng, kh&ocirc;ng thể &aacute;p dụng ch&iacute;nh s&aacute;ch bảo h&agrave;nh v&agrave; đổi trả.</span></span></span></span></p>
                
                <p><span style="font-family:roboto; font-size:16px">*Kh&aacute;ch h&agrave;ng cần cung cấp video khi mở h&agrave;ng.</span></p>
                
                <p>&nbsp;</p>',
                'faq_slug' => 'chinh-sach-bao-hanh-va-doi-tra',
                'faq_hidden' => 1,
                'faq_about' => 0,
                'faq_created_by' => 'Thuận',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'faq_name' => 'Chính sách giao hàng',
                'faq_content' => '<h4><span style="font-family:roboto; font-size:16px">1. QUY ĐỊNH THỜI GIAN GIAO NHẬN H&Agrave;NG</span></h4>

                <p><span style="font-family:roboto; font-size:16px">Thời gian phục vụ giao h&agrave;ng: thứ 2 đến thứ 7 (trừ chủ nhật, ng&agrave;y lễ, tết).<br />
                Khu vực giao h&agrave;ng:</span></p>
                
                <table border="1" cellpadding="1" cellspacing="1" style="width:700px">
                    <tbody>
                        <tr>
                            <th><span style="font-family:roboto; font-size:16px"><strong>Khu vực</strong></span></th>
                            <th><span style="font-family:roboto; font-size:16px"><strong>Giao h&agrave;ng ti&ecirc;u chuẩn</strong></span></th>
                        </tr>
                        <tr>
                            <td><span style="font-family:roboto; font-size:16px">Nội th&agrave;nh (HCM)</span></td>
                            <td><span style="font-family:roboto; font-size:16px">1-2 ng&agrave;y l&agrave;m việc</span></td>
                        </tr>
                        <tr>
                            <td><span style="font-family:roboto; font-size:16px">Nội v&ugrave;ng</span></td>
                            <td><span style="font-family:roboto; font-size:16px">1-3 ng&agrave;y l&agrave;m việc</span></td>
                        </tr>
                        <tr>
                            <td><span style="font-family:roboto; font-size:16px">Li&ecirc;n v&ugrave;ng (giữa 3 th&agrave;nh phố HCM, H&agrave; Nội v&agrave; Đ&agrave; Nẵng)</span></td>
                            <td><span style="font-family:roboto; font-size:16px">3-5 ng&agrave;y l&agrave;m việc</span></td>
                        </tr>
                        <tr>
                            <td><span style="font-family:roboto; font-size:16px">Li&ecirc;n v&ugrave;ng (từ 3 th&agrave;nh phố lớn HCM, H&agrave; Nội, Đ&agrave; Nẵng đến c&aacute;c th&agrave;nh phố kh&aacute;c thuộc v&ugrave;ng kh&aacute;c)</span></td>
                            <td><span style="font-family:roboto; font-size:16px">5-7 ng&agrave;y l&agrave;m việc</span></td>
                        </tr>
                    </tbody>
                </table>
                
                <p><span style="font-family:roboto; font-size:16px">Hỗ trợ giao h&agrave;ng ngay qua c&aacute;c ứng dụng như Grab, Ahamove,&hellip; nếu sản phẩm c&oacute; sẵn tại kho của Sneaker Square tr&ugrave;ng với nội th&agrave;nh địa chỉ giao h&agrave;ng của Qu&yacute; kh&aacute;ch.</span></p>
                
                <h4><span style="font-family:roboto; font-size:16px">2. QUY ĐỊNH KIỂM TRA H&Agrave;NG H&Oacute;A KHI GIAO NHẬN:</span></h4>
                
                <p><span style="font-family:roboto; font-size:16px">Nhằm bảo vệ tối đa quyền lợi kh&aacute;ch h&agrave;ng khi mua sắm tại Sneaker Square, ch&uacute;ng t&ocirc;i c&oacute; ch&iacute;nh s&aacute;ch đồng kiểm khi nhận h&agrave;ng như sau:<br />
                - Qu&yacute; kh&aacute;ch được quyền y&ecirc;u cầu nh&acirc;n vi&ecirc;n giao h&agrave;ng mở ni&ecirc;m phong th&ugrave;ng h&agrave;ng của Sneaker Square để kiểm tra số lượng, m&agrave;u sắc, chủng loại, k&iacute;ch cỡ, ngoại quan của c&aacute;c sản phẩm đ&atilde; mua trước khi nhận. (Lưu &yacute;: Việc đồng kiểm chỉ &aacute;p dụng kiểm tra ngoại quan, kh&ocirc;ng &aacute;p dụng cho việc d&ugrave;ng thử sản phẩm v&agrave; kiểm tra s&acirc;u chi tiết lỗi của sản phẩm).</span></p>
                
                <p><span style="font-family:roboto; font-size:16px">- Trường hợp Qu&yacute; kh&aacute;ch kh&ocirc;ng ưng &yacute; sản phẩm nhưng đ&atilde; thanh to&aacute;n online, Qu&yacute; kh&aacute;ch c&oacute; thể gửi lại sản phẩm cho nh&acirc;n vi&ecirc;n giao h&agrave;ng đồng thời Qu&yacute; kh&aacute;ch vui l&ograve;ng li&ecirc;n hệ Sneaker Square để được hỗ trợ về vấn đề ho&agrave;n tiền m&agrave; kh&ocirc;ng mất bất cứ chi ph&iacute; n&agrave;o.</span></p>
                
                <p><span style="font-family:roboto; font-size:16px">- Trường hợp nh&acirc;n vi&ecirc;n giao h&agrave;ng từ chối cho Qu&yacute; kh&aacute;ch kiểm tra h&agrave;ng, Qu&yacute; kh&aacute;ch c&oacute; quyền từ chối nhận h&agrave;ng, sau đ&oacute; li&ecirc;n hệ đến hotline để Sneaker Square y&ecirc;u cầu nh&acirc;n vi&ecirc;n phải giao h&agrave;ng lại cho Qu&yacute; kh&aacute;ch v&agrave; phải cho Qu&yacute; kh&aacute;ch kiểm tra h&agrave;ng. Hoặc Qu&yacute; kh&aacute;ch c&oacute; thể gọi ngay v&agrave;o hotline để được hỗ trợ ngay lập tức.</span></p>
                
                <p><span style="font-family:roboto; font-size:16px">- Trường hợp Qu&yacute; kh&aacute;ch h&agrave;i l&ograve;ng với t&igrave;nh trạng sản phẩm được giao v&agrave; đồng &yacute; mua sản phẩm, Qu&yacute; kh&aacute;ch vui l&ograve;ng k&yacute; v&agrave;o bi&ecirc;n bản đồng kiểm. Bằng việc k&yacute; v&agrave;o bi&ecirc;n bản đồng kiểm x&aacute;c nhận h&agrave;i l&ograve;ng với t&igrave;nh trạng sản phẩm được giao, Qu&yacute; kh&aacute;ch x&aacute;c nhận đ&atilde; ho&agrave;n th&agrave;nh việc đồng kiểm v&agrave; ho&agrave;n tất mua h&agrave;ng.</span></p>
                
                <p><span style="font-family:roboto; font-size:16px">- C&aacute;c sản phẩm nằm trong chương tr&igrave;nh ưu đ&atilde;i, khuyến m&atilde;i, giảm gi&aacute;, qu&agrave; tặng&hellip; kh&ocirc;ng &aacute;p dụng đổi/trả, v&igrave; vậy qu&yacute; kh&aacute;ch vui l&ograve;ng kiểm tra sản phẩm kỹ trước khi nhận h&agrave;ng. Sneaker Square sẽ kh&ocirc;ng &aacute;p dụng đổi/ trả với c&aacute;c đơn h&agrave;ng đ&atilde; được x&aacute;c nhận đồng kiểm bởi Qu&yacute; kh&aacute;ch.</span></p>
                
                <p><span style="font-family:roboto; font-size:16px">* Mọi chi tiết hoặc thắc mắc qu&yacute; kh&aacute;ch vui l&ograve;ng li&ecirc;n hệ với&nbsp;Sneaker Square&nbsp;để được hỗ trợ. Xin ch&acirc;n th&agrave;nh cảm ơn.</span></p>',
                'faq_slug' => 'chinh-sach-giao-hang',
                'faq_hidden' => 1,
                'faq_about' => 0,
                'faq_created_by' => 'Thuận',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'faq_name' => 'Chính sách bảo mật',
                'faq_content' => '<p><span style="font-size:16px"><span style="font-family:roboto">Ch&iacute;nh s&aacute;ch bảo mật của Sneaker Square được x&acirc;y dựng nhằm đảm bảo sự h&agrave;i l&ograve;ng của bạn v&agrave; tăng cường niềm tin v&agrave;o Sneaker Square. Dưới đ&acirc;y l&agrave; một số nguy&ecirc;n tắc chung trong ch&iacute;nh s&aacute;ch bảo mật: </span></span></p>

                <p><span style="font-size:16px"><span style="font-family:roboto"><strong>1. Website Sneaker Square thu thập th&ocirc;ng tin c&aacute; nh&acirc;n từ kh&aacute;ch h&agrave;ng th&ocirc;ng qua c&aacute;c phương thức sau: </strong></span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Đăng k&yacute;: Khi kh&aacute;ch h&agrave;ng đăng k&yacute; t&agrave;i khoản tr&ecirc;n website, th&ocirc;ng tin c&aacute; nh&acirc;n cơ bản như t&ecirc;n, địa chỉ email v&agrave; mật khẩu c&oacute; thể được y&ecirc;u cầu. Th&ocirc;ng tin c&aacute; nh&acirc;n n&agrave;y được sử dụng để x&aacute;c nhận danh t&iacute;nh của kh&aacute;ch h&agrave;ng, gi&uacute;p quản l&yacute; t&agrave;i khoản v&agrave; cung cấp c&aacute;c dịch vụ li&ecirc;n quan. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Giao dịch: Khi kh&aacute;ch h&agrave;ng thực hiện giao dịch tr&ecirc;n website, như mua h&agrave;ng hoặc đặt h&agrave;ng, th&ocirc;ng tin thanh to&aacute;n v&agrave; giao h&agrave;ng c&oacute; thể được thu thập. Th&ocirc;ng tin n&agrave;y bao gồm th&ocirc;ng tin thẻ t&iacute;n dụng, địa chỉ giao h&agrave;ng v&agrave; c&aacute;c chi tiết kh&aacute;c li&ecirc;n quan đến giao dịch. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Điều tra hoặc khảo s&aacute;t: Website c&oacute; thể y&ecirc;u cầu kh&aacute;ch h&agrave;ng tham gia v&agrave;o c&aacute;c điều tra hoặc khảo s&aacute;t để thu thập &yacute; kiến phản hồi hoặc th&ocirc;ng tin th&ecirc;m về sự h&agrave;i l&ograve;ng với sản phẩm hoặc dịch vụ. Th&ocirc;ng tin thu thập từ c&aacute;c điều tra hoặc khảo s&aacute;t n&agrave;y c&oacute; thể bao gồm &yacute; kiến, sở th&iacute;ch v&agrave; th&ocirc;ng tin kh&aacute;c li&ecirc;n quan đến trải nghiệm của kh&aacute;ch h&agrave;ng. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Cookie: Website c&oacute; thể sử dụng cookie để thu thập th&ocirc;ng tin về hoạt động truy cập của kh&aacute;ch h&agrave;ng tr&ecirc;n trang web. C&aacute;c cookie l&agrave; c&aacute;c tệp nhỏ chứa th&ocirc;ng tin được lưu trữ tr&ecirc;n thiết bị của kh&aacute;ch h&agrave;ng v&agrave; được sử dụng để nhận dạng v&agrave; theo d&otilde;i kh&aacute;ch h&agrave;ng khi truy cập v&agrave;o website. C&aacute;c loại th&ocirc;ng tin thu thập từ cookie bao gồm địa chỉ IP, loại tr&igrave;nh duyệt, thời gian truy cập v&agrave; c&aacute;c trang đ&atilde; xem. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Sneaker Square cam kết bảo mật th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng v&agrave; tu&acirc;n thủ c&aacute;c quy định ph&aacute;p luật hiện h&agrave;nh về bảo vệ dữ liệu c&aacute; nh&acirc;n. Th&ocirc;ng tin thu thập được sẽ chỉ được sử dụng cho mục đ&iacute;ch đăng k&yacute;, quản l&yacute; t&agrave;i khoản v&agrave; cung cấp dịch vụ li&ecirc;n quan. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto"><strong>2. Một số v&iacute; dụ về mục đ&iacute;ch sử dụng th&ocirc;ng tin m&agrave; Sneaker Square thu thập ph&iacute;a tr&ecirc;n:</strong> </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Xử l&yacute; đơn h&agrave;ng: Sneaker Square c&oacute; thể y&ecirc;u cầu th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng để x&aacute;c nhận v&agrave; xử l&yacute; c&aacute;c đơn h&agrave;ng, bao gồm địa chỉ giao h&agrave;ng, th&ocirc;ng tin thanh to&aacute;n v&agrave; th&ocirc;ng tin li&ecirc;n lạc. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Cung cấp dịch vụ: Th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng c&oacute; thể được sử dụng để cung cấp c&aacute;c dịch vụ như t&agrave;i khoản người d&ugrave;ng, quản l&yacute; th&ocirc;ng tin c&aacute; nh&acirc;n, hoặc hỗ trợ kh&aacute;ch h&agrave;ng. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Cải thiện trải nghiệm người d&ugrave;ng: Th&ocirc;ng tin c&aacute; nh&acirc;n c&oacute; thể được sử dụng để c&aacute; nh&acirc;n h&oacute;a trải nghiệm kh&aacute;ch h&agrave;ng v&agrave; cung cấp nội dung, sản phẩm v&agrave; dịch vụ ph&ugrave; hợp với sở th&iacute;ch v&agrave; quan t&acirc;m của kh&aacute;ch h&agrave;ng. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Gửi th&ocirc;ng tin marketing: Nếu kh&aacute;ch h&agrave;ng đồng &yacute;, th&ocirc;ng tin c&aacute; nh&acirc;n c&oacute; thể được sử dụng để gửi th&ocirc;ng tin marketing, bao gồm tin tức, khuyến m&atilde;i, sự kiện hoặc sản phẩm mới. Tuy nhi&ecirc;n, kh&aacute;ch h&agrave;ng lu&ocirc;n c&oacute; quyền lựa chọn kh&ocirc;ng nhận th&ocirc;ng tin n&agrave;y. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Nghi&ecirc;n cứu v&agrave; ph&acirc;n t&iacute;ch: Th&ocirc;ng tin c&aacute; nh&acirc;n c&oacute; thể được sử dụng để thực hiện nghi&ecirc;n cứu v&agrave; ph&acirc;n t&iacute;ch thị trường, gi&uacute;p website hiểu r&otilde; hơn về kh&aacute;ch h&agrave;ng v&agrave; cải thiện dịch vụ của Sneaker Square.</span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto"><strong>3.Để đảm bảo an to&agrave;n th&ocirc;ng tin c&aacute; nh&acirc;n, c&oacute; một số biện ph&aacute;p bảo mật đ&atilde; được Sneaker Square &aacute;p dụng.</strong></span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- M&atilde; h&oacute;a </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Quản l&yacute; truy cập </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Kiểm so&aacute;t b&ecirc;n thứ ba </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Bảo vệ chống lại chiếm đoạt hoặc sử dụng tr&aacute;i ph&eacute;p </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">4.Chia sẻ th&ocirc;ng tin kh&aacute;ch h&agrave;ng với b&ecirc;n thứ ba.</span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Ở đ&acirc;y Sneaker Square sẽ chia sẻ th&ocirc;ng tin kh&aacute;ch h&agrave;ng cho nh&agrave; cung cấp dịch vụ giao h&agrave;ng. Để đảm bảo tu&acirc;n thủ quy định bảo vệ th&ocirc;ng tin c&aacute; nh&acirc;n. Dưới đ&acirc;y l&agrave; một m&ocirc; tả chi tiết về việc chia sẻ th&ocirc;ng tin:</span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Nh&agrave; cung cấp dịch vụ giao h&agrave;ng: Khi sử dụng dịch vụ giao h&agrave;ng, th&ocirc;ng tin kh&aacute;ch h&agrave;ng c&oacute; thể được chia sẻ để hỗ trợ việc cung cấp dịch vụ.</span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Mục đ&iacute;ch: x&aacute;c minh danh t&iacute;nh, hỗ trợ nhận dạng kh&aacute;ch h&agrave;ng v&agrave; để x&aacute;c định c&aacute;c dịch vụ, tr&aacute;ch nhiệm ph&ugrave; hợp. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Phạm vi chia sẻ th&ocirc;ng tin: sử dụng th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng để gửi c&aacute;c th&ocirc;ng b&aacute;o quan trọng, chẳng hạn như li&ecirc;n lạc về việc sử dụng dịch vụ giao h&agrave;ng, th&ocirc;ng b&aacute;o đơn h&agrave;ng sắp đến nơi. V&igrave; th&ocirc;ng tin n&agrave;y rất quan trọng đối với sự tương t&aacute;c của kh&aacute;ch h&agrave;ng với Sneaker Square, kh&aacute;ch h&agrave;ng kh&ocirc;ng thể từ chối nhận c&aacute;c th&ocirc;ng tin li&ecirc;n lạc n&agrave;y. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto"><strong>5.Quyền lựa chọn của người d&ugrave;ng.</strong> </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Người d&ugrave;ng c&oacute; quyền lựa chọn v&agrave; kiểm so&aacute;t việc thu thập, sử dụng v&agrave; chia sẻ th&ocirc;ng tin c&aacute; nh&acirc;n của m&igrave;nh. Dưới đ&acirc;y l&agrave; c&aacute;c quyền lựa chọn cơ bản m&agrave; người d&ugrave;ng c&oacute;: </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Đồng &yacute;: kh&aacute;ch h&agrave;ng c&oacute; quyền tự nguyện cung cấp th&ocirc;ng tin c&aacute; nh&acirc;n cho Sneaker Square. Th&ocirc;ng qua việc đồng &yacute; n&agrave;y, kh&aacute;ch h&agrave;ng hiểu r&otilde; rằng th&ocirc;ng tin của họ sẽ được thu thập, sử dụng v&agrave; chia sẻ theo c&aacute;c điều khoản đ&atilde; quy định. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Kiểm so&aacute;t quyền ri&ecirc;ng tư: kh&aacute;ch h&agrave;ng c&oacute; quyền kiểm so&aacute;t th&ocirc;ng tin c&aacute; nh&acirc;n của m&igrave;nh. Bạn c&oacute; quyền xem, chỉnh sửa hoặc cập nhật th&ocirc;ng tin c&aacute; nh&acirc;n khi cần thiết. Ngo&agrave;i ra, người d&ugrave;ng cũng c&oacute; quyền y&ecirc;u cầu Sneaker Square xo&aacute; th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Từ chối: kh&aacute;ch h&agrave;ng c&oacute; quyền từ chối việc thu thập th&ocirc;ng tin c&aacute; nh&acirc;n của m&igrave;nh. Bạn c&oacute; thể kh&ocirc;ng cung cấp th&ocirc;ng tin y&ecirc;u cầu trong những trường hợp kh&ocirc;ng bắt buộc hoặc kh&ocirc;ng li&ecirc;n quan đến việc sử dụng dịch vụ. Để từ chối việc thu thập th&ocirc;ng tin hoặc y&ecirc;u cầu xo&aacute; th&ocirc;ng tin đ&atilde; được thu thập trước đ&oacute;, bạn c&oacute; thể l&agrave;m theo c&aacute;c bước sau:</span></span></p>
                
                <ul>
                    <li>
                    <p><span style="font-size:16px"><span style="font-family:roboto">Đọc v&agrave; hiểu ch&iacute;nh s&aacute;ch bảo mật: kh&aacute;ch h&agrave;ng n&ecirc;n đọc v&agrave; hiểu r&otilde; ch&iacute;nh s&aacute;ch bảo mật của Sneaker Square. </span></span></p>
                    </li>
                    <li>
                    <p><span style="font-size:16px"><span style="font-family:roboto">Li&ecirc;n hệ với Sneaker Square: Nếu kh&aacute;ch h&agrave;ng muốn từ chối việc thu thập th&ocirc;ng tin hoặc y&ecirc;u cầu xo&aacute; th&ocirc;ng tin đ&atilde; thu thập, bạn c&oacute; thể li&ecirc;n hệ trực tiếp với Sneaker Square để y&ecirc;u cầu điều n&agrave;y. Th&ocirc;ng qua y&ecirc;u cầu của m&igrave;nh, kh&aacute;ch h&agrave;ng c&oacute; thể y&ecirc;u cầu xo&aacute; th&ocirc;ng tin c&aacute; nh&acirc;n hoặc kh&ocirc;ng cho ph&eacute;p thu thập th&ocirc;ng tin mới. </span></span></p>
                    </li>
                </ul>
                
                <p><span style="font-size:16px"><span style="font-family:roboto"><strong>6. Cập nhật ch&iacute;nh s&aacute;ch đều đặn để ph&ugrave; hợp với thay đổi của quyền ph&aacute;p luật v&agrave; y&ecirc;u cầu của người d&ugrave;ng.</strong></span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">- Cam kết cập nhật ch&iacute;nh s&aacute;ch bảo mật l&agrave; một phần quan trọng trong việc đảm bảo an to&agrave;n v&agrave; bảo vệ th&ocirc;ng tin c&aacute; nh&acirc;n của kh&aacute;ch h&agrave;ng. C&aacute;c điểm cần lưu &yacute; khi cam kết cập nhật ch&iacute;nh s&aacute;ch bảo mật th&ocirc;ng tin kh&aacute;ch h&agrave;ng l&agrave;: </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Thực hiện kiểm tra định kỳ: Tổ chức hoặc dịch vụ cần tiến h&agrave;nh kiểm tra định kỳ c&aacute;c quy định mới về quyền ri&ecirc;ng tư v&agrave; bảo mật th&ocirc;ng tin. Điều n&agrave;y gi&uacute;p đảm bảo rằng ch&iacute;nh s&aacute;ch bảo mật lu&ocirc;n tu&acirc;n thủ c&aacute;c quy định mới nhất v&agrave; phản &aacute;nh sự thay đổi trong quyền ph&aacute;p luật. Theo d&otilde;i y&ecirc;u cầu của người d&ugrave;ng: Sneaker Square sẽ theo d&otilde;i v&agrave; xem x&eacute;t c&aacute;c y&ecirc;u cầu của kh&aacute;ch h&agrave;ng li&ecirc;n quan đến quyền ri&ecirc;ng tư v&agrave; bảo mật th&ocirc;ng tin c&aacute; nh&acirc;n. Nếu c&oacute; y&ecirc;u cầu mới, ch&iacute;nh s&aacute;ch bảo mật n&ecirc;n được điều chỉnh để đ&aacute;p ứng y&ecirc;u cầu n&agrave;y. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Th&ocirc;ng b&aacute;o cho kh&aacute;ch h&agrave;ng: Khi c&oacute; sự thay đổi quan trọng trong ch&iacute;nh s&aacute;ch bảo mật, Sneaker Square sẽ th&ocirc;ng b&aacute;o cho kh&aacute;ch h&agrave;ng về những thay đổi n&agrave;y. Điều n&agrave;y gi&uacute;p kh&aacute;ch h&agrave;ng hiểu r&otilde; c&aacute;c quyền lợi v&agrave; quyền tự nguyện của khi sử dụng dịch vụ hoặc cung cấp th&ocirc;ng tin c&aacute; nh&acirc;n. </span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">+ Tu&acirc;n thủ quyền ph&aacute;p luật: Tất cả c&aacute;c thay đổi trong ch&iacute;nh s&aacute;ch bảo mật cần tu&acirc;n thủ quyền ph&aacute;p luật li&ecirc;n quan đến quyền ri&ecirc;ng tư v&agrave; bảo mật th&ocirc;ng tin. Việc cập nhật ch&iacute;nh s&aacute;ch bảo mật kh&ocirc;ng chỉ l&agrave; cam kết với kh&aacute;ch h&agrave;ng, m&agrave; c&ograve;n l&agrave; sự đảm bảo rằng Sneaker Square tu&acirc;n thủ c&aacute;c quy định ph&aacute;p luật li&ecirc;n quan.</span></span></p>
                
                <p><span style="font-size:16px"><span style="font-family:roboto">* Mọi chi tiết hoặc thắc mắc qu&yacute; kh&aacute;ch vui l&ograve;ng li&ecirc;n hệ với&nbsp;Sneaker Square&nbsp;để được hỗ trợ. Xin ch&acirc;n th&agrave;nh cảm ơn.</span></span></p>
                
                <p>&nbsp;</p>',
                'faq_slug' => 'chinh-sach-bao-mat',
                'faq_hidden' => 1,
                'faq_about' => 0,
                'faq_created_by' => 'Thuận',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'faq_name' => 'Chính sách thanh toán',
                'faq_content' => '<p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Để tăng t&iacute;nh linh hoạt v&agrave; thuận tiện cho kh&aacute;ch h&agrave;ng, Sneaker Square &aacute;p dụng c&aacute;c phương thức thanh to&aacute;n sau :</span></span></span></span></p>

                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Thanh to&aacute;n bằng tiền mặt khi nhận h&agrave;ng (COD - Cash on Delivery): Đ&acirc;y l&agrave; phương thức phổ biến trong việc mua sắm trực tuyến. Kh&aacute;ch h&agrave;ng c&oacute; thể thanh to&aacute;n tiền mặt ngay tại thời điểm giao h&agrave;ng.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Chuyển khoản qua Momo: Momo l&agrave; một ứng dụng v&iacute; điện tử được sử dụng rộng r&atilde;i tại Việt Nam. Kh&aacute;ch h&agrave;ng c&oacute; thể chuyển tiền từ t&agrave;i khoản Momo của m&igrave;nh v&agrave;o t&agrave;i khoản của ch&uacute;ng t&ocirc;i th&ocirc;ng qua số điện thoại.</span></span></span></span></p>
                
                <p style="text-align:left"><span style="font-family:roboto; font-size:16px"><span style="background-color:#ffffff"><span style="background-color:#ffffff"><span style="color:#081c36">Thanh to&aacute;n qua Paypal: Paypal l&agrave; một h&igrave;nh thức thanh to&aacute;n trực tuyến quốc tế. Kh&aacute;ch h&agrave;ng c&oacute; thể thanh to&aacute;n bằng thẻ t&iacute;n dụng hoặc t&agrave;i khoản Paypal.</span></span></span></span></p>
                
                <p><span style="font-family:roboto; font-size:16px">* Mọi chi tiết hoặc thắc mắc qu&yacute; kh&aacute;ch vui l&ograve;ng li&ecirc;n hệ với&nbsp;Sneaker Square&nbsp;để được hỗ trợ. Xin ch&acirc;n th&agrave;nh cảm ơn.</span></p>',
                'faq_slug' => 'chinh-sach-thanh-toan',
                'faq_hidden' => 1,
                'faq_about' => 0,
                'faq_created_by' => 'Thuận',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'faq_name' => 'Giới thiệu về Sneaker Square',
                'faq_content' => '<p>Ch&agrave;o mừng bạn đến với <strong>Sneaker Square</strong> - một địa chỉ uy t&iacute;n v&agrave; tin cậy cho những người y&ecirc;u th&iacute;ch gi&agrave;y thể thao. Ch&uacute;ng t&ocirc;i tự h&agrave;o l&agrave; cửa h&agrave;ng chuy&ecirc;n cung cấp gi&agrave;y thể thao ch&iacute;nh h&atilde;ng từ c&aacute;c thương hiệu nổi tiếng tr&ecirc;n to&agrave;n thế giới. Tại <strong>Sneaker Square</strong>, ch&uacute;ng t&ocirc;i cam kết đem đến cho bạn những sản phẩm chất lượng, đa dạng về mẫu m&atilde;, v&agrave; lu&ocirc;n tu&acirc;n thủ nguy&ecirc;n tắc cung cấp h&agrave;ng h&oacute;a ch&iacute;nh h&atilde;ng.</p>

                <p>Những điểm nổi bật tại Sneaker Square:</p>
                
                <ol>
                    <li>
                    <p><strong>Sản Phẩm Ch&iacute;nh H&atilde;ng:</strong> Ch&uacute;ng t&ocirc;i lu&ocirc;n đặt uy t&iacute;n v&agrave; chất lượng l&ecirc;n h&agrave;ng đầu. Mọi sản phẩm tại Sneaker Square đều l&agrave; h&agrave;ng ch&iacute;nh h&atilde;ng, đảm bảo nguồn gốc xuất xứ v&agrave; chất lượng tốt nhất.</p>
                    </li>
                    <li>
                    <p><strong>Đa Dạng Mẫu M&atilde;:</strong> Sneaker Square tự h&agrave;o sở hữu một bộ sưu tập đa dạng về mẫu m&atilde;, từ gi&agrave;y thể thao cổ điển đến những sản phẩm mới nhất tr&ecirc;n thị trường. Ch&uacute;ng t&ocirc;i lu&ocirc;n cập nhật c&aacute;c xu hướng thời trang gi&agrave;y để bạn c&oacute; nhiều lựa chọn.</p>
                    </li>
                    <li>
                    <p><strong>Dịch Vụ Tận T&acirc;m:</strong> Đội ngũ nh&acirc;n vi&ecirc;n của ch&uacute;ng t&ocirc;i lu&ocirc;n sẵn s&agrave;ng hỗ trợ v&agrave; tư vấn cho bạn trong qu&aacute; tr&igrave;nh mua sắm. Ch&uacute;ng t&ocirc;i hiểu rằng việc chọn lựa đ&ocirc;i gi&agrave;y ho&agrave;n hảo c&oacute; thể l&agrave; một qu&aacute; tr&igrave;nh kh&aacute; th&aacute;ch thức, v&agrave; ch&uacute;ng t&ocirc;i sẽ gi&uacute;p bạn t&igrave;m ra đ&uacute;ng sản phẩm ph&ugrave; hợp với bạn.</p>
                    </li>
                    <li>
                    <p><strong>Ưu Đ&atilde;i Hấp Dẫn:</strong> Sneaker Square thường xuy&ecirc;n tổ chức c&aacute;c chương tr&igrave;nh khuyến m&atilde;i v&agrave; giảm gi&aacute; đặc biệt để gi&uacute;p bạn tiết kiệm hơn khi mua sắm gi&agrave;y thể thao y&ecirc;u th&iacute;ch.</p>
                    </li>
                    <li>
                    <p><strong>Cộng Đồng Sneaker:</strong> Ch&uacute;ng t&ocirc;i tự h&agrave;o c&oacute; một cộng đồng đam m&ecirc; gi&agrave;y thể thao đang ph&aacute;t triển mạnh mẽ. Tại Sneaker Square, bạn sẽ c&oacute; cơ hội kết nối với những người c&oacute; c&ugrave;ng niềm đam m&ecirc; v&agrave; đổi dẫn thảo về gi&agrave;y thể thao.</p>
                    </li>
                </ol>
                
                <p>H&atilde;y đến v&agrave; trải nghiệm thế giới gi&agrave;y thể thao ch&iacute;nh h&atilde;ng tại Sneaker Square. Ch&uacute;ng t&ocirc;i tin rằng bạn sẽ t&igrave;m thấy những đ&ocirc;i gi&agrave;y ưng &yacute; v&agrave; c&oacute; trải nghiệm mua sắm th&uacute; vị tại cửa h&agrave;ng của ch&uacute;ng t&ocirc;i.</p>',
                'faq_slug' => 've-chung-toi',
                'faq_hidden' => 1,
                'faq_about' => 1,
                'faq_created_by' => 'Thuận',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
