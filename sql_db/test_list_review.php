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


function doReviewsList(array $req) {
  $works_id = trim($req['works_id'] ?? '');
  if ($works_id === '') return ['status'=>'fail','message'=>'missing works_id'];

  $conn = db();

  $stmt = $conn->prepare("
    SELECT r.id, r.user_id, u.username, r.rating, r.body, r.created_at
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.works_id = ?
    ORDER BY r.created_at DESC
    LIMIT 200
  ");
  $stmt->bind_param("s", $works_id);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($row = $res->fetch_assoc()) $items[] = $row;

  return [
    'status' => 'success',
    'items'  => $items
  ];
}

$testData = [
    'works_id' => 'OL82548W'
];

$result = doReviewsList($testData);


echo "List reviews for user :\n";
print_r($result);
?>
