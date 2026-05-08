<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'pro_name' => 'Nike Flex Experience Run 10',
                'pro_slug' => 'nike-flex-experience-run-10',
                'pro_code' => 'CI9960',
                'pro_price' => 1909000,
                'pro_price_sale' => 0,
                'capital_price' => 1500000,
                'pro_img' => 'backend/uploads/product/nike-flex-experience-run-10.jpg',
                'pro_description' => 'Đơn giản và linh hoạt, Nike Flex Experience Run 10 được chế tạo để vận động, dành cho người chạy bình thường, thiết kế an toàn và đệm nhẹ ở gót chân giúp bạn đi được nhiều dặm. có thể tận hưởng sự thoải mái được thiết kế để mặc cả ngày.',
                'pro_views' => 150,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Air Zoom Pegasus 39',
                'pro_slug' => 'nike-air-zoom-pegasus-39',
                'pro_code' => 'DH4071-401',
                'pro_price' => 3519000,
                'pro_price_sale' => 0,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/nike-air-zoom-pegasus-39.jpg',
                'pro_description' => 'Chạy là nghi thức hàng ngày của bạn, với mỗi bước sẽ đưa bạn đến gần hơn với mục tiêu cá nhân của mình. Hãy để Nike Air Zoom Pegasus 39 giúp bạn nâng lên tầm cao mới — cho dù bạn đang tập luyện hay chạy bộ  với thiết kế trực quan của nó. Trọng lượng nhẹ hơn Pegasus 38 và lý tưởng để mang trong bất kỳ mùa nào, nó có cảm giác hỗ trợ để giúp giữ chân của bạn, trong khi đệm dưới chân và bộ phận Zoom Air gấp đôi (nhiều hơn 1 chiếc so với Peg 38) cho bạn thêm cảm giác bước của bạn. Chú ngựa ô đáng tin cậy với đôi cánh của bạn đã trở lại. Đã đến giờ cất cánh.',
                'pro_views' => 130,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike React Escape Run 2',
                'pro_slug' => 'nike-react-escape-run-2',
                'pro_code' => 'DJ9976-002',
                'pro_price' => 1859000,
                'pro_price_sale' => 0,
                'capital_price' => 1000000,
                'pro_img' => 'backend/uploads/product/nike-react-escape-run-2.jpg',
                'pro_description' => 'Sải những bước chân chạy bộ trên từng cung đường, là khoảnh khắc bình yên nhất sau một ngày dài bận rộn. Nike React Escape Run 2 với thiết kế thoáng khí, êm ái và hỗ trợ lực kéo tối ưu sẽ là sự lựa chọn hoàn hảo để đồng hành cùng bạn dạo quanh các con phố.',
                'pro_views' => 170,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Free Run 5.0',
                'pro_slug' => 'nike-free-run-5.0',
                'pro_code' => 'CZ1884-002',
                'pro_price' => 3403000,
                'pro_price_sale' => 0,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/nike-free-run-5.0.jpg',
                'pro_description' => 'Giày Thể Thao Nam Nike Free Run 5.0 là mẫu giày thể thao đến từ thương hiệu Nike nổi tiếng của Mỹ được rất nhiều tín đồ thể thao ưa chuộng. Nike Free Run 5.0 mang thiết kế thời trang cùng chất liệu cao cấp chắc chắn sẽ là một item mà bạn không thể bỏ lỡ trong bộ sưu tập giày của mình.',
                'pro_views' => 100,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Avryn',
                'pro_slug' => 'addidas-avryn',
                'pro_code' => 'HP5968',
                'pro_price' => 2660000,
                'pro_price_sale' => 2500000,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/adidas-avryn.jpg',
                'pro_description' => 'Trải nghiệm công nghệ Boost và Bounce của AVRYN với sự kết hợp mới của hai yếu tố đa năng và thoải mái. Các công nghệ giày thể thao tốt nhất nay góp mặt trong mẫu giày thường ngày dành cho tất cả mọi người. Muôn vàn cách phối đồ.',
                'pro_views' => 120,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Hyperturf',
                'pro_slug' => 'adidas-hyperturf',
                'pro_code' => 'GW6756',
                'pro_price' => 2200000,
                'pro_price_sale' => 0,
                'capital_price' => 1500000,
                'pro_img' => 'backend/uploads/product/adidas-hyperturf.jpg',
                'pro_description' => 'Đâu cứ phải ra ngoài thiên nhiên mới gọi là phiêu lưu. Mà dạo phố cũng là cả một hành trình khám phá. Đôi Giày adidas Hyperturf này cùng bạn sẵn sàng xuống phố với nguồn cảm hứng từ kho di sản trang phục ngoài trời của adidas. Các chất liệu bền bỉ hội tụ trên thân giày — vải ripstop, da nubuck, da lộn và vải lưới. Bên dưới là màn kết hợp tuyệt hảo giữa lớp đệm Adiprene+, lớp đệm EVA mềm mại và công nghệ FORMOTION cho cảm giác nâng đỡ tuyệt đối. Sản phẩm này có sử dụng thành phần tái chế từ rác thải sản xuất, chẳng hạn như vật liệu cắt bỏ, cũng như rác thải sinh hoạt sau tiêu dùng, nhằm giảm thiểu tác động môi trường do sử dụng thành phần nguyên sinh trong sản xuất.',
                'pro_views' => 190,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance MENS CLASSIC - ML574EVW',
                'pro_slug' => 'new-balance-mens-classic-ml574evw',
                'pro_code' => 'ML574EVW',
                'pro_price' => 1098000,
                'pro_price_sale' => 1000000,
                'capital_price' => 800000,
                'pro_img' => 'backend/uploads/product/new-balance-mens-classic-ml574evw.jpg',
                'pro_description' => 'New Balance 574 mẫu giày biểu tượng lấy cảm hứng từ phong cách những năm 80 làm nên phong cách old-school đầy sức hút cho outfit của bạn. Mang đến những đường nét gọn gàng với sự kết hợp của những gam màu rực rỡ và đế ngoài chắc chắn, cùng chất liệu da lộn kinh điển sẽ tạo nên sự nổi bật cho trang phục của bạn nhưng vẫn giữ những nét đẹp thời mang hơi thở cổ điển của thập niên 80.',
                'pro_views' => 120,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance Classic - ML2002RA',
                'pro_slug' => 'new-balance-classic-ml2002ra',
                'pro_code' => 'ML2002RA',
                'pro_price' => 2496000,
                'pro_price_sale' => 0,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/new-balance-classic-ml2002ra.jpg',
                'pro_description' => 'Lấy cảm hứng từ những đôi giày chạy bộ những năm 2000, New Balance 2002R xuất hiện với phong cách retro mới mẻ, kết hợp đế Abzorb trợ lực cho độ thoải mái và ổn định đáp ứng mọi chuyển động của bạn. Đế ngoài N-ergy bền bỉ có khả năng chống chịu mài mòn trên suốt các đường chạy.',
                'pro_views' => 60,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày sneakers unisex cổ thấp RIPNDIP Suede',
                'pro_slug' => 'giay-sneakers-unisex-co-thap-ripndip-suede',
                'pro_code' => '393872',
                'pro_price' => 3399000,
                'pro_price_sale' => 0,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/giay-sneakers-unisex-co-thap-ripndip-suede.jpg',
                'pro_description' => 'Giày sneakers RIPNDIP Suede là sự kết hợp đầy độc đáo với điểm nhấn là hình chú mèo trắng dễ thương trên nền thiết kế giày cổ điển đặc trưng của thương hiệu PUMA. Đây chắc chắn là item bạn không thể bỏ qua để có một diện mạo đầy năng động nhưng cũng không kém phần thú vị.',
                'pro_views' => 50,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày sneakers unisex cổ thấp Slipstream Snake',
                'pro_slug' => 'giay-sneakers-unisex-co-thap-slipstream-snake',
                'pro_code' => '393265',
                'pro_price' => 3399000,
                'pro_price_sale' => 0,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/giay-sneakers-unisex-co-thap-slipstream-snake.jpg',
                'pro_description' => 'Vào năm 1987, đôi giày sneakers Slipstream Snake ra đời với tư cách là một đôi giày bóng rổ. Ngày nay, đôi giày được kết hợp bởi phiên bản Slipstream - một sự cải tiến của phiên bản gốc mang đến sự mới mẻ hoàn toàn cho thiết kế, nhưng vẫn giữ nguyên tinh thần thể thao. Bằng cách thêm một chút hoa văn vào phiên bản gốc, PUMA đã tạo nên đôi giày sneakers Slipstream Snake đặc biệt này.',
                'pro_views' => 90,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ Nữ Bitis Hunter ASWH00300',
                'pro_slug' => 'vo-nu-bitis-hunter-aswh00300',
                'pro_code' => 'ASWH00300',
                'pro_price' => 78000,
                'pro_price_sale' => 0,
                'capital_price' => 50000,
                'pro_img' => 'backend/uploads/product/vo-nu-bitis-hunter-aswh00300.jpg',
                'pro_description' => 'Sản phẩm vớ nữ Bitis Hunter ASWH00300',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dây giày ALB002',
                'pro_slug' => 'day-giay-alb002',
                'pro_code' => 'ALB002',
                'pro_price' => 1550000,
                'pro_price_sale' => 0,
                'capital_price' => 1000000,
                'pro_img' => 'backend/uploads/product/day-giay-alb002.jpg',
                'pro_description' => 'Sản phẩm dây giày ALB002',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 8,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Chai xịt tạo bọt vệ sinh giày Sneaker XIMO',
                'pro_slug' => 'chai-xit-tao-bot-ve-sinh-giay-sneaker-ximo',
                'pro_code' => 'VSG02-1',
                'pro_price' => 85000,
                'pro_price_sale' => 0,
                'capital_price' => 60000,
                'pro_img' => 'backend/uploads/product/chai-xit-tao-bot-ve-sinh-giay-sneaker-cao-cap-vsg02-1.jpg',
                'pro_description' => 'Vệ sinh, làm sạch cho giày luôn là điều khó khăn với mọi người. Bởi vậy, thương hiệu XIMO đem đến cho mọi người Chai Xịt Tạo Bọt Vệ Sinh Giày XIMO Cao Cấp. Khác với những chất tẩy rửa phổ biến mà chúng ta thường đem ra để vệ sinh giày luôn cho tiện như bột giặt, kem đánh răng,... Thì chai xịt tạo bọt vệ sinh giày XIMO có chức năng chuyên dụng cho việc vệ sinh giày, làm sạch giày với công thức và tỉ lệ chuẩn, phù hợp với đúng việc vệ sinh giày, giúp vệ sinh sạch hơn, nhanh hơn, không làm phai màu sắc, hỏng vải như những chất tẩy rửa ở trên.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 9,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma Cali',
                'pro_slug' => 'puma-cali',
                'pro_code' => '69155-04',
                'pro_price' => 2800000,
                'pro_price_sale' => 0,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/puma-Cali-4.jpg',
                'pro_description' => 'Giới thiệu Puma Cali. Lấy cảm hứng thiết kế từ những năm 80, kiểu dáng quần vợt cổ điển này mang lại cảm giác như ở nhà với phong cách giày thể thao thoải mái của các thị trấn bãi biển ở California. Giữ nguyên vẻ ngoài và cảm nhận được yêu thích, Cali bước vào mùa giải mới với một số điểm mới: các đường kẻ rộng hơn ở gót chân và các lỗ đục lỗ trên mũi giày mang lại thêm góc cạnh. Hơn nữa, 
                miếng lót giày SOFTFOAM+ của Puma luôn sẵn sàng cung cấp lớp đệm êm ái và thoải mái suốt cả ngày dài.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma Clyde Super',
                'pro_slug' => 'puma-clyde-super',
                'pro_code' => 'MSP493',
                'pro_price' => 2800000,
                'pro_price_sale' => 0,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/puma-Clyde-Super.jpg',
                'pro_description' => 'Super Puma xuất hiện lần đầu tiên vào đầu những năm 1960 và đã là người bạn đồng hành trung thành với thương hiệu Puma kể từ đó. 
                Clyde Super Puma nhằm tri ân linh vật của hãng Puma bằng phần trên hoàn toàn màu xanh lá cây và các chi tiết bằng da lộn có lông.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Campus Standard',
                'pro_slug' => 'adidas-campus-standard',
                'pro_code' => 'IE2217',
                'pro_price' => 3000000,
                'pro_price_sale' => 0,
                'capital_price' => 2400000,
                'pro_img' => 'backend/uploads/product/adidas-Campus-standard-2.jpg',
                'pro_description' => 'đôi giày adidas này giữ đúng phong cách mang tính biểu tượng của giày thể thao Campus nguyên bản. 
                Thiết kế của họ tuân theo các chi tiết trên chiếc quần jean yêu thích của bạn, chẳng hạn như đường khâu tương phản trên đầu và lỗ cúc trên cùng. 
                Hình dáng cắt thấp có vẻ ngoài gọn gàng được đánh dấu bằng 3 Sọc đặc trưng của chúng tôi. 
                Được làm bằng nhiều loại vật liệu tái chế, phần trên này có hàm lượng tái chế ít nhất 50%. Sản phẩm này chỉ là một trong những giải pháp của chúng tôi nhằm giúp chấm dứt rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Mũ Bóng Chày Adidas Pride',
                'pro_slug' => 'mu-bong-chay-adidas-pride',
                'pro_code' => 'IM1537',
                'pro_price' => 850000,
                'pro_price_sale' => 0,
                'capital_price' => 750000,
                'pro_img' => 'backend/uploads/product/adidas-mu-Bong-Chay-PRIDE-RM.jpg',
                'pro_description' => 'Hãy để tình yêu là di sản của bạn. Nhà thiết kế người Nam Phi Rich Mnisi đã từng viết lời nhắc nhở đó trong một bức thư viết tay cho người đồng tính trẻ tuổi của mình và ngày nay nó đã phát triển mạnh mẽ trong Bộ sưu tập adidas x Rich Mnisi Pride. 
                Tôn vinh sự thể hiện bản thân, trí tưởng tượng và niềm tin vững chắc rằng tình yêu gắn kết, sự hợp tác khám phá tính trôi chảy, màu sắc và hoa văn.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas NMD R1 V3',
                'pro_slug' => 'adidas-mnd-r1-v3',
                'pro_code' => 'HQ4275',
                'pro_price' => 4800000,
                'pro_price_sale' => 0,
                'capital_price' => 2850000,
                'pro_img' => 'backend/uploads/product/adidas-NMD-R1-V3.jpg',
                'pro_description' => ' Đôi giày adidas NMD_R1 V3 này phát triển cùng một ý tưởng, sử dụng chất liệu trong suốt. 
                Bạn có biết những miếng đệm đế giữa đặc trưng mà bạn yêu thích không? 
                Trên đây, TPU bán trong mờ đưa chúng đến tương lai. 
                Lớp đệm BOOST dưới chân mang đến cho bạn sự thoải mái mà bạn cần có vào bất kỳ ngày nào, bất kể có bất kỳ chuyển động xoay chuyển nào theo cách của bạn.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'NewBalance 2002R Deep Ocean Grey',
                'pro_slug' => 'newbalance-200r-deep-ocean-grey',
                'pro_code' => 'M2002RHC',
                'pro_price' => 4120000,
                'pro_price_sale' => 0,
                'capital_price' => 3200000,
                'pro_img' => 'backend/uploads/product/NewBalance-2002R-DEEP-OCEAN-GREY.jpg',
                'pro_description' => ' New Balance 2002R Deep Ocean Grey Slate có màu đá phiến, xám đại dương sâu và hỗn hợp màu trắng. 
                Giày sneaker có phần thân trên màu xám đại dương đậm với dây buộc màu trắng để buộc giày. 
                Ở bên cạnh, bạn sẽ tìm thấy một miếng lưới cũng như logo New Balance ở cả hai bên. 
                Ngoài ra, giày còn có đế cao su dày dặn với phần đế giữa màu trắng và phần đá phiến phía dưới.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'NewBalance CRT 300 2.0 Blue Aqua',
                'pro_slug' => 'newbalance-crt-300-2-blue-aqua',
                'pro_code' => 'SS0013519C',
                'pro_price' => 1200000,
                'pro_price_sale' => 0,
                'capital_price' => 945000,
                'pro_img' => 'backend/uploads/product/newbalance-crt-300-2-blue-aqua.jpg',
                'pro_description' => 'Đây là một trong những sản phẩm được ưa chuộng nhất của thương hiệu New Balance. 
                Được thiết kế với phong cách Retro và Vintage, giày sở hữu màu sắc xanh dương tươi mát, mang đến cho bạn nét đẹp và sang trọng.
                Chất liệu da cao cấp và đệm EVA đặc biệt cứng cáp, giúp giày có độ bền cao và đảm bảo độ thoải mái tối đa cho người sử dụng.
                Đế cao su cứng cáp, cứng nhắc, giúp bạn có trải nghiệm chạy bền bỉ hơn.
                Thiết kế đơn giản, dễ dàng kết hợp với nhiều phong cách thời trang khác nhau.
                Các chi tiết như logo, cổng lưới, đường chỉ viền, được thiết kế tinh tế, tạo nên sự hoàn hảo cho giày.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Ultraboost Light',
                'pro_slug' => 'adidas-ultraboost-light',
                'pro_code' => 'HQ6351',
                'pro_price' => 5200000,
                'pro_price_sale' => 3120000,
                'capital_price' => 2800000,
                'pro_img' => 'backend/uploads/product/adidas-ultraboost-light.jpg',
                'pro_description' => '
                    Adidas Ultraboost Light: Sự Kết Hợp Hoàn Hảo Giữa Thể Thao và Thời Trang.
                    Adidas Ultraboost Light, sản phẩm mới nhất từ thương hiệu thể thao hàng đầu thế giới, đưa bạn vào một hành trình hoàn hảo giữa phong cách và hiệu suất. Được thiết kế với sự tập trung vào sự thoải mái và độ nhẹ, giày thể thao Ultraboost Light là sự lựa chọn tuyệt vời cho cả việc chạy bộ và cuộc sống hàng ngày.
                    Siêu Nhẹ: Ultraboost Light sử dụng công nghệ tiên tiến để tạo ra một đôi giày siêu nhẹ, giúp bạn di chuyển dễ dàng và thoải mái suốt cả ngày.
                    Đệm Bounce: Công nghệ đệm Bounce mang lại cảm giác êm ái và sự đàn hồi tối đa, giúp bạn đạt được hiệu suất tối ưu trong mọi hoạt động thể thao.
                    Thiết Kế Thời Trang: Ultraboost Light có thiết kế hiện đại và thời trang với nhiều tùy chọn màu sắc, làm cho bạn luôn tỏa sáng trong từng bước đi.
                    Độ Bám: Đế ngoài của giày được thiết kế để cung cấp độ bám tốt trên mọi loại bề mặt, đảm bảo an toàn và ổn định khi bạn hoạt động.
                    Thích Hợp Cho Mọi Hoàn Cảnh: Adidas Ultraboost Light không chỉ phục vụ cho các buổi tập thể dục mà còn hoàn hảo cho việc di chuyển trong cuộc sống hàng ngày, từ công việc đến dạo chơi cùng bạn bè.
                    Khám phá sự kết hợp hoàn hảo giữa thể thao và thời trang với Adidas Ultraboost Light. Đặt hàng ngay hôm nay và trải nghiệm sự thoải mái và phong cách không giới hạn.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas ADIZERO ADIOS 8',
                'pro_slug' => 'adidas-adizero-adios-8',
                'pro_code' => 'HP9721',
                'pro_price' => 3500000,
                'pro_price_sale' => 0,
                'capital_price' => 2800000,
                'pro_img' => 'backend/uploads/product/adidas-adizero-adios-8.jpg',
                'pro_description' => '
                    Adidas Adizero Adios 8: Cuộc Cách Mạng Trong Giày Chạy Đường Dài
                    Adidas Adizero Adios 8 là sự hòa quyện hoàn hảo giữa công nghệ và thiết kế để mang lại hiệu 
                    suất tối ưu cho các vận động viên chạy đường dài. Với khả năng cân bằng giữa sự nhẹ nhàng và 
                    sự đáng tin cậy, đây là đôi giày hoàn hảo cho mọi người đang tìm kiếm sự nâng cao trong việc rèn luyện và thi đấu.
                    Công Nghệ BOOST: Sản phẩm Adios 8 sử dụng công nghệ đệm BOOST độc quyền của Adidas, giúp bạn trải nghiệm sự thoải mái và đàn hồi tối đa trong mỗi bước chạy.
                    Lớp Vỏ Chất Liệu Primeknit: Thiết kế với lớp vỏ Primeknit siêu nhẹ và thoáng khí, giúp bạn cảm thấy thoải mái và thông thoáng trong mọi điều kiện thời tiết.
                    Công Nghệ Torsion System: Đế giày được cung cấp bởi công nghệ Torsion System để đảm bảo sự ổn định và độ bám tốt, đặc biệt trong các cuộc đua đường dài.
                    Thiết Kế Thời Trang: Adios 8 có thiết kế thời trang và tinh tế, với nhiều tùy chọn màu sắc để bạn tỏa sáng trong cuộc đua.
                    Cảm Giác Tự Tin: Với Adidas Adizero Adios 8, bạn có thể tự tin vượt qua mọi thử thách, từ huấn luyện hàng ngày đến thi đấu quan trọng.
                    Khám phá sự thăng hoa trong việc chạy đường dài với Adidas Adizero Adios 8. Đặt hàng ngay hôm nay và trải nghiệm hiệu suất tối ưu và phong cách không giới hạn."
                    Mô tả này sử dụng từ khóa liên quan đến sản phẩm, cung cấp thông tin chi tiết và tạo ấn tượng về tính năng và lợi ích của sản phẩm "Adidas Adizero Adios 8.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Air Jordan 1 High OG UNC University Blue',
                'pro_slug' => 'nike-air-jordan-1-high-og-unc-university-blue',
                'pro_code' => 'NJ965UB',
                'pro_price' => 11500000,
                'pro_price_sale' => 10150000,
                'capital_price' => 9565000,
                'pro_img' => 'backend/uploads/product/nike-air-jordan-1-high-og-unc-university-blue.jpg',
                'pro_description' => '
                    Nike Air Jordan 1 High OG UNC University Blue: Sự Kết Hợp Đầy Phong Cách và Lịch Sử.
                    Nike Air Jordan 1 High OG UNC University Blue là một biểu tượng thời trang và thể thao, mang trong mình một phần của lịch sử bóng rổ và văn hóa thời trang. Được lấy cảm hứng từ trường đại học North Carolina (UNC) và thiết kế bởi ngôi sao bóng rổ Michael Jordan, đôi giày này là biểu tượng về sự thăng hoa và phong cách.
                    Màu Sắc UNC University Blue: Với sắc xanh đặc trưng của UNC, đôi giày này là một tuyên bố thời trang với sự kết hợp hoàn hảo giữa màu xanh và trắng.
                    Thiết Kế High OG: Sản phẩm này giữ nguyên thiết kế gốc của Air Jordan 1 High, với dây đeo cao và đế giày đầy thăng hoa.
                    Chất Liệu Premium: Được làm từ các chất liệu cao cấp như da và nubuck, đôi giày này đảm bảo sự thoải mái và độ bền.
                    Biểu Tượng Thể Thao và Thời Trang: Nike Air Jordan 1 High OG UNC University Blue không chỉ là một đôi giày thể thao, mà còn là một biểu tượng thời trang, thể hiện phong cách và cái tôi của bạn.
                    Kỷ Niệm Lịch Sử: Đôi giày này là một sự kỷ niệm đẹp về một thời kỳ lịch sử của bóng rổ và thương hiệu Nike.
                    Khám phá sự kết hợp đầy phong cách và lịch sử với Nike Air Jordan 1 High OG UNC University Blue. Đặt hàng ngay hôm nay và trải nghiệm sự thăng hoa và phong cách không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Downshifter 11 Venice CW3413-502 Màu Hồng',
                'pro_slug' => 'nike-downshifter-11-venice-cw3413-502-mau-hong',
                'pro_code' => 'H105223',
                'pro_price' => 2500000,
                'pro_price_sale' => 0,
                'capital_price' => 1800000,
                'pro_img' => 'backend/uploads/product/nike-downshifter-11-venice-cw3413-502-mau-hong.jpg',
                'pro_description' => '
                    Nike Downshifter 11 Venice CW3413-502 Màu Hồng: Sự Kết Hợp Hoàn Hảo Giữa Thể Thao và Thời Trang. 
                    Nike Downshifter 11 Venice CW3413-502 Màu Hồng mang đến cho bạn sự thoải mái và phong cách trong một đôi giày thể thao đầy năng động. Với thiết kế thời trang và màu hồng tươi sáng, đôi giày này thể hiện cá tính của bạn một cách tuyệt vời.
                    Thiết Kế Thời Trang: Nike Downshifter 11 Venice CW3413-502 với màu hồng nổi bật và thiết kế hiện đại, đây không chỉ là một đôi giày thể thao, mà còn là biểu tượng thời trang.
                    Sự Thoải Mái Quan Trọng: Với đệm mềm và êm ái, đôi giày này mang lại sự thoải mái cho cả những buổi tập luyện và mọi hoạt động hàng ngày.
                    Đế Giày Bền: Đế giày bền bỉ giúp đối mặt với mọi điều kiện đường phố và đảm bảo độ bền cao.
                    Tương Thích Với Mọi Loại Trang Phục: Màu hồng tươi sáng là một lựa chọn tuyệt vời để kết hợp với nhiều loại trang phục khác nhau, từ thể thao đến thời trang hàng ngày.
                    Làm Tôn Lên Phong Cách Cá Nhân: Nike Downshifter 11 Venice CW3413-502 Màu Hồng giúp bạn tỏa sáng và thể hiện phong cách cá nhân độc đáo.
                    Khám phá sự kết hợp hoàn hảo giữa thể thao và thời trang với Nike Downshifter 11 Venice CW3413-502 Màu Hồng. Đặt hàng ngay hôm nay để trải nghiệm sự thoải mái và phong cách không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma Evert Fs Casual Skate Red',
                'pro_slug' => 'puma-evert-fs-casual-skate-red',
                'pro_code' => 'H101601',
                'pro_price' => 2500000,
                'pro_price_sale' => 0,
                'capital_price' => 1880000,
                'pro_img' => 'backend/uploads/product/puma-evert-fs-casual-skate-red.jpg',
                'pro_description' => '
                    Puma Evert Fs Casual Skate Red: Phong Cách Đường Phố Tinh Tế
                    Puma Evert Fs Casual Skate Red là sự kết hợp hoàn hảo giữa phong cách đường phố và sự thoải mái. Với thiết kế tinh tế và màu đỏ nổi bật, đôi giày này là một biểu tượng của phong cách cá nhân và sự tự tin.
                    Thiết Kế Đường Phố: Với thiết kế casual skate, đôi giày này phù hợp cho cả các hoạt động đường phố và thời trang hàng ngày.
                    Màu Đỏ Nổi Bật: Màu đỏ tươi sáng làm cho đôi giày Puma Evert Fs nổi bật và tạo điểm nhấn cho trang phục của bạn.
                    Sự Thoải Mái Quan Trọng: Được thiết kế với sự thoải mái là ưu tiên hàng đầu, đôi giày này mang lại sự êm ái cho cả những cuộc phiêu lưu hàng ngày.
                    Độ Bền Cao: Đôi giày Puma Evert Fs được làm bằng các chất liệu chất lượng, đảm bảo độ bền và độ ổn định.
                    Kết Hợp Đa Dạng: Màu đỏ tươi sáng dễ dàng kết hợp với nhiều loại trang phục khác nhau, từ quần jean đến quần shorts.
                    Khám phá sự kết hợp độc đáo giữa phong cách đường phố và sự thoải mái với Puma Evert Fs Casual Skate Red. Đặt hàng ngay hôm nay để trải nghiệm phong cách cá nhân và sự tự tin không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma RSX Suede Vitruta',
                'pro_slug' => 'puma-rsx-suede-vitruta',
                'pro_code' => 'HSX1354',
                'pro_price' => 3500000,
                'pro_price_sale' => 0,
                'capital_price' => 2800000,
                'pro_img' => 'backend/uploads/product/puma-rsx-suede-vitruta.jpg',
                'pro_description' => '
                    Puma RSX Suede Vitruta là biểu tượng của sự đẳng cấp và phong cách trong thế giới thời trang. Với thiết kế bền bỉ và lớp vỏ da sang trọng, đôi giày này là sự kết hợp hoàn hảo giữa tiện ích và thẩm mỹ.
                    Da Suede Chất Lượng Cao: Với lớp vỏ da Suede chất lượng cao, đôi giày này có vẻ ngoại hình sang trọng và bền bỉ, đồng thời đảm bảo sự thoải mái.
                    Thiết Kế Hiện Đại: Thiết kế RSX Suede Vitruta thể hiện sự hiện đại và phong cách, đặc biệt là với sự kết hợp của màu sắc tinh tế.
                    Công Nghệ Đệm Cao Cấp: Đế giày được trang bị công nghệ đệm cao cấp giúp giảm áp lực và tạo sự thoải mái trong mọi bước đi.
                    Thích Hợp Cho Mọi Hoàn Cảnh: Puma RSX Suede Vitruta không chỉ phục vụ cho các hoạt động thể thao mà còn hoàn hảo cho cuộc sống hàng ngày, từ công việc đến dạo chơi.
                    Phong Cách Đa Dạng: Đôi giày này dễ dàng kết hợp với nhiều loại trang phục khác nhau, làm nổi bật phong cách của bạn.
                    Khám phá sự đẳng cấp và phong cách vượt thời gian với Puma RSX Suede Vitruta. Đặt hàng ngay hôm nay để trải nghiệm sự thoải mái và phong cách không giới hạn
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance Road Fuel Cell REBEL V2 WFCXLM2',
                'pro_slug' => 'new-balance-road-fuel-cell-rebel-v2-wfcxlm2',
                'pro_code' => 'WFCXLM2',
                'pro_price' => 3500000,
                'pro_price_sale' => 0,
                'capital_price' => 2850000,
                'pro_img' => 'backend/uploads/product/new-balance-road-fuel-cell-rebel-v2-wfcxlm2.jpg',
                'pro_description' => '
                    New Balance Road Fuel Cell REBEL: Đột Phá Vượt Thời Gian Cho Thế Giới Chạy Đường Dài.
                    New Balance Road Fuel Cell REBEL là một sự kết hợp đột phá giữa thiết kế hiện đại và hiệu suất cao, đem lại sự thoải mái và tốc độ cho những người yêu thích chạy đường dài.
                    Công Nghệ Fuel Cell: Được trang bị công nghệ Fuel Cell đỉnh cao của New Balance, đôi giày này mang lại độ đàn hồi và phản ứng vượt trội, giúp bạn chinh phục mọi quãng đường.
                    Thiết Kế Hiện Đại: Với thiết kế thời trang và đẳng cấp, đôi giày REBEL thể hiện sự hiện đại và phong cách tối ưu.
                    Lớp Upper Gọn Nhẹ: Lớp upper nhẹ và thoáng khí giúp cải thiện luồng không khí và thoải mái trong suốt chặng đường.
                    Cân Bằng Tốt: Đế giày thiết kế đặc biệt giúp duy trì cân bằng và độ ổn định trong mọi bước đi.
                    Thích Hợp Cho Cuộc Đua Và Huấn Luyện: Road Fuel Cell REBEL là sự lựa chọn lý tưởng cho cả cuộc đua và buổi huấn luyện hàng ngày, giúp bạn cải thiện hiệu suất chạy của mình.
                    Khám phá sự đột phá và hiệu suất vượt trội với New Balance Road Fuel Cell REBEL. Đặt hàng ngay hôm nay để trải nghiệm sự thoải mái và tốc độ không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance Proct Court Sky Blue',
                'pro_slug' => 'new-balance-proct-court-sky-blue',
                'pro_code' => 'PROCTC216',
                'pro_price' => 2500000,
                'pro_price_sale' => 0,
                'capital_price' => 1900000,
                'pro_img' => 'backend/uploads/product/new-balance-proct-court-sky-blue.jpeg',
                'pro_description' => '
                    New Balance Proct Court Sky Blue: Phong Cách Thời Trang và Thoải Mái   
                    New Balance Proct Court Sky Blue là sự kết hợp hoàn hảo giữa phong cách thời trang và sự thoải mái cho cuộc sống hàng ngày. Với màu xanh Sky Blue tươi sáng và thiết kế tối giản, đôi giày này thể hiện sự thanh lịch và đẳng cấp.
                    Màu Sắc Sky Blue: Màu xanh Sky Blue nổi bật làm cho đôi giày Proct Court trở thành điểm nhấn cho bất kỳ trang phục nào.
                    Thiết Kế Tối Giản: Thiết kế đơn giản nhưng thời trang của giày Proct Court tạo nên một phong cách lịch lãm và đẳng cấp.
                    Chất Liệu Chất Lượng: Được làm bằng chất liệu cao cấp, đôi giày này đảm bảo độ bền và thoải mái suốt cả ngày.
                    Đế Giày Bám Tốt: Đế giày được thiết kế để đảm bảo độ bám tốt trên mọi loại bề mặt, giúp bạn tự tin di chuyển.
                    Thích Hợp Cho Cuộc Sống Hàng Ngày: New Balance Proct Court Sky Blue không chỉ phục vụ cho thời trang mà còn hoàn hảo cho cuộc sống hàng ngày, từ công việc đến dạo chơi.
                    Khám phá sự kết hợp độc đáo giữa phong cách thời trang và sự thoải mái với New Balance Proct Court Sky Blue. Đặt hàng ngay hôm nay để trải nghiệm sự lịch lãm và đẳng cấp không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ Adidas Cush Ankle 1 Pair',
                'pro_slug' => 'vo-adidas-cush-ankle-1-pair',
                'pro_code' => 'DZ9367',
                'pro_price' => 150000,
                'pro_price_sale' => 0,
                'capital_price' => 80000,
                'pro_img' => 'backend/uploads/product/vo-adidas-cush-ankle-1-pair.jpeg',
                'pro_description' => '
                    Đôi tất dài tới mắt cá này có lớp đệm từ mũi tới gót chân mang đến cảm giác siêu thoải mái. Thiết kế nâng đỡ vòm bàn chân và đường may mượt mà ở phần mũi chân mang lại cảm giác vừa vặn thoải mái. Chất liệu chủ yếu làm từ cotton với logo adidas đậm chất thể thao.
                    Vớ Adidas Cush Ankle 1 Pair là sự kết hợp hoàn hảo giữa thoải mái và bảo vệ cho đôi chân của bạn. Với thiết kế chất lượng và công nghệ tiên tiến, đôi vớ này đem lại sự tự tin cho mọi hoạt động thể thao và hàng ngày.
                    Công Nghệ Cushioning: Được trang bị công nghệ đệm mềm, đôi vớ này giúp giảm áp lực và tạo sự thoải mái cho đôi chân trong suốt cả ngày.
                    Bảo Vệ Mắt Ngón Chân: Vớ Adidas Cush Ankle giữ cho ngón tay và bàn chân được bảo vệ tốt, đặc biệt khi tham gia vào các hoạt động thể thao hoặc hoạt động ngoài trời.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu cao cấp, đôi vớ này đảm bảo độ bền và thoải mái suốt thời gian sử dụng.
                    Thiết Kế Thời Trang: Thiết kế tinh tế và hiện đại giúp đôi vớ này phù hợp với nhiều loại giày và trang phục khác nhau.
                    Thích Hợp Cho Mọi Hoàn Cảnh: Vớ Adidas Cush Ankle 1 Pair là sự lựa chọn lý tưởng cho mọi hoạt động, từ thể thao đến hàng ngày.   
                    Khám phá sự kết hợp độc đáo giữa thoải mái và bảo vệ với Vớ Adidas Cush Ankle 1 Pair. Đặt hàng ngay hôm nay để trải nghiệm sự tự tin và thoải mái không giới hạn cho đôi chân của bạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ Adidas Solid Crew',
                'pro_slug' => 'vo-adidas-solid-crew',
                'pro_code' => 'S21490',
                'pro_price' => 400000,
                'pro_price_sale' => 0,
                'capital_price' => 285000,
                'pro_img' => 'backend/uploads/product/vo-adidas-solid-crew.jpeg',
                'pro_description' => '
                    Vớ Adidas Solid Crew mang đến sự thoải mái và phong cách cho cuộc sống hàng ngày và hoạt động thể thao. Với chất liệu chất lượng, thiết kế tối giản và thương hiệu uy tín, đôi vớ này thể hiện sự tinh tế và chất lượng đích thực.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu chất lượng cao, đôi vớ này đảm bảo độ bền và thoải mái suốt cả ngày.
                    Thiết Kế Tối Giản: Thiết kế đơn giản và tối giản làm cho đôi vớ này dễ dàng kết hợp với nhiều loại giày và trang phục khác nhau.
                    Công Nghệ Thoát Ẩm: Được trang bị công nghệ thoát ẩm giúp duy trì sự khô thoáng cho đôi chân trong mọi hoàn cảnh.
                    Phù Hợp Cho Mọi Hoạt Động: Vớ Adidas Solid Crew phù hợp cho cả hoạt động thể thao và cuộc sống hàng ngày, đảm bảo sự thoải mái và bảo vệ cho đôi chân.
                    Thương Hiệu Uy Tín: Adidas là một thương hiệu uy tín trong ngành thể thao, đảm bảo chất lượng và giá trị cho mỗi sản phẩm.
                    Khám phá sự kết hợp độc đáo giữa thể thao và phong cách với Vớ Adidas Solid Crew. Đặt hàng ngay hôm nay để trải nghiệm sự tinh tế và thoải mái không giới hạn cho đôi chân của bạn.
                    Vớ Adidas Solid Crew không chỉ là một sản phẩm thời trang mà còn là một phần quan trọng trong việc bảo vệ đôi chân của bạn. Thiết kế cao cổ của vớ Solid Crew cung cấp sự ấm áp và bảo vệ cho mắt cá và mắt cổ chân. Điều này đặc biệt quan trọng trong những ngày lạnh hoặc khi bạn tham gia vào các hoạt động thể thao cần sự ổn định và bảo vệ cho đôi chân.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ Thể Thao Nike Everyday Plus Cush',
                'pro_slug' => 'vo-the-thao-nike-everyday-plus-cush',
                'pro_code' => 'DH3827-902',
                'pro_price' => 539000,
                'pro_price_sale' => 0,
                'capital_price' => 469000,
                'pro_img' => 'backend/uploads/product/vo-the-thao-nike-everyday-plus-cush.jpeg',
                'pro_description' => '
                    Vớ Thể Thao Nike Everyday Plus Cush không chỉ đáp ứng nhu cầu hoạt động thể thao mà còn mang lại sự thoải mái cho cuộc sống hàng ngày. Với thiết kế đa dạng và công nghệ tiên tiến, đôi vớ này thể hiện sự tinh tế và sự chăm sóc cho đôi chân của bạn.
                    Công Nghệ Cushioning: Được trang bị công nghệ đệm mềm, đôi vớ này giúp giảm áp lực và tạo sự thoải mái cho đôi chân trong suốt cả ngày.
                    Thiết Kế Đa Dạng: Vớ Nike Everyday Plus Cush có nhiều lựa chọn màu sắc và kiểu dáng để phù hợp với phong cách cá nhân của bạn.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu cao cấp, đôi vớ này đảm bảo độ bền và thoải mái suốt thời gian sử dụng.
                    Thiết Kế Cao Cổ: Vớ có thiết kế cao cổ giúp bảo vệ mắt cá và mắt cổ chân, đặc biệt trong các hoạt động thể thao.
                    Thích Hợp Cho Mọi Hoàn Cảnh: Vớ Nike Everyday Plus Cush là sự lựa chọn lý tưởng cho cả thể thao và cuộc sống hàng ngày, đảm bảo sự thoải mái và bảo vệ cho đôi chân.
                    Vớ Thể Thao Nike Everyday Plus Cush là một sự đầu tư hoàn hảo cho sức khỏe và phong cách của bạn. Đôi vớ này không chỉ mang lại sự thoải mái tối ưu cho đôi chân trong mọi hoàn cảnh mà còn thể hiện phong cách cá nhân của bạn.
                    Với công nghệ đệm tiên tiến, vớ Nike Everyday Plus Cush giúp giảm mệt mỏi và áp lực cho đôi chân trong suốt cả ngày. Điều này làm cho chúng trở thành sự lựa chọn hoàn hảo cho cả những buổi tập luyện và cuộc sống hàng ngày.
                    Vớ Nike Everyday Plus Cush có nhiều màu sắc và kiểu dáng đa dạng để bạn có thể tạo ra phong cách riêng, phù hợp với mọi loại giày và trang phục. Đội vớ này, bạn sẽ cảm nhận sự khác biệt về sự thoải mái và bảo vệ đôi chân của mình.
                    Không chỉ đơn giản là một sản phẩm thể thao, Vớ Thể Thao Nike Everyday Plus Cush là một phần của cuộc sống của bạn, giúp bạn tỏa sáng trong mọi hoàn cảnh. Đừng bỏ lỡ cơ hội trải nghiệm sự thoải mái và phong cách với đôi vớ này. Hãy đặt hàng ngay hôm nay để thể hiện sự chăm sóc cho đôi chân và phong cách riêng của bạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ Nike Mulitplier No Show',
                'pro_slug' => 'vo-nike-mulitplier-no-show',
                'pro_code' => 'SX7554-938',
                'pro_price' => 489000,
                'pro_price_sale' => 0,
                'capital_price' => 380000,
                'pro_img' => 'backend/uploads/product/vo-nike-mulitplier-no-show.jpeg',
                'pro_description' => '
                    Vớ Nike Multiplier No Show: Sự Thoải Mái và Hiệu Quả Cho Đôi Chân Của Bạn
                    Vớ Nike Multiplier No Show là một lựa chọn hoàn hảo cho những người yêu thể thao và hoạt động hàng ngày. Với thiết kế không cổ, đôi vớ này mang lại sự thoải mái và hiệu quả cho đôi chân của bạn mà không cần phải lo lắng về việc chúng bị lộ ra ngoài.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu cao cấp, đôi vớ này đảm bảo độ bền và thoải mái suốt cả ngày.
                    Thiết Kế No Show: Thiết kế không cổ giúp đôi vớ này giữ nguyên dáng vẻ thời trang và không bị lộ ra ngoài khi bạn mặc giày thể thao hoặc giày thời trang.
                    Công Nghệ Đệm: Vớ Nike Multiplier No Show được trang bị công nghệ đệm giúp giảm áp lực và tạo sự thoải mái cho đôi chân trong suốt cả ngày.
                    Phù Hợp Cho Mọi Hoàn Cảnh: Đôi vớ này phù hợp cho cả thể thao và cuộc sống hàng ngày, đảm bảo sự thoải mái và bảo vệ cho đôi chân.
                    Thương Hiệu Uy Tín: Nike là một thương hiệu uy tín trong ngành thể thao, đảm bảo chất lượng và giá trị cho mỗi sản phẩm.
                    Khám phá sự kết hợp độc đáo giữa thoải mái và hiệu quả với Vớ Nike Multiplier No Show. Đặt hàng ngay hôm nay để trải nghiệm sự thoải mái và phong cách không giới hạn cho đôi chân của bạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ New Balance No Show Run Sock 3 Pair',
                'pro_slug' => 'vo-new-balance-no-show-run-sock-3-pair',
                'pro_code' => 'LAS44223BKW',
                'pro_price' => 595000,
                'pro_price_sale' => 0,
                'capital_price' => 480000,
                'pro_img' => 'backend/uploads/product/vo-new-balance-no-show-run-sock-3-pair.jpeg',
                'pro_description' => '
                    Vớ New Balance No Show Run Sock 3 Pair: Sự Thoải Mái Cho Cuộc Chạy Đỉnh Cao
                    Vớ New Balance No Show Run Sock 3 Pair là sự lựa chọn hoàn hảo cho những người yêu thích chạy và hoạt động thể thao. Với thiết kế không cổ, đôi vớ này mang lại sự thoải mái và hiệu quả cho đôi chân của bạn trong suốt cuộc chạy.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu cao cấp, đôi vớ này đảm bảo độ bền và thoải mái suốt cả quãng đường chạy dài.
                    Thiết Kế No Show: Thiết kế không cổ giúp đôi vớ này không bị lộ ra ngoài khi bạn mặc giày thể thao hoặc giày chạy.
                    Công Nghệ Đệm: Vớ New Balance No Show Run Sock được trang bị công nghệ đệm giúp giảm áp lực và tạo sự thoải mái cho đôi chân trong suốt cuộc chạy.
                    Phù Hợp Cho Chạy Đường Dài: Đôi vớ này là sự lựa chọn lý tưởng cho các vận động viên chạy đường dài và người yêu thích thể thao, đảm bảo sự thoải mái và hiệu quả.
                    Gói 3 Đôi: Với gói 3 đôi, bạn sẽ có đủ đôi vớ để chuẩn bị cho nhiều cuộc chạy và hoạt động thể thao.
                    Vớ New Balance No Show Run Sock 3 Pair giúp bạn tận hưởng cuộc chạy đỉnh cao với sự thoải mái và bảo vệ cho đôi chân. Đừng bỏ lỡ cơ hội trải nghiệm sự thoải mái và hiệu quả với đôi vớ này. Hãy đặt hàng ngay hôm nay để thể hiện sự quyết tâm trong mỗi bước chạy của bạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ New Balance Sneaker Fit No Show',
                'pro_slug' => 'vo-new-balance-sneaker-fit-no-show',
                'pro_code' => 'LAS82221PGM',
                'pro_price' => 195000,
                'pro_price_sale' => 0,
                'capital_price' => 82000,
                'pro_img' => 'backend/uploads/product/vo-new-balance-sneaker-fit-no-show.jpeg',
                'pro_description' => '
                    Vớ New Balance Sneaker Fit No Show: Sự Kết Hợp Hoàn Hảo Cho Sự Thoải Mái và Phong Cách.
                    Vớ New Balance Sneaker Fit No Show là sự lựa chọn lý tưởng cho những người yêu thích thời trang và thoải mái. Thiết kế không cổ của đôi vớ này giúp chúng không bị lộ ra ngoài khi bạn mặc giày thể thao hoặc giày sneaker, tạo nên sự sáng sủa và thời trang.
                    Chất Liệu Chất Lượng Cao: Được làm từ chất liệu cao cấp, đôi vớ này đảm bảo độ bền và thoải mái suốt cả ngày.
                    Thiết Kế No Show: Thiết kế không cổ giúp đôi vớ này giữ nguyên dáng vẻ thời trang và không bị lộ ra ngoài khi bạn mặc giày sneaker.
                    Công Nghệ Thoát Ẩm: Vớ New Balance Sneaker Fit No Show được trang bị công nghệ thoát ẩm giúp duy trì sự khô thoáng cho đôi chân trong mọi hoàn cảnh.
                    Phù Hợp Cho Mọi Loại Giày: Đôi vớ này phù hợp cho cả giày thể thao và giày sneaker, đảm bảo sự thoải mái và phong cách cho mọi trang phục.
                    Thương Hiệu Uy Tín: New Balance là một thương hiệu uy tín trong ngành thể thao, đảm bảo chất lượng và giá trị cho mỗi sản phẩm.
                    Khám phá sự kết hợp độc đáo giữa phong cách và thoải mái với Vớ New Balance Sneaker Fit No Show. Đặt hàng ngay hôm nay để trải nghiệm sự sáng sủa và thoải mái không giới hạn cho đôi chân của bạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dây giày Blank Shoelaces Black',
                'pro_slug' => 'day-giay-blank-shoelaces-black',
                'pro_code' => 'ALB008',
                'pro_price' => 35000,
                'pro_price_sale' => 0,
                'capital_price' => 20000,
                'pro_img' => 'backend/uploads/product/day-giay-blank-shoelaces-black.jpeg',
                'pro_description' => '
                    Dây Giày Blank Shoelaces Black: Phong Cách Tinh Tế Và Đa Dụng
                    Dây Giày Blank Shoelaces Black là phụ kiện hoàn hảo để thêm một chút phong cách và cá tính vào đôi giày của bạn. Màu sắc đen cổ điển của dây giày này rất đa dụng và có thể được sử dụng để nâng cấp diện mạo của nhiều loại giày, từ giày sneakers đến giày công sở.
                    Màu Sắc Đen Cổ Điển: Màu đen không bao giờ lỗi mốt và tạo nên một chút sự tinh tế cho đôi giày của bạn, phù hợp cho cả các dịp thông thường và lễ trang trọng.
                    Chất Liệu Bền Bỉ: Dây giày này được làm từ chất liệu chất lượng và bền bỉ, đảm bảo chúng có thể chịu được sự mài mòn hàng ngày.
                    Nhiều Chiều Dài Khác Nhau: Có sẵn trong nhiều chiều dài khác nhau, bạn có thể lựa chọn kích thước phù hợp nhất cho đôi giày và sở thích cá nhân của bạn.
                    Dễ Lắp Đặt: Dây giày Blank Shoelaces Black dễ dàng lắp đặt và có thể thay thế dây cũ hoặc hỏng trên giày.
                    Sử Dụng Đa Năng: Sử dụng dây giày màu đen này để nâng cấp diện mạo của giày sneakers yêu thích, giày thể thao, giày boots hoặc bất kỳ loại giày nào cần một sự cập nhật phong cách.
                    Dây Giày Blank Shoelaces Black cho phép bạn tùy chỉnh và làm mới diện mạo của giày của bạn, tạo nên một phong cách riêng và thể hiện tính cá nhân. Đừng bỏ lỡ cơ hội tạo điểm nhấn cho đôi giày của bạn và đặt hàng ngay hôm nay để thể hiện sự tinh tế và phong cách không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 8,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dây giày Blank Shoelaces Carrot',
                'pro_slug' => 'day-giay-blank-shoelaces-carrot',
                'pro_code' => 'ALB004',
                'pro_price' => 35000,
                'pro_price_sale' => 0,
                'capital_price' => 22000,
                'pro_img' => 'backend/uploads/product/day-giay-blank-shoelaces-carrot.jpg',
                'pro_description' => '
                    Dây Giày Blank Shoelaces Carrot: Sự Tươi Mới và Phong Cách Độc Đáo
                    Dây Giày Blank Shoelaces Carrot mang đến sự tươi mới và phong cách độc đáo cho đôi giày của bạn. Với màu sắc giống màu cà rốt, dây giày này tạo điểm nhấn độc đáo và sáng sủa cho bất kỳ loại giày nào.
                    Màu Sắc Cà Rốt: Màu cà rốt tươi sáng làm cho đôi giày của bạn nổi bật và thể hiện sự độc đáo.
                    Chất Liệu Bền Bỉ: Dây giày này được làm từ chất liệu chất lượng và bền bỉ, đảm bảo chúng có thể chịu được sự mài mòn hàng ngày.
                    Nhiều Chiều Dài Khác Nhau: Có sẵn trong nhiều chiều dài khác nhau, bạn có thể lựa chọn kích thước phù hợp nhất cho đôi giày và sở thích cá nhân của bạn.
                    Dễ Lắp Đặt: Dây giày Blank Shoelaces Carrot dễ dàng lắp đặt và có thể thay thế dây cũ hoặc hỏng trên giày.
                    Sử Dụng Đa Năng: Sử dụng dây giày màu cà rốt này để tạo sự tươi mới cho giày sneakers, giày thể thao, giày boots hoặc bất kỳ loại giày nào cần một chút màu sắc thú vị.
                    Dây Giày Blank Shoelaces Carrot cho phép bạn thể hiện sự sáng sủa và sáng tạo thông qua đôi giày của bạn. Đừng bỏ lỡ cơ hội tạo điểm nhấn phong cách với dây giày này. Hãy đặt hàng ngay hôm nay để thể hiện sự độc đáo và phong cách không giới hạn.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 8,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dung dịch vệ sinh giày Sneaker Cleaner (50ml)',
                'pro_slug' => 'dung-dich-ve-sinh-giay-sneaker-cleaner-50ml',
                'pro_code' => 'LABCLNR050',
                'pro_price' => 179000,
                'pro_price_sale' => 0,
                'capital_price' => 145000,
                'pro_img' => 'backend/uploads/product/dung-dich-ve-sinh-giay-sneaker-cleaner-50ml.jpeg',
                'pro_description' => '
                    Sneaker Cleaner là một sản phẩm tuyệt vời để bảo quản và làm sạch đôi giày yêu thích của bạn. Với công thức phân hủy sinh học độc quyền, dung dịch này dễ dàng sử dụng, cũng như giúp bạn loại bỏ mọi bụi bẩn và vết ố trên bề mặt giày một cách nhanh chóng và hiệu quả nhất.
                    Dung Dịch Vệ Sinh Giày Sneaker Cleaner là sản phẩm chất lượng cao giúp bảo quản và làm sạch đôi giày sneaker của bạn một cách dễ dàng. Với công thức độc đáo, sản phẩm này giúp giữ cho giày luôn mới mẻ và sạch sẽ.
                    Thương hiệu: Sneaker Lab
                    Xuất xứ: Nam Phi
                    Chất liệu chai: Nhựa polyetylen (HDPE) mật độ cao
                    Dung tích: 50ml
                    Công dụng:
                    Công thức phân hủy sinh học độc quyền, cải tiến mới
                    Làm sạch chuyên sâu, giúp giày sạch lâu hơn
                    Không độc hại, không gây cháy nổ và bào mòn
                    Dễ dàng sử dụng cho hầu hết mọi loại chất liệu: da, da lộn, canvas...
                    Thành phần thân thiện với môi trường, an toàn cho sức khỏe
                    Thích hợp mang theo khi đi du lịch, sử dụng tại nhà, văn phòng.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 9,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dung dịch vệ sinh giày chuyên dụng Crep Protect Cure Refill',
                'pro_slug' => 'dung-dich-ve-sinh-giay-chuyen-dung-crep-protect-cure-refill',
                'pro_code' => 'CPCR200ML',
                'pro_price' => 420000,
                'pro_price_sale' => 0,
                'capital_price' => 320000,
                'pro_img' => 'backend/uploads/product/dung-dich-ve-sinh-giay-chuyen-dung-crep-protect-cure-refill.jfif',
                'pro_description' => '
                    Dung Dịch Vệ Sinh Giày Chuyên Dụng Crep Protect Cure Refill: Sự Lựa Chọn Tối Ưu Cho Bảo Quản và Làm Sạch Đôi Giày Yêu Thích
                    Dung Dịch Vệ Sinh Giày Chuyên Dụng Crep Protect Cure Refill là sản phẩm hàng đầu giúp bạn bảo quản và làm sạch đôi giày một cách chuyên nghiệp. Được phát triển đặc biệt cho giày sneakers và giày thể thao, sản phẩm này đảm bảo rằng đôi giày của bạn luôn giữ được vẻ mới mẻ và bền bỉ.
                    Hiệu Quả Vượt Trội: Crep Protect Cure Refill có khả năng loại bỏ mọi vết bẩn, dầu mỡ, và bụi bẩn trên giày, để chúng trở nên sạch sẽ và như mới.
                    Bảo Vệ Chất Liệu: Sản phẩm này không gây hại cho chất liệu của giày, bảo vệ chúng khỏi tác động của thời tiết và môi trường.
                    Dễ Sử Dụng: Với hướng dẫn sử dụng chi tiết, bạn có thể làm sạch đôi giày một cách dễ dàng và hiệu quả.
                    Phù Hợp Cho Mọi Loại Giày: Crep Protect Cure Refill thích hợp cho nhiều loại giày, bao gồm sneakers, giày thể thao, giày da, và nhiều loại khác.
                    Bảo Quản Đôi Giày Yêu Thích: Dung dịch này giúp bảo quản và kéo dài tuổi thọ của đôi giày yêu thích của bạn.
                    Dung Dịch Vệ Sinh Giày Chuyên Dụng Crep Protect Cure Refill là lựa chọn hàng đầu để đảm bảo đôi giày của bạn luôn giữ vẻ mới mẻ và bền bỉ. Đừng để bất kỳ vết bẩn nào làm mất đi vẻ đẹp của giày. Hãy đặt hàng ngay hôm nay để bảo quản và làm sạch đôi giày của bạn một cách chuyên nghiệp.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 9,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Dung Dịch Vệ Sinh Giày Shucare Sneaker Shampoo 85ml',
                'pro_slug' => 'dung-dich-ve-sinh-giay-shucare-sneaker-shampoo-85ml',
                'pro_code' => 'CPCARE629',
                'pro_price' => 89000,
                'pro_price_sale' => 0,
                'capital_price' => 42000,
                'pro_img' => 'backend/uploads/product/dung-dich-ve-sinh-giay-shucare-sneaker-shampoo-85ml.jpeg',
                'pro_description' => '
                    Dung Dịch Vệ Sinh Giày Shucare Sneaker Shampoo 85ml: Bí Quyết Bảo Quản Và Làm Sạch Đôi Giày Hoàn Hảo
                    Dung Dịch Vệ Sinh Giày Shucare Sneaker Shampoo 85ml là sản phẩm chất lượng hàng đầu giúp bạn bảo quản và làm sạch đôi giày của mình một cách tối ưu. Được phát triển đặc biệt cho giày sneakers và giày thể thao, sản phẩm này đảm bảo rằng đôi giày của bạn luôn giữ được vẻ mới mẻ và sạch sẽ.
                    Hiệu Quả Làm Sạch: Shucare Sneaker Shampoo có khả năng loại bỏ mọi vết bẩn, dầu mỡ, và bụi bẩn trên giày, để chúng trở nên sạch sẽ và như mới.
                    Bảo Vệ Chất Liệu: Sản phẩm này không gây hại cho chất liệu của giày, bảo vệ chúng khỏi tác động của thời tiết và môi trường.
                    Dễ Sử Dụng: Với hướng dẫn sử dụng chi tiết, bạn có thể làm sạch đôi giày một cách dễ dàng và hiệu quả.
                    Phù Hợp Cho Mọi Loại Giày: Shucare Sneaker Shampoo thích hợp cho nhiều loại giày, bao gồm sneakers, giày thể thao, giày da, và nhiều loại khác.
                    Kích Thước Tiện Lợi: Với dung tích 85ml, sản phẩm này tiện lợi để mang theo khi bạn cần làm sạch giày khi đi ra ngoài hoặc đi du lịch.
                    Dung Dịch Vệ Sinh Giày Shucare Sneaker Shampoo 85ml là lựa chọn hàng đầu để đảm bảo đôi giày của bạn luôn giữ vẻ mới mẻ và bền bỉ. Đừng để bất kỳ vết bẩn nào làm mất đi vẻ đẹp của giày. Hãy đặt hàng ngay hôm nay để bảo quản và làm sạch đôi giày của bạn một cách chuyên nghiệp.
                ',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 9,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Ultraboost 21',
                'pro_slug' => 'adidas-ultraboost-21',
                'pro_code' => 'EG2389',
                'pro_price' => 2200000,
                'pro_price_sale' => 1899000,
                'capital_price' => 1800000,
                'pro_img' => 'backend/uploads/product/adidas-ultraboost-21.jpg',
                'pro_description' => 'ĐÔI GIÀY CÙNG BẠN KHÁM PHÁ MỖI NGÀY, CÓ SỬ DỤNG CHẤT LIỆU TÁI CHẾ.
                    Từ quán cà phê đến hành trình di chuyển hàng ngày cho tới CLB dance, đôi giày adidas X_PLR Boost sẽ thổi bùng năng lượng cho từng sải bước của bạn suốt cả ngày. Đó là vì giày có đế giữa BOOST đàn hồi. Dùng hết bao nhiêu năng lượng, bạn sẽ nhận lại bấy nhiêu. Là phiên bản mới mẻ của phong cách quen thuộc, đôi giày này có thân giày bằng vải lưới đặt bên trong khung giày đặc trưng với hiệu ứng sờn bạc.
                    Làm từ một loạt chất liệu tái chế, thân giày có chứa tối thiểu 50% thành phần tái chế. Sản phẩm này đại diện cho một trong số rất nhiều các giải pháp của chúng tôi hướng tới chấm dứt rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma RS-X Cubed',
                'pro_slug' => 'puma-rs-x-cubed',
                'pro_code' => '369850',
                'pro_price' => 1500000,
                'pro_price_sale' => 1299000,
                'capital_price' => 1300000,
                'pro_img' => 'backend/uploads/product/puma-rs-x-cubed.jpg',
                'pro_description' => 'PUMA RS-X Mix ‘White Angel Blue’ (Mã sản phẩm: 380462-02) là một phiên bản đặc biệt của dòng giày PUMA RS-X Mix, với thiết kế màu “White Angel Blue” (màu trắng và xanh dương), tạo nên vẻ ngoại hình thể thao và phong cách.
                    
                    Mô tả chi tiết:
                    
                    1. Thiết kế: Giày PUMA RS-X Mix ‘White Angel Blue’ có kiểu dáng thể thao với phần trên được làm từ chất liệu da tổng hợp và vải, tạo sự bền bỉ và thoải mái cho chân. Thiết kế của giày thường có các chi tiết đặc trưng như lỗ thoáng khí và đường may tạo điểm nhấn.
                    
                    2. Màu sắc: Màu “White Angel Blue” kết hợp giữa các gam màu trắng và xanh dương, tạo nên vẻ ngoại hình trẻ trung và phá cách.
                    
                    3. Đế giày: Đế giày PUMA RS-X Mix thường được thiết kế để cung cấp độ bám tốt và thoải mái cho bàn chân.
                    
                    4. Công nghệ và tính năng: Đôi giày có thể được trang bị các công nghệ và tính năng đặc biệt để hỗ trợ và tối ưu hóa hiệu suất trong các hoạt động thể thao và thời trang.
                    
                    5. Thương hiệu: PUMA là một thương hiệu thể thao nổi tiếng với nhiều năm kinh nghiệm trong việc sản xuất giày thể thao và thời trang.
                    
                    PUMA RS-X Mix ‘White Angel Blue’ 380462-02 là một lựa chọn tuyệt vời cho những người yêu thích thể thao và muốn thể hiện phong cách thời trang thông qua giày. Để biết thêm thông tin chi tiết và hình ảnh, bạn có thể kiểm tra từ các nguồn tin cậy hoặc trang web chính thức của PUMA.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'GIÀY ULTRABOOST LIGHT',
                'pro_slug' => 'giay-ultraboost-light',
                'pro_code' => '569850',
                'pro_price' => 2550000,
                'pro_price_sale' => 1999000,
                'capital_price' => 1300000,
                'pro_img' => 'backend/uploads/product/giay-ultraboost-light.jpg',
                'pro_description' => 'ĐÔI GIÀY CHẠY BỘ SIÊU NHẸ CÓ SỬ DỤNG SỢI PARLEY OCEAN PLASTIC.
                    Hãy mang đôi giày chạy bộ Ultraboost Light và trải nghiệm khả năng hoàn trả năng lượng chưa từng có. Giày có đế giữa Light BOOST, thế hệ mới của đệm BOOST và cũng là phiên bản nhẹ nhất từ trước đến nay, giúp bạn vững bước trên mọi cự ly và tốc độ. Hệ thống Linear Energy Push tích hợp ở đế ngoài giúp tăng cường ổn định, cùng thân giày adidas PRIMEKNIT ôm chân cho sải bước mượt mà, đàn hồi.
                    Thân giày làm từ loại sợi hiệu năng cao có chứa tối thiểu 50% chất liệu Parley Ocean Plastic — rác thải nhựa tái chế thu gom từ các vùng đảo xa, bãi biển, khu dân cư ven biển và đường bờ biển, nhằm ngăn chặn ô nhiễm đại dương. 50% thành phần còn lại của sợi là polyester tái chế.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'COURTJAM CONTROL M',
                'pro_slug' => 'courtjam-control-m',
                'pro_code' => '2514850',
                'pro_price' => 3950000,
                'pro_price_sale' => 3299000,
                'capital_price' => 2500000,
                'pro_img' => 'backend/uploads/product/courtjam-control-m.jpg',
                'pro_description' => '
                    ĐÔI GIÀY Quần vợt 80 CLASSIC HỢP TÁC THIẾT KẾ CÙNG HỌA SĨ YEO KAA.
                    Là một thiết kế classic thập niên 80, dòng giày adidas Rivalry nhanh chóng vượt ra khỏi sân bóng rổ đến với sân trượt ván cũng như đường phố. Ở phiên bản này, dòng giày biểu tượng trở thành phông nền tôn lên lối vẽ cách điệu tươi sáng của họa sĩ Philippines Yeo Kaa. Đôi giày này ngập tràn những sắc màu rực rỡ và nổi bật với những hình vẽ đặc trưng của nữ họa sĩ.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma Jada Renew',
                'pro_slug' => 'puma-jada-renew',
                'pro_code' => '1524850',
                'pro_price' => 2950000,
                'pro_price_sale' => 1999000,
                'capital_price' => 1800000,
                'pro_img' => 'backend/uploads/product/puma-jada-renew.jpg',
                'pro_description' => 'Bộ sưu tập dòng giày sneakers Jada Renew chính là một tác phẩm nghệ thuật lấy cảm hứng từ môn thể thao quần vợt. Bên cạnh việc Puma tận dụng sự hoàn hảo trong thiết kế để mang đến một đôi giày vừa thời thượng, vừa thoải mái, Jada Renew còn sở hữu ngoại hình vượt trội để tạo nên những điểm nhấn độc đáo, kết hợp cùng lớp đệm Softfoam+ ở phần đế, item này hứa hẹn sẽ giúp bạn nâng tầm phong cách cá nhân một cách nổi bật nhất. ',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Vớ unisex Sneaker Cushioned',
                'pro_slug' => 'vo-unisex-sneaker-cushioned',
                'pro_code' => '1521450',
                'pro_price' => 150000,
                'pro_price_sale' => 149900,
                'capital_price' => 149000,
                'pro_img' => 'backend/uploads/product/vo-unisex-sneaker-cushioned.jpg',
                'pro_description' => 'Tăng cường sự thoải mái với chiếc vớ Sneaker Cushioned. Được làm từ chất vải mềm mịn và khả năng hút ẩm tốt, sản phẩm này sẽ mang lại một cảm giác dễ chịu, thoải mái, đồng thời bảo vệ đôi chân và cho phép bạn thỏa sức vận động mà không gặp bất kỳ trở ngại nào. ',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'GRAND COURT 2.0 SHOES',
                'pro_slug' => 'grand-court-2.0shoes',
                'pro_code' => '5141450',
                'pro_price' => 2500000,
                'pro_price_sale' => 2499000,
                'capital_price' => 2490000,
                'pro_img' => 'backend/uploads/product/grand-court-2.0shoes.jpg',
                'pro_description' => 'ĐÔI GIÀY BIỂU TƯỢNG CÓ SỬ DỤNG CHẤT LIỆU TÁI CHẾ.
                    Mang trên mình một biểu tượng vượt thời gian và biến tấu thành của riêng bạn với đôi giày này. Với phong cách đúng chuẩn adidas, đôi giày này có kiểu dáng thuôn gọn kinh điển, tôn lên mọi outfit. 3 Sọc khâu dọc hai bên trung thành theo vẻ ngoài nguyên bản. Đế giữa Cloudfoam tạo cảm giác êm ái vượt trội để bạn luôn cảm thấy dễ chịu suốt ngày dài.
                    Làm từ một loạt chất liệu tái chế, thân giày có chứa tối thiểu 50% thành phần tái chế. Sản phẩm này đại diện cho một trong số rất nhiều các giải pháp của chúng tôi hướng tới chấm dứt rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'GRAND COURT CLOUDFOAM LIFESTYLE COURT COMFORT SHOES',
                'pro_slug' => 'grand-court-cloudfoam-lifestyle-court-comfort-shoes',
                'pro_code' => '5141455',
                'pro_price' => 2500000,
                'pro_price_sale' => 2499000,
                'capital_price' => 2490000,
                'pro_img' => 'backend/uploads/product/grand-court-cloudfoam-lifestyle-court-comfort-shoes.jpg',
                'pro_description' => 'ĐÔI GIÀY BIỂU TƯỢNG CÓ SỬ DỤNG CHẤT LIỆU TÁI CHẾ.
                    Mang trên mình một biểu tượng vượt thời gian và biến tấu thành của riêng bạn với đôi giày này. Với phong cách đúng chuẩn adidas, đôi giày này có kiểu dáng thuôn gọn kinh điển, tôn lên mọi outfit. 3 Sọc khâu dọc hai bên trung thành theo vẻ ngoài nguyên bản. Đế giữa Cloudfoam tạo cảm giác êm ái vượt trội để bạn luôn cảm thấy dễ chịu suốt ngày dài.
                    Làm từ một loạt chất liệu tái chế, thân giày có chứa tối thiểu 50% thành phần tái chế. Sản phẩm này đại diện cho một trong số rất nhiều các giải pháp của chúng tôi hướng tới chấm dứt rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'GIÀY SUPERSTAR HANAMI',
                'pro_slug' => 'giay-superstar-hanami',
                'pro_code' => 'SPHM45625',
                'pro_price' => 2600000,
                'pro_price_sale' => 2599000,
                'capital_price' => 2590000,
                'pro_img' => 'backend/uploads/product/giay-superstar-hanami.jpg',
                'pro_description' => '
                    ĐÔI GIÀY ADIDAS SUPERSTAR LẤY CẢM HỨNG TỪ VẺ ĐẸP CỦA HOA ANH ĐÀO NHẬT BẢN.
                    Nếu đã từng ngắm hoa anh đào Nhật Bản nở rộ, hẳn bạn cũng thấy được thiên nhiên tráng lệ đến dường nào. Đôi giày adidas này bắt trọn vẻ đẹp mong manh của Hanami, truyền thống ngắm hoa anh đào vào đúng mùa nở rộ. Sử dụng một kỹ thuật phản ứng với ánh sáng UV, các bông hoa anh đào thêu nổi sẽ khó thấy hơn khi ở trong nhà — nhưng hãy bước ra ngoài trời và ngắm nhìn những bông hoa trên thân giày từ từ chuyển sang màu hồng. Mỗi lần mang giày là bạn đang ngợi ca Sakura — những cây hoa anh đào xinh đẹp. Hai tùy chọn dây giày giúp bạn biến hóa phong cách.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'GIÀY SUPERSTAR',
                'pro_slug' => 'giay-superstar-original',
                'pro_code' => 'SMST551499',
                'pro_price' => 3690000,
                'pro_price_sale' => 3299000,
                'capital_price' => 2290000,
                'pro_img' => 'backend/uploads/product/giay-superstar-original.jpg',
                'pro_description' => 'ĐÔI GIÀY ADIDAS SUPERSTAR NỔI BẬT PHỦ HỌA TIẾT RẰN RI.
                    Họa tiết rằn ri đã trở thành một thiết kế thời trang chủ đạo suốt nhiều thập kỷ. Xuất hiện trên mọi item từ đồ bơi đến áo khoác nỉ, họa tiết vốn chú trọng tính thiết thực nay đại diện cho sức mạnh và sự bảo vệ. Giờ đây, với đôi giày adidas Superstar đặc trưng và đầy ấn tượng này, hai phong cách chủ đạo kết hợp tạo nên một thiết kế nổi bật. Họa tiết rằn ri phủ toàn bộ thân giày bằng chất liệu tổng hợp với điểm nhấn là mũi giày vỏ sò bằng cao su classic. 3 Sọc răng cưa và logo Ba Lá trên viền gót giày tương phản tạo điểm nhấn đậm chất di sản. Bất kể kết hợp với quần short túi hộp hay quần track pant, đôi giày táo bạo này chắc chắn sẽ giúp bạn nổi bật.',
                'pro_views' => 0,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(),
                'updated_at' => Now(),
            ],
            
            [
                'pro_name' => 'Adidas Forum Low Pride RM',
                'pro_slug' => 'adidas-forum-low-pride-rm',
                'pro_code' => 'ID7491',
                'pro_price' => 3500000,
                'pro_price_sale' => 0,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/adidas-Forum-Low-PRIDE-RM-DJen-4.jpg',
                'pro_description' => 'ĐÔI GIÀY PHONG CÁCH BÓNG RỔ HỢP TÁC CÙNG NHÀ THIẾT KẾ RICH MNISI.
                Biến tình yêu thành di sản. Nhà thiết kế Nam Phi Rich Mnisi từng viết như vậy trong một lá thư tay gửi bản thân mình ngày nhỏ là một cậu bé queer, và giờ đây lời nhắn nhủ ấy trở thành thông điệp chính của bộ sưu tập adidas x Rich Mnisi Pride. Tôn vinh tinh thần biểu đạt bản thân, trí tưởng tượng và niềm tin không gì lay chuyển rằng yêu thương hợp nhất chúng ta, màn hợp tác này khai thác các yếu tố linh hoạt, màu sắc và họa tiết. 
                Dự án hợp tác này là một phần trong nỗ lực của chúng tôi nhằm tôn vinh cộng đồng LGBTQIA+ cùng đối tác Global Purpose là Athlete Ally. Quay trở lại thập niên 80 với tất cả các chi tiết adidas Forum vốn đã làm nên huyền thoại cho đôi giày này. Thiết kế cổ chân đan chéo và đế ngoài siêu bám đúng chuẩn classic. Các điểm nhấn sắc màu đến từ nhà thiết kế định hình xu hướng Rich Mnisi thổi thêm hơi thở hiện đại. Đã đến giờ cất cánh.',
                'pro_views' => 190,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Air Zoom SuperRep 3 Premium',
                'pro_slug' => 'nike-air-zoom-superrep-3-premium',
                'pro_code' => 'DM0334-555',
                'pro_price' => 4690000,
                'pro_price_sale' => 0,
                'capital_price' => 4300000,
                'pro_img' => 'backend/uploads/product/nike-air-zoom-superrep-3-premium.jpg',
                'pro_description' => 'Sức mạnh thông qua mỗi lần đại diện trong Nike Air Zoom SuperRep 3 Premium, được thiết kế lại để mang lại sự hỗ trợ và ổn định cho mỗi bước di chuyển của bạn. Nó nhẹ hơn các phiên bản trước, vì vậy bạn có thể đạt được tốc độ nhanh nhất của mình trong quá trình tập luyện mạch và HIIT. Với đệm Zoom Air và sự linh hoạt dưới bàn chân, nó giúp bạn luôn sẵn sàng cho mọi động tác nhảy, bước và nhảy.                ',
                'pro_views' => 90,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Puma Mayze Artisan',
                'pro_slug' => 'puma-mayze-artisan',
                'pro_code' => '391086-01',
                'pro_price' => 2500000,
                'pro_price_sale' => 0,
                'capital_price' => 1700000,
                'pro_img' => 'backend/uploads/product/puma-Mayze-Artisan-2.jpg',
                'pro_description' => 'Hòa mình vào phong cách đường phố sắc sảo với Mayze Classic, nổi bật với sự kết hợp bùng nổ giữa vẻ quyến rũ thành thị và phong cách thể thao sang trọng. 
                Đế xếp chồng lên nhau dày dặn và thân trên bằng da thật mang đến cảm giác mạnh mẽ cho những đôi giày thể thao sành điệu trên đường phố này, trong khi các chi tiết kim loại dập nổi mang lại chút quyến rũ. 
                Các chi tiết tỉ mỉ như lớp hoàn thiện lỗ giày bóng loáng và vòng vải ở gót để dễ dàng mang vào và tháo ra sẽ tạo thêm nét hoàn thiện cho thiết kế đúng điểm này.',
                'pro_views' => 60,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            
            [
                'pro_name' => 'Giày Thể Thao Puma Erupt Trail Running Shoes 193152_02 Phối Màu',
                'pro_slug' => 'giay-the-thao-puma-erupt-trail-running-shoes-193152-02-phoi-mau',
                'pro_code' => 'h102452',
                'pro_price' => 1900000,
                'pro_price_sale' => 1500000,
                'capital_price' => 1300000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-puma-erupt-trail-running-shoes-193152_02-phoi-mau-65028cc22c52a-14092023113202.jpg',
                'pro_description' => 'Giày thể thao Puma Erupt Trail Running Shoes 193152_02 Phối Màu là một sản phẩm thể thao của Puma với điểm nổi bật là sự kết hợp tinh tế của các màu sắc, thiết kế đẹp mắt và tính năng phù hợp cho hoạt động chạy trên địa hình khó khăn.',
                'pro_views' => 150,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Puma RS X3 Unity 373308-01 Phối Màu',
                'pro_slug' => 'giay-the-thao-puma-rs-x3-unity-373308-01-phoi-mau',
                'pro_code' => 'h067125',
                'pro_price' => 2750000,
                'pro_price_sale' => 2300000,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-puma-rs-x3-unity-373308-01-phoi-mau-size-36-63969cf3c9af2-12122022101603.jpg',
                'pro_description' => 'Giày thể thao Puma Cali Space Girls Sneakers JR 373467-01 là một sản phẩm thời trang thể thao dành riêng cho nữ giới, nổi bật với thiết kế tươi sáng, sáng tạo và đa dạng màu sắc',
                'pro_views' => 150,
                'pro_hot' => 1,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Puma Slipstream Suede Fs 388634 03',
                'pro_slug' => 'giay-the-thao-puma-slipstream-suede-fs-388634-03',
                'pro_code' => 'h090113',
                'pro_price' => 2900000,
                'pro_price_sale' => 2350000,
                'capital_price' => 1950000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-puma-slipstream-suede-fs-388634-03-mau-xanh-trang-6474121b98f7f-29052023094651.jpg',
                'pro_description' => 'Giày thể thao Puma Slipstream Suede Fs 388634 03 là một sản phẩm thể thao đẳng cấp của Puma với thiết kế lôi cuốn và sự kết hợp của vật liệu chất lượng.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Nam Nike Air Force 1 07 Men Shoe DM2845-100',
                'pro_slug' => 'giay-the-thao-nam-nike-air-force-1-07-mens-shoe-dm2845-100',
                'pro_code' => 'h092679',
                'pro_price' => 4000000,
                'pro_price_sale' => 3650000,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-nam-nike-air-force-1-07-men-s-shoe-dm2845-100-mau-trang-xanh-64916a30a0ab3-20062023155824.jpg',
                'pro_description' => 'Giày thể thao nam Nike Air Force 1 07 (Mã sản phẩm: DM2845-100) là một biểu tượng trong thế giới giày thể thao, đại diện cho sự kết hợp hoàn hảo giữa thiết kế thời trang và tính năng thể thao của Nike.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Nike Joyride Flyknit',
                'pro_slug' => 'giay-the-thao-nike-joyride-flyknit',
                'pro_code' => 'h025597',
                'pro_price' => 4000000,
                'pro_price_sale' => 3300000,
                'capital_price' => 3000000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-nike-joyride-flyknit-mau-hong-5f71637fda607-28092020111559.jpg',
                'pro_description' => 'Giày thể thao Nike Joyride Flyknit là một sản phẩm thể thao đẳng cấp của Nike với công nghệ đệm độc đáo và thiết kế hiện đại.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Adidas Adifom Q Shoes IE7447',
                'pro_slug' => 'giay-the-thao-adidas-adifom-q-shoes-ie7447',
                'pro_code' => 'h092651',
                'pro_price' => 3970000,
                'pro_price_sale' => 3200000,
                'capital_price' => 2900000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-adidas-adifom-q-shoes-ie7447-mau-xam-trang-64915bbe5af6e-20062023145646.jpg',
                'pro_description' => 'Được định hình bởi một bộ xương ngoài nổi bật, những đôi giày adidas này từ kho lưu trữ du hành đến tương lai. Phần trên chunky giờ đây bằng xốp, với các đường cắt để lộ phần ủng bên trong, tạo cảm giác vừa vặn và dễ xỏ vào. Đệm Adiplus ở đế giữa giúp bạn tiếp tục, bất kể cuộc phiêu lưu của bạn dẫn đến đâu trong thời gian hay không gian. Được làm bằng một loạt vật liệu tái chế, phần trên này có ít nhất 50% thành phần tái chế. Sản phẩm này chỉ là một trong những giải pháp của chúng tôi nhằm giúp chấm dứt rác thải nhựa.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Adidas Runfalcon 2.0 Shoes HR1411',
                'pro_slug' => 'giay-the-thao-adidas-runfalcon-2-0-shoes-hr1411',
                'pro_code' => 'h105413',
                'pro_price' => 999000,
                'pro_price_sale' => 1450000,
                'capital_price' => 800000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-adidas-runfalcon-2-0-shoes-hr1411-mau-hong-size-35-5-651e6b078aef8-05102023145135.jpg',
                'pro_description' => 'Giày Thể Thao Adidas Runfalcon 2.0 Shoes HR1411 là sự kết hợp hoàn hảo giữa hiệu suất thể thao và phong cách cá nhân. Với thiết kế đẹp mắt và chất lượng vượt trội, đây là một lựa chọn tuyệt vời cho những người yêu thích hoạt động thể thao và thời trang đường phố.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Adidas Ultraboost 21 FY0403',
                'pro_slug' => 'giay-the-thao-adidas-ultraboost-21-fy0403',
                'pro_code' => 'h107045',
                'pro_price' => 3350000,
                'pro_price_sale' => 2650000,
                'capital_price' => 2000000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-adidas-ultraboost-21-fy0403-mau-trang-size-36-5-652f79834693c-18102023132155.jpg',
                'pro_description' => 'Giày Thể Thao Adidas Runfalcon 2.0 Shoes HR1411 là sự kết hợp hoàn hảo giữa hiệu suất thể thao và phong cách cá nhân. Với thiết kế đẹp mắt và chất lượng vượt trội, đây là một lựa chọn tuyệt vời cho những người yêu thích hoạt động thể thao và thời trang đường phố.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Giày Thể Thao Adidas Forum Plus FY5223',
                'pro_slug' => 'giay-the-thao-adidas-forum-plus-fy5223',
                'pro_code' => 'h101871',
                'pro_price' => 3310000,
                'pro_price_sale' => 2700000,
                'capital_price' => 2300000,
                'pro_img' => 'backend/uploads/product/giay-the-thao-adidas-forum-plus-fy5223-phoi-mau-64fbf2615d627-09092023111945.jpg',
                'pro_description' => 'Giày thể thao Adidas Forum Plus FY5223 là một sản phẩm thời trang đường phố đầy phong cách và đa dạng màu sắc từ Adidas.',
                'pro_views' => 150,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            // thuận
            [
                'pro_name' => 'Nike Dunk Low',
                'pro_slug' => 'nike-dunk-low',
                'pro_code' => 'FV0384-001',
                'pro_price' => 3829000,
                'pro_price_sale' => 0,
                'capital_price' => 2890000,
                'pro_img' => 'backend/uploads/product/nike-dunk-low.jpg',
                'pro_description' => 'Được thiết kế dành cho gỗ cứng nhưng lại được ưa chuộng trên đường phố, 
                biểu tượng bóng rổ của thập niên 80 trở lại với các chi tiết cổ điển và sự tinh tế của những
                chiếc vòng quay cổ điển. Da bền và vải Ripstop được kết hợp với các chi tiết thiết kế phản chiếu
                và đế ngoài màu xanh mờ tạo nên kết cấu chắc chắn giúp bạn đối mặt với các điều kiện khắc nghiệt. 
                Và cổ áo có đệm, cổ thấp cho phép bạn mang trò chơi của mình đi bất cứ đâu—một cách thoải mái.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Air Zoom Arcadia 2',
                'pro_slug' => 'nike-air-zoom-arcadia-2',
                'pro_code' => 'DM8491-009',
                'pro_price' => 1909000,
                'pro_price_sale' => 0,
                'capital_price' => 1490000,
                'pro_img' => 'backend/uploads/product/nike-air-zoom-2.jpg',
                'pro_description' => 'Ngày đua là mỗi ngày! Có được cảm giác dễ chịu khi xuất phát khi bạn mang Nike Air Zoom Arcadia 2. 
                Chạy bộ, chạy hoặc chạy nước rút với lực đẩy thêm từ đệm Zoom Air phản ứng nhanh của chúng tôi. 
                Áo vừa vặn được cải tiến đảm bảo áo không quá chật hoặc quá rộng khi bạn đi xa hơn.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Flex Runner 2',
                'pro_slug' => 'nike-flex-runner-2',
                'pro_code' => 'DJ6038-002',
                'pro_price' => 1279000,
                'pro_price_sale' => 0,
                'capital_price' => 850000,
                'pro_img' => 'backend/uploads/product/nike-flex-runner.jpg',
                'pro_description' => 'Giày chạy bộ thật dễ dàng! Nike Flex Runner 2 dành cho trẻ thích di chuyển và vui chơi cả ngày—từ lớp thể dục cho đến chạy nước rút trong khu nhà. 
                Chúng không có dây buộc, nghĩa là chúng có thể xỏ vào và cởi ra cực nhanh. Ai đã sẵn sàng đua?',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Nike Elite Crew',
                'pro_slug' => 'nike-elite-crew',
                'pro_code' => 'SX7676-100',
                'pro_price' => 459000,
                'pro_price_sale' => 0,
                'capital_price' => 250000,
                'pro_img' => 'backend/uploads/product/nike-crew.jpg',
                'pro_description' => 'Tăng cường sức mạnh cho quá trình tập luyện của bạn với Tất hàng ngày của Nike. 
                Sợi mềm với công nghệ thấm mồ hôi giúp đôi chân bạn luôn thoải mái và khô ráo.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'PUMA RS X Efekt Energy',
                'pro_slug' => 'puma-rs-x-efekt-energy',
                'pro_code' => 'MSP756',
                'pro_price' => 4099000,
                'pro_price_sale' => 0,
                'capital_price' => 3300000,
                'pro_img' => 'backend/uploads/product/puma-rs-x-efekt-energy.jpg',
                'pro_description' => 'Hãy trải nghiệm cảm giác tràn đầy năng lượng với đôi giày sneakers RS X Efekt Energy. Được thiết kế đầy bắt mắt khi kết hợp chi tiết TPU cùng lớp vải lưới thoáng khí được cải tiến từ phiên bản gốc ra mắt vào năm 2018,
                 đôi giày sẽ giúp bạn dễ dàng mix&match với nhiều loại trang phục cũng như tạo nên những bản phối đầy năng động.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'PUMA Sportstyle RS X Reinvention',
                'pro_slug' => 'puma-sportstyle-rs-x-revention',
                'pro_code' => 'MSP9579',
                'pro_price' => 3499000,
                'pro_price_sale' => 0,
                'capital_price' => 2700000,
                'pro_img' => 'backend/uploads/product/puma-sportstyle-rs-x-revention.jpg',
                'pro_description' => 'Đôi giày sneakers Sportstyle RS X Reinvention nằm trong bộ sưu tập giày RS của Puma, được mệnh danh là "huyền thoại của thế kỷ 80 đã trở lại và hoàn thiện hơn bao giờ hết". 
                    Những thiết kế này đều được lấy cảm hứng từ thập kỷ 80, mang đậm vẻ cổ điển để tôn vinh phiên bản gốc nhưng được cải tiến với một lớp đế giữa siêu nhẹ nhằm nâng cấp sự êm ái, cũng như đem đến cho bạn cảm giác tự tin và thoải mái trên mọi địa hình.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance Shifted 327',
                'pro_slug' => 'new-balance-shifted-327',
                'pro_code' => 'U327WCB',
                'pro_price' => 2595000,
                'pro_price_sale' => 0,
                'capital_price' => 1900000,
                'pro_img' => 'backend/uploads/product/new-balance-shifted-327.jpg',
                'pro_description' => 'Đánh bại mọi đối thủ với Giày Thể Thao Unisex New Balance Shifted 327 - 
                    một đôi giày thể thao lấy cảm hứng từ thập kỷ 70 với nhiều chi tiết hiện đại. Đế giày với chi tiết gai kết hợp với thiết kế thon gọn mang đến cho đôi giày thể thao này sự đẳng cấp. Đế giữa được đệm để mang lại sự thoải mái kéo dài suốt cả ngày.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 4,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas X_Plrboost',
                'pro_slug' => 'adidas-x-plrboost',
                'pro_code' => 'ID9574',
                'pro_price' => 4200000,
                'pro_price_sale' => 3750000,
                'capital_price' => 3400000,
                'pro_img' => 'backend/uploads/product/adidas-x-plrboost.jpg',
                'pro_description' => 'Hãy nâng tầm phong cách và năng lượng của bạn với Giày Thể Thao Nam Adidas X_Plrboost!
                Sản phẩm này không chỉ làm bạn tỏa sáng về mặt thời trang mà còn mang lại sự thoải mái và hiệu suất ấn tượng.
                Phần upper của giày được thiết kế mềm mại và được lót bằng chất liệu thoải mái, đảm bảo rằng bạn sẽ luôn cảm thấy thoải mái trong mọi hoạt động. Đế giữa BOOST độc đáo mang đến sự thoải mái tối ưu với mỗi bước đi. 
                Không chỉ thế, sản phẩm này còn được làm từ loạt vật liệu tái chế, với ít nhất 50% chất liệu tái chế, góp phần giúp bảo vệ môi trường và chấm dứt ô nhiễm rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'Adidas Duramo Speed',
                'pro_slug' => 'adidas-duramo-speed',
                'pro_code' => 'IE9673',
                'pro_price' => 2500000,
                'pro_price_sale' => 0,
                'capital_price' => 1700000,
                'pro_img' => 'backend/uploads/product/adidas-duramo-speed.jpg',
                'pro_description' => 'Thân giày bằng vải lưới kỹ thuật siêu nhẹ và thoáng khí kết hợp cùng đế giữa LIGHTSTRIKE toàn phần cho chuyển động nhanh hơn và
                đế ngoài bền bỉ được thiết kế dành cho các runner mới bắt đầu và muốn thúc đẩy bản thân vươn lên một tầm cao mới. <br>

                Làm từ một loạt chất liệu tái chế. Sản phẩm này đại diện cho một trong số rất nhiều các giải pháp của chúng tôi hướng tới chấm dứt rác thải nhựa.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'pro_name' => 'New Balance Front Tab Mesh',
                'pro_slug' => 'new-balance-front-tab-mesh',
                'pro_code' => 'LAS17061SYR',
                'pro_price' => 89000,
                'pro_price_sale' => 0,
                'capital_price' => 50000,
                'pro_img' => 'backend/uploads/product/new-balance-front-tab-mesh.jpg',
                'pro_description' => 'Tăng cường sức mạnh cho quá trình tập luyện của bạn với Tất hàng ngày của New Blance. 
                Sợi mềm với công nghệ thấm mồ hôi giúp đôi chân bạn luôn thoải mái và khô ráo.',
                'pro_views' => 0,
                'pro_hot' => 0,
                'pro_date' => Now(),
                'cate_id' => 7,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ]
        ]);
    }
}
