<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The note an admin action leaves after the redirect: a toast that goes away
 * on its own, not a dialog to dismiss before the page can be used.
 */
class AdminToastTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private function adminPage(string $message, string $icon): string
    {
        $staff = $this->makeUser('toast@example.test', 'toast', role: 1);
        $staff->givePermissionTo(Permission::findOrCreate('Khách hàng liên hệ', 'web'));

        return (string) $this->actingAs($staff)
            ->withSession(['message' => $message, 'iconMessage' => $icon])
            ->get(route('assistant_admin.answers'))
            ->assertOk()
            ->getContent();
    }

    public function test_thong_bao_sau_thao_tac_la_toast_tu_tat_khong_phai_hop_thoai(): void
    {
        $html = $this->adminPage('Đã xoá câu trả lời.', 'success');

        $this->assertStringContainsString('new Noty({', $html);
        $this->assertStringContainsString("type: 'success'", $html);
        $this->assertStringContainsString('text: '.Js::from('Đã xoá câu trả lời.'), $html);
        $this->assertStringContainsString('timeout: 3500', $html);
        $this->assertStringNotContainsString('swal(', $html);
        $this->assertStringNotContainsString('Click để hoàn tất', $html);
        $this->assertStringNotContainsString('sweetalert.min.js', $html);
        $this->assertStringContainsString('backend/assets/css/toast.css', $html);
    }

    public function test_canh_bao_va_loi_o_lau_hon_va_noi_dung_duoc_escape(): void
    {
        $html = $this->adminPage('Không lưu được <b>mã</b>.', 'error');

        $this->assertStringContainsString("type: 'error'", $html);
        $this->assertStringContainsString('timeout: 6000', $html);
        $this->assertStringContainsString('text: '.Js::from('Không lưu được &lt;b&gt;mã&lt;/b&gt;.'), $html);
        $this->assertStringNotContainsString('<b>mã</b>', $html);
    }
}
