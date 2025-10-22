<?php
// /links/book_link.inc.php
function book_link(array $book_link_detail): string {

  $worksID = $book_link_detail['olid'] ?? $book_link_detail['works_id'] ?? '';
  if ($worksID === '') return 'book.php'; // fallback
  // CHECK - might need to change book.php to smth like _REQUEST['content']='book';

  $params = [
    'works_id'     => $worksID,
    'title'        => $book_link_detail['title'] ?? null,
    'author_names[]' => isset($book_link_detail['author']) ? [$book_link_detail['author']] : null,

  ];

  $book_query = [];
  foreach ($params as $k => $v) {
    if ($v === null) continue;
    if (is_array($v)) {
      foreach ($v as $vv) $book_query[] = urlencode($k) . '=' . urlencode($vv);
    } else {
      $book_query[] = urlencode($k) . '=' . urlencode($v);
    }
  }
  return 'book.php?' . implode('&', $book_query);
}