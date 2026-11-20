<?php

namespace Tests\Feature;

use App\Services\FootSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The photo is measured in the browser; what reaches the server is the size the
 * customer settles on, and the product page reads it back as its suggestion.
 */
class FootMeasureTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    public function test_quy_doi_chieu_dai_ban_chan_ra_size_eu(): void
    {
        $sizes = new FootSize;

        $this->assertSame(36, $sizes->sizeFor(225));
        $this->assertSame(39, $sizes->sizeFor(245));
        $this->assertSame(40, $sizes->sizeFor(250));
        $this->assertSame(42, $sizes->sizeFor(265));
        // One millimetre past what a size 42 fits is a size 43, never a squeeze.
        $this->assertSame(43, $sizes->sizeFor(266));
    }

    public function test_bang_size_khop_dung_cong_thuc_quy_doi(): void
    {
        $sizes = new FootSize;
        $chart = $sizes->chart();

        $this->assertSame(range(FootSize::MIN_SIZE, FootSize::MAX_SIZE), array_keys($chart));
        foreach ($chart as $size => $longest) {
            // The longest foot a size fits, and not a millimetre more.
            $this->assertSame($size, $sizes->sizeFor($longest));
            $this->assertSame($size + 1, $sizes->sizeFor($longest + 1));
        }
    }

    public function test_khoang_chieu_dai_moi_size_noi_tiep_nhau_va_khop_bang_size(): void
    {
        $sizes = new FootSize;
        $ranges = $sizes->ranges();

        $this->assertSame(array_keys($sizes->chart()), array_keys($ranges));
        $previous = null;
        foreach ($ranges as $size => $range) {
            $this->assertSame($sizes->chart()[$size], $range['to']);
            $this->assertSame($size, $sizes->sizeFor($range['from']));
            $this->assertSame($size - 1, $sizes->sizeFor($range['from'] - 1));
            // The Japanese/Korean label is a 5 mm step this size fits.
            $this->assertSame(0, $range['asian'] % 5);
            $this->assertSame($size, $sizes->sizeFor($range['asian']));
            $this->assertGreaterThan($range['to'] - 5, $range['asian']);
            if ($previous !== null) {
                $this->assertSame($previous + 1, $range['from']);
            }
            $previous = $range['to'];
        }
    }

    public function test_trang_bang_quy_doi_size_lay_size_cua_shop_lam_chinh_kem_size_nhat_han(): void
    {
        $rows = [];
        foreach ((new FootSize)->ranges() as $size => $range) {
            $rows[] = '<th scope="row">'.$size.'</th>';
            $rows[] = $range['from'].' – '.$range['to'].' mm';
            $rows[] = '>'.number_format($range['asian'] / 10, 1, ',', '').' / '.$range['asian'].'<';
        }

        $this->get(route('size.chart'))
            ->assertOk()
            ->assertSee('Bảng quy đổi size giày')
            ->assertSee('Size giày ở Việt Nam, kể cả trên Sneaker Square, đánh số theo chuẩn EU.')
            ->assertSee('chừa sẵn '.FootSize::TOE_ROOM_MM.' mm cho ngón chân', false)
            ->assertSeeInOrder(['Size<small>VN / EU</small>', 'Chiều dài bàn chân', 'Size Nhật / Hàn<small>cm / mm</small>'], false)
            ->assertSee('<th scope="row">42</th>', false)
            ->assertSee('259 – 265 mm')
            ->assertSee('26,5 / 265')
            ->assertSeeInOrder($rows, false)
            ->assertDontSee('Size EU');
    }

    public function test_trang_bang_quy_doi_size_va_trang_do_co_chan_link_toi_nhau(): void
    {
        $this->get(route('size.chart'))
            ->assertOk()
            ->assertSee('href="'.route('foot.measure').'"', false)
            ->assertSee('Đo cỡ chân bằng ảnh');

        $this->get(route('foot.measure'))
            ->assertOk()
            ->assertSee('href="'.route('size.chart').'"', false)
            ->assertSee('Xem bảng quy đổi size');
    }

    public function test_khach_vang_lai_mo_trang_do_co_chan_khong_bi_nhac_dang_nhap(): void
    {
        $this->get(route('foot.measure'))
            ->assertOk()
            ->assertDontSee('Bạn chưa đăng nhập')
            ->assertDontSee('href="'.route('user.login').'">Đăng nhập</a> để lưu', false);
    }

    public function test_khach_da_dang_nhap_luu_size_vao_tai_khoan(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->postJson(route('foot.store'), ['size' => 42])
            ->assertOk()
            ->assertExactJson(['size' => 42]);

        $this->assertSame(42, (int) $user->fresh()->shoe_size);
    }

    public function test_tai_khoan_da_co_size_thi_lan_sau_tu_goi_y(): void
    {
        $user = $this->makeUser();
        $user->shoe_size = 40;
        $user->save();

        $this->actingAs($user)
            ->getJson(route('foot.show'))
            ->assertExactJson(['size' => 40, 'source' => 'measured']);
    }

    public function test_khach_chua_dang_nhap_van_duoc_goi_y_trong_lan_truy_cap(): void
    {
        $this->postJson(route('foot.store'), ['size' => 40])
            ->assertOk()
            ->assertExactJson(['size' => 40]);

        $this->getJson(route('foot.show'))->assertExactJson(['size' => 40, 'source' => 'measured']);
    }

    public function test_chua_chon_size_thi_khong_goi_y_size_nao(): void
    {
        $this->getJson(route('foot.show'))->assertExactJson(['size' => null, 'source' => null]);
    }

    public function test_size_khong_hop_le_bi_tu_choi_va_khong_luu(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->postJson(route('foot.store'), ['size' => 20])
            ->assertStatus(422)
            ->assertJsonPath('errors.size.0', 'Size giày phải từ 35 đến 49.');

        $this->actingAs($user)
            ->postJson(route('foot.store'), ['size' => 'abc'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('foot.store'), [])
            ->assertStatus(422)
            ->assertJsonPath('errors.size.0', 'Vui lòng chọn size giày của bạn.');

        $this->assertNull($user->fresh()->shoe_size);
    }

    public function test_trang_do_co_chan_dan_ve_dung_san_pham_dang_xem(): void
    {
        $product = $this->makeProduct(slug: 'giay-can-do');

        $this->get(route('foot.measure', ['sp' => 'giay-can-do']))
            ->assertOk()
            ->assertSee('Đang chọn size cho')
            ->assertSee(route('product.detail', $product->pro_slug), false);

        $this->get(route('foot.measure', ['sp' => 'khong-co-san-pham-nay']))
            ->assertOk()
            ->assertDontSee('Đang chọn size cho');
    }

    public function test_trang_do_co_chan_chon_san_size_da_luu(): void
    {
        $user = $this->makeUser();
        $user->shoe_size = 42;
        $user->save();

        $this->actingAs($user)
            ->get(route('foot.measure'))
            ->assertOk()
            ->assertSee('Size bạn thường đi')
            ->assertSee('<option value="42" selected', false)
            ->assertSee('Muốn đi thoải mái hơn, bạn có thể lên 1 size')
            ->assertDontSee('name="foot_length_mm"', false);
    }

    public function test_trang_do_co_chan_doc_duoc_anh_heic_cua_iphone(): void
    {
        $this->get(route('foot.measure'))
            ->assertOk()
            ->assertSee(json_encode(asset('frontend/vendor/heic-to/heic-to.js')), false)
            ->assertDontSee('Ảnh được xử lý ngay trên máy của bạn');

        $this->assertFileExists(public_path('frontend/vendor/heic-to/heic-to.js'));
    }

    public function test_giay_co_size_thi_trang_san_pham_co_nut_do_co_chan(): void
    {
        $product = $this->makeProduct(slug: 'giay-co-size');

        $this->get(route('product.detail', $product->pro_slug))
            ->assertOk()
            ->assertSee('Đo cỡ chân của tôi')
            ->assertSee(route('foot.measure', ['sp' => 'giay-co-size']), false)
            ->assertSee('data-size="42"', false);
    }

    public function test_san_pham_khong_co_size_thi_khong_co_nut_do_co_chan(): void
    {
        $product = $this->makeProduct(slug: 'dung-dich-ve-sinh', withVariant: false);

        $this->get(route('product.detail', $product->pro_slug))
            ->assertOk()
            ->assertDontSee('Đo cỡ chân của tôi');
    }
}
