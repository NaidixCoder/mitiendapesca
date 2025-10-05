<?php
// --- "Recordarme" (selector + validator) ---
const REMEMBER_COOKIE = 'REMEMBER_ME';
function b64url(string $bin): string { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
function hmac_sha256_b64(string $v): string { return b64url(hash('sha256', $v, true)); }

function set_remember_me(int $userId, int $days = 30): void {
  $selector  = b64url(random_bytes(16));
  $validator = b64url(random_bytes(32));
  $tokenHash = hmac_sha256_b64($validator);
  $expires   = (new DateTime("+{$days} days"))->format('Y-m-d H:i:s');

  $stmt = db()->prepare("INSERT INTO user_tokens (user_id, selector, token_hash, expires_at, ip, ua) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$userId, $selector, $tokenHash, $expires, ($_SERVER['REMOTE_ADDR'] ?? ''), substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,200)]);

  $cookieVal = $selector . ':' . $validator;
  setcookie(REMEMBER_COOKIE, $cookieVal, [
    'expires' => time() + 60*60*24*$days,
    'path' => '/',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function clear_remember_me(): void {
  if (empty($_COOKIE[REMEMBER_COOKIE])) return;
  [$selector] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2) + [null, null];
  if ($selector) {
    $del = db()->prepare("DELETE FROM user_tokens WHERE selector=?");
    $del->execute([$selector]);
  }
  setcookie(REMEMBER_COOKIE, '', ['expires'=>time()-3600,'path'=>'/','secure'=>is_https(),'httponly'=>true,'samesite'=>'Lax']);
}

function try_autologin_from_cookie(): void {
  if (!empty($_SESSION['uid']) || empty($_COOKIE[REMEMBER_COOKIE])) return;

  [$selector, $validator] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2) + [null, null];
  if (!$selector || !$validator) { clear_remember_me(); return; }

  $row = db()->prepare("SELECT ut.user_id, ut.token_hash, ut.expires_at, u.name, u.role, u.email, u.avatar_url
                        FROM user_tokens ut JOIN users u ON u.id=ut.user_id WHERE ut.selector=? LIMIT 1");
  $row->execute([$selector]);
  $tok = $row->fetch(PDO::FETCH_ASSOC);
  if (!$tok) { clear_remember_me(); return; }

  if (strtotime($tok['expires_at']) < time()) { clear_remember_me(); return; }

  if (hash_equals($tok['token_hash'], hmac_sha256_b64($validator))) {
    // éxito → loguear sesión
    session_regenerate_id(true);
    $_SESSION['uid']     = (int)$tok['user_id'];
    $_SESSION['uname']   = $tok['name'] ?? '';
    $_SESSION['email']   = $tok['email'] ?? '';
    $_SESSION['urole']   = $tok['role'] ?? 'customer';
    $_SESSION['uavatar'] = $tok['avatar_url'] ?? '';
    $_SESSION['_last_activity'] = time();

    // rotación de token: borrar viejo y emitir uno nuevo
    $del = db()->prepare("DELETE FROM user_tokens WHERE selector=?");
    $del->execute([$selector]);
    set_remember_me((int)$tok['user_id']); // nuevo token
  } else {
    // posible robo/alteración → invalidar
    clear_remember_me();
  }
}
