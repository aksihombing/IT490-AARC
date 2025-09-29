<?php
session_start();

// Destroy all session data and return a JSON response
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status'=>'success','message'=>'Logged out']);
exit;
?>