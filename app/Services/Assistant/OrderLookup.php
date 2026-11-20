<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\UserModel;
use App\Services\ShopSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Answers about the asker's own orders. Every query here is bound to the
 * logged-in customer's user_id: a code belonging to someone else is simply
 * not found, which is what stops one customer reading another's order.
 */
class OrderLookup
{
    private const RECENT = 20;

    private const LINES_SHOWN = 5;

    private const DATE = 'd/m/Y';

    private const WINDOW_OPEN = 'open';

    private const EXPIRED = 'expired';

    private const RETURNING = 'returning';

    private const RETURNED = 'returned';

    public function __construct(
        private readonly IntentRouter $router,
        private readonly ShopSettings $settings,
    ) {}

    /**
     * The customer's order holding a product the question names, if any.
     */
    public function namedPurchase(?UserModel $user, string $question): ?OrderModel
    {
        if ($user === null) {
            return null;
        }

        $orders = $this->recentOrders($user);
        $names = [];

        foreach ($orders as $order) {
            foreach ($order->orderDetail as $line) {
                $names[(int) $line->order_details_id] = (string) $line->pro_name;
            }
        }

        $lineId = TextMatch::bestNamed($question, $names);

        return $lineId === null ? null : $orders->first(
            fn (OrderModel $order) => $order->orderDetail->contains('order_details_id', $lineId),
        );
    }

    public function reply(?UserModel $user, string $question): ReplyDraft
    {
        if ($user === null) {
            return ReplyDraft::fixed(
                AssistantIntent::Order,
                'Để bảo vệ thông tin đơn hàng, mình chỉ tra được đơn của tài khoản đang đăng nhập. Bạn đăng nhập rồi hỏi lại giúp mình nhé.',
                [ReplyDraft::link('Đăng nhập', route('user.login'), primary: true), ReplyDraft::handoff()],
            );
        }

        $order = $this->find($user, $question);

        if ($order === null) {
            return ReplyDraft::fixed(
                AssistantIntent::Order,
                'Mình không tìm thấy đơn nào khớp với câu hỏi trong tài khoản của bạn. Bạn kiểm tra lại mã đơn giúp mình nhé.',
                [ReplyDraft::link('Xem đơn hàng', route('user.order')), ReplyDraft::handoff()],
            );
        }

        $returns = $order->hasStatus(OrderStatus::Completed) && $order->returnDeadline() !== null
            ? $this->returnState($order)
            : null;

        return new ReplyDraft(
            AssistantIntent::Order,
            $this->summary($order, $returns),
            $this->facts($order, $returns),
            [ReplyDraft::handoff(), ReplyDraft::link('Xem đơn hàng', route('user.order'))],
            $this->card($order, $returns),
        );
    }

    private function find(UserModel $user, string $question): ?OrderModel
    {
        $codes = $this->router->orderCodes($question);

        if ($codes !== []) {
            return OrderModel::with('orderDetail')
                ->where('user_id', $user->user_id)
                ->whereIn('order_code', $codes)
                ->orderByDesc('order_id')
                ->first();
        }

        return $this->namedPurchase($user, $question) ?? $this->recentOrders($user)->first();
    }

    /**
     * @return Collection<int, OrderModel>
     */
    private function recentOrders(UserModel $user): Collection
    {
        return OrderModel::with('orderDetail')
            ->where('user_id', $user->user_id)
            ->orderByDesc('order_id')
            ->limit(self::RECENT)
            ->get();
    }

    /**
     * canRequestReturn() is also false while a return is open or after one
     * settled; neither of those is an expired window.
     */
    private function returnState(OrderModel $order): string
    {
        return match (true) {
            $order->activeReturn()->exists() => self::RETURNING,
            $order->orderReturns()->whereIn('status', OrderReturnModel::SETTLED)->exists() => self::RETURNED,
            $order->canRequestReturn() => self::WINDOW_OPEN,
            default => self::EXPIRED,
        };
    }

    private function summary(OrderModel $order, ?string $returns): string
    {
        $text = 'Đơn '.$order->order_code.' đang ở trạng thái "'.$order->order_status->label().'".';
        $deadline = $order->returnDeadline()?->format(self::DATE);

        return $text.match ($returns) {
            self::WINDOW_OPEN => ' Shop nhận đổi trả trong '.$this->settings->returnDays().' ngày kể từ khi đơn hoàn tất, bạn còn hạn đến '.$deadline.'.',
            self::EXPIRED => ' Shop nhận đổi trả trong '.$this->settings->returnDays().' ngày kể từ khi đơn hoàn tất, hạn đổi trả của đơn này đã hết từ '.$deadline.'.',
            self::RETURNING => ' Đơn này đang có yêu cầu trả hàng, shop đang xử lý.',
            self::RETURNED => ' Đơn này đã được trả hàng.',
            default => '',
        };
    }

    /**
     * @return array<int, string>
     */
    private function facts(OrderModel $order, ?string $returns): array
    {
        $facts = [
            'Đơn '.$order->order_code.', đặt ngày '.Carbon::parse($order->order_date)->format(self::DATE)
                .', trạng thái: '.$order->order_status->label().'.',
            'Tổng tiền '.number_format((int) $order->order_total, 0, ',', '.').'đ, '
                .$order->paymentLabel().' ('.$order->paymentStatusLabel().').',
        ];

        foreach ($order->orderDetail->take(self::LINES_SHOWN) as $line) {
            $facts[] = 'Sản phẩm: '.$line->pro_name.($line->size ? ', size '.$line->size : '')
                .($line->color ? ', màu '.$line->color : '').', số lượng '.$line->quantity.'.';
        }

        if ($order->order_shipping_code) {
            $facts[] = 'Mã vận đơn: '.$order->order_shipping_code.'.';
        }

        if (! $order->order_status->isFinal() && $order->order_expected_delivery) {
            $facts[] = 'Dự kiến giao: '.Carbon::parse($order->order_expected_delivery)->format(self::DATE).'.';
        }

        return array_merge($facts, $this->returnFacts($order, $returns));
    }

    /**
     * @return array<int, string>
     */
    private function returnFacts(OrderModel $order, ?string $returns): array
    {
        if ($returns === null) {
            return [];
        }

        $deadline = $order->returnDeadline()?->format(self::DATE);
        $facts = [
            'Đơn hoàn tất ngày '.($order->order_completed_at ?? $order->updated_at)?->format(self::DATE).'.',
            'Shop nhận đổi trả trong '.$this->settings->returnDays().' ngày kể từ khi đơn hoàn tất.',
        ];

        // How to send it back is left out unless the window is open: given both, the model read it as still allowed.
        return [...$facts, ...match ($returns) {
            self::WINDOW_OPEN => [
                'Đơn này còn hạn đổi trả đến hết ngày '.$deadline.'.',
                'Khách tự gửi yêu cầu trả hàng ở trang Đơn hàng của tôi; trợ lý không tạo yêu cầu thay khách.',
            ],
            self::RETURNING => [
                'Đơn này đang có yêu cầu trả hàng ('.$order->activeReturn?->statusLabel().'), shop đang xử lý; khách theo dõi ở trang Đơn hàng của tôi.',
                'Mỗi đơn chỉ gửi một yêu cầu trả hàng, nên khách không gửi thêm yêu cầu khác cho đơn này.',
            ],
            self::RETURNED => [
                'Đơn này đã được trả hàng. Mỗi đơn chỉ trả hàng một lần, nên khách không gửi thêm yêu cầu khác cho đơn này.',
            ],
            default => [
                'Đơn này đã hết hạn đổi trả từ '.$deadline.': khách không thể đổi size hay trả hàng đơn này nữa.',
                'Nếu giày lỗi do nhà sản xuất (bung keo, đứt chỉ) thì xét theo chính sách bảo hành; khách có thể nhờ nhân viên kiểm tra.',
            ],
        }];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(OrderModel $order, ?string $returns): array
    {
        $first = $order->orderDetail->first();
        $more = $order->orderDetail->count() - 1;
        $rows = [
            ['Sản phẩm', (string) $first?->pro_name.($more > 0 ? ' +'.$more : ''), null],
            ['Size', ($first?->size ?? '—').' × '.($first?->quantity ?? 0), null],
        ];

        if ($returns !== null) {
            $rows[] = ['Hoàn tất', ($order->order_completed_at ?? $order->updated_at)?->format(self::DATE), null];
            $rows[] = ['Hạn đổi trả', $order->returnDeadline()?->format(self::DATE), match ($returns) {
                self::EXPIRED => 'Đã hết',
                self::RETURNING => 'Đang trả hàng',
                self::RETURNED => 'Đã trả hàng',
                default => null,
            }];
        } elseif ($order->order_shipping_code) {
            $rows[] = ['Mã vận đơn', $order->order_shipping_code, null];
        } else {
            $rows[] = ['Ngày đặt', Carbon::parse($order->order_date)->format(self::DATE), null];
        }

        return [
            'title' => 'Đơn '.$order->order_code,
            'status' => $order->order_status->label(),
            'tone' => $this->tone($order->order_status),
            'rows' => $rows,
        ];
    }

    private function tone(OrderStatus $status): string
    {
        return match (true) {
            $status === OrderStatus::Cancelled => 'muted',
            $status->isComingBack(), $status === OrderStatus::CancelRequested => 'warn',
            default => 'ok',
        };
    }
}
