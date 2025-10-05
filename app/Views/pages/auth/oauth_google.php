<?php
// Handler puro: POST /oauth/google
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$idToken = $_POST['id_token'] ?? '';
if (!$idToken) { http_response_code(400); echo json_encode(['error'=>'Missing id_token']); exit; }

// Rate limit por IP (antes de pegarle a Google)
rate_limit_or_fail('oauth:ip:'.client_ip(), 10, 60); // 10/min por IP

try {
  // Validar token con Google (tokeninfo)
  $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
  $resp = null; $http=0;
  if (function_exists('curl_init')) {
    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12, CURLOPT_SSL_VERIFYPEER=>true]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
  } else {
    $resp = @file_get_contents($verifyUrl);
    $http = $resp !== false ? 200 : 0;
  }
  if ($http !== 200 || !$resp) { http_response_code(401); echo json_encode(['error'=>'Token inválido']); exit; }

  $data = json_decode($resp, true);
  $aud  = $data['aud'] ?? '';
  $iss  = $data['iss'] ?? '';
  $sub  = $data['sub'] ?? null;
  $email= $data['email'] ?? null;

  $expected = env('GOOGLE_CLIENT_ID', '');
  if ($aud !== $expected || !in_array($iss, ['https://accounts.google.com','accounts.google.com'], true) || !$sub) {
    http_response_code(401); echo json_encode(['error'=>'Token no autorizado']); exit;
  }

  // Rate limits extra por identidad (tras validar aud/iss/sub)
  rate_limit_or_fail('oauth:sub:'.$sub, 10, 300); // 10/5min por sub
  if (!empty($email)) {
    rate_limit_or_fail('oauth:email:'.mb_strtolower($email), 10, 300); // 10/5min por email
  }

  $name     = $data['name'] ?? ($data['given_name'] ?? 'Usuario');
  $avatar   = $data['picture'] ?? null;

  // Normalizar avatar de Google si viene sin tamaño
  if ($avatar && (parse_url($avatar, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com' && !preg_match('/=s\d+-c$/', $avatar)) {
      $avatar .= '=s96-c';
  }

  $verified = ($data['email_verified'] ?? 'false') === 'true';

  $pdo = db();
  $pdo->beginTransaction();

  // 1) existe por oauth?
  $sel = $pdo->prepare('SELECT id, name, role FROM users WHERE oauth_provider=? AND oauth_sub=? LIMIT 1');
  $sel->execute(['google', $sub]);
  $user = $sel->fetch(PDO::FETCH_ASSOC);

  if (!$user && $email) {
    // 2) existe por email? -> vincular
    $sel2 = $pdo->prepare('SELECT id, name, role FROM users WHERE email=? LIMIT 1');
    $sel2->execute([$email]);
    $byEmail = $sel2->fetch(PDO::FETCH_ASSOC);
    if ($byEmail) {
      $upd = $pdo->prepare('UPDATE users SET oauth_provider=?, oauth_sub=?, email_verified=?, avatar_url=?, updated_at=NOW() WHERE id=?');
      $upd->execute(['google', $sub, $verified?1:0, $avatar, $byEmail['id']]);
      // volver a leer completo (por si triggers/cambios)
      $sel3 = $pdo->prepare('SELECT id, name, role FROM users WHERE id=? LIMIT 1');
      $sel3->execute([$byEmail['id']]);
      $user = $sel3->fetch(PDO::FETCH_ASSOC);
    }
  }

  if (!$user) {
    // 3) crear nuevo (password_hash NULL) → role por defecto 'customer' en schema
    $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,oauth_provider,oauth_sub,email_verified,avatar_url,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $ins->execute([$name, $email, null, 'google', $sub, $verified?1:0, $avatar]);
    $user = [
      'id'   => (int)$pdo->lastInsertId(),
      'name' => $name,
      'role' => 'customer'
    ];
  }

  // login
  session_regenerate_id(true);
  $_SESSION['uid']     = (int)$user['id'];
  $_SESSION['uname']   = $user['name'];
  $_SESSION['email']   = $email ?? '';
  $_SESSION['urole']   = $user['role'] ?? 'customer';
  $_SESSION['uavatar'] = $avatar ?? '';

  $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$_SESSION['uid']]);
  $pdo->commit();

  echo json_encode(['ok'=>true, 'redirect'=> url('/cuenta')]);
} catch (Throwable $e) {
  error_log('[oauth_google] '.$e->getMessage());
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500); echo json_encode(['error'=>'Server error']);
}
