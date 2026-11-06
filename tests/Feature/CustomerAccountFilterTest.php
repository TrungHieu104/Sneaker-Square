<?php

namespace Tests\Feature;

use App\Enums\CustomerSegment;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\SegmentFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The customer list's filter panel: a search and a segment that apply together
 * and stay applied while the admin sorts and turns pages.
 */
class CustomerAccountFilterTest extends TestCase
{
    use RefreshDatabase;
    use SegmentFixtures;
    use ShopFixtures;

    private const CLEAR_FILTER = 'Xoá lọc';

    private UserModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->admin = $this->makeUser(email: 'quantri@example.test', username: 'quantri', role: 1);
        $this->admin->givePermissionTo(Permission::findOrCreate('Quản trị Tài khoản (Khách hàng)', 'web'));
    }

    private function named(string $name, string $email, ?CustomerSegment $segment = null): UserModel
    {
        $customer = $this->makeUser(email: $email, username: str_replace(['@', '.'], '', $email));
        $customer->update(['name' => $name]);

        if ($segment !== null) {
            $this->inSegment($customer, $segment);
        }

        return $customer;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function listing(array $query = []): TestResponse
    {
        return $this->actingAs($this->admin)->get(route('account.user', $query))->assertOk();
    }

    public function test_tim_theo_mot_phan_email(): void
    {
        $this->named('Khách Một', 'mot.khach@example.test');
        $this->named('Khách Hai', 'hai.khach@example.test');

        $this->listing(['tim-kiem' => 'mot.kh'])
            ->assertSee('mot.khach@example.test')
            ->assertDontSee('hai.khach@example.test');
    }

    public function test_tim_theo_ten(): void
    {
        $this->named('Trần Văn An', 'an@example.test');
        $this->named('Lê Thị Bình', 'binh@example.test');

        $this->listing(['tim-kiem' => 'Trần'])
            ->assertSee('an@example.test')
            ->assertDontSee('binh@example.test');
    }

    public function test_tim_va_phan_khuc_ap_dung_cung_luc(): void
    {
        $this->named('Trần An', 'tran.an@example.test', CustomerSegment::Loyal);
        $this->named('Trần Bình', 'tran.binh@example.test', CustomerSegment::Lost);
        $this->named('Lê Cường', 'le.cuong@example.test', CustomerSegment::Loyal);

        $this->listing(['tim-kiem' => 'Trần', 'phan-khuc' => CustomerSegment::Loyal->value])
            ->assertSee('tran.an@example.test')
            ->assertDontSee('tran.binh@example.test')
            ->assertDontSee('le.cuong@example.test');
    }

    public function test_tim_kiem_khong_ra_tai_khoan_quan_tri(): void
    {
        $this->listing(['tim-kiem' => 'quantri@example.test'])
            ->assertSee('Không có dữ liệu để hiển thị.');
    }

    public function test_o_tim_kiem_dau_trang_van_dung_duoc(): void
    {
        $this->named('Khách Một', 'mot.khach@example.test');
        $this->named('Khách Hai', 'hai.khach@example.test');

        $this->listing(['keyword' => 'mot.kh'])
            ->assertSee('mot.khach@example.test')
            ->assertDontSee('hai.khach@example.test');
    }

    public function test_bo_cot_vai_tro_vi_trang_chi_co_khach_hang(): void
    {
        $this->named('Khách Một', 'mot.khach@example.test');

        $this->listing()
            ->assertSee('mot.khach@example.test')
            ->assertDontSee('sort-by=user_role', false)
            ->assertDontSee('<span style="color: green">Khách hàng</span>', false);
    }

    public function test_chua_loc_thi_khong_co_so_dem_va_nut_xoa_loc(): void
    {
        $this->listing()
            ->assertSee('aria-label="Lọc danh sách"', false)
            ->assertSee('Áp dụng')
            ->assertDontSee('data-so-dieu-kien', false)
            ->assertDontSee(self::CLEAR_FILTER);
    }

    public function test_dang_loc_thi_bao_so_dieu_kien_va_giu_gia_tri_da_chon(): void
    {
        $this->listing(['tim-kiem' => 'trần', 'phan-khuc' => CustomerSegment::Loyal->value])
            ->assertSee('data-so-dieu-kien="2"', false)
            ->assertSee('value="trần"', false)
            ->assertSee('value="loyal" selected', false)
            ->assertSee(self::CLEAR_FILTER);

        $this->listing(['phan-khuc' => CustomerSegment::Loyal->value])
            ->assertSee('data-so-dieu-kien="1"', false);
    }

    public function test_sap_xep_giu_dieu_kien_loc(): void
    {
        $this->listing(['tim-kiem' => 'an', 'phan-khuc' => CustomerSegment::Loyal->value])
            ->assertSee('tim-kiem=an&amp;phan-khuc=loyal&amp;sort-by=name', false);
    }

    public function test_ap_dung_loc_khong_dao_chieu_sap_xep_dang_chon(): void
    {
        $this->named('Khách A', 'a@example.test');
        $this->named('Khách B', 'b@example.test');

        $sorted = ['sort-by' => 'email', 'sort-type' => 'asc'];
        $this->listing($sorted)
            ->assertSee('name="sort-by" value="email"', false)
            ->assertSee('name="sort-type" value="asc"', false)
            ->assertSeeInOrder(['b@example.test', 'a@example.test']);

        $this->listing($sorted + ['tim-kiem' => '@example.test'])
            ->assertSeeInOrder(['b@example.test', 'a@example.test']);
    }

    public function test_chuyen_trang_giu_dieu_kien_loc(): void
    {
        foreach (range(1, 11) as $number) {
            $this->named('Khách '.$number, "trang{$number}@example.test", CustomerSegment::Loyal);
        }

        $this->listing(['tim-kiem' => 'trang', 'phan-khuc' => CustomerSegment::Loyal->value])
            ->assertSee('tim-kiem=trang&amp;phan-khuc=loyal&amp;page=2', false);
    }
}
