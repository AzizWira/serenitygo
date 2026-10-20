<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class SerenityPaginationTest extends TestCase
{
    private function paginator(int $currentPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            array_fill(0, 12, ['id' => 1]),
            335,
            12,
            $currentPage,
            ['path' => '/owner/batches', 'query' => ['search' => 'JP', 'origin' => 'JP']]
        );
    }

    public function test_pagination_uses_small_text_controls_without_svg_dependencies(): void
    {
        $html = (string) $this->paginator(2)->onEachSide(1)->links('pagination.serenity');

        $this->assertStringContainsString('class="serenity-pagination"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('335', $html);
        $this->assertStringContainsString('Halaman berikutnya', $html);
        $this->assertStringContainsString('serenity-pagination-mobile', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringNotContainsString('class="w-5 h-5"', $html);
    }

    public function test_query_filters_preserved_across_pages(): void
    {
        $html = (string) $this->paginator(2)->onEachSide(1)->links('pagination.serenity');

        $this->assertStringContainsString('search=JP', $html);
        $this->assertStringContainsString('origin=JP', $html);
        $this->assertStringContainsString('page=3', $html);
    }

    public function test_first_and_last_page_disable_correct_direction(): void
    {
        $first = (string) $this->paginator(1)->onEachSide(1)->links('pagination.serenity');
        $last = (string) $this->paginator(28)->onEachSide(1)->links('pagination.serenity');

        $this->assertStringContainsString('aria-disabled="true"', $first);
        $this->assertStringContainsString('aria-disabled="true"', $last);
        $this->assertStringContainsString('rel="next"', $first);
        $this->assertStringContainsString('rel="prev"', $last);
    }
}
