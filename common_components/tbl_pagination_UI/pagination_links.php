<?php
function buildPageUrl($baseHref, $queryPageParam, $pageNum) {
    $separator = (strpos($baseHref, '?') !== false) ? '&' : '?';
    return htmlspecialchars($baseHref . $separator . $queryPageParam . '=' . $pageNum, ENT_QUOTES, 'UTF-8');
}

function renderPaginationLinks($currentPage, $totalPages, $baseHref, $queryPageParam = 'page_num', $range = 2) {
    if ($totalPages <= 1) return;

    echo '<nav><ul class="pagination">';

    // Previous link
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        echo '<li class="page-item">
                <a class="page-link pagination-link" href="' . buildPageUrl($baseHref, $queryPageParam, $prevPage) . '" data-page="' . $prevPage . '">Previous</a>
              </li>';
    }

    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);

    if ($start > 1) {
        echo '<li class="page-item">
                <a class="page-link pagination-link" href="' . buildPageUrl($baseHref, $queryPageParam, 1) . '" data-page="1">1</a>
              </li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $currentPage) ? 'active' : '';
        echo '<li class="page-item ' . $active . '">
                <a class="page-link pagination-link" href="' . buildPageUrl($baseHref, $queryPageParam, $i) . '" data-page="' . $i . '">' . $i . '</a>
              </li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item">
                <a class="page-link pagination-link" href="' . buildPageUrl($baseHref, $queryPageParam, $totalPages) . '" data-page="' . $totalPages . '">' . $totalPages . '</a>
              </li>';
    }

    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        echo '<li class="page-item">
                <a class="page-link pagination-link" href="' . buildPageUrl($baseHref, $queryPageParam, $nextPage) . '" data-page="' . $nextPage . '">Next</a>
              </li>';
    }

    echo '</ul></nav>';
}

?>