<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;

/**
 * Sorts a question by plain rules, in this order: asking for a person, an
 * order (a code, words about one's own order, or a product the customer
 * bought), the return policy or size chart, picking a size, a product (the
 * page being viewed or a product named), and the shop's policies for
 * everything else.
 */
class IntentRouter
{
    // SAMPLE-00081 from the sample data, 290920263059 from checkout; 0 or 84
    // followed by nine digits is a phone number, not an order.
    public const ORDER_CODE = '/\b([a-z]+-\d{3,}|(?!(?:0|84)\d{9}\b)\d{10,14})\b/i';

    private const HANDOFF_WORDS = ['nhan vien', 'nguoi that', 'tu van vien', 'chuyen giup', 'gap nguoi'];

    // About one's own order. "Đơn hàng bao lâu thì giao" or "muốn huỷ đơn thì làm sao"
    // ask about the policy, so the bare "đơn hàng" or "huỷ đơn" is not enough.
    private const ORDER_WORDS = [
        'don cua', 'don minh', 'don toi', 'don em', 'don hang cua', 'don hang minh', 'don hang toi', 'don hang em',
        'don vua dat', 'don da dat', 'don moi dat', 'don gan nhat', 'don hang gan nhat',
        'ma don', 'van don', 'kiem tra don', 'tra cuu don', 'tinh trang don', 'trang thai don',
        'giao chua', 'toi dau', 'den dau', 'da giao',
    ];

    private const SIZE_WORDS = ['size', 'co giay', 'co chan', 'so may', 'di so', 'vua chan', 'lay so'];

    // The chart itself is in the shop's knowledge; no shopper's size is needed to read it.
    private const SIZE_CHART_WORDS = ['bang size', 'bang quy doi', 'quy doi size', 'bang co'];

    // "đôi" (a pair) folds to the same "doi" as "đổi" (exchange), so the bare word is not enough.
    public const RETURN_WORDS = [
        'doi tra', 'doi size', 'doi hang', 'doi sang', 'doi duoc', 'duoc doi', 'doi lai',
        'tra hang', 'tra lai', 'hoan tien', 'bao hanh',
    ];

    public function classify(string $question, bool $aboutProduct, bool $namesOwnPurchase): AssistantIntent
    {
        $folded = TextMatch::fold($question);

        return match (true) {
            TextMatch::mentions($folded, self::HANDOFF_WORDS) => AssistantIntent::Handoff,
            $this->asksAboutOrder($question, $folded, $namesOwnPurchase) => AssistantIntent::Order,
            TextMatch::mentions($folded, self::RETURN_WORDS), TextMatch::mentions($folded, self::SIZE_CHART_WORDS) => AssistantIntent::Policy,
            TextMatch::mentions($folded, self::SIZE_WORDS) => AssistantIntent::Size,
            $aboutProduct => AssistantIntent::Product,
            default => AssistantIntent::Policy,
        };
    }

    // Naming a product the customer bought is about that order only when the
    // question is about the purchase; "does it come in 42" is about the product.
    private const PURCHASE_WORDS = ['mua', 'da dat', 'dat hang', 'nhan hang', 'giao'];

    public function talksAboutPurchase(string $question): bool
    {
        $folded = TextMatch::fold($question);

        return TextMatch::mentions($folded, self::PURCHASE_WORDS) || TextMatch::mentions($folded, self::RETURN_WORDS);
    }

    /**
     * @return array<int, string>
     */
    public function orderCodes(string $question): array
    {
        preg_match_all(self::ORDER_CODE, $question, $found);

        return array_map('strtoupper', $found[1]);
    }

    private function asksAboutOrder(string $question, string $folded, bool $namesOwnPurchase): bool
    {
        return $this->orderCodes($question) !== []
            || TextMatch::mentions($folded, self::ORDER_WORDS)
            || $namesOwnPurchase;
    }
}
