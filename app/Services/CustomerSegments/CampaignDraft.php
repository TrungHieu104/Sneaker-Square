<?php

namespace App\Services\CustomerSegments;

use App\Enums\CustomerSegment;
use App\Services\ShopSettings;

/**
 * What the campaign form starts from for each segment. The wording never names
 * the segment: "you are about to leave us" is no way to greet a customer.
 */
class CampaignDraft
{
    public const SUBJECT = 'Sneaker Square gửi riêng bạn một mã giảm giá';

    public const MAX_DISCOUNT = 200_000;

    public const MIN_ORDER = 1_000_000;

    public const DAYS = 7;

    public function __construct(private readonly ShopSettings $settings) {}

    /**
     * @return array{name: string, percent: int, min_order: int, max_discount: int, days: int, subject: string, message: string}
     */
    public function for(CustomerSegment $segment): array
    {
        [$name, $message] = match ($segment) {
            CustomerSegment::Loyal => ['Tri ân khách thân thiết', 'Cảm ơn bạn đã luôn đồng hành cùng Sneaker Square. Cửa hàng gửi riêng bạn một mã giảm giá cho lần mua tới.'],
            CustomerSegment::Promising => ['Thúc đơn thứ hai', 'Cảm ơn bạn đã mua hàng tại Sneaker Square. Cửa hàng gửi riêng bạn một mã giảm giá cho đơn tiếp theo.'],
            CustomerSegment::Regular => ['Ưu đãi khách hàng', 'Sneaker Square có nhiều mẫu mới và gửi riêng bạn một mã giảm giá cho lần mua tới.'],
            CustomerSegment::AtRisk => ['Mời khách cũ quay lại', 'Đã lâu bạn chưa ghé Sneaker Square. Cửa hàng có nhiều mẫu mới và gửi riêng bạn một mã giảm giá cho lần mua tới.'],
            CustomerSegment::Lost => ['Chiến dịch cuối', 'Đã lâu bạn chưa ghé Sneaker Square. Nếu bạn muốn quay lại, đây là mã giảm giá dành riêng cho bạn.'],
        };

        return [
            'name' => $name,
            'percent' => $this->settings->segmentOffer($segment)['discount'],
            'min_order' => self::MIN_ORDER,
            'max_discount' => self::MAX_DISCOUNT,
            'days' => self::DAYS,
            'subject' => self::SUBJECT,
            'message' => $message,
        ];
    }
}
