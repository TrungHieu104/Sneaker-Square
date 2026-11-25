<?php

namespace App\Services\Assistant;

use App\Enums\PageType;
use App\Models\AssistantKnowledgeModel;
use App\Models\AssistantUnansweredModel;
use App\Models\CategoryModel;
use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use App\Models\PageModel;
use App\Models\ProductModel;
use App\Services\FootSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What the assistant may quote: the size chart, the policy and about pages
 * and the public FAQ as they read now, and the answers staff wrote for
 * questions it could not answer.
 *
 * At a few pages the whole set goes to the model with every question; the
 * prompt cache keeps that cheap, and an admin edit applies to the next one.
 */
class KnowledgeBase
{
    public const SIZE_CHART = 'Bảng quy đổi size';

    public function __construct(private readonly FootSize $feet) {}

    /**
     * Everything that applies shop-wide: the size chart, the shown pages, the
     * shown FAQ questions, then staff answers.
     *
     * @return array<int, array{title: string, content: string}>
     */
    public function shared(): array
    {
        $pages = PageModel::visible()
            ->orderBy('page_id')
            ->get(['page_name', 'page_content'])
            ->map(fn (PageModel $page) => ['title' => (string) $page->page_name, 'content' => self::plainText((string) $page->page_content)]);

        $faqs = $this->publishedFaqs()
            ->map(fn (FaqModel $faq) => ['title' => $faq->faq_question, 'content' => self::plainText($faq->faq_answer)]);

        $answers = AssistantKnowledgeModel::where('scope_type', AssistantKnowledgeModel::SCOPE_ALL)
            ->orderBy('id')
            ->get(['title', 'content'])
            ->map(fn (AssistantKnowledgeModel $entry) => ['title' => $entry->title, 'content' => $entry->content]);

        return collect([$this->sizeChart()])->concat($pages)->concat($faqs)->concat($answers)
            ->filter(fn (array $entry) => $entry['content'] !== '')
            ->values()
            ->all();
    }

    /**
     * Where a customer reads each quoted source in full, by title: a policy's
     * own page, the about page, the size chart page, or the exact question on
     * the FAQ page. A staff answer has no page of its own, so it is not here.
     *
     * @return array<string, string>
     */
    public function pages(): array
    {
        $links = PageModel::visible()
            ->get(['page_name', 'page_slug', 'page_type'])
            ->mapWithKeys(fn (PageModel $page) => [
                (string) $page->page_name => $page->page_type === PageType::About
                    ? route('about.page')
                    : route('policy.detail', (string) $page->page_slug),
            ]);

        return $this->publishedFaqs()
            ->mapWithKeys(fn (FaqModel $faq) => [$faq->faq_question => route('faq.page').'#cau-hoi-'.$faq->faq_id])
            ->union($links)
            ->put(self::SIZE_CHART, route('size.chart'))
            ->all();
    }

    /**
     * The questions the storefront FAQ page shows, and only those.
     *
     * @return Collection<int, FaqModel>
     */
    private function publishedFaqs(): Collection
    {
        return FaqGroupModel::published()->flatMap(fn (FaqGroupModel $group) => $group->faqs)->values()->toBase();
    }

    /**
     * The same chart the size chart page prints and the foot-measuring page
     * uses, so the three never disagree.
     *
     * @return array{title: string, content: string}
     */
    private function sizeChart(): array
    {
        $lines = collect($this->feet->ranges())->map(fn (array $range, int $size) => 'Size '.$size.': bàn chân dài đến '.$range['to'].' mm, size Nhật '.number_format($range['asian'] / 10, 1, ',', '').' / Hàn '.$range['asian']);

        return [
            'title' => self::SIZE_CHART,
            'content' => 'Size giày ở Việt Nam và của shop đánh số theo chuẩn EU, dò theo chiều dài bàn chân, đo từ gót đến đầu ngón dài nhất; bảng đã chừa '.FootSize::TOE_ROOM_MM." mm cho ngón chân.\n"
                .$lines->implode("\n")."\n"
                .'Bảng đầy đủ ở trang '.route('size.chart').'. Khách có thể đo chiều dài bàn chân bằng ảnh chụp ở trang '.route('foot.measure').'.',
        ];
    }

    /**
     * Staff answers that apply only to this product or its category.
     *
     * @return array<int, array{title: string, content: string}>
     */
    public function forProduct(ProductModel $product): array
    {
        return AssistantKnowledgeModel::query()
            ->where(fn ($q) => $q->where('scope_type', AssistantKnowledgeModel::SCOPE_PRODUCT)->where('scope_id', $product->pro_id))
            ->orWhere(fn ($q) => $q->where('scope_type', AssistantKnowledgeModel::SCOPE_CATEGORY)->where('scope_id', $product->cate_id))
            ->orderBy('id')
            ->get(['title', 'content'])
            ->map(fn (AssistantKnowledgeModel $entry) => ['title' => $entry->title, 'content' => $entry->content])
            ->all();
    }

    /**
     * The same answer saved twice (a double submit) is kept once: every copy would be sent with each question.
     *
     * @param  int|null  $messageId  the staff reply in a chat it is saved from
     */
    public function addAnswer(string $question, string $answer, string $scopeType, ?int $scopeId, ?int $authorId, ?int $messageId = null): AssistantKnowledgeModel
    {
        return AssistantKnowledgeModel::firstOrCreate([
            'scope_type' => $scopeType,
            'scope_id' => $scopeType === AssistantKnowledgeModel::SCOPE_ALL ? null : $scopeId,
            'title' => $question,
            'content' => $answer,
        ], ['created_by' => $authorId, 'message_id' => $messageId]);
    }

    /**
     * The questions it answered are open again: without it, they are unanswered.
     */
    public function deleteAnswer(AssistantKnowledgeModel $entry): void
    {
        DB::transaction(function () use ($entry) {
            AssistantUnansweredModel::where('knowledge_id', $entry->id)
                ->update(['resolution' => null, 'resolved_at' => null, 'knowledge_id' => null]);
            $entry->delete();
        });
    }

    /**
     * Moves a shop-wide answer onto the public FAQ page. It leaves the saved
     * answers rather than staying in both, so the model is not shown it twice;
     * the questions it answered stay answered.
     */
    public function publishAnswer(AssistantKnowledgeModel $entry, int $groupId, ?string $author): FaqModel
    {
        return DB::transaction(function () use ($entry, $groupId, $author) {
            $faq = FaqModel::create([
                'group_id' => $groupId,
                'faq_question' => Str::limit($entry->title, 255, ''),
                'faq_answer' => '<p>'.nl2br(e($entry->content), false).'</p>',
                'faq_position' => FaqModel::nextPosition($groupId),
                'faq_created_by' => $author,
            ]);

            AssistantUnansweredModel::where('knowledge_id', $entry->id)->update(['knowledge_id' => null]);
            $entry->delete();

            return $faq;
        });
    }

    public function updateAnswer(AssistantKnowledgeModel $entry, string $question, string $answer, string $scopeType, ?int $scopeId): void
    {
        $entry->update([
            'scope_type' => $scopeType,
            'scope_id' => $scopeType === AssistantKnowledgeModel::SCOPE_ALL ? null : $scopeId,
            'title' => $question,
            'content' => $answer,
        ]);
    }

    /**
     * Where an answer about this product may apply, for the admin forms.
     *
     * @return array<int, array{scope: string, id: ?int, label: string}>
     */
    public function scopeChoices(?ProductModel $product): array
    {
        $choices = [['scope' => AssistantKnowledgeModel::SCOPE_ALL, 'id' => null, 'label' => 'Toàn shop']];

        if ($product === null) {
            return $choices;
        }

        $choices[] = ['scope' => AssistantKnowledgeModel::SCOPE_PRODUCT, 'id' => $product->pro_id, 'label' => 'Chỉ sản phẩm '.$product->pro_name];

        if ($product->getCate !== null) {
            $choices[] = ['scope' => AssistantKnowledgeModel::SCOPE_CATEGORY, 'id' => $product->cate_id, 'label' => 'Tất cả sản phẩm '.$product->getCate->cate_name];
        }

        return $choices;
    }

    /**
     * Every staff answer, newest first, with where it applies in words and
     * the choices its edit form offers.
     *
     * @return Collection<int, array{entry: AssistantKnowledgeModel, scope: string, choices: array<int, array{scope: string, id: ?int, label: string}>}>
     */
    public function answers(): Collection
    {
        $entries = AssistantKnowledgeModel::latest('updated_at')->get();
        $products = ProductModel::with('getCate')
            ->whereIn('pro_id', $entries->where('scope_type', AssistantKnowledgeModel::SCOPE_PRODUCT)->pluck('scope_id'))
            ->get()
            ->keyBy('pro_id');
        $categories = CategoryModel::whereIn('cate_id', $entries->where('scope_type', AssistantKnowledgeModel::SCOPE_CATEGORY)->pluck('scope_id'))
            ->pluck('cate_name', 'cate_id');

        return $entries->map(function (AssistantKnowledgeModel $entry) use ($products, $categories) {
            $product = $entry->scope_type === AssistantKnowledgeModel::SCOPE_PRODUCT ? $products->get($entry->scope_id) : null;
            $choices = $this->scopeChoices($product);

            if ($entry->scope_type === AssistantKnowledgeModel::SCOPE_CATEGORY) {
                $choices[] = ['scope' => AssistantKnowledgeModel::SCOPE_CATEGORY, 'id' => $entry->scope_id, 'label' => 'Tất cả sản phẩm '.($categories[$entry->scope_id] ?? 'đã xoá')];
            }

            $current = collect($choices)->first(fn (array $choice) => $choice['scope'] === $entry->scope_type && $choice['id'] === $entry->scope_id);

            return [
                'entry' => $entry,
                'scope' => $current['label'] ?? 'Sản phẩm đã xoá',
                'choices' => $choices,
            ];
        });
    }

    /**
     * Pages and FAQ answers are written in the admin editor: HTML with entities.
     */
    public static function plainText(string $html): string
    {
        $text = preg_replace('/<\s*(br|\/p|\/li|\/h\d|\/div)[^>]*>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim(preg_replace('/\s*\n\s*/', "\n", $text) ?? $text);
    }
}
