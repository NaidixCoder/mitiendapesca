<?php
// ---------- PDO Singleton ----------
final class DB {
  private static ?PDO $pdo=null;
  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
      env('DB_HOST','localhost'), env('DB_PORT','3306'), env('DB_NAME','tienda_pesca')
    );
    $opt = [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
    ];
    self::$pdo = new PDO($dsn, env('DB_USER','root'), env('DB_PASS',''), $opt);

    // Asegurar collation consistente en la conexión para evitar "Illegal mix of collations".
    // El schema usa utf8mb4_unicode_ci por defecto.
    try {
      self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {
      // best-effort
    }

    // Mantener la zona horaria de MySQL alineada a la app para evitar desfases (NOW(), INTERVAL, etc.).
    // Usamos offset (±HH:MM) para no depender de tablas de timezones en el servidor.
    try {
      $tz = defined('APP_TIMEZONE') ? (string)APP_TIMEZONE : 'UTC';
      $dtz = new DateTimeZone($tz);
      $now = new DateTime('now', $dtz);
      $offset = $dtz->getOffset($now); // segundos
      $sign = ($offset >= 0) ? '+' : '-';
      $abs = abs($offset);
      $hh = (int)floor($abs / 3600);
      $mm = (int)floor(($abs % 3600) / 60);
      $tzOffset = sprintf('%s%02d:%02d', $sign, $hh, $mm);
      self::$pdo->exec("SET time_zone = '{$tzOffset}'");
    } catch (Throwable $e) {
      // best-effort
    }

    return self::$pdo;
  }
}
function db(): PDO { return DB::pdo(); }
