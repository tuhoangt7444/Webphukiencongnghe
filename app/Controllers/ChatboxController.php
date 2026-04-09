<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Voucher;

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
                'image' => $this->normalizeProductImage((string)($product['image'] ?? '')),
                'is_sale' => ((int)($product['discount_percent'] ?? 0) > 0) || ((int)($product['price'] ?? 0) < (int)($product['original_price'] ?? 0)),
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
        # Intentionally disable keyword-score override because it can wrongly force
        # intents like price_filter and break high-priority intents (voucher/reset/out_of_scope).

        if ($analysis['intent'] === 'out_of_scope') {
            $reply = $this->buildReplyMessage($analysis, []);
            $this->response->json([
                'ok' => true,
                'reply' => $reply,
                'html' => false,
                'source' => 'local',
                'intent' => $analysis['intent'],
                'detected' => [
                    'keyword' => $analysis['keyword'],
                    'minPrice' => $analysis['minPrice'],
                    'maxPrice' => $analysis['maxPrice'],
                ],
                'products' => [],
            ]);
            return;
        }

        $products = [];
        $reply = '';
        $replySource = 'local';
        $replyHtml = false;

        if ($analysis['intent'] === 'ask_voucher') {
            $reply = $this->buildVoucherReplyHtml();
            $this->response->json([
                'ok' => true,
                'reply' => $reply,
                'html' => true,
                'source' => $replySource,
                'intent' => $analysis['intent'],
                'detected' => [
                    'keyword' => $analysis['keyword'],
                    'minPrice' => $analysis['minPrice'],
                    'maxPrice' => $analysis['maxPrice'],
                ],
                'products' => [],
            ]);
            return;
        } elseif ($analysis['intent'] === 'ask_sale') {
            $reply = 'Mình đã lọc nhanh các sản phẩm đang giảm giá cho bạn ngay bên dưới.';
            $products = $this->findSaleProductsForChat($analysis);
            if ($products === []) {
                $reply = 'Hiện tại mình chưa thấy sản phẩm nào đang giảm giá trong kho. Bạn có thể thử lại sau hoặc xem nhóm sản phẩm bán chạy.';
            }
        } elseif ($analysis['intent'] === 'reset_password') {
            $reply = $this->buildResetPasswordReplyHtml();
            $this->response->json([
                'ok' => true,
                'reply' => $reply,
                'html' => true,
                'source' => $replySource,
                'intent' => $analysis['intent'],
                'detected' => [
                    'keyword' => $analysis['keyword'],
                    'minPrice' => $analysis['minPrice'],
                    'maxPrice' => $analysis['maxPrice'],
                ],
                'products' => [],
            ]);
            return;
        } elseif ($analysis['intent'] === 'greeting') {
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
            $mouseKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'chuot';
            $products = Product::suggestForChat($mouseKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'chuot');
        } elseif ($analysis['intent'] === 'keyboard') {
            $reply = 'Mình lọc nhanh nhóm bàn phím đang có sẵn cho bạn ngay bên dưới.';
            $keyboardKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'bàn phím';
            $products = Product::suggestForChat($keyboardKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'bàn phím');
            if ($products === []) {
                $products = Product::suggestForChat($keyboardKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'ban phim');
            }
            if ($products === []) {
                $products = Product::suggestForChat($keyboardKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'keyboard');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'bàn phím');
            }
        } elseif ($analysis['intent'] === 'headphone') {
            $reply = 'Mình lọc nhanh nhóm tai nghe đang có sẵn cho bạn ngay bên dưới.';
            $headphoneKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'tai nghe';
            $products = Product::suggestForChat($headphoneKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'tai nghe');
            if ($products === []) {
                $products = Product::suggestForChat($headphoneKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'tay nghe');
            }
            if ($products === []) {
                $products = Product::suggestForChat($headphoneKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'headphone');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'tai nghe');
            }
        } elseif ($analysis['intent'] === 'monitor') {
            $reply = 'Mình lọc nhanh nhóm màn hình đang có sẵn cho bạn ngay bên dưới.';
            $monitorKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'màn hình';
            $products = Product::suggestForChat($monitorKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'màn hình');
            if ($products === []) {
                $products = Product::suggestForChat($monitorKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'man hinh');
            }
            if ($products === []) {
                $products = Product::suggestForChat($monitorKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'monitor');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'màn hình');
            }
        } elseif ($analysis['intent'] === 'speaker') {
            $reply = 'Mình lọc nhanh nhóm loa đang có sẵn cho bạn ngay bên dưới.';
            $speakerKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'loa';
            $products = Product::suggestForChat($speakerKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'loa');
            if ($products === []) {
                $products = Product::suggestForChat($speakerKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'soundbar');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'loa');
            }
        } elseif ($analysis['intent'] === 'mousepad') {
            $reply = 'Mình lọc nhanh nhóm lót chuột đang có sẵn cho bạn ngay bên dưới.';
            $mousepadKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'lót chuột';
            $products = Product::suggestForChat($mousepadKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'lót chuột');
            if ($products === []) {
                $products = Product::suggestForChat($mousepadKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'lot chuot');
            }
            if ($products === []) {
                $products = Product::suggestForChat($mousepadKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'pad chuot');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'lót chuột');
            }
        } elseif ($analysis['intent'] === 'backpack') {
            $reply = 'Mình lọc nhanh nhóm balo và túi chống sốc đang có sẵn cho bạn ngay bên dưới.';
            $backpackKeyword = $analysis['keyword'] !== '' ? $analysis['keyword'] : 'balo';
            $products = Product::suggestForChat($backpackKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'balo');
            if ($products === []) {
                $products = Product::suggestForChat($backpackKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'ba lo');
            }
            if ($products === []) {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5, 'balo');
            }
            if ($products === []) {
                $products = Product::suggestForChat($backpackKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'túi chống sốc');
            }
            if ($products === []) {
                $products = Product::suggestForChat($backpackKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5, 'tui chong soc');
            }
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
            if ($this->isGenericBudgetKeyword($baseKeyword)) {
                $baseKeyword = '';
            }
            $products = Product::suggestForChat($baseKeyword, $analysis['minPrice'], $analysis['maxPrice'], 5);
            if ($products === [] && $baseKeyword !== '') {
                $products = Product::suggestForChat('', $analysis['minPrice'], $analysis['maxPrice'], 5);
            }
            if ($products === [] && ($analysis['minPrice'] !== null || $analysis['maxPrice'] !== null)) {
                $nearRange = $this->expandPriceRange($analysis['minPrice'], $analysis['maxPrice']);
                $products = Product::suggestForChat($baseKeyword, $nearRange['min'], $nearRange['max'], 5);
                if ($products === [] && $baseKeyword !== '') {
                    $products = Product::suggestForChat('', $nearRange['min'], $nearRange['max'], 5);
                }
                if ($products !== []) {
                    $reply = 'Mình chưa thấy mẫu khớp đúng ngân sách bạn nhập, nên đã nới nhẹ khoảng giá để gợi ý các sản phẩm gần mức bạn cần.';
                }
            }
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

        if ($products !== []) {
            $products = $this->prioritizeProductsWithImage($products);
        }

        if ($products !== [] && !in_array($analysis['intent'], ['product_detail', 'spec_vram'], true)) {
            $reply .= "\n\n" . $this->buildProductSummary($products);
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
            'html' => $replyHtml,
            'source' => $replySource,
            'intent' => $analysis['intent'],
            'detected' => [
                'keyword' => $analysis['keyword'],
                'minPrice' => $analysis['minPrice'],
                'maxPrice' => $analysis['maxPrice'],
            ],
            'products' => array_map(function (array $item): array {
                $discountPercent = (int)($item['discount_percent'] ?? 0);
                $price = (int)($item['price'] ?? 0);
                $originalPrice = (int)($item['original_price'] ?? 0);

                return [
                    'id' => (int)($item['id'] ?? 0),
                    'name' => (string)($item['name'] ?? ''),
                    'slug' => (string)($item['slug'] ?? ''),
                    'category' => (string)($item['category_name'] ?? ''),
                    'price' => $price,
                    'original_price' => $originalPrice,
                    'stock' => (int)($item['stock_total'] ?? 0),
                    'discount_percent' => $discountPercent,
                    'image' => $this->normalizeProductImage((string)($item['image'] ?? '')),
                    'is_sale' => $discountPercent > 0 || $price < $originalPrice,
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

        if (preg_match('/(?:duoi|dưới|toi da|tối đa|max)\s*([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)?/u', $clean, $m)) {
            $detectedMax = $this->toVnd($m[1], $m[2] ?? '');
        }

        if (preg_match('/(?:tren|trên|tu|từ|min)\s*([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)?/u', $clean, $m)) {
            $detectedMin = $this->toVnd($m[1], $m[2] ?? '');
        }

        if ($detectedMin === null && $detectedMax === null
            && preg_match('/([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)/u', $clean, $m)) {
            $detectedMax = $this->toVnd($m[1], $m[2] ?? '');
        }

        if (preg_match('/([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)?\s*[-~]\s*([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)?/u', $clean, $m)) {
            $detectedMin = $this->toVnd($m[1], $m[2] ?? '');
            $detectedMax = $this->toVnd($m[3], $m[4] ?? ($m[2] ?? ''));
        }

        if (preg_match('/([\d]{1,2})\s*tr\s*([\d]{1,2})/u', $clean, $m)) {
            $detectedMin = ((int)$m[1]) * 1000000;
            $detectedMax = $detectedMin + ((int)$m[2]) * 100000;
        }

        # Handle "tầm/khoảng/cỡ/xấp xỉ" as an approximate budget window.
        if (preg_match('/(?:tam|tầm|khoang|khoảng|co|cỡ|xap xi|xấp xỉ)\s*([\d\.,]+)\s*(trieu|triệu|tr|k|nghin|nghìn|cu|củ)/u', $clean, $m)) {
            $center = $this->toVnd($m[1], $m[2] ?? '');
            if ($center > 0) {
                $detectedMin = (int)floor($center * 0.8);
                $detectedMax = (int)ceil($center * 1.2);
            }
        }

        $keywordsToStrip = [
            'gợi ý', 'goi y', 'mua', 'cần', 'can', 'tim', 'tìm', 'giup', 'giúp', 'cho toi', 'cho tôi',
            'thong tin', 'thông tin', 'chi tiet', 'chi tiết', 'xem',
            'duoi', 'dưới', 'toi da', 'tối đa', 'max', 'tren', 'trên', 'tu', 'từ', 'min',
            'trieu', 'triệu', 'tr', 'k', 'nghin', 'nghìn', 'cu', 'củ',
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
        $text = trim((string)preg_replace('/\s+/', ' ', $normalizedAscii));
        if ($text === '' && $productTerm === '') {
            return 'out_of_scope';
        }

        # 1) High-priority intents: detect first to prevent fall-through.
        if ($this->containsAnyKeyword($text, [
            'khong phai voucher', 'ko phai voucher', 'khong phai ma giam gia', 'ko phai ma giam gia'
        ], false) && $this->containsAnyKeyword($text, [
            'sale', 'giam gia', 'khuyen mai', 'khuyến mãi', 'san pham sale', 'hang sale'
        ])) {
            return 'ask_sale';
        }

        if ($this->containsAnyKeyword($text, [
            'voucher', 'ma giam gia', 'mã giảm giá', 'ma khuyen mai', 'mã khuyến mãi',
            'coupon', 'code giam gia', 'code khuyen mai', 'ma uu dai', 'mã ưu đãi'
        ])) {
            return 'ask_voucher';
        }

        if ($this->containsAnyKeyword($text, [
            'sale', 'dang sale', 'giam gia', 'khuyen mai', 'khuyến mãi',
            'khuyen maii', 'khuyển mãi', 'san pham sale', 'mat hang sale', 'hang dang sale', 'uu dai'
        ], false)) {
            return 'ask_sale';
        }

        if ($this->containsAnyKeyword($text, [
            'quen mat khau', 'quên mật khẩu', 'doi mat khau', 'đổi mật khẩu',
            'doi pass', 'đổi pass', 'lay lai pass', 'lấy lại pass',
            'lay lai mat khau', 'reset password', 'forgot password'
        ])) {
            return 'reset_password';
        }

        if ($this->containsAnyKeyword($text, [
            'quan ao', 'quần áo', 'giay', 'giày', 'do an', 'đồ ăn', 'thoi trang', 'fashion'
        ], false)) {
            return 'out_of_scope';
        }

        # 2) Product/service intents.
        if (preg_match('/\b(xin chao|chao|hello|hi|alo)\b/u', $text)) {
            return 'greeting';
        }

        if ($this->containsAnyKeyword($text, ['cam on', 'thanks', 'thank you'], false)) {
            return 'thanks';
        }

        if ($this->containsAnyKeyword($text, ['giao hang', 'van chuyen', 'ship'], false)) {
            return 'shipping_policy';
        }

        if ($this->containsAnyKeyword($text, ['bao hanh', 'bao tri', 'doi tra', 'chinh sach bao hanh'], false)) {
            return 'warranty_policy';
        }

        if ($this->containsAnyKeyword($text, ['so sanh', 'vs', 'khac nhau', 'compare'])) {
            return 'compare';
        }

        if ($this->containsAnyKeyword($text, ['vram', 'bo nho', 'memory', 'gb']) && $productTerm !== '') {
            return 'spec_vram';
        }

        if ($this->containsAnyKeyword($text, ['bàn phím', 'ban phim', 'keyboard', 'phím', 'phim'], false)) {
            return 'keyboard';
        }

        if ($this->containsAnyKeyword($text, ['tai nghe', 'tay nghe', 'headphone', 'earphone', 'head set', 'headset'], false)) {
            return 'headphone';
        }

        if ($this->containsAnyKeyword($text, ['màn hình', 'man hinh', 'monitor'], false)) {
            return 'monitor';
        }

        if ($this->containsAnyKeyword($text, ['lót chuột', 'lot chuot', 'pad chuột', 'pad chuot', 'mousepad'], false)) {
            return 'mousepad';
        }

        if ($this->containsAnyKeyword($text, ['balo', 'ba lô', 'ba lo', 'túi chống sốc', 'tui chong soc'], false)) {
            return 'backpack';
        }

        if ($this->containsAnyKeyword($text, ['chuot', 'mouse', 'chuot gaming', 'chuot choi game', 'chuot choi'], false)) {
            return 'mouse';
        }

        if ($this->containsAnyKeyword($text, ['loa', 'soundbar'], false)) {
            return 'speaker';
        }

        if ($this->containsAnyKeyword($text, ['gaming', 'choi game', 'chien game', 'fps', 'esport', 'rgb'])) {
            return 'gaming';
        }

        if ($this->containsAnyKeyword($text, ['van phong', 'hoc tap', 'lam viec', 'on dinh', 'nhe'])) {
            return 'office';
        }

        if ($this->containsAnyKeyword($text, ['chi tiet', 'thong tin', 'cau hinh', 'xem'])) {
            return 'product_detail';
        }

        if ($this->containsAnyKeyword($text, ['gia', 'duoi', 'tren', 'budget', 'trieu', 'nghin']) || preg_match('/\d/u', $text)) {
            return 'price_filter';
        }

        if ($productTerm !== '') {
            return 'product_detail';
        }

        # 3) Domain fallback: if message still looks like tech accessory context, keep general.
        if ($this->containsAnyKeyword($text, [
            'phu kien', 'phu kien cong nghe', 'techgear', 'tai nghe', 'ban phim',
            'laptop', 'vga', 'gpu', 'ram', 'ssd', 'man hinh', 'loa', 'sac', 'cap', 'chuot'
        ])) {
            return 'general';
        }

        return 'out_of_scope';
    }

    private function containsAnyKeyword(string $text, array $keywords, bool $allowFuzzy = true): bool
    {
        foreach ($keywords as $keyword) {
            $term = trim((string)preg_replace('/\s+/', ' ', $this->normalizeText((string)$keyword)));
            if ($term === '') {
                continue;
            }

            if (str_contains($text, $term)) {
                return true;
            }

            if ($allowFuzzy && $this->scoreFuzzyPhrase($text, $term) >= 68) {
                return true;
            }
        }

        return false;
    }

    private function scoreIntentKeywords(string $text, array $keywords): int
    {
        $best = 0;

        foreach ($keywords as $keyword) {
            $score = $this->scoreFuzzyPhrase($text, (string)$keyword);
            if ($score > $best) {
                $best = $score;
            }
        }

        return $best;
    }

    private function scoreFuzzyPhrase(string $text, string $phrase): int
    {
        $text = trim((string)preg_replace('/\s+/', ' ', $text));
        $phrase = trim((string)preg_replace('/\s+/', ' ', $phrase));

        if ($text === '' || $phrase === '') {
            return 0;
        }

        if (str_contains($text, $phrase)) {
            return 100;
        }

        $compactText = preg_replace('/\s+/', '', $text) ?? $text;
        $compactPhrase = preg_replace('/\s+/', '', $phrase) ?? $phrase;

        if ($compactPhrase !== '' && str_contains($compactText, $compactPhrase)) {
            return 95;
        }

        similar_text($compactText, $compactPhrase, $similarity);
        $best = (int)round($similarity);

        $textWords = array_values(array_filter(explode(' ', $text)));
        $phraseWords = array_values(array_filter(explode(' ', $phrase)));

        if ($textWords === [] || $phraseWords === []) {
            return $best;
        }

        $phraseLength = count($phraseWords);
        $windowSizes = [max(1, $phraseLength - 1), $phraseLength, $phraseLength + 1];
        $windows = [];

        foreach ($windowSizes as $windowSize) {
            if ($windowSize <= 0 || $windowSize > count($textWords)) {
                continue;
            }

            for ($index = 0; $index <= count($textWords) - $windowSize; $index++) {
                $windows[] = implode(' ', array_slice($textWords, $index, $windowSize));
            }
        }

        foreach (array_unique($windows) as $window) {
            $compactWindow = preg_replace('/\s+/', '', $window) ?? $window;
            similar_text($compactWindow, $compactPhrase, $windowSimilarity);
            $best = max($best, (int)round($windowSimilarity));

            $distance = levenshtein($compactWindow, $compactPhrase);
            $maxLength = max(strlen($compactWindow), strlen($compactPhrase), 1);
            $distanceScore = (int)round(100 - (($distance / $maxLength) * 100));
            $best = max($best, max(0, $distanceScore));
        }

        foreach ($textWords as $word) {
            similar_text($word, $compactPhrase, $wordSimilarity);
            $best = max($best, (int)round($wordSimilarity));

            $distance = levenshtein($word, $compactPhrase);
            $maxLength = max(strlen($word), strlen($compactPhrase), 1);
            $distanceScore = (int)round(100 - (($distance / $maxLength) * 100));
            $best = max($best, max(0, $distanceScore));
        }

        return $best;
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

        $carryPriceContext = in_array((string)($analysis['intent'] ?? ''), ['price_filter', 'ask_sale'], true)
            || $needContextKeyword;

        if ($carryPriceContext && $analysis['minPrice'] === null && isset($last['minPrice']) && is_int($last['minPrice'])) {
            $analysis['minPrice'] = $last['minPrice'];
        }

        if ($carryPriceContext && $analysis['maxPrice'] === null && isset($last['maxPrice']) && is_int($last['maxPrice'])) {
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

        if (in_array($unit, ['trieu', 'triệu', 'tr', 'cu', 'củ'], true)) {
            return (int)round($number * 1000000);
        }

        if (in_array($unit, ['k', 'nghin', 'nghìn'], true)) {
            return (int)round($number * 1000);
        }

        return (int)round($number);
    }

    private function isGenericBudgetKeyword(string $keyword): bool
    {
        $normalized = $this->normalizeText($keyword);
        if ($normalized === '') {
            return true;
        }

        $generic = [
            'co', 'co gi', 'co gi khong', 'khong', 'nua', 'nhe', 'shop',
            'gia', 'muc gia', 'tam gia', 'ngan sach', 'phu kien', 'san pham',
        ];

        return in_array($normalized, $generic, true);
    }

    private function expandPriceRange(?int $minPrice, ?int $maxPrice): array
    {
        $min = $minPrice;
        $max = $maxPrice;

        if ($min !== null) {
            $min = max(0, (int)floor($min * 0.8));
        }

        if ($max !== null) {
            $max = (int)ceil($max * 1.2);
        }

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return ['min' => $min, 'max' => $max];
    }

    private function buildReplyMessage(array $analysis, array $products): string
    {
        if (($analysis['intent'] ?? '') === 'out_of_scope') {
            return 'Xin lỗi, chúng tôi không thể trả lời câu hỏi không liên quan đến shop. Bạn có cần tư vấn về phụ kiện công nghệ nào không?';
        }

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

    private function buildVoucherReplyHtml(): string
    {
        $rows = Voucher::listPublicAvailable(6);

        if ($rows === []) {
            $rows = [
                ['code' => 'TECH10', 'name' => 'Giảm 10% cho đơn từ 500k'],
                ['code' => 'FREESHIP', 'name' => 'Miễn phí giao hàng'],
            ];
        }

        $html = 'Hiện tại TECHGEAR có các ưu đãi sau:<br>';
        foreach ($rows as $row) {
            $code = htmlspecialchars(strtoupper((string)($row['code'] ?? '')), ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= '- Mã <b>' . $code . '</b>: ' . $name . '<br>';
        }

        $html .= 'Bạn không cần nhập mã trong chat. Hãy vào <a href="/#home-voucher-claim" target="_blank" rel="noopener noreferrer"><b>trang chủ - mục Phiếu giảm giá</b></a> để bấm <b>Lấy phiếu</b> nhé.';

        return $html;
    }

    private function buildResetPasswordReplyHtml(): string
    {
        return 'Để đặt lại mật khẩu, bạn làm theo 3 bước sau:<br>'
            . '1. Bấm vào <b>Đăng nhập</b> ở góc phải màn hình.<br>'
            . '2. Chọn <b>Quên mật khẩu</b>.<br>'
            . '3. Nhập email của bạn để nhận link đổi mật khẩu.<br>'
            . 'Hoặc click trực tiếp <a href="/forgot-password" target="_blank" rel="noopener noreferrer">vào link này</a> nhé.';
    }

    private function findSaleProductsForChat(array $analysis, int $limit = 6): array
    {
        $pool = [];
        $keywords = array_values(array_unique([
            (string)($analysis['keyword'] ?? ''),
            'sale',
            'giam gia',
            'khuyen mai',
            '',
        ]));

        foreach ($keywords as $kw) {
            $rows = Product::suggestForChat(
                $kw,
                $analysis['minPrice'] ?? null,
                $analysis['maxPrice'] ?? null,
                12
            );

            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $discountPercent = (int)($row['discount_percent'] ?? 0);
                $price = (int)($row['price'] ?? 0);
                $originalPrice = (int)($row['original_price'] ?? 0);
                $isSale = $discountPercent > 0 || ($originalPrice > 0 && $price < $originalPrice);

                if (!$isSale) {
                    continue;
                }

                $pool[$id] = $row;
                if (count($pool) >= $limit) {
                    break 2;
                }
            }
        }

        return array_slice(array_values($pool), 0, $limit);
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

    private function normalizeProductImage(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $image) || str_starts_with($image, '/')) {
            return $image;
        }

        return '/' . ltrim($image, '/');
    }

    private function prioritizeProductsWithImage(array $products): array
    {
        usort($products, function (array $left, array $right): int {
            $leftImage = trim((string)($left['image'] ?? ''));
            $rightImage = trim((string)($right['image'] ?? ''));

            $leftHasImage = $leftImage !== '' && $leftImage !== '/images/logo.png';
            $rightHasImage = $rightImage !== '' && $rightImage !== '/images/logo.png';

            if ($leftHasImage === $rightHasImage) {
                return 0;
            }

            return $leftHasImage ? -1 : 1;
        });

        return $products;
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
