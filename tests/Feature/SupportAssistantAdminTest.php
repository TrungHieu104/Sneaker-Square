<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\AssistantReplyMail;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantKnowledgeModel;
use App\Models\AssistantMessageModel as Message;
use App\Models\AssistantUnansweredModel as Unanswered;
use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use App\Models\UserModel;
use App\Services\Assistant\SupportDesk;
use App\Services\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsTooltips;
use Tests\Support\AssistantFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The staff side of the support assistant: answering the chats customers
 * hand over, and teaching it the questions it could not answer.
 */
class SupportAssistantAdminTest extends TestCase
{
    use AssertsTooltips;
    use AssistantFixtures;
    use RefreshDatabase;
    use ShopFixtures;

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // Inside working hours, so only the away rule decides whether a reply is emailed.
        $this->travelTo(now()->setTime(10, 0));
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    private function staff(bool $canEditPages = true): UserModel
    {
        $staff = $this->makeUser(uniqid().'@example.test', uniqid('nv'), role: 1);
        $staff->givePermissionTo(Permission::findOrCreate('Khách hàng liên hệ', 'web'));

        if ($canEditPages) {
            $staff->givePermissionTo(Permission::findOrCreate('Quản trị Chính sách & FAQ', 'web'));
        }

        return $staff;
    }

    private function handedOver(?string $seenAgo = null): Conversation
    {
        $this->fakeClaude();
        $this->actingAs($this->customer)->ask('Chân mình bè, nên lấy size nào?');
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'))->assertOk();

        $conversation = Conversation::sole();
        $conversation->update(['customer_seen_at' => $seenAgo === null ? null : now()->sub($seenAgo)]);

        return $conversation;
    }

    /**
     * Two customers asking the same thing the assistant could not answer.
     */
    private function openGroup(): Unanswered
    {
        // Claude has no data for either, and puts the second with the first.
        $this->fakeClaude(fn (array $request) => preg_match('/CÂU HỎI ĐANG MỞ:\n(\d+)\./u', (string) $request['messages'][0]['content'], $open)
            ? ['same_as' => (int) $open[1]]
            : ['answered' => false]);
        $this->asVisitor()->ask('Có cho kiểm hàng trước khi nhận không?');
        $this->actingAs($this->customer)->ask('Được xem hàng trước khi trả tiền không?');

        return Unanswered::orderBy('id')->firstOrFail();
    }

    // ------------------------------------------------------------- inbox

    public function test_nhan_vien_tra_loi_thi_khach_thay_ngay_trong_khung_chat(): void
    {
        $conversation = $this->handedOver(seenAgo: '10 seconds');

        $this->actingAs($this->staff())
            ->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy lớn hơn nửa size nhé.'])
            ->assertRedirect(route('assistant_admin.index', ['chat' => $conversation->id]));

        $this->assertSame(Conversation::ANSWERED, $conversation->fresh()->status);
        $last = collect($this->actingAs($this->customer)->getJson(route('assistant.show'))->json('messages'))->last();
        $this->assertSame(['staff', 'Bạn lấy lớn hơn nửa size nhé.'], [$last['sender'], $last['body']]);
        Mail::assertNothingQueued();
    }

    public function test_khach_da_roi_trang_thi_cau_tra_loi_gui_ve_email(): void
    {
        $conversation = $this->handedOver(seenAgo: '10 minutes');

        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Shop đã kiểm tra giúp bạn.']);

        Mail::assertQueued(AssistantReplyMail::class, fn (AssistantReplyMail $mail) => $mail->hasTo($this->customer->email)
            && $mail->reply->body === 'Shop đã kiểm tra giúp bạn.');
    }

    public function test_hop_thu_hien_cuoc_chat_cho_nhan_vien_va_thong_tin_khach(): void
    {
        $this->handedOver();
        $this->orderFor($this->customer, 'DH-90001');

        $this->actingAs($this->staff())->get(route('assistant_admin.index'))
            ->assertOk()
            ->assertSee('Chân mình bè, nên lấy size nào?')
            ->assertSee('Chờ nhân viên')
            ->assertSee('DH-90001')
            ->assertSee('Size đã mua');
    }

    public function test_khach_chua_dang_nhap_chi_ghi_la_khach_vang_lai(): void
    {
        $this->fakeClaude();
        $this->asVisitor()->ask('Shop có cửa hàng ở Huế không?');
        $this->asVisitor()->postJson(route('assistant.handoff'))->assertOk();

        $this->actingAs($this->staff())->get(route('assistant_admin.index'))
            ->assertOk()
            ->assertSee('<h6>Khách hàng</h6>'."\n".'                        <p class="small mb-3">Khách vãng lai</p>', false)
            ->assertDontSee('Khách chưa đăng nhập nên không có lịch sử đơn hàng')
            ->assertDontSee('Size đã mua');
    }

    public function test_dong_cuoc_chat_thi_cau_hoi_sau_bat_dau_cuoc_moi_voi_bot(): void
    {
        $conversation = $this->handedOver();

        $this->actingAs($this->staff())->post(route('assistant_admin.close', $conversation->id))->assertRedirect();
        $this->actingAs($this->customer)->ask('Chân mình bè, nên lấy size nào?')->assertOk();

        $this->assertSame(Conversation::CLOSED, $conversation->fresh()->status);
        $this->assertSame(2, Conversation::count());
    }

    public function test_dong_cuoc_chat_bot_dang_tra_loi_thi_khach_hoi_tiep_van_thay_dong_ket_thuc_truoc_cuoc_moi(): void
    {
        $this->fakeClaude(['reply' => 'Size 42 còn hàng bạn nhé.', 'answered' => true]);
        $seen = (int) collect($this->asVisitor()->ask('Mẫu này còn size 42 không?')->json('messages'))->last()['id'];
        $old = Conversation::sole();

        // The chat never reached staff, so the widget is not polling when it closes.
        $this->actingAs($this->staff())->post(route('assistant_admin.close', $old->id))->assertRedirect();
        $this->app['auth']->forgetGuards();

        $lines = collect($this->asVisitor()->ask('chào', ['sau' => $seen])->assertOk()->json('messages'));

        $this->assertSame(['system', 'customer', 'bot'], $lines->pluck('sender')->all());
        $this->assertStringStartsWith('Nhân viên đã kết thúc cuộc trò chuyện', $lines[0]['body']);
        $this->assertSame('chào', $lines[1]['body']);
        $this->assertSame(Conversation::CLOSED, $old->fresh()->status);
        $this->assertSame(2, Conversation::count());

        // Opened afresh, the widget shows the new chat alone, as the inbox does.
        $this->assertSame(['customer', 'bot'], collect($this->asVisitor()->getJson(route('assistant.show'))->json('messages'))->pluck('sender')->all());
    }

    public function test_admin_khong_co_quyen_lien_he_khong_vao_duoc(): void
    {
        $admin = $this->makeUser('ad@example.test', 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Bài viết', 'web'));
        Permission::findOrCreate('Khách hàng liên hệ', 'web');

        $this->actingAs($admin)->get(route('assistant_admin.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('assistant_admin.unanswered'))->assertForbidden();
    }

    // -------------------------------------------------- unanswered groups

    public function test_trang_chua_tra_loi_gom_cau_hoi_va_dem_so_lan(): void
    {
        $this->openGroup();

        $this->actingAs($this->staff())->get(route('assistant_admin.unanswered'))
            ->assertOk()
            ->assertSee('Có cho kiểm hàng trước khi nhận không?')
            ->assertSee('Được xem hàng trước khi trả tiền không?')
            ->assertSee('1 nhóm')
            ->assertDontSee('Gồm câu trợ lý không có dữ liệu để trả lời');
    }

    public function test_luu_cau_tra_loi_thi_dong_nhom_va_tro_ly_dung_cho_lan_sau(): void
    {
        $lead = $this->openGroup();

        $this->actingAs($this->staff())->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Có được kiểm hàng khi nhận không?',
            'answer' => 'Được, bạn mở hộp kiểm tra ngoại quan trước khi trả tiền.',
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertRedirect(route('assistant_admin.unanswered'));

        $this->assertSame(0, Unanswered::whereNull('resolved_at')->count());
        $this->assertSame('Có được kiểm hàng khi nhận không?', AssistantKnowledgeModel::sole()->title);

        $this->fakeClaude('Được bạn nhé.');
        $reply = $this->lastBot($this->asVisitor()->ask('Mình kiểm hàng trước được không?'));
        $this->assertSame('Được bạn nhé.', $reply['body']);
        $this->assertStringContainsString('mở hộp kiểm tra ngoại quan', $this->lastPrompt());
    }

    public function test_cau_tra_loi_cho_mot_san_pham_khong_dung_o_san_pham_khac(): void
    {
        $this->fakeClaude(['answered' => false]);
        $nike = $this->makeProduct(slug: 'giay-mot');
        $other = $this->makeProduct(slug: 'giay-hai');
        $this->asVisitor()->ask('Mẫu này form rộng không?', ['pro_id' => $nike->pro_id]);
        $lead = Unanswered::sole();

        $this->actingAs($this->staff())->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Mẫu này form rộng không?',
            'answer' => 'Form rộng, chân bè đi vừa.',
            'scope' => AssistantKnowledgeModel::SCOPE_PRODUCT,
            'scope_id' => $nike->pro_id,
        ]);

        $this->fakeClaude(fn (array $request) => str_contains((string) $request['messages'][0]['content'], 'chân bè đi vừa') ? 'Rộng bạn nhé.' : ['answered' => false]);
        $this->assertSame('Rộng bạn nhé.', $this->lastBot($this->asVisitor()->ask('Form có rộng không, chân bè?', ['pro_id' => $nike->pro_id]))['body']);
        $this->assertStringContainsString('chưa có thông tin', $this->lastBot($this->asVisitor()->ask('Form có rộng không, chân bè?', ['pro_id' => $other->pro_id]))['body']);
    }

    public function test_luu_cau_tra_loi_can_quyen_chinh_sach_va_faq(): void
    {
        $lead = $this->openGroup();

        $this->actingAs($this->staff(canEditPages: false))->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Có được kiểm hàng không?', 'answer' => 'Được.', 'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertForbidden();

        $this->assertSame(0, AssistantKnowledgeModel::count());
    }

    public function test_bo_qua_nhom_thi_nhom_khong_con_trong_danh_sach(): void
    {
        $lead = $this->openGroup();

        $this->actingAs($this->staff())->post(route('assistant_admin.dismiss', $lead->id))->assertRedirect();

        $this->assertSame([Unanswered::DISMISSED], Unanswered::pluck('resolution')->unique()->values()->all());
        $this->assertSame(0, AssistantKnowledgeModel::count());
    }

    public function test_menu_admin_dem_cuoc_chat_dang_cho(): void
    {
        $this->handedOver();
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('assistant_admin.unanswered'))
            ->assertSee('Trợ lý AI')
            ->assertSee('<span class="badge rounded-pill bg-danger ms-auto">1</span>', false);
        $this->assertSame(1, Message::where('sender', Message::SYSTEM)->count());
    }

    public function test_trang_cau_hinh_khong_con_o_ma_nhung_tawk(): void
    {
        $admin = $this->makeUser('cauhinh@example.test', 'cauhinh', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertDontSee('Tawk')
            ->assertDontSee('name="chat_embed"', false);
    }

    // ------------------------------------------------------------ model

    public function test_admin_chon_model_claude_cho_tro_ly_trong_cau_hinh(): void
    {
        $admin = $this->makeUser('model@example.test', 'chonmodel', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('name="assistant_model"', false)
            ->assertSee('Chưa có ANTHROPIC_API_KEY')
            // What a bigger model buys sits behind the help icon, not under the field.
            ->assertSeeInOrder(['Model Claude', 'data-bs-toggle="tooltip"', 'Model lớn hơn hiểu câu hỏi khó'], false)
            ->assertDontSee('model chỉ đọc rồi trả lời');

        $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'claude-opus-5-5', 'idle_close_hours' => 24])
            ->assertRedirect(route('setting.edit').'#tab-tro-ly');

        $this->assertSame('claude-opus-5-5', app(ShopSettings::class)->assistantModel());
    }

    public function test_admin_dat_so_gio_tu_dong_chat_trong_cau_hinh(): void
    {
        $admin = $this->makeUser('model@example.test', 'chonmodel', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $this->actingAs($admin)->get(route('setting.edit'))
            ->assertOk()
            ->assertSee('name="idle_close_hours"', false)
            ->assertSee('value="'.ShopSettings::DEFAULT_ASSISTANT_IDLE_HOURS.'"', false);

        $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'claude-haiku-5-5', 'idle_close_hours' => 6])
            ->assertRedirect(route('setting.edit').'#tab-tro-ly');

        $this->assertSame(6, app(ShopSettings::class)->assistantIdleHours());
    }

    public function test_cach_tinh_tu_dong_chat_nam_trong_tooltip(): void
    {
        $admin = $this->makeUser('model@example.test', 'chonmodel', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $page = $this->actingAs($admin)->get(route('setting.edit'))->assertOk();

        $this->assertOnlyInTooltip($page, 'Tự đóng chat nhân viên đã trả lời sau (giờ)',
            'Tính từ tin nhắn cuối. Hết khoảng này mà khách không nhắn thêm, cuộc chat tự đóng và trợ lý trả lời khách trở lại. Chat đang chờ nhân viên không bị đóng.');
    }

    public function test_so_gio_tu_dong_ngoai_khoang_bi_tu_choi_va_giu_so_cu(): void
    {
        $admin = $this->makeUser('model@example.test', 'chonmodel', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        foreach ([0, ShopSettings::MAX_ASSISTANT_IDLE_HOURS + 1, 'mot ngay'] as $hours) {
            $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'claude-haiku-5-5', 'idle_close_hours' => $hours])
                ->assertSessionHasErrors('idle_close_hours');
        }

        $this->assertSame(ShopSettings::DEFAULT_ASSISTANT_IDLE_HOURS, app(ShopSettings::class)->assistantIdleHours());
    }

    public function test_model_ngoai_danh_sach_bi_tu_choi_va_giu_model_cu(): void
    {
        $admin = $this->makeUser('model@example.test', 'chonmodel', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'gpt-5'])
            ->assertSessionHasErrors('assistant_model');

        $this->assertSame(ShopSettings::DEFAULT_ASSISTANT_MODEL, app(ShopSettings::class)->assistantModel());
    }

    public function test_quan_tri_don_hang_khong_doi_duoc_model_tro_ly(): void
    {
        $admin = $this->makeUser('donhang@example.test', 'donhang', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'claude-opus-5-5'])->assertForbidden();

        $this->assertSame(ShopSettings::DEFAULT_ASSISTANT_MODEL, app(ShopSettings::class)->assistantModel());
    }

    // ------------------------------------------------- after staff answer

    public function test_khach_chuyen_nhan_vien_ngoai_gio_thi_tra_loi_gui_email_du_dang_mo_trang(): void
    {
        $this->travelTo(now()->setTime(22, 0));
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $this->travelTo(now()->addHours(11));
        $conversation->update(['customer_seen_at' => now()]);

        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Sáng nay shop đã kiểm tra.']);

        Mail::assertQueued(AssistantReplyMail::class);
    }

    public function test_chuyen_nhan_vien_lan_hai_ngoai_gio_thi_tra_loi_van_gui_email(): void
    {
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.']);
        Mail::assertNothingQueued();

        $this->travelTo(now()->setTime(22, 0));
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'))->assertOk();
        $this->travelTo(now()->addHours(11));
        $conversation->update(['customer_seen_at' => now()]);

        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Sáng nay shop đã kiểm tra.']);

        Mail::assertQueued(AssistantReplyMail::class, 1);
    }

    public function test_cuoc_chat_da_tra_loi_im_lang_mot_ngay_thi_tu_dong_va_bot_tra_loi_lai(): void
    {
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.']);
        $this->travelTo(now()->addHours(25));

        $this->artisan('assistant:close-idle')->assertSuccessful();
        $reply = $this->lastBot($this->actingAs($this->customer)->ask('Mẫu này còn size 42 không?'));

        $this->assertSame(Conversation::CLOSED, $conversation->fresh()->status);
        $this->assertSame('bot', $reply['sender']);
    }

    public function test_cuoc_chat_tu_dong_theo_so_gio_admin_dat(): void
    {
        app(ShopSettings::class)->setAssistantIdleHours(6);
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.']);

        $this->travelTo(now()->addHours(5));
        $this->artisan('assistant:close-idle')->assertSuccessful();
        $this->assertSame(Conversation::ANSWERED, $conversation->fresh()->status);

        $this->travelTo(now()->addHours(2));
        $this->artisan('assistant:close-idle')->assertSuccessful();
        $this->assertSame(Conversation::CLOSED, $conversation->fresh()->status);
    }

    public function test_so_gio_tu_dong_sai_trong_csdl_van_nam_trong_khoang(): void
    {
        $settings = app(ShopSettings::class);

        $settings->setAssistantIdleHours(0);
        $this->assertSame(ShopSettings::MIN_ASSISTANT_IDLE_HOURS, $settings->assistantIdleHours());

        $settings->setAssistantIdleHours(10000);
        $this->assertSame(ShopSettings::MAX_ASSISTANT_IDLE_HOURS, $settings->assistantIdleHours());
    }

    public function test_cuoc_chat_dang_cho_hoac_moi_tra_loi_khong_bi_tu_dong(): void
    {
        $conversation = $this->handedOver();
        $this->travelTo(now()->addHours(30));

        $this->artisan('assistant:close-idle')->assertSuccessful();

        $this->assertSame(Conversation::WAITING_STAFF, $conversation->fresh()->status);
    }

    // ----------------------------------------------- saved answers

    public function test_luu_tra_loi_trong_hop_thu_thanh_cau_tra_loi_mau_va_dong_nhom_cua_cuoc_chat(): void
    {
        $this->fakeClaude(['answered' => false]);
        $this->actingAs($this->customer)->ask('Có cho kiểm hàng trước khi nhận không?');
        $conversation = Conversation::sole();
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'));
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Được, bạn mở hộp kiểm tra trước khi trả tiền.']);

        // A muted link under the reply: it must not outweigh what the customer was told.
        $this->actingAs($staff)->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertSeeInOrder(['Được, bạn mở hộp kiểm tra trước khi trả tiền.', 'class="btn btn-link btn-sm p-0 d-block mt-1 text-muted fw-normal"', 'Lưu thành câu trả lời mẫu'], false);
        $this->actingAs($staff)->post(route('assistant_admin.save_answer', $conversation->id), [
            'message_id' => Message::where('sender', Message::STAFF)->value('id'),
            'question' => 'Có cho kiểm hàng trước khi nhận không?',
            'answer' => 'Được, bạn mở hộp kiểm tra trước khi trả tiền.',
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertRedirect(route('assistant_admin.index', ['chat' => $conversation->id]));

        $this->assertSame('Được, bạn mở hộp kiểm tra trước khi trả tiền.', AssistantKnowledgeModel::sole()->content);
        $this->assertSame([Unanswered::ANSWERED], Unanswered::pluck('resolution')->unique()->values()->all());
    }

    /**
     * A chat where the assistant could not answer two questions, then staff
     * answered the second one.
     *
     * @return array{0: Conversation, 1: Message}
     */
    private function twoUnansweredThenStaffReply(): array
    {
        $this->fakeClaude(['answered' => false]);
        $this->actingAs($this->customer)->ask('Thời tiết hôm nay thế nào?');
        $this->actingAs($this->customer)->ask('Đơn 123123123 của tôi giao đi đâu rồi?');
        $conversation = Conversation::sole();
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'));
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Đơn của bạn đang giao.']);

        return [$conversation, Message::where('sender', Message::STAFF)->sole()];
    }

    public function test_cau_tra_loi_trong_hop_thu_chi_luu_mau_duoc_mot_lan(): void
    {
        [$conversation, $reply] = $this->twoUnansweredThenStaffReply();
        $staff = $this->staff();
        $save = fn () => $this->actingAs($staff)->post(route('assistant_admin.save_answer', $conversation->id), [
            'message_id' => $reply->id,
            'question' => 'Đơn của tôi giao đi đâu rồi?',
            'answer' => 'Bạn xem mã vận đơn ở trang Đơn hàng của tôi.',
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ]);

        $save()->assertRedirect(route('assistant_admin.index', ['chat' => $conversation->id]));
        $save()->assertRedirect(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertSessionHas('message', 'Câu trả lời này đã được lưu thành câu trả lời mẫu trước đó.');

        $this->assertSame(1, AssistantKnowledgeModel::count());
        $this->assertSame($reply->id, (int) AssistantKnowledgeModel::sole()->message_id);
        $this->actingAs($staff)->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertSee('Đã lưu thành câu trả lời mẫu')
            ->assertDontSee('data-bs-target="#luu-mau-'.$reply->id.'"', false);
    }

    public function test_goi_y_luu_cau_tra_loi_mau_nam_trong_tooltip_cua_nut_luu(): void
    {
        [$conversation] = $this->twoUnansweredThenStaffReply();

        $page = $this->actingAs($this->staff())->get(route('assistant_admin.index', ['chat' => $conversation->id]))->assertOk();

        $this->assertOnlyInTooltip($page, 'Đơn của bạn đang giao.', 'Câu trả lời dùng được cho khách khác thì lưu lại để trợ lý dùng cho lần sau');
        $page->assertSeeInOrder(['title="Câu trả lời dùng được cho khách khác', 'Lưu thành câu trả lời mẫu</button>'], false)
            ->assertDontSee('Sau khi trả lời');
    }

    public function test_luu_mau_chi_dong_cau_hoi_nhan_vien_vua_tra_loi_cau_truoc_van_mo(): void
    {
        [$conversation, $reply] = $this->twoUnansweredThenStaffReply();
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertSee('value="Đơn 123123123 của tôi giao đi đâu rồi?"', false);
        $this->actingAs($staff)->post(route('assistant_admin.save_answer', $conversation->id), [
            'message_id' => $reply->id,
            'question' => 'Đơn của tôi giao đi đâu rồi?',
            'answer' => 'Bạn xem mã vận đơn ở trang Đơn hàng của tôi.',
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ]);

        $this->assertSame(Unanswered::ANSWERED, Unanswered::where('question', 'Đơn 123123123 của tôi giao đi đâu rồi?')->value('resolution'));
        $this->assertNull(Unanswered::where('question', 'Thời tiết hôm nay thế nào?')->value('resolved_at'));
        // A second reply with no new question in between still answers the same one.
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Có gì bạn nhắn thêm nhé.']);
        $this->actingAs($staff)->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertDontSee('value="Thời tiết hôm nay thế nào?"', false);
    }

    public function test_luu_tu_hop_thu_can_quyen_chinh_sach_va_faq(): void
    {
        $conversation = $this->handedOver();

        $this->actingAs($this->staff(canEditPages: false))->post(route('assistant_admin.save_answer', $conversation->id), [
            'question' => 'Hỏi', 'answer' => 'Đáp', 'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertForbidden();
        $this->actingAs($this->staff(canEditPages: false))->get(route('assistant_admin.index'))->assertDontSee('Lưu thành câu trả lời mẫu');

        $this->assertSame(0, AssistantKnowledgeModel::count());
    }

    public function test_tab_cau_tra_loi_mau_xem_sua_va_xoa(): void
    {
        $entry = $this->knowledge('Shop có gói quà không?', 'Có, gói quà miễn phí.');
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('assistant_admin.answers'))->assertOk()->assertSee('Shop có gói quà không?')->assertSee('Toàn shop');

        $this->actingAs($staff)->put(route('assistant_admin.answers.update', $entry->id), [
            'question' => 'Shop có gói quà không?', 'answer' => 'Có, gói quà 20.000đ.', 'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertRedirect(route('assistant_admin.answers'));
        $this->assertSame('Có, gói quà 20.000đ.', $entry->fresh()->content);

        $this->actingAs($staff)->delete(route('assistant_admin.answers.destroy', $entry->id))->assertRedirect(route('assistant_admin.answers'));
        $this->assertSame(0, AssistantKnowledgeModel::count());
    }

    public function test_tab_cau_tra_loi_mau_dung_nut_thao_tac_chung_va_hop_xac_nhan_cua_admin(): void
    {
        $entry = $this->knowledge('Shop có gói quà không?', 'Có, gói quà miễn phí.');

        $this->actingAs($this->staff())->get(route('assistant_admin.answers'))
            ->assertOk()
            ->assertSee('class="row-action row-action-edit"', false)
            ->assertSee('data-bs-target="#sua-'.$entry->id.'"', false)
            ->assertSee('class="row-action row-action-more"', false)
            ->assertSee('title="Đăng lên trang FAQ"', false)
            ->assertSee('class="row-action row-action-delete"', false)
            ->assertSee('data-confirm="Xoá câu trả lời này? Trợ lý sẽ không dùng nó nữa."', false)
            ->assertSee('id="admin-confirm"', false)
            ->assertSee('backend/assets/js/confirm-modal.js', false)
            ->assertDontSee("return confirm('Xoá câu trả lời này", false)
            ->assertDontSee('Câu trả lời nhân viên đã lưu từ tab');
    }

    public function test_sua_xoa_cau_tra_loi_mau_can_quyen_chinh_sach_va_faq(): void
    {
        $entry = $this->knowledge('Shop có gói quà không?', 'Có, gói quà miễn phí.');
        $staff = $this->staff(canEditPages: false);

        $this->actingAs($staff)->get(route('assistant_admin.answers'))->assertOk()->assertDontSee('Sửa câu trả lời');
        $this->actingAs($staff)->delete(route('assistant_admin.answers.destroy', $entry->id))->assertForbidden();

        $this->assertSame(1, AssistantKnowledgeModel::count());
    }

    public function test_dang_cau_mau_len_trang_faq_thi_chuyen_han_va_cau_hoi_van_da_tra_loi(): void
    {
        $lead = $this->openGroup();
        $this->actingAs($this->staff())->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Có được kiểm hàng khi nhận không?',
            'answer' => "Được, bạn mở hộp kiểm tra trước khi trả tiền.\nHàng lỗi thì từ chối nhận.",
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ]);
        $entry = AssistantKnowledgeModel::firstOrFail();
        $group = FaqGroupModel::create(['group_name' => 'Giao hàng', 'group_position' => 1]);

        $this->actingAs($this->staff())->get(route('assistant_admin.answers'))->assertOk()->assertSee('Đăng lên trang FAQ');
        $this->actingAs($this->staff())->post(route('assistant_admin.answers.publish', $entry->id), ['group_id' => $group->group_id])
            ->assertRedirect(route('assistant_admin.answers'))
            ->assertSessionHas('message', 'Đã đăng lên trang câu hỏi thường gặp. Trợ lý vẫn dùng câu này, giờ lấy từ trang đó.');

        $faq = FaqModel::firstOrFail();
        $this->assertSame('Có được kiểm hàng khi nhận không?', $faq->faq_question);
        $this->assertSame('<p>Được, bạn mở hộp kiểm tra trước khi trả tiền.<br>'."\n".'Hàng lỗi thì từ chối nhận.</p>', $faq->faq_answer);
        $this->assertSame(0, AssistantKnowledgeModel::count());
        $this->assertSame(0, Unanswered::whereNull('resolved_at')->count());
        $this->get(route('faq.page'))->assertOk()->assertSee('Có được kiểm hàng khi nhận không?');

        $this->fakeClaude('Được bạn nhé.');
        $this->asVisitor()->ask('Chính sách kiểm hàng thế nào?');
        $this->assertSame(1, substr_count($this->lastPrompt(), '## Có được kiểm hàng khi nhận không?'));
    }

    public function test_cau_mau_theo_san_pham_khong_dang_len_trang_faq(): void
    {
        $nike = $this->makeProduct(slug: 'nike-dang-faq');
        $entry = $this->knowledge('Mẫu này form rộng không?', 'Form rộng.', AssistantKnowledgeModel::SCOPE_PRODUCT, $nike->pro_id);
        $group = FaqGroupModel::create(['group_name' => 'Size & sản phẩm', 'group_position' => 1]);

        $this->actingAs($this->staff())->post(route('assistant_admin.answers.publish', $entry->id), ['group_id' => $group->group_id])
            ->assertSessionHas('message', 'Chỉ câu trả lời áp dụng cho toàn shop mới đăng lên trang câu hỏi thường gặp được.');

        $this->assertSame(0, FaqModel::count());
        $this->assertSame(1, AssistantKnowledgeModel::count());
    }

    public function test_dang_cau_mau_len_trang_faq_can_quyen_va_nhom_hop_le(): void
    {
        $entry = $this->knowledge('Shop có gói quà không?', 'Có, gói quà miễn phí.');

        $this->actingAs($this->staff(canEditPages: false))->post(route('assistant_admin.answers.publish', $entry->id), ['group_id' => 1])->assertForbidden();
        $this->actingAs($this->staff())->post(route('assistant_admin.answers.publish', $entry->id), ['group_id' => 999])
            ->assertSessionHasErrors(['group_id' => 'Nhóm câu hỏi không tồn tại.']);

        $this->assertSame(1, AssistantKnowledgeModel::count());
    }

    public function test_khong_dang_cau_mau_vao_nhom_faq_dang_an(): void
    {
        $entry = $this->knowledge('Shop có gói quà không?', 'Có, gói quà miễn phí.');
        $hidden = FaqGroupModel::create(['group_name' => 'Nhóm đang ẩn', 'group_position' => 1, 'group_hidden' => 0]);
        FaqGroupModel::create(['group_name' => 'Nhóm đang hiện', 'group_position' => 2]);

        $this->actingAs($this->staff())->get(route('assistant_admin.answers'))
            ->assertOk()
            ->assertSee('Nhóm đang hiện')
            ->assertDontSee('Nhóm đang ẩn');
        $this->actingAs($this->staff())->post(route('assistant_admin.answers.publish', $entry->id), ['group_id' => $hidden->group_id])
            ->assertSessionHasErrors(['group_id' => 'Nhóm này đang ẩn trên trang câu hỏi thường gặp, trợ lý sẽ không đọc được câu trả lời. Hãy chọn nhóm đang hiện.']);

        $this->assertSame(0, FaqModel::count());
        $this->assertSame(1, AssistantKnowledgeModel::count());
    }

    // ------------------------------------------- unanswered page details

    public function test_nhom_khach_chua_hai_long_hien_cau_bot_da_tra_loi_va_chu_de(): void
    {
        $this->fakeClaude(['reply' => 'Shop nhận MoMo nhé.', 'topic' => 'Thanh toán']);
        $this->policyPage('Chính sách thanh toán', 'Thanh toán bằng MoMo hoặc VNPay.');
        $this->asVisitor()->ask('Trả góp qua MoMo được không?');
        $this->asVisitor()->postJson(route('assistant.handoff'));

        $this->actingAs($this->staff())->get(route('assistant_admin.unanswered'))
            ->assertOk()
            ->assertSee('Khách chưa hài lòng')
            ->assertSee('Shop nhận MoMo nhé.')
            ->assertSee('Thanh toán')
            ->assertSee('offcanvas-end', false);
    }

    public function test_size_da_mua_bo_qua_size_phu_kien_va_don_da_tra(): void
    {
        $conversation = $this->handedOver();
        $this->orderFor($this->customer, 'DH-90010', size: '41');
        $this->orderFor($this->customer, 'DH-90011', size: '44', status: OrderStatus::Returned);
        $this->orderFor($this->customer, 'DH-90012', productName: 'Vớ cổ ngắn', size: 'M');

        $this->actingAs($this->staff())->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertOk()
            ->assertSeeInOrder(['Size đã mua', '41']);
    }

    public function test_trung_vi_thoi_gian_cho_tinh_tu_luc_chuyen_den_cau_tra_loi_dau(): void
    {
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $this->travelTo(now()->addMinutes(6));
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Chào bạn.']);
        $this->travelTo(now()->addMinutes(10));
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn còn cần gì không?']);

        $this->actingAs($this->staff())->get(route('assistant_admin.unanswered'))->assertSee('thời gian chờ trung vị 6 phút');
    }

    // ------------------------------------------------- state guards

    public function test_tra_loi_cuoc_chat_da_dong_khong_mo_lai_cuoc_chat(): void
    {
        $conversation = $this->handedOver();
        $this->actingAs($this->staff())->post(route('assistant_admin.close', $conversation->id));

        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Trả lời muộn.'])
            ->assertRedirect(route('assistant_admin.index', ['tab' => SupportDesk::TAB_ALL, 'chat' => $conversation->id]))
            ->assertSessionHas('message', 'Cuộc chat này đã được đóng nên tin nhắn chưa được gửi. Khách hỏi tiếp sẽ mở cuộc chat mới.');

        $this->assertSame(Conversation::CLOSED, $conversation->fresh()->status);
        $this->assertSame(0, Message::where('sender', Message::STAFF)->count());
    }

    public function test_dong_cuoc_chat_hai_lan_chi_co_mot_thong_bao(): void
    {
        $conversation = $this->handedOver();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('assistant_admin.close', $conversation->id));
        $this->actingAs($staff)->post(route('assistant_admin.close', $conversation->id))->assertSessionHas('message', 'Cuộc chat này đã được đóng trước đó.');

        $this->assertSame(1, Message::where('body', 'like', 'Nhân viên đã kết thúc%')->count());
    }

    public function test_cuoc_chat_da_dong_khach_van_doc_duoc_cau_tra_loi_cuoi(): void
    {
        $conversation = $this->handedOver();
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.']);
        $this->actingAs($staff)->post(route('assistant_admin.close', $conversation->id));

        $state = $this->actingAs($this->customer)->getJson(route('assistant.show'))->assertOk()->assertJsonPath('status', Conversation::CLOSED);

        $bodies = collect($state->json('messages'))->pluck('body');
        $this->assertContains('Bạn lấy size 42 nhé.', $bodies);
        $this->assertStringStartsWith('Nhân viên đã kết thúc', (string) $bodies->last());
    }

    public function test_khach_doi_gap_nhan_vien_lai_sau_khi_da_duoc_tra_loi_thi_chat_ve_cho(): void
    {
        $conversation = $this->handedOver();
        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.']);

        $this->actingAs($this->customer)->postJson(route('assistant.handoff'))->assertOk()->assertJsonPath('status', Conversation::WAITING_STAFF);

        $this->assertSame(2, Message::where('sender', Message::SYSTEM)->count());
    }

    public function test_ngoai_gio_chi_cau_tra_loi_dau_tien_gui_email_khi_khach_dang_mo_trang(): void
    {
        $this->travelTo(now()->setTime(22, 0));
        $conversation = $this->handedOver(seenAgo: '5 seconds');
        $this->travelTo(now()->addHours(11));
        $conversation->update(['customer_seen_at' => now()]);
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Sáng nay shop đã kiểm tra.']);
        $conversation->update(['customer_seen_at' => now()]);
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn cần gì thêm không?']);

        Mail::assertQueuedCount(1);
    }

    // ------------------------------------------- groups and answers

    public function test_luu_tu_hop_thu_chi_dong_nhom_cua_cau_duoc_tra_loi(): void
    {
        $this->fakeClaude(fn (array $request) => str_contains((string) $request['messages'][0]['content'], 'CÂU HỎI ĐANG MỞ')
            ? ['same_as' => 0]
            : ['answered' => false]);
        $nike = $this->makeProduct(slug: 'giay-hoi-truoc');
        $other = $this->makeProduct(slug: 'giay-xem-sau');
        $this->actingAs($this->customer)->ask('Shop có chi nhánh ở Hà Nội không?');
        $this->actingAs($this->customer)->ask('Mẫu này bảo hành keo bao lâu?', ['pro_id' => $nike->pro_id]);
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'));
        $this->actingAs($this->customer)->ask('Shop ơi?', ['pro_id' => $other->pro_id]);
        $conversation = Conversation::sole();
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Keo bảo hành 6 tháng.']);

        $this->actingAs($staff)->get(route('assistant_admin.index', ['chat' => $conversation->id]))
            ->assertSee('value="Mẫu này bảo hành keo bao lâu?"', false)
            ->assertSee('Chỉ sản phẩm '.$nike->pro_name)
            ->assertDontSee('Chỉ sản phẩm '.$other->pro_name);
        $this->actingAs($staff)->post(route('assistant_admin.save_answer', $conversation->id), [
            'message_id' => Message::where('sender', Message::STAFF)->value('id'),
            'question' => 'Bảo hành keo bao lâu?',
            'answer' => 'Keo bảo hành 6 tháng.',
            'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ])->assertRedirect();

        $this->assertNull(Unanswered::where('question', 'Shop có chi nhánh ở Hà Nội không?')->value('resolved_at'));
        $this->assertSame(Unanswered::ANSWERED, Unanswered::where('question', 'Mẫu này bảo hành keo bao lâu?')->value('resolution'));
    }

    public function test_tra_loi_mot_nhom_hai_lan_chi_luu_mot_cau_tra_loi(): void
    {
        $lead = $this->openGroup();
        $staff = $this->staff();
        $form = ['question' => 'Có được kiểm hàng không?', 'answer' => 'Được.', 'scope' => AssistantKnowledgeModel::SCOPE_ALL];

        $this->actingAs($staff)->post(route('assistant_admin.answer', $lead->id), $form);
        $this->actingAs($staff)->post(route('assistant_admin.answer', $lead->id), ['answer' => 'Được, mở hộp xem trước.'] + $form)
            ->assertSessionHas('message', 'Nhóm câu hỏi này đã được xử lý trước đó, câu trả lời không được lưu thêm.');

        $this->assertSame('Được.', AssistantKnowledgeModel::sole()->content);
    }

    public function test_pham_vi_tro_toi_san_pham_khong_ton_tai_bi_tu_choi(): void
    {
        $lead = $this->openGroup();

        $this->actingAs($this->staff())->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Có được kiểm hàng không?', 'answer' => 'Được.',
            'scope' => AssistantKnowledgeModel::SCOPE_PRODUCT, 'scope_id' => 999999,
        ])->assertSessionHasErrors('scope_id');

        $this->assertSame(0, AssistantKnowledgeModel::count());
    }

    public function test_nhom_duoc_bo_qua_trong_luc_gom_thi_cau_moi_thanh_nhom_rieng(): void
    {
        $this->fakeClaude(['answered' => false]);
        $this->asVisitor()->ask('Có cho kiểm hàng trước khi nhận không?');
        $lead = Unanswered::sole();
        $this->fakeClaude(function (array $request) use ($lead) {
            if (! str_contains((string) $request['messages'][0]['content'], 'CÂU HỎI ĐANG MỞ')) {
                return ['answered' => false];
            }

            // Staff set the group aside while Claude was reading it.
            $lead->update(['resolution' => Unanswered::DISMISSED, 'resolved_at' => now()]);

            return ['same_as' => $lead->id];
        });

        $this->actingAs($this->customer)->ask('Được xem hàng trước khi trả tiền không?');

        $row = Unanswered::where('id', '!=', $lead->id)->sole();
        $this->assertSame($row->id, $row->group_id);
    }

    public function test_xoa_cau_tra_loi_mau_thi_cau_hoi_quay_lai_danh_sach(): void
    {
        $lead = $this->openGroup();
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('assistant_admin.answer', $lead->id), [
            'question' => 'Có được kiểm hàng không?', 'answer' => 'Được.', 'scope' => AssistantKnowledgeModel::SCOPE_ALL,
        ]);

        $this->actingAs($staff)->delete(route('assistant_admin.answers.destroy', AssistantKnowledgeModel::sole()->id));

        $this->assertSame(2, Unanswered::whereNull('resolved_at')->whereNull('knowledge_id')->count());
        $this->actingAs($staff)->get(route('assistant_admin.unanswered'))->assertSee('Có cho kiểm hàng trước khi nhận không?');
    }

    public function test_cau_trung_lap_trong_nhom_van_duoc_dem_vao_so_cau_con_lai(): void
    {
        $this->fakeClaude(fn (array $request) => preg_match('/CÂU HỎI ĐANG MỞ:\n(\d+)\./u', (string) $request['messages'][0]['content'], $open)
            ? ['same_as' => (int) $open[1]]
            : ['answered' => false]);
        $this->asVisitor()->ask('Có cho kiểm hàng trước khi nhận không?');
        $this->actingAs($this->customer)->ask('Được xem hàng trước không?');
        $this->actingAs($this->makeUser('ba@example.test', 'khachba'))->ask('Được xem hàng trước không?');

        $this->actingAs($this->staff())->get(route('assistant_admin.unanswered'))->assertSee('+1 câu');
    }

    // ------------------------------------------------------- links

    public function test_lien_ket_don_hang_trong_hop_thu_mo_trang_sua_don_khi_co_quyen(): void
    {
        $this->handedOver();
        $order = $this->orderFor($this->customer, 'DH-90002');
        $withOrders = $this->staff();
        $withOrders->givePermissionTo(Permission::findOrCreate('Quản trị Đơn hàng', 'web'));

        $this->actingAs($withOrders)->get(route('assistant_admin.index'))
            ->assertSee('Mở đơn hàng')
            ->assertDontSee(route('order.show', $order->order_id));
        $this->actingAs($this->staff())->get(route('assistant_admin.index'))->assertDontSee('Mở đơn hàng');
    }

    public function test_chon_model_loi_quay_ve_dung_tab_tro_ly(): void
    {
        $admin = $this->makeUser('tab@example.test', 'tabtroly', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Thông tin', 'web'));

        $this->actingAs($admin)->put(route('setting.update_assistant'), ['assistant_model' => 'gpt-5'])
            ->assertRedirect(route('setting.edit').'#tab-tro-ly');
    }
}
