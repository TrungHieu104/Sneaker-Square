<?php

namespace Tests\Feature\Legacy;

use App\Models\DeliveryInfoModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The length limits on a delivery address's name and phone. Every request here
 * fails validation, so none of them reaches the carrier.
 */
class SalesDeliveryRequestTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Http::preventStrayRequests();
        $this->customer = $this->makeUser();
    }

    public function test_ho_ten_qua_30_ky_tu_bi_tu_choi(): void
    {
        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_name' => str_repeat('a', 31)]))
            ->assertSessionHasErrors(['info_name' => 'Họ và tên quá dài']);

        $this->assertSame(0, DeliveryInfoModel::count());
    }

    public function test_so_dien_thoai_ngan_hon_10_hoac_dai_hon_11_so_bi_tu_choi(): void
    {
        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_phone' => '098765432']))
            ->assertSessionHasErrors(['info_phone' => 'Số điện thoại phải có ít nhất 10 số']);
        $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_phone' => '098765432101']))
            ->assertSessionHasErrors(['info_phone' => 'Số điện thoại quá dài']);

        $this->assertSame(0, DeliveryInfoModel::count());
    }

    public function test_so_dien_thoai_co_chu_hoac_ky_tu_bi_tu_choi(): void
    {
        foreach (['abcdefghij', '0987-65-43', '0987 654 321'] as $phone) {
            $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_phone' => $phone]))
                ->assertSessionHasErrors(['info_phone' => 'Số điện thoại chỉ gồm chữ số']);
        }

        $this->assertSame(0, DeliveryInfoModel::count());
    }

    public function test_ho_ten_va_so_dien_thoai_dung_do_dai_thi_hop_le(): void
    {
        foreach (['0987654321', '09876543210'] as $phone) {
            $this->actingAs($this->customer)->post(route('diachi.store'), $this->payload(['info_name' => str_repeat('a', 30), 'info_phone' => $phone]))
                ->assertSessionDoesntHaveErrors(['info_name', 'info_phone']);
        }
    }

    /**
     * Without a ward code the request never gets past validation.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'info_name' => 'Nguyễn Văn B',
            'info_phone' => '0987654321',
            'info_email' => 'khachmoi@gmail.com',
            'info_address' => '2 Võ Văn Ngân',
            'info_province' => 'Hà Nội',
            'info_district' => 'Quận Ba Đình',
            'info_ward' => 'Phường Tân Định',
            'info_district_id' => 1442,
        ], $overrides);
    }
}
