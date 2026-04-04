<?php
namespace App\Models;

use App\Core\DB;
use PDO;

final class User
{
    private static bool $avatarColumnsEnsured = false;

    public static function findByEmail(string $email): ?array
    {
        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);
        $st = $pdo->prepare(
            'SELECT u.*, r.code AS role_code
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $st->execute(['email' => $email]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function findByLogin(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);
        $st = $pdo->prepare(
            "SELECT u.*, r.code AS role_code
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE lower(u.email) = lower(:login)
                OR lower(split_part(u.email, '@', 1)) = lower(:login)
             ORDER BY CASE WHEN lower(u.email) = lower(:login) THEN 0 ELSE 1 END
             LIMIT 1"
        );
        $st->execute(['login' => $login]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function createCustomer(string $email, string $passwordHash, array $profile = []): int
    {
        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);

        $roleId = self::resolveCustomerRoleId($pdo);

        $st = $pdo->prepare(
            'INSERT INTO users (role_id, email, password_hash, status)
             VALUES (:role_id, :email, :password_hash, :status)
             RETURNING id'
        );
        $st->execute([
            'role_id' => $roleId,
            'email' => $email,
            'password_hash' => $passwordHash,
            'status' => 'active',
        ]);

        $userId = (int)$st->fetchColumn();

        if ($userId > 0) {
            self::upsertCustomerProfile($userId, [
                'full_name' => trim((string)($profile['full_name'] ?? '')),
                'phone' => trim((string)($profile['phone'] ?? '')),
                'address_line' => '',
                'ward' => '',
                'district' => '',
                'city' => '',
                'full_address' => '',
            ]);
        }

        return $userId;
    }

    public static function getCustomerProfileByUserId(int $userId): array
    {
        $pdo = DB::conn();
        self::ensureCustomerProfilesTable($pdo);

        $st = $pdo->prepare(
            'SELECT full_name, phone, address_line, ward, district, city, full_address
             FROM customer_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $st->execute(['user_id' => $userId]);
        $row = $st->fetch();

        if (!$row) {
            return [
                'full_name' => '',
                'phone' => '',
                'address_line' => '',
                'ward' => '',
                'district' => '',
                'city' => '',
                'full_address' => '',
            ];
        }

        return [
            'full_name' => (string)($row['full_name'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'address_line' => (string)($row['address_line'] ?? ''),
            'ward' => (string)($row['ward'] ?? ''),
            'district' => (string)($row['district'] ?? ''),
            'city' => (string)($row['city'] ?? ''),
            'full_address' => (string)($row['full_address'] ?? ''),
        ];
    }

    public static function getResolvedAvatarByUserId(int $userId): string
    {
        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);

        $st = $pdo->prepare(
            'SELECT avatar_url, google_avatar_url
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $st->execute(['id' => $userId]);
        $row = $st->fetch();

        if (!$row) {
            return '';
        }

        $avatarUrl = trim((string)($row['avatar_url'] ?? ''));
        $googleAvatarUrl = trim((string)($row['google_avatar_url'] ?? ''));

        if ($avatarUrl !== '') {
            return $avatarUrl;
        }

        return $googleAvatarUrl;
    }

    public static function updateAvatarByUserId(int $userId, string $avatarUrl): void
    {
        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);

        $st = $pdo->prepare(
            'UPDATE users
             SET avatar_url = :avatar_url
             WHERE id = :id'
        );
        $st->execute([
            'id' => $userId,
            'avatar_url' => trim($avatarUrl),
        ]);
    }

    public static function updateGoogleAvatarByUserId(int $userId, string $googleAvatarUrl): void
    {
        $pdo = DB::conn();
        self::ensureAvatarColumns($pdo);

        $st = $pdo->prepare(
            'UPDATE users
             SET google_avatar_url = :google_avatar_url
             WHERE id = :id'
        );
        $st->execute([
            'id' => $userId,
            'google_avatar_url' => trim($googleAvatarUrl),
        ]);
    }

    public static function resolveAvatarUrlFromUserRow(array $user): string
    {
        $avatarUrl = trim((string)($user['avatar_url'] ?? ''));
        if ($avatarUrl !== '') {
            return $avatarUrl;
        }

        return trim((string)($user['google_avatar_url'] ?? ''));
    }

    public static function upsertCustomerProfile(int $userId, array $profile): void
    {
        $pdo = DB::conn();
        self::ensureCustomerProfilesTable($pdo);

        $st = $pdo->prepare(
            'INSERT INTO customer_profiles (
                user_id, full_name, phone, address_line, ward, district, city, full_address, updated_at
             ) VALUES (
                :user_id, :full_name, :phone, :address_line, :ward, :district, :city, :full_address, now()
             )
             ON CONFLICT (user_id)
             DO UPDATE SET
                full_name = EXCLUDED.full_name,
                phone = EXCLUDED.phone,
                address_line = EXCLUDED.address_line,
                ward = EXCLUDED.ward,
                district = EXCLUDED.district,
                city = EXCLUDED.city,
                full_address = EXCLUDED.full_address,
                updated_at = now()'
        );
        $st->execute([
            'user_id' => $userId,
            'full_name' => $profile['full_name'],
            'phone' => $profile['phone'],
            'address_line' => $profile['address_line'],
            'ward' => $profile['ward'],
            'district' => $profile['district'],
            'city' => $profile['city'],
            'full_address' => $profile['full_address'],
        ]);
    }

    public static function updatePasswordById(int $userId, string $passwordHash): void
    {
        if ($userId <= 0 || trim($passwordHash) === '') {
            throw new \InvalidArgumentException('Dữ liệu cập nhật mật khẩu không hợp lệ.');
        }

        $st = DB::conn()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $st->execute([
            'id' => $userId,
            'password_hash' => $passwordHash,
        ]);
    }

    private static function resolveCustomerRoleId(PDO $pdo): int
    {
        $st = $pdo->query("SELECT id FROM roles WHERE code = 'customer' LIMIT 1");
        $roleId = $st->fetchColumn();

        if ($roleId !== false) {
            return (int)$roleId;
        }

        $fallback = $pdo->query('SELECT id FROM roles ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($fallback === false) {
            throw new \RuntimeException('Bảng roles chưa có dữ liệu.');
        }

        return (int)$fallback;
    }

    private static function ensureCustomerProfilesTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customer_profiles (
                id bigserial PRIMARY KEY,
                user_id bigint NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
                full_name text NOT NULL,
                phone text NOT NULL,
                address_line text NOT NULL,
                ward text NOT NULL,
                district text NOT NULL,
                city text NOT NULL,
                full_address text NOT NULL DEFAULT '',
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )"
        );

        $pdo->exec("ALTER TABLE customer_profiles ADD COLUMN IF NOT EXISTS full_address text NOT NULL DEFAULT ''");
    }

    private static function ensureAvatarColumns(PDO $pdo): void
    {
        if (self::$avatarColumnsEnsured) {
            return;
        }

        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url text");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS google_avatar_url text");

        self::$avatarColumnsEnsured = true;
    }
}
