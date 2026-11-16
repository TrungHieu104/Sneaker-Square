<?php

namespace Tests\Feature;

use App\Enums\PageType;
use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use App\Models\PageModel;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The storefront FAQ page: what it prints, in which order, and what it leaves out.
 */
class FaqPageTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    public function test_chi_hien_nhom_va_cau_hoi_dang_hien_theo_thu_tu(): void
    {
        $shipping = $this->group('Giao hàng', position: 2);
        $payment = $this->group('Đặt hàng & thanh toán', position: 1);
        $hiddenGroup = $this->group('Nhóm nháp', position: 3, visible: false);
        $onlyHidden = $this->group('Nhóm chỉ có câu ẩn', position: 4);
        $first = $this->question($shipping, 'Shop giao hàng bằng đơn vị nào?', position: 1, answer: '<p>Giao qua <b>GHN</b>.</p>');
        $this->question($shipping, 'Phí giao hàng bao nhiêu?', position: 2);
        $this->question($shipping, 'Câu đang ẩn', position: 3, visible: false);
        $this->question($payment, 'Shop nhận những hình thức thanh toán nào?');
        $this->question($hiddenGroup, 'Câu trong nhóm nháp');
        $this->question($onlyHidden, 'Câu ẩn duy nhất', visible: false);

        $this->get(route('faq.page'))
            ->assertOk()
            ->assertSeeInOrder(['Đặt hàng &amp; thanh toán', 'Shop nhận những hình thức thanh toán nào?', 'Giao hàng', 'Shop giao hàng bằng đơn vị nào?', 'Phí giao hàng bao nhiêu?'], false)
            ->assertSee('id="cau-hoi-'.$first->faq_id.'"', false)
            ->assertSee('Giao qua <b>GHN</b>.', false)
            ->assertDontSee('Câu đang ẩn')
            ->assertDontSee('Nhóm nháp')
            ->assertDontSee('Nhóm chỉ có câu ẩn');
    }

    public function test_cuoi_trang_dan_toi_chinh_sach_lien_he_va_hotline(): void
    {
        app(ShopSettings::class)->setShopInfo(['phone' => '0369469525']);
        $this->question($this->group('Giao hàng'), 'Shop giao hàng bằng đơn vị nào?');
        PageModel::create(['page_type' => PageType::Policy, 'page_name' => 'Chính sách giao hàng', 'page_slug' => 'chinh-sach-giao-hang', 'page_description' => '', 'page_content' => 'x']);

        $this->get(route('faq.page'))
            ->assertOk()
            ->assertSee(route('policy.detail', 'chinh-sach-giao-hang'), false)
            ->assertSee(route('contact.page'), false)
            ->assertSee('href="tel:0369469525"', false)
            ->assertSee('Gọi 0369 469 525');
    }

    public function test_chua_co_cau_hoi_thi_bao_trong(): void
    {
        $this->get(route('faq.page'))->assertOk()->assertSee('Chưa có câu hỏi nào.');
    }

    public function test_footer_co_link_trang_cau_hoi_thuong_gap(): void
    {
        $this->get(route('home.page'))->assertOk()->assertSee(route('faq.page'), false);
    }

    private function group(string $name, int $position = 1, bool $visible = true): FaqGroupModel
    {
        return FaqGroupModel::create(['group_name' => $name, 'group_position' => $position, 'group_hidden' => $visible ? 1 : 0]);
    }

    private function question(FaqGroupModel $group, string $question, int $position = 1, bool $visible = true, string $answer = '<p>Trả lời.</p>'): FaqModel
    {
        return FaqModel::create([
            'group_id' => $group->group_id,
            'faq_question' => $question,
            'faq_answer' => $answer,
            'faq_position' => $position,
            'faq_hidden' => $visible ? 1 : 0,
        ]);
    }
}
