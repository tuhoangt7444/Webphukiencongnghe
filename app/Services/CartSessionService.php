<?php
namespace App\Services;

use App\Core\DB;
use App\Models\Product;

final class CartSessionService
{
    private static bool $tableEnsured = false;

    public static function currentUserId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function loadForUser(int $userId): array
    {
        self::ensureTable();

        if ($userId <= 0) {
            return [];
        }

        $st = DB::conn()->prepare(
            'SELECT product_id, quantity
             FROM user_cart_items
             WHERE user_id = :user_id
             ORDER BY product_id ASC'
        );
        $st->execute(['user_id' => $userId]);
        $rows = $st->fetchAll();

        $cart = [];
        foreach ($rows as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            $quantity = max(1, (int)($row['quantity'] ?? 1));
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = Product::findForCart($productId);
            if (!$product) {
                continue;
            }

            $maxStock = max(0, (int)($product['stock_total'] ?? 0));
            if ($maxStock <= 0) {
                continue;
            }

            if ($quantity > $maxStock) {
                $quantity = $maxStock;
            }

            $cart[(string)$productId] = [
                'product_id' => $productId,
                'name' => (string)($product['name'] ?? 'Sản phẩm'),
                'slug' => (string)($product['slug'] ?? ''),
                'image' => trim((string)($product['image'] ?? '')),
                'price' => (int)($product['price'] ?? 0),
                'original_price' => (int)($product['original_price'] ?? ($product['price'] ?? 0)),
                'discount_percent' => (int)($product['discount_percent'] ?? 0),
                'qty' => $quantity,
                'stock_total' => $maxStock,
            ];
        }

        return $cart;
    }

    public static function saveForUser(int $userId, array $cart): void
    {
        self::ensureTable();

        if ($userId <= 0) {
            return;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();

        try {
            $normalized = self::normalizeCart($cart);
            $productIds = array_keys($normalized);

            if (empty($productIds)) {
                $delete = $pdo->prepare('DELETE FROM user_cart_items WHERE user_id = :user_id');
                $delete->execute(['user_id' => $userId]);
                $pdo->commit();
                return;
            }

            $placeholders = [];
            $params = ['user_id' => $userId];
            foreach ($productIds as $index => $productId) {
                $key = 'pid_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $productId;
            }

            $delete = $pdo->prepare(
                'DELETE FROM user_cart_items
                 WHERE user_id = :user_id
                   AND product_id NOT IN (' . implode(', ', $placeholders) . ')'
            );
            foreach ($params as $key => $value) {
                $delete->bindValue(':' . $key, $value, \PDO::PARAM_INT);
            }
            $delete->execute();

            $upsert = $pdo->prepare(
                'INSERT INTO user_cart_items (user_id, product_id, quantity, created_at, updated_at)
                 VALUES (:user_id, :product_id, :quantity, now(), now())
                 ON CONFLICT (user_id, product_id)
                 DO UPDATE SET quantity = EXCLUDED.quantity, updated_at = now()'
            );

            foreach ($normalized as $productId => $quantity) {
                $upsert->execute([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getCurrentCart(): array
    {
        $userId = self::currentUserId();
        if ($userId <= 0) {
            $cart = $_SESSION['cart'] ?? [];
            return is_array($cart) ? $cart : [];
        }

        return self::loadCurrentUserCartIntoSession();
    }

    public static function setCurrentCart(array $cart): void
    {
        $_SESSION['cart'] = $cart;

        $userId = self::currentUserId();
        if ($userId > 0) {
            self::saveForUser($userId, $cart);
        }
    }

    public static function loadCurrentUserCartIntoSession(): array
    {
        $userId = self::currentUserId();
        $cart = $userId > 0 ? self::loadForUser($userId) : [];
        $_SESSION['cart'] = $cart;

        return $cart;
    }

    public static function clearCurrentCart(bool $persistForLoggedInUser = true): void
    {
        $userId = self::currentUserId();
        if ($persistForLoggedInUser && $userId > 0) {
            self::saveForUser($userId, []);
        }

        unset($_SESSION['cart']);
    }

    private static function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = (int)($item['product_id'] ?? $key);
            $quantity = max(1, (int)($item['qty'] ?? 1));
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$productId] = $quantity;
        }

        return $normalized;
    }

    private static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $pdo = DB::conn();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_cart_items (
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT now(),
                updated_at TIMESTAMP NOT NULL DEFAULT now(),
                PRIMARY KEY (user_id, product_id)
            )'
        );

        self::$tableEnsured = true;
    }
}
