<?php
function paginate($mysqli_conn, $getTotalCountFunc, $getItemsFunc, $baseHref, $perPage = 10, $queryPageParam = 'page_num') {
    $page = isset($_GET[$queryPageParam]) && is_numeric($_GET[$queryPageParam]) ? (int) $_GET[$queryPageParam] : 1;
    $page = max($page, 1); // Ensure page is at least 1
    $offset = ($page - 1) * $perPage;

    $totalItems = call_user_func($getTotalCountFunc, $mysqli_conn);
    $totalPages = (int) ceil($totalItems / $perPage);

    $items = call_user_func($getItemsFunc, $mysqli_conn, $perPage, $offset);

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'offset' => $offset,   
        'baseHref' => $baseHref,
        'queryPageParam' => $queryPageParam,
    ];
}
