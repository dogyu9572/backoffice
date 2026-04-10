<?php

namespace App\View\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;
use Illuminate\View\View;

class Pagination extends Component
{
    public array $navigation;

    public function __construct(public LengthAwarePaginator $paginator)
    {
        $this->navigation = $this->buildNavigation(10);
    }

    /**
     * 페이지네이션 네비게이션 데이터를 생성한다.
     */
    private function buildNavigation(int $windowSize): array
    {
        $currentPage = $this->paginator->currentPage();
        $lastPage = $this->paginator->lastPage();
        $safeWindowSize = max($windowSize, 1);

        $startPage = (int) (floor(($currentPage - 1) / $safeWindowSize) * $safeWindowSize) + 1;
        $endPage = min($startPage + $safeWindowSize - 1, $lastPage);

        return [
            'first_page' => 1,
            'last_page' => $lastPage,
            'previous_chunk_page' => max($currentPage - $safeWindowSize, 1),
            'next_chunk_page' => min($currentPage + $safeWindowSize, $lastPage),
            'has_previous_chunk' => $currentPage > 1,
            'has_next_chunk' => $currentPage < $lastPage,
            'page_urls' => $this->paginator->getUrlRange($startPage, $endPage),
        ];
    }

    public function render(): View
    {
        return view('components.pagination');
    }
}
