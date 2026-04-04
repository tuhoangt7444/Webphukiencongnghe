<?php

declare(strict_types=1);

namespace App\Services;

final class GeminiChatService
{
    private string $apiKey;
    private string $model;
    private int $timeoutSeconds;

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash', int $timeoutSeconds = 12)
    {
        $this->apiKey = trim($apiKey);
        $this->model = trim($model) !== '' ? trim($model) : 'gemini-2.5-flash';
        $this->timeoutSeconds = max(3, $timeoutSeconds);
    }

    public function isReady(): bool
    {
        return $this->apiKey !== '';
    }

    public function generateReply(string $prompt): ?string
    {
        if (!$this->isReady()) {
            return null;
        }

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->model),
            rawurlencode($this->apiKey)
        );

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.6,
                'maxOutputTokens' => 900,
                'topP' => 0.95,
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        $attempt = $this->executeRequest($endpoint, $payload, true);
        $raw = $attempt['raw'];
        $httpCode = $attempt['httpCode'];

        // Some local PHP-on-Windows setups miss CA certificates. Retry once without SSL verification.
        if ((!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300)
            && str_contains((string)$attempt['error'], 'SSL certificate problem')) {
            $attempt = $this->executeRequest($endpoint, $payload, false);
            $raw = $attempt['raw'];
            $httpCode = $attempt['httpCode'];
        }

        if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $parts = $data['candidates'][0]['content']['parts'] ?? null;
        if (!is_array($parts) || $parts === []) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $chunks[] = $part['text'];
            }
        }

        if ($chunks === []) {
            return null;
        }

        $text = implode("\n", $chunks);
        $normalized = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return $normalized !== '' ? $normalized : null;
    }

    private function executeRequest(string $endpoint, array $payload, bool $verifySsl): array
    {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [
                'raw' => null,
                'httpCode' => 0,
                'error' => 'curl_init_failed',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [
            'raw' => $raw,
            'httpCode' => $httpCode,
            'error' => $error,
        ];
    }

    public function generateReplyWithContext(array $analysis, string $userMessage, array $dbContext, string $fallbackReply): ?string
    {
        $contextData = $this->buildContextData($analysis, $dbContext, $fallbackReply);
        $prompt = $this->buildGeminiPrompt($userMessage, $contextData);

        return $this->generateReply($prompt);
    }

    private function buildContextData(array $analysis, array $dbContext, string $fallbackReply): string
    {
        $campaigns = $dbContext['campaigns'] ?? [];
        $relatedProducts = $dbContext['related_products'] ?? [];
        $shippingPolicies = $dbContext['shipping_policies'] ?? [];
        $warrantyPolicies = $dbContext['warranty_policies'] ?? [];

        $campaignBlock = $this->buildCampaignBlock($campaigns);
        $productBlock = $this->buildProductBlock($relatedProducts);
        $shippingBlock = $this->buildPolicyBlock($shippingPolicies, 'shipping_info');
        $warrantyBlock = $this->buildPolicyBlock($warrantyPolicies, 'warranty_months');

        $intent = (string)($analysis['intent'] ?? 'general');

        return "[INTENT]\n"
            . $intent
            . "\n\n[DANH SACH KHUYEN MAI]\n"
            . $campaignBlock
            . "\n\n[DANH SACH SAN PHAM]\n"
            . $productBlock
            . "\n\n[THONG TIN VAN CHUYEN]\n"
            . $shippingBlock
            . "\n\n[THONG TIN BAO HANH]\n"
            . $warrantyBlock
            . "\n\n[GOI Y NOI BO]\n"
            . trim($fallbackReply);
        }

    private function buildGeminiPrompt(string $userMsg, string $contextData): string
    {
        $safeUserMsg = trim($userMsg);
        $safeContext = trim($contextData);

        return <<<PROMPT
BẠN LÀ: Trợ lý ảo thông minh của TECHGEAR, chuyên viên tư vấn TECHGEAR.
PHONG CÁCH: Lễ phép (ưu tiên dùng "Dạ", "ạ"), thân thiện, chuyên nghiệp, dùng icon công nghệ phù hợp như 🖱️ ⌨️ 📦 🚀.

DỮ LIỆU TỪ DATABASE (NGUỒN DUY NHẤT):
{$safeContext}

QUY TẮC PHẢN HỒI:
1. KHÔNG BỊA: Nếu khách hỏi sản phẩm không có trong dữ liệu, hãy xin lỗi và mời khách để lại SĐT để TECHGEAR hỗ trợ.
2. ĐỊNH DẠNG CARD (BẮT BUỘC): Mỗi khi nhắc đến sản phẩm có trong dữ liệu, phải ghi ngay sau tên sản phẩm mã:
[CARD: name={Tên SP} | price={Giá} | slug={slug} | image={tên_file_ảnh}]
3. ƯU TIÊN KHUYẾN MÃI: Nếu có sản phẩm giảm giá trong [DANH SACH KHUYEN MAI], hãy giới thiệu ngay.
4. TIN NHẮN THÂN THIỆN:
- Mở đầu đúng câu: "Dạ, TECHGEAR xin chào bạn ạ! ✨"
- Thân bài: tư vấn ngắn gọn, rõ ràng; nếu khách hỏi nhiều món thì so sánh nhẹ theo nhu cầu.
- Kết thúc đúng câu: "Chúc bạn chọn được món Gear ưng ý tại TECHGEAR ạ! 🚀"
5. Chỉ dùng thông tin có trong dữ liệu; không thêm thông tin ngoài ngữ cảnh.

CÂU HỎI CỦA KHÁCH: {$safeUserMsg}
TRẢ LỜI CỦA TECHGEAR:
PROMPT;
    }

    private function buildCampaignBlock(array $campaigns): string
    {
        if ($campaigns === []) {
            return 'Chua co campaign active.';
        }

        $lines = [];
        foreach ($campaigns as $idx => $item) {
            $lines[] = sprintf(
                '%d) %s | giam: %d%% | tu: %s | den: %s',
                $idx + 1,
                (string)($item['product_name'] ?? 'San pham'),
                (int)($item['discount_percent'] ?? 0),
                (string)($item['start_at'] ?? ''),
                (string)($item['end_at'] ?? '')
            );
        }

        return implode("\n", $lines);
    }

    private function buildProductBlock(array $products): string
    {
        if ($products === []) {
            return 'Khong co san pham lien quan.';
        }

        $lines = [];
        foreach ($products as $idx => $item) {
            $lines[] = sprintf(
                '%d) %s | slug: %s | gia: %s | bao hanh(thang): %d',
                $idx + 1,
                (string)($item['name'] ?? 'San pham'),
                (string)($item['slug'] ?? ''),
                number_format((int)($item['price'] ?? 0), 0, ',', '.') . 'd',
                (int)($item['warranty_months'] ?? 0)
            );
        }

        return implode("\n", $lines);
    }

    private function buildPolicyBlock(array $rows, string $field): string
    {
        if ($rows === []) {
            return 'Chua co du lieu.';
        }

        $lines = [];
        foreach ($rows as $idx => $item) {
            $value = trim((string)($item[$field] ?? ''));
            if ($field === 'warranty_months') {
                $value = ((int)($item[$field] ?? 0)) . ' thang';
            }
            if ($value === '') {
                continue;
            }

            $lines[] = sprintf('%d) %s: %s', $idx + 1, (string)($item['name'] ?? 'San pham'), $value);
        }

        return $lines !== [] ? implode("\n", $lines) : 'Chua co du lieu.';
    }
}
