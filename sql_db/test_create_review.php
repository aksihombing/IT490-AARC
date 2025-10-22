<?php
function db() {
  $host = '172.28.172.114'; // need local ip, NEED TO CHANGE
  $user = 'saas_user'; // needdatabase user
  $pass = 'p@ssw0rd'; // need database password
  $name = 'userdb.sql'; // needdatabase name

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}

function doReviewsCreate(array $req) {
  $user_id  = (int)($req['user_id'] ?? 0);
  $works_id = trim($req['works_id'] ?? '');
  $rating   = (int)($req['rating'] ?? 0);
  $body     = trim($req['body'] ?? ($req['comment'] ?? ''));

  if ($user_id <= 0 || $works_id === '' || $rating < 1 || $rating > 5) {
    return ['status'=>'fail','message'=>'missing or invalid fields'];
  }

  $conn = db();

  $stmt = $conn->prepare("
    INSERT INTO reviews (user_id, works_id, rating, body)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating=VALUES(rating), body=VALUES(body), created_at=NOW()
  ");
  $stmt->bind_param("isis", $user_id, $works_id, $rating, $body);
  $ok = $stmt->execute();

  return $ok
    ? ['status'=>'success','message'=>'review saved']
    : ['status'=>'fail','message'=>'database error'];
}



$testData = [
    'user_id' => 12,
    'works_id' => 'OL82548W',
    'rating' => 4,
    'body' => 'Great book!'
];

$result = doReviewsCreate($testData);


echo "Create Review for user :\n";
print_r($result);
?>
