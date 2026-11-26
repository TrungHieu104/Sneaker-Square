<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use App\Models\AssistantConversationModel as Conversation;
use App\Models\ProductModel;
use App\Services\FootSize;
use App\Services\ShoeSizeMemory;
use Illuminate\Support\Facades\DB;

/**
 * Which size to take: the size the shop already remembers for this shopper
 * (the same one the product page preselects) and whether this product still
 * has it. Nothing is said about how a model fits; the shop has no data on it.
 */
class SizeAdvice
{
    private const ORDERS_CITED = 3;

    private const SOURCE_LABELS = [
        ShoeSizeMemory::MEASURED => 'Size bạn đã đo',
        ShoeSizeMemory::PURCHASED => 'Lịch sử mua của bạn',
        ShoeSizeMemory::CARTED => 'Size bạn chọn lần trước',
    ];

    public function __construct(private readonly ShoeSizeMemory $memory) {}

    /**
     * @param  Conversation  $conversation  whose account and visitor cookie the size is remembered by
     * @param  int|null  $measured  the size the customer measured, read in their request
     */
    public function reply(?ProductModel $product, string $question, Conversation $conversation, ?int $measured): ReplyDraft
    {
        $measure = ReplyDraft::link('Đo cỡ chân của tôi', route('foot.measure', $product ? ['sp' => $product->pro_slug] : []));

        if ($product === null) {
            return ReplyDraft::fixed(
                AssistantIntent::Size,
                'Bạn mở trang mẫu giày đang quan tâm rồi hỏi lại, mình sẽ xem size của bạn còn hàng không. Hoặc bạn đo cỡ chân bằng ảnh chụp để biết size của mình.',
                [$measure],
            );
        }

        $asked = $this->askedSize($question);

        if ($asked !== null) {
            return $this->askedReply($product, $asked, $measure);
        }

        ['size' => $size, 'source' => $source] = $this->memory->rememberedFor(
            $measured,
            $conversation->user_id === null ? null : (int) $conversation->user_id,
            $conversation->session_id === null ? null : (string) $conversation->session_id,
        );

        if ($size === null) {
            return ReplyDraft::fixed(
                AssistantIntent::Size,
                'Mình chưa biết size chân của bạn. Bạn đo cỡ chân bằng ảnh chụp tờ A4, hệ thống sẽ gợi ý size và lần sau tự chọn sẵn.',
                [$measure],
            );
        }

        $stock = $this->stockFor($product, $size);
        $facts = array_merge(
            [$this->whereFrom($size, (string) $source, (int) $conversation->user_id)],
            $this->stockFacts($product, $size, $stock),
            [$stock === [] ? 'Gợi ý: đo lại cỡ chân để chọn mẫu khác.' : 'Gợi ý cho khách: chọn size '.$size.'.'],
        );
        $actions = $stock === [] ? [$measure] : [
            $this->pick($product, $size),
            $measure,
        ];

        return new ReplyDraft(
            AssistantIntent::Size,
            $this->summary($product, $size, $stock),
            $facts,
            $actions,
        );
    }

    /**
     * Selects the size on the product's page; from any other page it opens that page.
     *
     * @return array<string, mixed>
     */
    private function pick(ProductModel $product, int $size): array
    {
        return [
            'label' => 'Chọn size '.$size,
            'kind' => 'select_size',
            'value' => (string) $size,
            'product' => (int) $product->pro_id,
            'url' => route('product.detail', $product->pro_slug),
            'primary' => true,
        ];
    }

    /**
     * A shoe size typed in the question ("còn size 42 không"), not a weight or a length ("nặng 45 kg").
     */
    private function askedSize(string $question): ?int
    {
        preg_match_all('/\b(\d{2})\b(?!\s*(?:kg|cm|mm|tuổi|tuoi|%))/iu', $question, $found);

        foreach ($found[1] as $number) {
            if ((int) $number >= FootSize::MIN_SIZE && (int) $number <= FootSize::MAX_SIZE) {
                return (int) $number;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $measure
     */
    private function askedReply(ProductModel $product, int $size, array $measure): ReplyDraft
    {
        $stock = $this->stockFor($product, $size);
        $actions = $stock === [] ? [$measure] : [
            $this->pick($product, $size),
        ];

        return new ReplyDraft(
            AssistantIntent::Size,
            $stock === []
                ? $product->pro_name.' đã hết size '.$size.' ở mọi màu.'
                : $this->inStock($product, $size, $stock),
            $this->stockFacts($product, $size, $stock),
            $actions,
        );
    }

    private function whereFrom(int $size, string $source, int $userId): string
    {
        if ($source !== ShoeSizeMemory::PURCHASED) {
            return mb_strtolower(self::SOURCE_LABELS[$source] ?? 'size của bạn').': '.$size.'.';
        }

        $codes = ShoeSizeMemory::keptPurchases($userId)
            ->where('order_details.size', (string) $size)
            ->limit(self::ORDERS_CITED)
            ->pluck('order.order_code');

        return 'Khách đã mua size '.$size.' ở '.$codes->count().' đơn gần nhất ('.$codes->implode(', ').') và giữ lại, không trả hàng.';
    }

    /**
     * @return array<string, int> quantity left by colour name
     */
    private function stockFor(ProductModel $product, int $size): array
    {
        return DB::table('products_quantity')
            ->join('size', 'size.size_id', '=', 'products_quantity.size_id')
            ->leftJoin('color', 'color.color_id', '=', 'products_quantity.color_id')
            ->where('products_quantity.pro_id', $product->pro_id)
            ->where('size.size', (string) $size)
            ->where('products_quantity.quantity', '>', 0)
            ->orderBy('products_quantity.color_id')
            ->pluck('products_quantity.quantity', 'color.color_vn')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * @param  array<string, int>  $stock
     * @return array<int, string>
     */
    private function stockFacts(ProductModel $product, int $size, array $stock): array
    {
        if ($stock === []) {
            return [$product->pro_name.' đã hết size '.$size.' ở mọi màu.'];
        }

        return [$this->inStock($product, $size, $stock)];
    }

    /**
     * @param  array<string, int>  $stock
     */
    private function inStock(ProductModel $product, int $size, array $stock): string
    {
        return $product->pro_name.' size '.$size.' còn hàng: '.$this->stockList($stock).'.';
    }

    /**
     * @param  array<string, int>  $stock
     */
    private function summary(ProductModel $product, int $size, array $stock): string
    {
        return $stock === []
            ? 'Size của bạn là '.$size.', nhưng '.$product->pro_name.' đã hết size này.'
            : 'Size của bạn là '.$size.'. '.$this->inStock($product, $size, $stock);
    }

    /**
     * @param  array<string, int>  $stock
     */
    private function stockList(array $stock): string
    {
        return collect($stock)->map(fn (int $quantity, string $color) => 'màu '.($color !== '' ? $color : 'duy nhất').' '.$quantity.' đôi')->implode(', ');
    }
}
