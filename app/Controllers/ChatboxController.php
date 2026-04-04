<?php
namespace App\Controllers;

use App\Core\DB;
use App\Core\Controller;
use App\Models\Product;
use App\Services\GeminiChatService;

final class ChatboxController extends Controller
{
    public function productDetail(): void
    {
        $productId = (int)$this->request->input('id', 0);
        if ($productId <= 0) {
            $this->response->json([
                'ok' => false,
                'reply' => 'Không xác định được sản phẩm để xem chi tiết.',
            ], 422);
            return;
        }

        $product = Product::findDetailedForChat($productId);
        if ($product === null) {
            $this->response->json([
                'ok' => false,
                'reply' => 'Sản phẩm không tồn tại hoặc đã ngừng bán.',
            ], 404);
            return;
        }

        $this->response->json([
            'ok' => true,
            'reply' => $this->buildDetailedProductReply($product),
            'product' => [
                'id' => (int)($product['id'] ?? 0),
                'name' => (string)($product['name'] ?? ''),
                'url' => '/product/' . rawurlencode((string)($product['slug'] ?? '')),
            ],
        ]);
    }

    public function reply(): void
    {
        $message = trim((string)$this->request->input('q', ''));
        if ($message === '') {
            $this->response->json([
                'ok' => false,
                'reply' => 'Bạn hãy nhập nhu cầu, ví dụ: "gợi ý tai nghe gaming dưới 2 triệu".',
                'products' => [],
            ], 422);
            return;
        }

        $analysis = $this->analyzeMessage($message);
        $analysis = $this->mergeWithSessionContext($analysis);
        $intentFromKeywords = $this->detectIntentByKeywordScore($this->normalizeText($message));
        if ($intentFromKeywords !== 'general') {
            $analysis['intent'] = match ($intentFromKeywords) {
                'warranty' => 'warranty_policy',
                'price' => 'price_filter',
                default => $intentFromKeywords,
            };
        }
        $products = [];
        $reply = '';
        $replySource = 'local';

        if ($analysis['intent'] === 'greeting') {
            $reply = 'Chào bạn. Mình có thể giúp bạn tìm phụ kiện theo ngân sách, nhu cầu gaming/văn phòng, hoặc so sánh nhanh vài mẫu phù hợp.';
            $products = Product::suggestForChat('gaming', null, null, 4);
        } elseif ($analysis['intent'] === 'thanks') {
            $reply = 'Rất vui được hỗ trợ bạn. Nếu cần, bạn cứ nhắn thêm ngân sách và nhu cầu sử dụng, mình sẽ lọc nhanh mẫu phù hợp nhất.';
        } elseif ($analysis['intent'] === 'warranty_policy') {
            $reply = 'Về bảo hành: mỗi sản phẩm có thời gian bảo hành riêng theo hãng. Bạn có thể gửi tên/model sản phẩm, mình sẽ trả đúng thời hạn bảo hành của mẫu đó.';
            $products = Product::suggestForChat($analysis['keyword'], null, null, 3);
        } elseif ($analysis['intent'] === 'shipping_policy') {
            $reply = 'Về giao hàng: shop hỗ trợ giao toàn quốc. Bạn có thể đặt online, rồi theo dõi trạng thái đơn trong lịch sử mua hàng sau khi đăng nhập.';
        } elseif ($analysis['intent'] === 'spec_vram') {
            $spec = Product::findSpecForChat($analysis['productTerm'], 'vram');
            if ($spec !== null) {
                $reply = sprintf(
                    'Theo thông tin hiện có, %s có VRAM khoảng %s.',
                    (string)$spec['name'],
                    (string)$spec['value']
                );
                $products = Product::suggestForChat((string)$spec['name'], null, null, 3);
            } else {
                $reply = 'Mình chưa đọc được thông số VRAM chính xác trong dữ liệu hiện tại. Bạn có thể mở trang chi tiết sản phẩm để kiểm tra thông số mới nhất.';
                $products = Product::suggestForChat($analysis['productTerm'], null, null, 3);
            }
        } elseif ($analysis['intent'] === 'mouse') {
            $reply = 'Mình lọc nhanh nhóm chuột gaming đang có sẵn cho bạn ngay bên dưới.';
            $products = Product::suggestForChat('chuột gaming', $analysis['minPrice'], $analysis['maxPrice'], 5);
        } elseif ($analysis['intent'] === 'compare') {
            $reply = 'Mình đã tìm các mẫu gần nhất để bạn so sánh nhanh. Bạn có thể bấm "Thông tin chi tiết" từng mẫu để xem cấu hình và bảo hành đầy đủ.';
            $compareKeywords = $this->extractCompareKeywords($message, $analysis);
            foreach ($compareKeywords as $compareKeyword) {
                $batch = Product::suggestForChat($compareKeyword, $analysis['minPrice'], $analysis['maxPrice'], 3);
                $products = $this->mergeUniqueProducts($products, $batch, 6);
                if (count($products) >= 6) {
                    break;
                }
            }
            if ($products === [] && str_contains($this->toAscii(mb_strtolower($message, 'UTF-8')), 'rtx')) {
                $products = Product::suggestForChat('card màn hình', $analysis['minPrice'], $analysis['maxPrice'], 6);
            }
            if ($products === []) {
                $reply = 'Mình chưa thấy đúng 2 model bạn nêu trong kho hiện tại. Gợi ý so sánh nhanh: VRAM, bus nhớ, điện năng tiêu thụ, cổng xuất hình và thời gian bảo hành. Bạn có thể gửi lại model gần đúng hoặc mức ngân sách, mình sẽ lọc mẫu thay thế phù hợp ngay.';
            }
        } elseif ($analysis['intent'] === 'price_filter') {
            $reply = 'Mình đã lọc nhanh theo khoảng ngân sách bạn nhập.';
            $baseKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : (string)($_SESSION['chatbox_last_keyword'] ?? '');
            $products = Product::suggestForChat($baseKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5);
        } elseif ($analysis['intent'] === 'product_detail') {
            $lookupTerm = $analysis['productTerm'] !== ''
                ? $analysis['productTerm']
                : ($analysis['keyword'] !== '' ? $analysis['keyword'] : $message);
            $products = Product::suggestForChat($lookupTerm, null, null, 5);
            if ($products !== []) {
                $best = Product::findDetailedForChat((int)$products[0]['id']);
                if ($best !== null) {
                    $reply = $this->buildDetailedProductReply($best);
                } else {
                    $reply = 'Bạn có thể xem chi tiết sản phẩm ' . $lookupTerm . ' tại trang sản phẩm. Mình có đính kèm vài kết quả gần nhất.';
                }
            } else {
                $reply = 'Mình chưa tìm thấy sản phẩm khớp tên bạn nhập trong danh mục đang bán. Bạn thử nhập model gần đúng hoặc tên rút gọn nhé.';
            }
        } elseif ($analysis['intent'] === 'gaming') {
            $reply = 'Với nhu cầu gaming, mình ưu tiên các mẫu có hiệu năng ổn định và tồn kho tốt. Đây là một số gợi ý phù hợp.';
            $products = Product::suggestForChat($analysis['keyword'] !== '' ? $analysis['keyword'] : 'gaming', $analysis['minPrice'], $analysis['maxPrice'], 5);
        } elseif ($analysis['intent'] === 'office') {
            $reply = 'Với nhu cầu học tập/văn phòng, mình ưu tiên mẫu dễ dùng, giá hợp lý và bảo hành tốt. Đây là các lựa chọn phù hợp.';
            $products = Product::suggestForChat($analysis['keyword'] !== '' ? $analysis['keyword'] : 'văn phòng', $analysis['minPrice'], $analysis['maxPrice'], 5);
        } else {
            $products = Product::suggestForChat(
                $analysis['keyword'],
                $analysis['minPrice'],
                $analysis['maxPrice'],
                5
            );
            if ($products !== [] && $this->shouldReturnDetailedProduct($analysis['keyword'], $products[0])) {
                $best = Product::findDetailedForChat((int)$products[0]['id']);
                $reply = $best !== null
                    ? $this->buildDetailedProductReply($best)
                    : $this->buildReplyMessage($analysis, $products);
            } else {
                $reply = $this->buildReplyMessage($analysis, $products);
            }
        }

        if ($products !== [] && !in_array($analysis['intent'], ['product_detail', 'spec_vram'], true)) {
            $reply .= "\n\n" . $this->buildProductSummary($products);
        }

        $geminiReply = $this->tryGeminiRewrite($message, $analysis, $products, $reply);
        if ($geminiReply !== null) {
            $reply = $geminiReply;
            $replySource = 'gemini';
        } else {
            $reply = 'Hiện tại trợ lý Gemini đang tạm gián đoạn. Bạn vui lòng thử lại sau ít phút.';
            $replySource = 'gemini_unavailable';
        }

        if ($analysis['keyword'] !== '') {
            $_SESSION['chatbox_last_keyword'] = $analysis['keyword'];
        }
        $_SESSION['chatbox_last_filters'] = [
            'keyword' => $analysis['keyword'],
            'minPrice' => $analysis['minPrice'],
            'maxPrice' => $analysis['maxPrice'],
            'productTerm' => $analysis['productTerm'],
        ];

        $this->response->json([
            'ok' => true,
            'reply' => $reply,
            'source' => $replySource,
            'intent' => $analysis['intent'],
            'detected' => [
                'keyword' => $analysis['keyword'],
                'minPrice' => $analysis['minPrice'],
                'maxPrice' => $analysis['maxPrice'],
            ],
            'products' => array_map(static function (array $item): array {
                return [
                    'id' => (int)($item['id'] ?? 0),
                    'name' => (string)($item['name'] ?? ''),
                    'slug' => (string)($item['slug'] ?? ''),
                    'category' => (string)($item['category_name'] ?? ''),
                    'price' => (int)($item['price'] ?? 0),
                    'original_price' => (int)($item['original_price'] ?? 0),
                    'stock' => (int)($item['stock_total'] ?? 0),
                    'discount_percent' => (int)($item['discount_percent'] ?? 0),
                    'url' => '/product/' . rawurlencode((string)($item['slug'] ?? '')),
                ];
            }, $products),
        ]);
    }

    private function analyzeMessage(string $message): array
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $clean = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalizedAscii = $this->normalizeText($clean);

        $detectedMax = null;
        $detectedMin = null;

        if (preg_match('/(?:duoi|dưới|toi da|tối đa|max)\s*([\d\.,]+)\s*(trieu|triệu|k|nghin|nghìn)?/u', $clean, $m)) {
            $detectedMax = $this->toVnd($m[1], $m[2] ?? '');
        }

        if (preg_match('/(?:tren|trên|tu|từ|min)\s*([\d\.,]+)\s*(trieu|triệu|k|nghin|nghìn)?/u', $clean, $m)) {
            $detectedMin = $this->toVnd($m[1], $m[2] ?? '');
        }

        if ($detectedMax === null && preg_match('/([\d\.,]+)\s*(trieu|triệu|k|nghin|nghìn)/u', $clean, $m)) {
            $detectedMax = $this->toVnd($m[1], $m[2] ?? '');
        }

        if (preg_match('/([\d\.,]+)\s*(trieu|triệu|k|nghin|nghìn)?\s*[-~]\s*([\d\.,]+)\s*(trieu|triệu|k|nghin|nghìn)?/u', $clean, $m)) {
            $detectedMin = $this->toVnd($m[1], $m[2] ?? '');
            $detectedMax = $this->toVnd($m[3], $m[4] ?? ($m[2] ?? ''));
        }

        if (preg_match('/([\d]{1,2})\s*tr\s*([\d]{1,2})/u', $clean, $m)) {
            $detectedMin = ((int)$m[1]) * 1000000;
            $detectedMax = $detectedMin + ((int)$m[2]) * 100000;
        }

        $keywordsToStrip = [
            'gợi ý', 'goi y', 'mua', 'cần', 'can', 'tim', 'tìm', 'giup', 'giúp', 'cho toi', 'cho tôi',
            'thong tin', 'thông tin', 'chi tiet', 'chi tiết', 'xem',
            'duoi', 'dưới', 'toi da', 'tối đa', 'max', 'tren', 'trên', 'tu', 'từ', 'min',
            'trieu', 'triệu', 'k', 'nghin', 'nghìn',
        ];

        $keyword = $clean;
        foreach ($keywordsToStrip as $token) {
            $keyword = preg_replace('/\b' . preg_quote($token, '/') . '\b/u', ' ', $keyword) ?? $keyword;
        }
        if ($detectedMin !== null || $detectedMax !== null) {
            $keyword = preg_replace('/[\d\.,]+/u', ' ', $keyword) ?? $keyword;
        }
        $keyword = trim((string)preg_replace('/\s+/', ' ', $keyword));
        $keyword = $this->normalizeKeyword($keyword, $normalizedAscii);

        $productTerm = $this->detectProductTerm($message);
        $intent = $this->detectIntent($normalizedAscii, $productTerm);

        return [
            'keyword' => $keyword,
            'minPrice' => $detectedMin,
            'maxPrice' => $detectedMax,
            'productTerm' => $productTerm,
            'intent' => $intent,
        ];
    }

    private function detectIntent(string $normalizedAscii, string $productTerm): string
    {
        if (preg_match('/\b(xin chao|chao|hello|hi|alo)\b/u', $normalizedAscii)) {
            return 'greeting';
        }

        if (preg_match('/\b(cam on|thanks|thank you)\b/u', $normalizedAscii)) {
            return 'thanks';
        }

        $keywordIntent = $this->detectIntentByKeywordScore($normalizedAscii);
        if ($keywordIntent !== 'general') {
            return match ($keywordIntent) {
                'warranty' => 'warranty_policy',
                'price' => 'price_filter',
                default => $keywordIntent,
            };
        }

        if (str_contains($normalizedAscii, 'giao hang') || str_contains($normalizedAscii, 'van chuyen') || str_contains($normalizedAscii, 'ship')) {
            return 'shipping_policy';
        }

        if (str_contains($normalizedAscii, 'so sanh') || str_contains($normalizedAscii, 'vs ') || str_contains($normalizedAscii, 'khac nhau')) {
            return 'compare';
        }

        if ((str_contains($normalizedAscii, 'vram') || str_contains($normalizedAscii, 'gb')) && $productTerm !== '') {
            return 'spec_vram';
        }

        if (
            str_contains($normalizedAscii, 'chi tiet')
            || str_contains($normalizedAscii, 'thong tin')
            || str_contains($normalizedAscii, 'cau hinh')
            || str_contains($normalizedAscii, 'bao hanh')
            || str_contains($normalizedAscii, 'xem')
        ) {
            return 'product_detail';
        }

        if (str_contains($normalizedAscii, 'chuot')) {
            return 'mouse';
        }

        if ($productTerm !== '') {
            return 'product_detail';
        }

        return 'general';
    }

    private function normalizeText(string $text): string
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        $normalized = $this->toAscii($normalized);

        $phraseReplacements = [
            'jẻ' => 're',
            'je' => 're',
            'láp tốp' => 'laptop',
            'lap top' => 'laptop',
            'ko' => 'khong',
            'k ' => 'khong ',
            ' kh ' => ' khong ',
            'ship' => 'van chuyen',
        ];

        foreach ($phraseReplacements as $from => $to) {
            $normalized = str_replace($from, $to, $normalized);
        }

        $charReplacements = [
            'f' => 'ph',
            'w' => 'u',
            'j' => 'i',
        ];

        $normalized = strtr($normalized, $charReplacements);
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function detectIntentByKeywordScore(string $normalizedText): string
    {
        $keywords = [
            'gaming' => ['gaming', 'choi game', 'fps', 'esport', 'rgb', 'hieu nang'],
            'office' => ['van phong', 'hoc tap', 'lam viec', 'on dinh', 'nhe', 'laptop'],
            'price' => ['gia', 're', 'duoi', 'tren', 'trieu', 'nghin', 'budget', 'k'],
            'warranty' => ['bao hanh', 'bao tri', 'doi tra', 'chinh sach'],
        ];

        $scores = [
            'gaming' => 0,
            'office' => 0,
            'price' => 0,
            'warranty' => 0,
        ];

        foreach ($keywords as $intent => $terms) {
            foreach ($terms as $term) {
                if ($term === 'k') {
                    if (preg_match('/\b\d{2,3}\s*k\b/u', $normalizedText)) {
                        $scores[$intent] += 2;
                    }
                    continue;
                }

                if (str_contains($normalizedText, $term)) {
                    $scores[$intent] += 1;
                }
            }
        }

        arsort($scores);
        $bestIntent = array_key_first($scores);
        if ($bestIntent === null || $scores[$bestIntent] <= 0) {
            return 'general';
        }

        return $bestIntent;
    }

    private function tryGeminiRewrite(string $userMessage, array $analysis, array $products, string $fallbackReply): ?string
    {
        $apiKey = $this->getEnvValue('GEMINI_API_KEY');
        if ($apiKey === '') {
            return null;
        }

        $model = $this->getEnvValue('GEMINI_MODEL', 'gemini-2.5-flash');
        $service = new GeminiChatService($apiKey, $model);
        if (!$service->isReady()) {
            return null;
        }

        $dbContext = $this->collectGeminiContext($userMessage, $analysis, $products);
        $reply = $service->generateReplyWithContext($analysis, $userMessage, $dbContext, $fallbackReply);

        return $reply !== null && $reply !== '' ? $reply : null;
    }

    private function collectGeminiContext(string $userMessage, array $analysis, array $products): array
    {
        $campaigns = $this->fetchActiveCampaigns(8);
        $relatedProducts = $this->fetchRelatedProductsForQuestion($userMessage, $analysis, 5);

        if ($relatedProducts === [] && $products !== []) {
            $relatedProducts = array_map(static function (array $item): array {
                return [
                    'id' => (int)($item['id'] ?? 0),
                    'name' => (string)($item['name'] ?? ''),
                    'slug' => (string)($item['slug'] ?? ''),
                    'price' => (int)($item['price'] ?? 0),
                    'shipping_info' => '',
                    'warranty_months' => 0,
                ];
            }, array_slice($products, 0, 5));
        }

        return [
            'campaigns' => $campaigns,
            'related_products' => $relatedProducts,
            'shipping_policies' => $relatedProducts,
            'warranty_policies' => $relatedProducts,
        ];
    }

    private function fetchActiveCampaigns(int $limit = 8): array
    {
        $sql = "SELECT dc.product_id,
                       p.name AS product_name,
                       dc.discount_percent,
                       dc.start_at,
                       dc.end_at
                FROM product_discount_campaigns dc
                JOIN products p ON p.id = dc.product_id
                WHERE dc.status = 'active'
                  AND dc.start_at <= NOW()
                  AND dc.end_at >= NOW()
                  AND p.is_active = TRUE
                ORDER BY dc.discount_percent DESC, dc.end_at ASC
                LIMIT :lim";

        $st = DB::conn()->prepare($sql);
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll() ?: [];
    }

    private function fetchRelatedProductsForQuestion(string $userMessage, array $analysis, int $limit = 5): array
    {
        $normalized = $this->normalizeText($userMessage);
        $keyword = trim((string)($analysis['keyword'] ?? ''));

        $hints = [];
        if ($keyword !== '') {
            $hints[] = $keyword;
        }

        $typeMap = [
            'chuot' => ['chuot', 'mouse'],
            'ban phim' => ['ban phim', 'keyboard', 'phim'],
            'tai nghe' => ['tai nghe', 'headset', 'headphone'],
            'laptop' => ['laptop', 'lap top', 'notebook'],
            'vga' => ['vga', 'gpu', 'rtx', 'gtx', 'rx'],
        ];

        foreach ($typeMap as $main => $tokens) {
            foreach ($tokens as $token) {
                if (str_contains($normalized, $token)) {
                    $hints[] = $main;
                    break;
                }
            }
        }

        $hints = array_values(array_unique(array_filter($hints)));

        if ($hints !== []) {
            foreach ($hints as $hint) {
                $rows = $this->queryRelatedProductsByHint($hint, $limit);
                if ($rows !== []) {
                    return $rows;
                }
            }
        }

        return $this->queryNewestProducts($limit);
    }

    private function queryRelatedProductsByHint(string $hint, int $limit): array
    {
        $sql = "SELECT p.id,
                       p.name,
                       p.slug,
                       COALESCE(MIN(v.sale_price), p.price, 0)::bigint AS price,
                       COALESCE(MAX(p.shipping_info), '') AS shipping_info,
                       COALESCE(MAX(p.warranty_months), 0) AS warranty_months
                FROM products p
                LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
                WHERE p.is_active = TRUE
                  AND (
                    p.name ILIKE :hint
                    OR p.slug ILIKE :hint
                  )
                GROUP BY p.id, p.name, p.slug, p.created_at
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :lim";

        $st = DB::conn()->prepare($sql);
        $st->bindValue(':hint', '%' . trim($hint) . '%', \PDO::PARAM_STR);
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll() ?: [];
    }

    private function queryNewestProducts(int $limit): array
    {
        $sql = "SELECT p.id,
                       p.name,
                       p.slug,
                       COALESCE(MIN(v.sale_price), p.price, 0)::bigint AS price,
                       COALESCE(MAX(p.shipping_info), '') AS shipping_info,
                       COALESCE(MAX(p.warranty_months), 0) AS warranty_months
                FROM products p
                LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_active = TRUE
                WHERE p.is_active = TRUE
                GROUP BY p.id, p.name, p.slug, p.created_at
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :lim";

        $st = DB::conn()->prepare($sql);
        $st->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll() ?: [];
    }

    private function getEnvValue(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
            return trim($_ENV[$key]);
        }

        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && trim($_SERVER[$key]) !== '') {
            return trim($_SERVER[$key]);
        }

        return $default;
    }

    private function normalizeKeyword(string $keyword, string $normalizedAscii): string
    {
        if ($keyword !== '') {
            return $keyword;
        }

        $categoryMap = [
            'tai nghe' => ['tai nghe', 'headphone', 'headset'],
            'chuột gaming' => ['chuot', 'mouse'],
            'bàn phím' => ['ban phim', 'keyboard'],
            'card màn hình' => ['vga', 'gpu', 'rtx', 'gtx', 'rx '],
            'ram' => ['ram'],
            'ssd' => ['ssd', 'nvme'],
            'cpu' => ['cpu', 'intel', 'amd ryzen', 'core i'],
        ];

        foreach ($categoryMap as $label => $tokens) {
            foreach ($tokens as $token) {
                if (str_contains($normalizedAscii, $token)) {
                    return $label;
                }
            }
        }

        return '';
    }

    private function mergeWithSessionContext(array $analysis): array
    {
        $last = is_array($_SESSION['chatbox_last_filters'] ?? null)
            ? $_SESSION['chatbox_last_filters']
            : [];

        $needContextKeyword = in_array($analysis['keyword'], ['', 'nó', 'cái đó', 'cai do', 'san pham do'], true);
        if ($needContextKeyword && !empty($last['keyword'])) {
            $analysis['keyword'] = (string)$last['keyword'];
        }

        if ($analysis['productTerm'] === '' && !empty($last['productTerm']) && $analysis['intent'] === 'product_detail') {
            $analysis['productTerm'] = (string)$last['productTerm'];
        }

        if ($analysis['minPrice'] === null && isset($last['minPrice']) && is_int($last['minPrice'])) {
            $analysis['minPrice'] = $last['minPrice'];
        }

        if ($analysis['maxPrice'] === null && isset($last['maxPrice']) && is_int($last['maxPrice'])) {
            $analysis['maxPrice'] = $last['maxPrice'];
        }

        return $analysis;
    }

    private function detectProductTerm(string $message): string
    {
        if (preg_match('/(rtx\s?\d{3,4}|gtx\s?\d{3,4}|rx\s?\d{3,4}|i\s?\d[- ]?\d{4,5}[a-z]{0,2})/iu', $message, $m)) {
            return strtoupper(trim((string)$m[1]));
        }

        return '';
    }

    private function extractCompareKeywords(string $message, array $analysis): array
    {
        $keywords = [];

        if (!empty($analysis['productTerm'])) {
            $keywords[] = (string)$analysis['productTerm'];
        }

        $normalized = mb_strtolower($message, 'UTF-8');
        if (preg_match('/(?:so sánh|so sanh)\s+(.+?)\s+(?:và|va|vs|khác nhau với|khac nhau voi)\s+(.+)/u', $normalized, $m)) {
            $left = trim((string)$m[1]);
            $right = trim((string)$m[2]);
            if ($left !== '') {
                $keywords[] = $left;
            }
            if ($right !== '') {
                $keywords[] = $right;
            }
        } elseif (preg_match('/(.+?)\s+(?:vs|và|va)\s+(.+)/u', $normalized, $m)) {
            $left = trim((string)$m[1]);
            $right = trim((string)$m[2]);
            if ($left !== '') {
                $keywords[] = $left;
            }
            if ($right !== '') {
                $keywords[] = $right;
            }
        }

        if (!empty($analysis['keyword'])) {
            $keywords[] = (string)$analysis['keyword'];
        }

        $keywords = array_values(array_unique(array_filter(array_map(static function (string $item): string {
            $clean = preg_replace('/\b(so sánh|so sanh|vs|và|va|khác nhau|khac nhau|với|voi)\b/iu', ' ', $item) ?? $item;
            $clean = trim((string)preg_replace('/\s+/', ' ', $clean));
            return $clean;
        }, $keywords))));

        return $keywords !== [] ? $keywords : ['gaming'];
    }

    private function mergeUniqueProducts(array $base, array $incoming, int $limit): array
    {
        $seen = [];
        foreach ($base as $item) {
            $seen[(int)($item['id'] ?? 0)] = true;
        }

        foreach ($incoming as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $base[] = $item;
            $seen[$id] = true;
            if (count($base) >= $limit) {
                break;
            }
        }

        return $base;
    }

    private function toAscii(string $text): string
    {
        $map = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ];

        return strtr($text, $map);
    }

    private function toVnd(string $value, string $unit): int
    {
        $number = (float)str_replace(',', '.', preg_replace('/[^\d\.,]/', '', $value) ?? '0');
        $unit = mb_strtolower(trim($unit), 'UTF-8');

        if (in_array($unit, ['trieu', 'triệu'], true)) {
            return (int)round($number * 1000000);
        }

        if (in_array($unit, ['k', 'nghin', 'nghìn'], true)) {
            return (int)round($number * 1000);
        }

        return (int)round($number);
    }

    private function buildReplyMessage(array $analysis, array $products): string
    {
        if ($products === []) {
            return 'Mình chưa thấy sản phẩm phù hợp với yêu cầu hiện tại. Bạn thử nới khoảng giá hoặc nhập rõ hơn theo mẫu: "tai nghe gaming dưới 2 triệu", "chuột không dây 500k-1tr", hoặc "so sánh RTX 4060 và RTX 3060".';
        }

        $parts = ['Mình đã tìm thấy một số sản phẩm phù hợp'];
        if ($analysis['keyword'] !== '') {
            $parts[] = 'cho nhu cầu "' . $analysis['keyword'] . '"';
        }

        if ($analysis['minPrice'] !== null || $analysis['maxPrice'] !== null) {
            $range = [];
            if ($analysis['minPrice'] !== null) {
                $range[] = 'từ ' . number_format((int)$analysis['minPrice'], 0, ',', '.') . 'đ';
            }
            if ($analysis['maxPrice'] !== null) {
                $range[] = 'đến ' . number_format((int)$analysis['maxPrice'], 0, ',', '.') . 'đ';
            }
            $parts[] = '(' . implode(' ', $range) . ')';
        }

        return implode(' ', $parts) . '. Bạn có thể bấm vào từng sản phẩm để xem chi tiết kỹ hơn.';
    }

    private function buildProductSummary(array $products): string
    {
        $top = array_slice($products, 0, 3);
        if ($top === []) {
            return '';
        }

        $lines = ['Gợi ý nhanh:'];
        foreach ($top as $idx => $product) {
            $name = (string)($product['name'] ?? 'Sản phẩm');
            $price = (int)($product['price'] ?? 0);
            $stock = (int)($product['stock_total'] ?? 0);
            $discount = (int)($product['discount_percent'] ?? 0);

            $line = ($idx + 1) . '. ' . $name . ' - ' . number_format($price, 0, ',', '.') . 'đ';
            if ($discount > 0) {
                $line .= ' (giảm ' . $discount . '%)';
            }
            $line .= $stock > 0 ? ' - còn hàng' : ' - tạm hết hàng';
            $lines[] = $line;
        }

        $lines[] = 'Bạn muốn mình ưu tiên mẫu giá tốt nhất, mẫu bán chạy, hay mẫu hiệu năng cao nhất?';

        return implode("\n", $lines);
    }

    private function shouldReturnDetailedProduct(string $keyword, array $product): bool
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return false;
        }

        $tokens = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($keyword, 'UTF-8')) ?: [],
            static fn(string $t): bool => mb_strlen($t, 'UTF-8') >= 3
        ));

        if (count($tokens) < 2) {
            return false;
        }

        $name = mb_strtolower((string)($product['name'] ?? ''), 'UTF-8');
        $matched = 0;
        foreach ($tokens as $token) {
            if (str_contains($name, $token)) {
                $matched++;
            }
        }

        return $matched >= 2;
    }

    private function buildDetailedProductReply(array $product): string
    {
        $name = (string)($product['name'] ?? 'Sản phẩm');
        $brand = trim((string)($product['brand_name'] ?? ''));
        $category = trim((string)($product['category_name'] ?? ''));
        $price = (int)($product['price'] ?? 0);
        $stock = (int)($product['stock_total'] ?? 0);
        $warranty = (int)($product['warranty_months'] ?? 0);
        $discount = (int)($product['discount_percent'] ?? 0);

        $lines = [];
        $lines[] = 'Thông tin chi tiết sản phẩm: ' . $name;
        if ($brand !== '') {
            $lines[] = 'Thương hiệu: ' . $brand;
        }
        if ($category !== '') {
            $lines[] = 'Danh mục: ' . $category;
        }
        $lines[] = 'Giá hiện tại: ' . number_format($price, 0, ',', '.') . 'đ' . ($discount > 0 ? ' (đang giảm ' . $discount . '%)' : '');
        $lines[] = 'Tồn kho: ' . ($stock > 0 ? ($stock . ' sản phẩm') : 'Tạm hết hàng');
        if ($warranty > 0) {
            $lines[] = 'Bảo hành: ' . $warranty . ' tháng';
        }

        $optionText = trim((string)($product['option_text'] ?? ''));
        if ($optionText !== '') {
            $lines[] = 'Cấu hình/biến thể: ' . $this->truncateText($optionText, 220);
        }

        $highlights = trim((string)($product['highlights'] ?? ''));
        if ($highlights !== '') {
            $lines[] = 'Điểm nổi bật: ' . $this->truncateText($highlights, 220);
        }

        $specs = trim((string)($product['technical_specs'] ?? ''));
        if ($specs !== '') {
            $lines[] = 'Thông số kỹ thuật: ' . $this->truncateText($specs, 260);
        }

        $shortDesc = trim((string)($product['short_description'] ?? ''));
        if ($shortDesc !== '') {
            $lines[] = 'Mô tả nhanh: ' . $this->truncateText($shortDesc, 200);
        }

        $lines[] = 'Bạn có thể bấm vào link sản phẩm để xem đầy đủ hình ảnh và thông tin cập nhật mới nhất.';

        return implode("\n", $lines);
    }

    private function truncateText(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? $text);
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $limit - 3), 'UTF-8')) . '...';
    }
}
