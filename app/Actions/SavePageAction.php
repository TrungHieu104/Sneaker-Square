<?php

namespace App\Actions;

use App\Models\PageModel;

/**
 * Saves a policy or the about page from its admin form.
 */
class SavePageAction
{
    private const SLUG_SUFFIX = '.html';

    /**
     * @param  array<string, mixed>  $form  the admin form's fields
     */
    public function execute(PageModel $page, array $form, bool $visibleByDefault): PageModel
    {
        $creating = ! $page->exists;

        $page->page_name = (string) ($form['name'] ?? '');
        $page->page_content = (string) ($form['content'] ?? '');
        $page->page_meta_keywords = (string) ($form['key'] ?? '');
        $page->page_meta_description = (string) ($form['des'] ?? '');
        $page->page_hidden = (int) ($form['status'] ?? ($visibleByDefault ? 1 : 0));

        // A field the form leaves out keeps what is stored: the about page has no
        // slug field, its address being fixed.
        if ($creating || array_key_exists('slug', $form)) {
            $page->page_slug = $this->slug((string) ($form['slug'] ?? ''));
        }

        if ($creating || array_key_exists('description', $form)) {
            $page->page_description = (string) ($form['description'] ?? '');
        }

        if ($creating) {
            $page->page_created_by = (string) ($form['author'] ?? '');
        } else {
            $page->page_updated_by = (string) ($form['editor'] ?? '');
        }

        $page->save();

        return $page;
    }

    private function slug(string $slug): string
    {
        return $slug === '' || str_ends_with($slug, self::SLUG_SUFFIX) ? $slug : $slug.self::SLUG_SUFFIX;
    }
}
