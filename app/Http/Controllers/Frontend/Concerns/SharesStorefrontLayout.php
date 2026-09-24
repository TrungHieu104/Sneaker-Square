<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Models\ContactModel;
use App\Models\FaqModel;
use App\Models\MenuModel;
use App\Models\PromotionModel;

/**
 * The header, footer and menu every storefront page renders around its content.
 *
 * `frontend.index` reads these four out of the view bag rather than being
 * passed them, so a controller that does not share them renders a page that
 * dies on an undefined variable rather than one that merely looks wrong.
 */
trait SharesStorefrontLayout
{
    private function shareStorefrontLayout(): void
    {
        $menu = $this->menuTree(MenuModel::where('menu_hidden', 1)->orderBy('menu_position', 'asc')->get());
        $slide = PromotionModel::where('cate_slide_id', 1)->where('promotion_hidden', 1)->get();
        $contact = ContactModel::where('contact_hidden', 1)->limit(1)->get();
        $faq = FaqModel::where('faq_hidden', 1)->where('faq_about', 0)->orderBy('faq_id', 'desc')->get();

        view()->share(compact('menu', 'slide', 'contact', 'faq'));
    }

    /**
     * @param  iterable<int, MenuModel>  $items
     * @return array<int, MenuModel>
     */
    private function menuTree(iterable $items, int $parentId = 0, int $level = 0): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ((int) $item['menu_parent_id'] !== $parentId) {
                continue;
            }

            $item['level'] = $level;
            $tree[] = $item;

            foreach ($this->menuTree($items, (int) $item['menu_id'], $level + 1) as $child) {
                $tree[] = $child;
            }
        }

        return $tree;
    }
}
