<?php

namespace Tests\Feature;

use App\Models\CartReminderModel;
use App\Models\ProductModel;
use App\Models\UserEventModel as Event;
use App\Services\Analytics\BehaviourReport;
use App\Services\Analytics\EventAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The nightly roll-up of user_events and the behaviour page built on it.
 */
class BehaviourReportTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    private Carbon $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
        $this->day = Carbon::yesterday()->setTime(10, 0);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function event(string $type, string $session, array $fields = [], ?Carbon $at = null): void
    {
        Event::create($fields + [
            'session_id' => str_pad($session, 40, '0'),
            'event_type' => $type,
            'created_at' => $at ?? $this->day,
        ]);
    }

    private function aggregate(): void
    {
        app(EventAggregator::class)->aggregate($this->day);
    }

    private function report(): BehaviourReport
    {
        return BehaviourReport::lastDays(7);
    }

    public function test_tong_hop_dem_luot_va_so_phien_theo_loai_su_kien(): void
    {
        $this->event(Event::PAGE_VIEW, 'a');
        $this->event(Event::PAGE_VIEW, 'a');
        $this->event(Event::PAGE_VIEW, 'b');

        $this->aggregate();

        $row = DB::table('event_daily_totals')->where('event_type', Event::PAGE_VIEW)->first();
        $this->assertSame(3, (int) $row->events);
        $this->assertSame(2, (int) $row->sessions);
        $this->assertSame($this->day->toDateString(), Carbon::parse($row->day)->toDateString());
    }

    public function test_chay_lai_tong_hop_cung_mot_ngay_khong_cong_trung(): void
    {
        $product = $this->makeProduct();
        $this->event(Event::VIEW_PRODUCT, 'a', ['pro_id' => $product->pro_id]);
        $this->event(Event::SEARCH, 'a', ['meta' => ['keyword' => 'crocs', 'results' => 0]]);

        $this->aggregate();
        $this->aggregate();

        $this->assertSame(1, (int) DB::table('event_daily_totals')->where('event_type', Event::VIEW_PRODUCT)->sum('events'));
        $this->assertSame(1, (int) DB::table('event_daily_variants')->sum('events'));
        $this->assertSame(1, (int) DB::table('search_daily')->sum('searches'));
    }

    public function test_tong_hop_chi_lay_su_kien_trong_ngay_do(): void
    {
        $this->event(Event::PAGE_VIEW, 'a');
        $this->event(Event::PAGE_VIEW, 'b', at: $this->day->copy()->subDay());
        $this->event(Event::PAGE_VIEW, 'c', at: $this->day->copy()->addDay()->startOfDay());

        $this->aggregate();

        $this->assertSame(1, (int) DB::table('event_daily_totals')->sum('events'));
    }

    public function test_so_luong_trong_gio_va_don_duoc_cong_theo_so_doi(): void
    {
        $product = $this->makeProduct();
        $pair = ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1];
        $this->event(Event::PURCHASE, 'a', $pair + ['meta' => ['quantity' => 2, 'price' => 1_000_000]]);
        $this->event(Event::PURCHASE, 'b', $pair + ['meta' => ['quantity' => 1, 'price' => 1_000_000]]);

        $this->aggregate();

        $row = DB::table('event_daily_variants')->where('event_type', Event::PURCHASE)->first();
        $this->assertSame(2, (int) $row->events);
        $this->assertSame(3, (int) $row->quantity);
    }

    public function test_tu_khoa_duoc_gom_khong_phan_biet_hoa_thuong(): void
    {
        $this->event(Event::SEARCH, 'a', ['meta' => ['keyword' => 'Giày Da Nam', 'results' => 0]]);
        $this->event(Event::SEARCH, 'b', ['meta' => ['keyword' => ' giày da nam ', 'results' => 0]]);
        $this->event(Event::SEARCH, 'c', ['meta' => ['keyword' => 'nike', 'results' => 5]]);

        $this->aggregate();

        $missed = $this->report()->missedSearches();
        $this->assertCount(1, $missed, 'Từ khoá có kết quả không được lọt vào danh sách tìm trượt');
        $this->assertSame('giày da nam', $missed[0]->keyword);
        $this->assertSame(2, $missed[0]->misses);
    }

    public function test_pheu_tinh_ty_le_so_voi_buoc_truoc_va_buoc_dau(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $session) {
            $this->event(Event::PAGE_VIEW, $session);
        }
        foreach (['a', 'b'] as $session) {
            $this->event(Event::VIEW_PRODUCT, $session);
        }
        $this->event(Event::ADD_CART, 'a');

        $this->aggregate();

        $funnel = collect($this->report()->funnel())->keyBy('type');
        $this->assertSame(4, $funnel[Event::PAGE_VIEW]['sessions']);
        $this->assertNull($funnel[Event::PAGE_VIEW]['fromPrevious']);
        $this->assertSame(50.0, $funnel[Event::VIEW_PRODUCT]['fromPrevious']);
        $this->assertSame(50.0, $funnel[Event::ADD_CART]['fromPrevious']);
        $this->assertSame(25.0, $funnel[Event::ADD_CART]['fromTop']);
        $this->assertSame(0, $funnel[Event::PURCHASE]['sessions']);
    }

    public function test_san_pham_xem_nhieu_ma_khong_ai_mua_dung_dau(): void
    {
        $unsold = $this->makeProduct(slug: 'khong-ai-mua');
        $selling = $this->makeProduct(slug: 'ban-chay');
        $rare = $this->makeProduct(slug: 'it-nguoi-xem');

        for ($i = 0; $i < 12; $i++) {
            $this->event(Event::VIEW_PRODUCT, "u$i", ['pro_id' => $unsold->pro_id]);
            $this->event(Event::VIEW_PRODUCT, "s$i", ['pro_id' => $selling->pro_id]);
        }
        $this->event(Event::PURCHASE, 's1', ['pro_id' => $selling->pro_id, 'size_id' => 1, 'color_id' => 1, 'meta' => ['quantity' => 1]]);
        $this->event(Event::VIEW_PRODUCT, 'r', ['pro_id' => $rare->pro_id]);

        $this->aggregate();

        $rows = $this->report()->viewedNotBought();
        $this->assertSame([$unsold->pro_id, $selling->pro_id], $rows->pluck('pro_id')->all(), 'Sản phẩm dưới 10 lượt xem không đủ để kết luận');
        $this->assertSame(0.0, $rows[0]->conversion);
    }

    public function test_de_xuat_nhap_hang_theo_ton_kho_cua_tung_bien_the(): void
    {
        $product = $this->makeProduct(stock: 0);
        $this->addVariant($product, colorId: 2, stock: 2);
        $soldOut = ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1];
        $low = ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 2];

        $this->event(Event::STOCK_OUT_HIT, 'a', $soldOut);
        $this->event(Event::STOCK_OUT_HIT, 'b', $soldOut);
        $this->event(Event::PURCHASE, 'c', $low + ['meta' => ['quantity' => 2]]);

        $this->aggregate();

        $rows = $this->report()->restock();
        $this->assertCount(2, $rows);
        $this->assertSame(['Đen', 2, 0, BehaviourReport::RESTOCK_NOW], [$rows[0]->color, $rows[0]->hits, $rows[0]->stock, $rows[0]->advice]);
        $this->assertSame(['Đỏ', 2, BehaviourReport::RUNNING_LOW], [$rows[1]->color, $rows[1]->sold, $rows[1]->advice]);
    }

    public function test_bien_the_chua_tung_nhap_ma_khach_tim_duoc_de_xuat_rieng(): void
    {
        $product = $this->makeProduct();
        $this->event(Event::STOCK_OUT_HIT, 'a', ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 2]);
        $this->event(Event::SELECT_VARIANT, 'b', ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1]);

        $this->aggregate();

        $rows = $this->report()->restock();
        $this->assertCount(1, $rows, 'Biến thể còn hàng và bán chậm thì không cần đề xuất');
        $this->assertNull($rows[0]->stock);
        $this->assertSame(BehaviourReport::NEVER_STOCKED, $rows[0]->advice);
    }

    public function test_hieu_qua_goi_y_chi_tinh_mua_sau_khi_bam_va_moi_lan_bam_mot_lan(): void
    {
        $product = $this->makeProduct();
        $pair = ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1];
        $this->event(Event::RECOMMEND, 'a', ['pro_id' => 99, 'meta' => ['source' => 'similar', 'pro_ids' => [$product->pro_id, 7, 8]]]);
        $this->event(Event::RECOMMEND, 'b', ['pro_id' => 99, 'meta' => ['source' => 'similar', 'pro_ids' => [$product->pro_id]]]);

        // a clicks twice, then buys; b had it in the cart before clicking.
        $this->event(Event::VIEW_PRODUCT, 'a', ['pro_id' => $product->pro_id, 'meta' => ['source' => 'similar']], at: $this->day->copy()->addMinute());
        $this->event(Event::VIEW_PRODUCT, 'a', ['pro_id' => $product->pro_id, 'meta' => ['source' => 'similar']], at: $this->day->copy()->addMinutes(2));
        $this->event(Event::ADD_CART, 'a', $pair + ['meta' => ['quantity' => 1]], at: $this->day->copy()->addMinutes(3));
        $this->event(Event::PURCHASE, 'a', $pair + ['meta' => ['quantity' => 1]], at: $this->day->copy()->addMinutes(4));
        $this->event(Event::ADD_CART, 'b', $pair + ['meta' => ['quantity' => 1]], at: $this->day->copy()->addMinute());
        $this->event(Event::VIEW_PRODUCT, 'b', ['pro_id' => $product->pro_id, 'meta' => ['source' => 'similar']], at: $this->day->copy()->addMinutes(2));
        $this->event(Event::VIEW_PRODUCT, 'c', ['pro_id' => $product->pro_id], at: $this->day->copy()->addMinutes(2));

        $this->aggregate();

        $similar = collect($this->report()->recommendations())->keyBy('source')['similar'];
        $this->assertSame(4, $similar['shown']);
        $this->assertSame(2, $similar['clicks']);
        $this->assertSame(50.0, $similar['ctr']);
        $this->assertSame(1, $similar['carts'], 'Thêm giỏ trước khi bấm gợi ý không tính cho gợi ý');
        $this->assertSame(1, $similar['purchases']);
    }

    public function test_gio_khoi_phuc_chi_tinh_don_mua_lai_san_pham_sau_khi_bam(): void
    {
        $customer = $this->makeUser();
        $reminded = $this->makeProduct(slug: 'trong-gio');
        $other = $this->makeProduct(slug: 'khac');
        $reminder = fn (int $step, ?Carbon $clicked) => CartReminderModel::create([
            'user_id' => $customer->user_id, 'cart_key' => 'gio-'.$customer->user_id, 'step' => $step,
            'lines' => [['pro_id' => $reminded->pro_id, 'size_id' => 1, 'color_id' => 1, 'quantity' => 1]],
            'token' => str_repeat((string) $step, 40), 'sent_at' => $this->day->copy()->subHour(), 'clicked_at' => $clicked,
        ]);
        $reminder(CartReminderModel::FIRST, $this->day);
        $reminder(CartReminderModel::SECOND, null);
        $buy = fn (ProductModel $product, string $session, Carbon $at) => $this->event(Event::PURCHASE, $session, [
            'user_id' => $customer->user_id, 'pro_id' => $product->pro_id, 'meta' => ['quantity' => 2, 'price' => 500_000],
        ], at: $at);

        $buy($reminded, 'truoc-khi-bam', $this->day->copy()->subMinutes(30));
        $buy($reminded, 'sau-khi-bam', $this->day->copy()->addMinutes(10));
        $buy($other, 'sau-khi-bam', $this->day->copy()->addMinutes(10));
        $buy($other, 'don-khac', $this->day->copy()->addHour());

        [$first, $second] = $this->report()->cartReminders();
        $this->assertSame([1, 1, 1, 100.0, 1_000_000], [$first['sent'], $first['clicked'], $first['orders'], $first['orderRate'], $first['value']]);
        $this->assertSame([1, 0, 0], [$second['sent'], $second['clicked'], $second['orders']], 'Email chưa được bấm không được nhận đơn');
    }

    public function test_lenh_tong_hop_mac_dinh_chay_cho_hom_qua(): void
    {
        $this->event(Event::PAGE_VIEW, 'a');
        $this->event(Event::PAGE_VIEW, 'b', at: Carbon::now());

        $this->artisan('events:aggregate')->assertSuccessful();

        $this->assertSame([Carbon::yesterday()->toDateString()], DB::table('event_daily_totals')->pluck('day')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())->all());
    }

    public function test_lenh_tong_hop_tu_choi_ngay_sai_dinh_dang(): void
    {
        $this->artisan('events:aggregate', ['--from' => '19/10/2026'])->assertFailed();
        $this->artisan('events:aggregate', ['--from' => '2026-10-19', '--to' => '2026-10-18'])->assertFailed();
    }

    public function test_trang_hanh_vi_can_quyen_thong_ke_truy_cap(): void
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        Permission::findOrCreate('Thống kê truy cập', 'web');

        $this->actingAs($admin)->get(route('admin.behaviour'))->assertForbidden();

        $admin->givePermissionTo('Thống kê truy cập');
        $this->event(Event::SEARCH, 'a', ['meta' => ['keyword' => 'crocs', 'results' => 0]]);
        $this->aggregate();

        $this->actingAs($admin)->get(route('admin.behaviour', ['days' => 7]))
            ->assertOk()
            ->assertSee('Phễu chuyển đổi')
            ->assertSee('crocs');
    }

    private function page(): TestResponse
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Thống kê truy cập', 'web'));

        return $this->actingAs($admin)->get(route('admin.behaviour', ['days' => 7]))->assertOk();
    }

    public function test_trang_hanh_vi_chi_ra_buoc_khach_bo_di_nhieu_nhat(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $session) {
            $this->event(Event::PAGE_VIEW, $session);
            $this->event(Event::VIEW_PRODUCT, $session);
        }
        $this->event(Event::ADD_CART, 'a');

        $this->aggregate();

        $this->page()->assertSeeInOrder(['Khách bỏ đi nhiều nhất', 'Xem sản phẩm → Thêm vào giỏ', '75,0%']);
    }

    public function test_de_xuat_nhap_hang_gom_cac_bien_the_theo_san_pham(): void
    {
        $product = $this->makeProduct(stock: 0, slug: 'giay-het-hang');
        $this->event(Event::STOCK_OUT_HIT, 'a', ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 1]);
        $this->event(Event::STOCK_OUT_HIT, 'b', ['pro_id' => $product->pro_id, 'size_id' => 1, 'color_id' => 2]);

        $this->aggregate();

        $html = $this->page()->assertSeeInOrder(['Sản Phẩm Giay-het-hang', 'Đen · 42', 'Đỏ · 42'])->getContent();
        $this->assertSame(1, substr_count((string) $html, 'Sản Phẩm Giay-het-hang'), 'Mỗi sản phẩm chỉ hiện một dòng, các biến thể nằm chung');
    }

    public function test_danh_sach_xem_nhieu_mua_it_chi_hien_5_dong_dau(): void
    {
        for ($p = 1; $p <= 7; $p++) {
            $product = $this->makeProduct(slug: "xem-nhieu-$p");
            for ($i = 0; $i < 10 + $p; $i++) {
                $this->event(Event::VIEW_PRODUCT, "s$p-$i", ['pro_id' => $product->pro_id]);
            }
        }

        $this->aggregate();

        $this->page()->assertSeeInOrder([
            'Sản Phẩm Xem-nhieu-7', 'Sản Phẩm Xem-nhieu-3',
            'class="collapse" id="more-viewed"', 'Sản Phẩm Xem-nhieu-2', 'Sản Phẩm Xem-nhieu-1',
            'Xem thêm 2 sản phẩm',
        ], false);
    }
}
