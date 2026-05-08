<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('promotion')->insert([
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'Đồ hiệu sale to - Không lo về giá',
                'promotion_img' => 'backend/uploads/slide/do-hieu-sale-to-khong-lo-ve-gia.jpg',
                'promotion_link' => '/san-pham',
                'promotion_hidden' => 1,
                'promotion_sort' => 1,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Mua ngay các sản phẩm chính hãng từ các thương hiệu nổi tiếng giá tốt nhất thị trường chỉ có thể là Sneaker Square.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'ADIDAS ULTRABOOST 21 - Sự Kết Hợp Giữa Hiệu Suất Và Phong Cách',
                'promotion_img' => 'backend/uploads/slide/adidas-ultraboost-21.jpg',
                'promotion_link' => '/san-pham',
                'promotion_hidden' => 1,
                'promotion_sort' => 2,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Được làm bằng chất liệu BOOST nhẹ hơn 30%, đây là dòng Ultraboost nhẹ nhất từ trước đến nay đến từ thương hiệu Adidas.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'Hãy rèn luyện để trở thành phiên bản xuất sắc nhất của bạn',
                'promotion_img' => 'backend/uploads/slide/hay-ren-luyen-de-tro-thanh-phien-ban-xuat-sac-nhat-cua-ban.jpg',
                'promotion_link' => '/san-pham/adidas-hyperturf',
                'promotion_hidden' => 1,
                'promotion_sort' => 3,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Adidas Hyperturf mang đến trải nghiệm tuyệt vời trong quá trình tập luyện, với sự hỗ trợ từ các công nghệ giày cao cấp, giúp cải thiện và nâng cao hiệu suất.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'Ưu đãi độc quyền các sản phẩm từ Nike',
                'promotion_img' => 'backend/uploads/slide/uu-dai-doc-quyen-cac-san-pham-tu-nike.jpg',
                'promotion_link' => '/danh-muc/nike',
                'promotion_hidden' => 1,
                'promotion_sort' => 4,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Ưu đãi online độc quyền: giảm đến 50% cho một số sản phẩm phát hành giới hạn của Nike. Chương trình diễn ra trong tháng 12-2023.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'Puma RSX Suede Virtual - Chạm Vào Tương Lai Của Thời Trang',
                'promotion_img' => 'backend/uploads/slide/puma-rsx-suede-vitruta.jpg',
                'promotion_link' => '/san-pham',
                'promotion_hidden' => 1,
                'promotion_sort' => 5,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Biểu tượng của sự đẳng cấp và phong cách thời trang, với thiết kế bền bỉ và lớp vỏ da sang trọng, đôi giày này là sự kết hợp hoàn hảo giữa tiện ích và thẩm mỹ.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'type' => 'Khuyến mãi',
                'promotion_name' => 'New Balance Shifted 327 - Sự Kết Hợp Giữa Phong Cách và Độc Đáo',
                'promotion_img' => 'backend/uploads/slide/new-balance-shifted-327.jpg',
                'promotion_link' => '/san-pham/new-balance-shifted-327',
                'promotion_hidden' => 1,
                'promotion_sort' => 6,
                'promotion_date' => Now(),
                'promotion_start' => '2023-10-01',
                'promotion_end' => '2024-01-01',
                'promotion_content' => 'Với sự kết hợp tinh tế giữa các gam màu và chất liệu cao cấp, đây không chỉ là một đôi giày, mà là một tuyên bố về phong cách cá nhân.',
                'promotion_note' => null,
                'cate_slide_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
