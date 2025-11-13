<?php
// /links/book_link.inc.php
function book_link(array $b): string {

  $worksID = $b['olid'] ?? $b['works_id'] ?? '';
  if ($worksID === '') return 'book.php'; // fallback

  $params = [
    'works_id'     => $worksID,
    'title'        => $b['title'] ?? null,
    'author_names[]' => isset($b['author']) ? [$b['author']] : null,

  ];

  $q = [];
  foreach ($params as $k => $v) {
    if ($v === null) continue;
    if (is_array($v)) {
      foreach ($v as $vv) $q[] = urlencode($k) . '=' . urlencode($vv);
    } else {
      $q[] = urlencode($k) . '=' . urlencode($v);
    }
  }
  return 'book.php?' . implode('&', $q);
}
