<?php 
namespace App\Core;
use PDO;
use PDOException;

final class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        #kiem tra ket noi neu roi thi tra ve luon
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
                # nếu có lỗi thì ném ngoại lệ
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                # trao về mảng kết hợp key là tên cột
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                # chống nhiều lần tấn công SQL Injection
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException('Kết nối CSDL thất bại: ' . $e->getMessage(), 500 );
        }
    }
}