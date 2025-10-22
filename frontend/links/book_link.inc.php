<?php

// index.php?content=book&olid=
function book_link(array $b): string {
  if (empty($b['works_id'])) return '#';
  $base = 'content=book';
  $q = [
    'works_id' => $b['works_id'],
  ];
  if (!empty($b['title']))    $q['title']    = $b['title'];
  if (!empty($b['cover_id'])) $q['cover_id'] = $b['cover_id'];

  $url = $base . '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
  // https://www.php.net/manual/en/function.http-build-query.php
  // ex. ) index.php?content=book?

  if (!empty($b['author_names']) && is_array($b['author_names'])) {
    foreach ($b['author_names'] as $name) {
      $url .= '&author_names[]=' . rawurlencode($name);
    }
  }
  return $url;
}
?>
