<?php

namespace Tests\Feature;

use App\Events\AssistantChatChanged;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\AssistantMessageModel as Message;
use App\Models\UserModel;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssistantFixtures;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The support chat over Reverb: every change sends the chat id to the
 * customer's widget and the staff inbox, who fetch the chat themselves.
 */
class SupportChatLiveTest extends TestCase
{
    use AssistantFixtures;
    use RefreshDatabase;
    use ShopFixtures;

    private const KEY = 'khoa-cong-khai';

    private const SECRET = 'khoa-bi-mat';

    private const SOCKET = '1234.5678';

    private UserModel $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(now()->setTime(10, 0));
        $this->seedLookupTables();
        $this->customer = $this->makeUser();
    }

    /**
     * Broadcasting on Reverb, as a shop running `reverb:start` has it. The
     * channels are registered again, since the ones from boot went to the
     * null broadcaster the tests start with.
     */
    private function reverbOn(int $port = 8080): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => self::KEY,
            'broadcasting.connections.reverb.secret' => self::SECRET,
            'broadcasting.connections.reverb.app_id' => '1001',
            'broadcasting.connections.reverb.options' => ['host' => '127.0.0.1', 'port' => $port, 'scheme' => 'http', 'useTLS' => false],
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    private function staff(): UserModel
    {
        $staff = $this->makeUser(uniqid().'@example.test', uniqid('nv'), role: 1);
        $staff->givePermissionTo(Permission::findOrCreate('Khách hàng liên hệ', 'web'));

        return $staff;
    }

    private function handedOver(): Conversation
    {
        $this->fakeClaude();
        $this->actingAs($this->customer)->ask('Chân mình bè, nên lấy size nào?');
        $this->actingAs($this->customer)->postJson(route('assistant.handoff'))->assertOk();

        return Conversation::sole();
    }

    private function signalsFor(int $conversationId): int
    {
        return Event::dispatched(AssistantChatChanged::class, fn (AssistantChatChanged $event) => $event->conversationId === $conversationId)->count();
    }

    private function signature(string $channel): string
    {
        return self::KEY.':'.hash_hmac('sha256', self::SOCKET.':'.$channel, self::SECRET);
    }

    public function test_khach_hoi_va_bot_tra_loi_deu_bao_cuoc_chat_doi(): void
    {
        Event::fake([AssistantChatChanged::class]);
        $this->fakeClaude();

        $this->actingAs($this->customer)->ask('Shop đổi trả trong bao lâu?')->assertOk();

        $conversation = Conversation::sole();
        // The question, then the answer the queued job wrote.
        $this->assertSame(Message::count(), $this->signalsFor($conversation->id));
        $this->assertSame(2, Message::count());
    }

    public function test_chuyen_nhan_vien_tra_loi_va_dong_chat_deu_bao_cuoc_chat_doi(): void
    {
        $conversation = $this->handedOver();
        Event::fake([AssistantChatChanged::class]);
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.'])->assertRedirect();
        $this->assertGreaterThanOrEqual(1, $this->signalsFor($conversation->id));

        Event::fake([AssistantChatChanged::class]);
        $this->actingAs($staff)->post(route('assistant_admin.close', $conversation->id))->assertRedirect();
        $this->assertGreaterThanOrEqual(1, $this->signalsFor($conversation->id));
    }

    public function test_khach_mo_khung_chat_xem_tin_khong_bao_cuoc_chat_doi(): void
    {
        $conversation = $this->handedOver();
        Event::fake([AssistantChatChanged::class]);

        // Opening the widget marks the chat seen; signalling that would set off another fetch.
        $this->actingAs($this->customer)->getJson(route('assistant.show', ['mo' => 1]))->assertOk();

        $this->assertSame(0, $this->signalsFor($conversation->id));
        $this->assertNotNull($conversation->fresh()?->customer_seen_at);
    }

    public function test_tin_hieu_chi_mang_ma_cuoc_chat_tren_kenh_rieng_va_kenh_hop_thu(): void
    {
        $event = new AssistantChatChanged(15);

        $this->assertSame(
            ['private-assistant.chat.15', 'private-assistant.inbox'],
            array_map(fn ($channel) => $channel->name, $event->broadcastOn()),
        );
        $this->assertSame('changed', $event->broadcastAs());
        $this->assertSame(['chat' => 15], $event->broadcastWith());
    }

    public function test_khung_chat_biet_kenh_cua_cuoc_chat_dang_mo(): void
    {
        $this->asVisitor()->getJson(route('assistant.show'))->assertOk()->assertJsonPath('channel', null);

        $this->fakeClaude();
        $this->asVisitor()->ask('Shop ở đâu?')->assertOk();

        $this->asVisitor()->getJson(route('assistant.show'))
            ->assertJsonPath('channel', 'assistant.chat.'.Conversation::sole()->id);
    }

    public function test_khach_vang_lai_duoc_nghe_kenh_cuoc_chat_cua_minh(): void
    {
        $this->reverbOn();
        $this->fakeClaude();
        $this->asVisitor()->ask('Shop ở đâu?')->assertOk();
        $channel = 'private-'.Conversation::sole()->channel();

        $this->asVisitor()->postJson(route('assistant.channel'), ['socket_id' => self::SOCKET, 'channel_name' => $channel])
            ->assertOk()
            ->assertExactJson(['auth' => $this->signature($channel)]);
    }

    public function test_khong_ai_nghe_duoc_kenh_cuoc_chat_cua_nguoi_khac(): void
    {
        $this->reverbOn();
        $this->fakeClaude();
        $this->actingAs($this->customer)->ask('Đơn của mình tới đâu rồi?')->assertOk();
        $theirs = 'private-'.Conversation::sole()->channel();
        $this->app['auth']->forgetGuards();

        // A visitor with no chat of their own, then one with a chat of their own.
        $this->asVisitor()->postJson(route('assistant.channel'), ['socket_id' => self::SOCKET, 'channel_name' => $theirs])->assertForbidden();
        $this->asVisitor()->ask('Shop ở đâu?')->assertOk();
        $this->asVisitor()->postJson(route('assistant.channel'), ['socket_id' => self::SOCKET, 'channel_name' => $theirs])->assertForbidden();
        $this->asVisitor()->postJson(route('assistant.channel'), ['socket_id' => self::SOCKET, 'channel_name' => 'private-'.Conversation::INBOX_CHANNEL])->assertForbidden();
    }

    public function test_chi_nhan_vien_co_quyen_moi_nghe_duoc_kenh_hop_thu(): void
    {
        $this->reverbOn();
        $inbox = 'private-'.Conversation::INBOX_CHANNEL;

        $this->actingAs($this->staff())->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $inbox])
            ->assertOk()
            ->assertExactJson(['auth' => $this->signature($inbox)]);

        $this->actingAs($this->customer)->post('/broadcasting/auth', ['socket_id' => self::SOCKET, 'channel_name' => $inbox])
            ->assertForbidden();
    }

    public function test_may_chu_socket_khong_chay_thi_nhan_vien_van_tra_loi_duoc(): void
    {
        $conversation = $this->handedOver();
        // Nothing listens on port 1, so every signal fails to go out.
        $this->reverbOn(port: 1);
        Exceptions::fake();

        $this->actingAs($this->staff())->post(route('assistant_admin.reply', $conversation->id), ['body' => 'Bạn lấy size 42 nhé.'])
            ->assertRedirect(route('assistant_admin.index', ['chat' => $conversation->id]));

        $this->assertSame('Bạn lấy size 42 nhé.', $conversation->messages()->reorder('id', 'desc')->value('body'));
        $this->assertSame(Conversation::ANSWERED, $conversation->fresh()?->status);
        Exceptions::assertReported(BroadcastException::class);
    }

    public function test_bat_reverb_thi_khung_chat_nhan_khoa_cong_khai_khong_nhan_khoa_bi_mat(): void
    {
        $this->get(route('size.chart'))->assertOk()->assertDontSee('data-live=', false);

        $this->reverbOn();

        $this->get(route('size.chart'))->assertOk()
            ->assertSee('data-live=', false)
            ->assertSee(self::KEY)
            ->assertSee(route('assistant.channel'), false)
            ->assertSee('js/realtime/echo-2.6.3.iife.js', false)
            ->assertDontSee(self::SECRET);
    }

    public function test_bat_reverb_thi_trang_hop_thu_tu_cap_nhat(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('assistant_admin.index'))->assertOk()
            ->assertDontSee('assistant-inbox.js', false);

        $this->reverbOn();

        $this->actingAs($staff)->get(route('assistant_admin.index'))->assertOk()
            ->assertSee('js/realtime/pusher-8.6.0.min.js', false)
            ->assertSee('backend/assets/js/assistant-inbox.js', false)
            ->assertSee('"channel":"assistant.inbox"', false)
            ->assertSee(self::KEY)
            ->assertDontSee(self::SECRET);
    }
}
