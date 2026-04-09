<?php 
namespace App\Core;
use PDO;
use PDOException;

final class DB {
    private static ?PDO $pdo = null;

    # lấy kết nối PDO dùng chung toàn hệ thống
    public static function conn(): PDO {
        if (self::$pdo !== null) return self::$pdo;

        $cfg = require __DIR__ . '/../../config/database.php';
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $cfg['host'],
            (int)$cfg['port'],
            $cfg['database']
        );
        
        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException('Kết nối CSDL thất bại: ' . $e->getMessage(), 500 );
        }
    }
}