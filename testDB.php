function testdb() {
  $db = new mysqli('172.28.172.114','testUser','userPass','userdb');
  if ($db->connect_errno) { error_log('DB connect failed: '.$db->connect_error); return null; }
  $db->set_charset('utf8mb4');
  return $db;
}
