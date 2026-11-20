<?php

namespace Tests\Support;

use App\Enums\OrderStatus;
use App\Enums\PageType;
use App\Models\AssistantKnowledgeModel;
use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\PageModel;
use App\Models\UserModel;
use App\Services\Assistant\ClaudeClient;
use App\Services\ShopSettings;
use App\Services\UserEventLog;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

/**
 * A stand-in for the Claude API at the HTTP layer, so the request the SDK
 * really builds is what the tests read. The test decides the JSON answer.
 */
trait AssistantFixtures
{
    private const VISITOR = 'abcdefghijABCDEFGHIJ0123456789abcdefghij';

    /** @var string|array<string, mixed>|callable(array<string, mixed>): (string|array<string, mixed>) */
    private $claudeReply = '';

    /** @var array<int, array<string, mixed>> request bodies, oldest first */
    private array $claudeRequests = [];

    /**
     * A string is a confident answer; an array is merged over one, so
     * ['answered' => false] is a model that does not know. Calling it again
     * changes the answer from then on.
     *
     * @param  string|array<string, mixed>|callable(array<string, mixed>): (string|array<string, mixed>)  $reply
     */
    private function fakeClaude(string|array|callable $reply = 'Câu trả lời của trợ lý.'): void
    {
        $this->claudeReply = $reply;
        config(['services.anthropic.key' => 'test-key']);

        $handler = new MockHandler;
        $handler->append(...array_fill(0, 20, fn (RequestInterface $request) => $this->claudeResponse($request)));

        $this->app->instance(ClaudeClient::class, new ClaudeClient(
            $this->app->make(ShopSettings::class),
            new Guzzle(['handler' => HandlerStack::create($handler)]),
        ));
    }

    /**
     * No key configured: the assistant cannot reach Claude at all.
     */
    private function claudeDown(): void
    {
        config(['services.anthropic.key' => '']);
    }

    private function claudeResponse(RequestInterface $request): Response
    {
        $body = (array) json_decode((string) $request->getBody(), true);
        $this->claudeRequests[] = $body;
        $reply = is_callable($this->claudeReply) ? ($this->claudeReply)($body) : $this->claudeReply;
        $answer = is_string($reply)
            ? ['reply' => $reply, 'answered' => true, 'handoff' => false, 'sources' => []]
            : $reply + ['reply' => '', 'answered' => true, 'handoff' => false, 'sources' => []];

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => $body['model'] ?? '',
            'content' => [['type' => 'text', 'text' => json_encode($answer)]],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
        ]));
    }

    /**
     * Everything the model was shown on the last call: system text, then the customer's message.
     */
    private function lastPrompt(): string
    {
        $body = end($this->claudeRequests) ?: [];

        return ($body['system'][0]['text'] ?? '')."\n".($body['messages'][0]['content'] ?? '');
    }

    private function chatCalls(): int
    {
        return count($this->claudeRequests);
    }

    private function policyPage(string $title, string $content, bool $visible = true): PageModel
    {
        return PageModel::create([
            'page_type' => PageType::Policy,
            'page_name' => $title,
            'page_slug' => Str::slug($title),
            'page_hidden' => $visible ? 1 : 0,
            'page_description' => 'Mô tả',
            'page_content' => '<p>'.$content.'</p>',
        ]);
    }

    private function faqQuestion(string $question, string $answer, string $group = 'Giao hàng', bool $visible = true): FaqModel
    {
        $faqGroup = FaqGroupModel::firstOrCreate(['group_name' => $group], ['group_position' => 1]);

        return FaqModel::create([
            'group_id' => $faqGroup->group_id,
            'faq_question' => $question,
            'faq_answer' => '<p>'.$answer.'</p>',
            'faq_hidden' => $visible ? 1 : 0,
        ]);
    }

    private function knowledge(string $title, string $content, string $scope = AssistantKnowledgeModel::SCOPE_ALL, ?int $scopeId = null): AssistantKnowledgeModel
    {
        return AssistantKnowledgeModel::create([
            'scope_type' => $scope,
            'scope_id' => $scopeId,
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * The widget's fetch sends cookies; a JSON test request only does with credentials on.
     */
    private function asVisitor(): static
    {
        return $this->withCookie(UserEventLog::COOKIE, self::VISITOR)->withCredentials();
    }

    private function orderFor(
        UserModel $user,
        string $code,
        OrderStatus $status = OrderStatus::Completed,
        string $productName = 'Adidas Forum Low Pride',
        string $size = '42',
        ?int $completedDaysAgo = 2,
    ): OrderModel {
        $order = OrderModel::create([
            'order_code' => $code,
            'order_name' => $user->name,
            'order_email' => $user->email,
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức, TP.HCM',
            'order_phone' => '0912345678',
            'order_delivery_fee' => 30000,
            'order_coupon_value' => 0,
            'order_total' => 1_030_000,
            'order_payment' => 'cod',
            'order_payment_status' => 0,
            'order_date' => now()->subDays(($completedDaysAgo ?? 0) + 3)->toDateString(),
            'order_status' => $status,
            'order_completed_at' => $status === OrderStatus::Completed && $completedDaysAgo !== null ? now()->subDays($completedDaysAgo) : null,
            'user_id' => $user->user_id,
        ]);

        OrderDetailModel::create([
            'order_id' => $order->order_id,
            'pro_id' => $this->makeProduct(slug: 'da-mua-'.strtolower($code))->pro_id,
            'pro_name' => $productName,
            'size' => $size,
            'color' => 'Đen',
            'price' => 1_000_000,
            'quantity' => 1,
        ]);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function ask(string $question, array $extra = [])
    {
        return $this->postJson(route('assistant.send'), ['message' => $question] + $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function lastBot($response): array
    {
        $bots = collect($response->json('messages'))->whereIn('sender', ['bot', 'system'])->values();

        return $bots->last() ?? [];
    }
}
