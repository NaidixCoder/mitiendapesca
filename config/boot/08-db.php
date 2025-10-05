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
    return self::$pdo;
  }
}
function db(): PDO { return DB::pdo(); }
