<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

trait AssertsTooltips
{
    /**
     * The note sits in a tooltip after its label and nowhere else on the page,
     * so it no longer takes room under the field.
     */
    private function assertOnlyInTooltip(TestResponse $page, string $label, string $note): void
    {
        $page->assertSeeInOrder([$label, 'data-bs-toggle="tooltip"', 'title="'.e($note).'"'], false);

        $this->assertSame(1, substr_count((string) $page->getContent(), e($note)), "Shown outside its tooltip: {$note}");
    }
}
