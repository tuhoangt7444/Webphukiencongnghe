<?php
namespace App\Core;

/**
 * Công cụ tính giá sản phẩm
 * 
 * Công thức: price = cost_price × (1 + import_tax_percent + vat_percent + profit_percent)
 */
final class PricingCalculator {
    
    /**
     * Tính giá bán từ giá gốc và các % thuế/lợi nhuận
     * @param int $costPrice Giá gốc (cost_price)
     * @param float $importTaxPercent % thuế nhập khẩu (e.g., 0.05 for 5%)
     * @param float $vatPercent % VAT (e.g., 0.10 for 10%)
     * @param float $profitPercent % lợi nhuận (e.g., 0.15 for 15%)
     * @return int Giá bán (làm tròn lên)
     */
    public static function calculate(
        int $costPrice,
        float $importTaxPercent = 0,
        float $vatPercent = 0,
        float $profitPercent = 0
    ): int {
        // Đảm bảo các % không âm
        $importTaxPercent = max(0, (float)$importTaxPercent);
        $vatPercent = max(0, (float)$vatPercent);
        $profitPercent = max(0, (float)$profitPercent);
        
        // Công thức: price = cost_price × (1 + import_tax + vat + profit)
        $multiplier = 1 + $importTaxPercent + $vatPercent + $profitPercent;
        $price = $costPrice * $multiplier;
        
        // Làm tròn đến số gần nhất (tránh lỗi floating-point từ ceil)
        return (int)round($price);
    }

    /**
     * Xác thực các thông số % (phải từ 0 đến 100)
     * @param float $percent Giá trị % cần kiểm tra
     * @return bool True nếu hợp lệ
     */
    public static function isValidPercent(float $percent): bool {
        return $percent >= 0 && $percent <= 100;
    }

    /**
     * Chuyển đổi % từ dạng số thập phân (e.g., 5.5) sang dạng hệ số (e.g., 0.055)
     * @param float $percentValue Giá trị %, e.g., 5.5 -> 0.055
     * @return float Hệ số, e.g., 0.055
     */
    public static function percentToDecimal(float $percentValue): float {
        return $percentValue / 100;
    }

    /**
     * Chi tiết cách tính giá (cho hiển thị)
     * @param int $costPrice Giá gốc
     * @param float $importTaxPercent % thuế nhập khẩu
     * @param float $vatPercent % VAT
     * @param float $profitPercent % lợi nhuận
     * @return array ['cost_price', 'import_tax_amount', 'vat_amount', 'profit_amount', 'final_price']
     */
    public static function breakdown(
        int $costPrice,
        float $importTaxPercent = 0,
        float $vatPercent = 0,
        float $profitPercent = 0
    ): array {
        $importTaxPercent = max(0, (float)$importTaxPercent);
        $vatPercent = max(0, (float)$vatPercent);
        $profitPercent = max(0, (float)$profitPercent);

        // Tính từng thành phần
        $importTaxAmount = $costPrice * $importTaxPercent;
        $vatAmount = $costPrice * $vatPercent;
        $profitAmount = $costPrice * $profitPercent;
        
        return [
            'cost_price' => $costPrice,
            'import_tax_amount' => (int)$importTaxAmount,
            'vat_amount' => (int)$vatAmount,
            'profit_amount' => (int)$profitAmount,
            'final_price' => self::calculate($costPrice, $importTaxPercent, $vatPercent, $profitPercent)
        ];
    }
}
