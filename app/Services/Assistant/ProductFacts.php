<?php

namespace App\Services\Assistant;

use App\Models\ProductModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What the shop knows about one product, as lines for the model: its name,
 * category, price, the variants in stock and its description.
 */
class ProductFacts
{
    private const DESCRIPTION_LENGTH = 1200;

    /**
     * A visible product the question names, for a question asked away from
     * that product's page.
     */
    public function named(string $question): ?ProductModel
    {
        $names = ProductModel::where('pro_hidden', 1)->pluck('pro_name', 'pro_id');
        $id = TextMatch::bestNamed($question, $names);

        return $id === null ? null : ProductModel::find($id);
    }

    /**
     * @return array<int, string>
     */
    public function lines(ProductModel $product): array
    {
        $product = ProductModel::withPriceRange()->with('getCate')->findOrFail($product->pro_id);
        $price = $product->priceRange();
        $variants = DB::table('products_quantity')
            ->leftJoin('size', 'size.size_id', '=', 'products_quantity.size_id')
            ->leftJoin('color', 'color.color_id', '=', 'products_quantity.color_id')
            ->where('products_quantity.pro_id', $product->pro_id)
            ->where('products_quantity.quantity', '>', 0)
            ->get(['size.size', 'color.color_vn']);

        $lines = [
            'Sản phẩm: '.$product->pro_name.' (mã '.$product->pro_code.'), danh mục '.($product->getCate->cate_name ?? '—').'.',
            'Giá bán: '.$this->money($price['min']).($price['max'] > $price['min'] ? ' đến '.$this->money($price['max']) : '').'.',
            $variants->isEmpty()
                ? 'Sản phẩm đang hết hàng.'
                : 'Còn hàng màu: '.$variants->pluck('color_vn')->filter()->unique()->implode(', ')
                    .'; size: '.$variants->pluck('size')->filter()->unique()->sort()->implode(', ').'.',
        ];

        $description = KnowledgeBase::plainText((string) $product->pro_description);

        if ($description !== '') {
            $lines[] = 'Mô tả sản phẩm: '.Str::limit($description, self::DESCRIPTION_LENGTH);
        }

        return $lines;
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', '.').'đ';
    }
}
