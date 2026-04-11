(function () {
    const root = document.querySelector('[data-chatbox]');
    if (!root) {
        return;
    }

    const rawChatboxUserId = Number(root.getAttribute('data-chatbox-user-id') || 0);
    const chatboxUserId = Number.isFinite(rawChatboxUserId) && rawChatboxUserId > 0 ? rawChatboxUserId : 0;
    const STORAGE_KEY = 'techgear_chatbox_v1_u' + String(chatboxUserId);

    const panel = root.querySelector('[data-chatbox-panel]');
    const toggleButton = root.querySelector('[data-chatbox-toggle]');
    const closeButton = root.querySelector('[data-chatbox-close]');
    const form = root.querySelector('[data-chatbox-form]');
    const input = root.querySelector('[data-chatbox-input]');
    const body = root.querySelector('[data-chatbox-body]');
    const quick = root.querySelector('[data-chatbox-quick]');
    let transcript = [];

    const quickPrompts = [
        'Gợi ý tai nghe gaming dưới 2 triệu',
        'Chuột không dây khoảng 500k-1 triệu',
        'So sánh RTX 4060 và RTX 3060',
        'Sản phẩm nào phù hợp học tập văn phòng?',
    ];

    function escapeHtml(raw) {
        return String(raw)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMessageHtml(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function renderMessageContent(el, text, html) {
        const normalized = String(text || '').trim();
        if (html === true) {
            el.innerHTML = normalized;
            return;
        }

        el.innerHTML = formatMessageHtml(normalized);
    }

    function formatMoney(value) {
        const amount = Number(value || 0);
        return amount.toLocaleString('vi-VN') + 'đ';
    }

    function normalizeProductImage(product) {
        const image = String((product && (product.image || product.thumbnail || product.thumb)) || '').trim();
        if (image !== '') {
            return image;
        }

        return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240">' +
            '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1">' +
            '<stop offset="0%" stop-color="#dbeafe"/><stop offset="100%" stop-color="#eff6ff"/>' +
            '</linearGradient></defs>' +
            '<rect width="320" height="240" rx="24" fill="url(#g)"/>' +
            '<circle cx="160" cy="104" r="38" fill="#93c5fd" opacity="0.85"/>' +
            '<rect x="92" y="152" width="136" height="26" rx="13" fill="#60a5fa" opacity="0.55"/>' +
            '<text x="160" y="214" text-anchor="middle" fill="#1d4ed8" font-family="Arial, sans-serif" font-size="20" font-weight="700">TECHGEAR</text>' +
            '</svg>'
        );
    }

    function isProductOnSale(product) {
        return Boolean(
            product && (
                product.is_sale === true ||
                product.is_sale === 'true' ||
                Number(product.discount_percent || 0) > 0
            )
        );
    }

    function createProductCard(product) {
        const item = document.createElement('article');
        item.className = 'chatbox-product';

        const media = document.createElement('div');
        media.className = 'chatbox-product-media';

        const imageWrap = document.createElement('div');
        imageWrap.className = 'chatbox-product-image-wrap';

        const img = document.createElement('img');
        img.className = 'chatbox-product-image';
        img.alt = product && product.name ? String(product.name) : 'Sản phẩm';
        img.loading = 'lazy';
        img.src = normalizeProductImage(product);
        img.onerror = function () {
            this.onerror = null;
            this.src = normalizeProductImage({});
        };
        imageWrap.appendChild(img);

        if (isProductOnSale(product)) {
            const saleBadge = document.createElement('span');
            saleBadge.className = 'chatbox-product-sale-badge';
            saleBadge.textContent = '🔥 Đang Sale';
            imageWrap.appendChild(saleBadge);
        }

        media.appendChild(imageWrap);
        item.appendChild(media);

        const content = document.createElement('div');
        content.className = 'chatbox-product-content';

        const title = document.createElement('h4');
        title.className = 'chatbox-product-title';
        title.textContent = product && product.name ? String(product.name) : 'Sản phẩm';
        content.appendChild(title);

        const price = document.createElement('div');
        price.className = 'chatbox-product-price';
        price.textContent = 'Giá bán: ' + formatMoney(product && product.price);
        content.appendChild(price);

        if (Number(product && product.original_price || 0) > Number(product && product.price || 0)) {
            const oldPrice = document.createElement('div');
            oldPrice.className = 'chatbox-product-old-price';
            oldPrice.textContent = 'Giá gốc: ' + formatMoney(product.original_price);
            content.appendChild(oldPrice);
        }

        const actions = document.createElement('div');
        actions.className = 'chatbox-product-actions';

        const detailButton = document.createElement('button');
        detailButton.type = 'button';
        detailButton.className = 'chatbox-product-detail-btn';
        detailButton.setAttribute('data-chat-product-detail', String(product && product.id || 0));
        detailButton.textContent = 'Chi tiết sản phẩm';
        actions.appendChild(detailButton);

        const openButton = document.createElement('a');
        openButton.className = 'chatbox-product-open-btn';
        openButton.href = (product && product.url) || '/products';
        openButton.target = '_self';
        openButton.textContent = 'Vô trang sản phẩm';
        actions.appendChild(openButton);

        content.appendChild(actions);
        item.appendChild(content);

        return item;
    }

    function removeTypingIndicators() {
        const typingNodes = body.querySelectorAll('.chatbox-typing');
        typingNodes.forEach(function (node) {
            node.remove();
        });
    }

    function animateMessageText(el, text) {
        return new Promise(function (resolve) {
            const normalized = String(text || '');
            if (normalized === '') {
                el.innerHTML = '';
                resolve();
                return;
            }

            let index = 0;
            el.classList.add('is-typing');

            const step = function () {
                if (index >= normalized.length) {
                    el.classList.remove('is-typing');
                    resolve();
                    return;
                }

                const remaining = normalized.length - index;
                const chunkSize = remaining > 420 ? 6 : (remaining > 220 ? 4 : 2);
                index = Math.min(normalized.length, index + chunkSize);
                el.innerHTML = formatMessageHtml(normalized.slice(0, index));
                body.scrollTop = body.scrollHeight;

                window.setTimeout(step, 18);
            };

            step();
        });
    }

    function appendMessage(text, role, options) {
        const settings = options && typeof options === 'object' ? options : {};
        const el = document.createElement('div');
        el.className = 'chatbox-message ' + role;
        const normalized = String(text || '').trim();
        el.innerHTML = '';
        body.appendChild(el);
        body.scrollTop = body.scrollHeight;

        const shouldAnimate = settings.animate === true && role === 'bot' && settings.html !== true;
        const renderPromise = shouldAnimate
            ? animateMessageText(el, normalized)
            : Promise.resolve().then(function () {
                renderMessageContent(el, normalized, settings.html === true);
                body.scrollTop = body.scrollHeight;
            });

        return renderPromise.then(function () {
            pushTranscriptEntry({
                type: 'message',
                role: role,
                text: normalized,
                html: settings.html === true,
            });
            return el;
        });
    }

    function appendTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'chatbox-typing';
        wrap.innerHTML = '<span></span><span></span><span></span><em>Đang phân tích...</em>';
        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;
        return wrap;
    }

    function openChatbox() {
        panel.classList.add('open');
        input.focus();
    }

    function closeChatbox() {
        panel.classList.remove('open');
        persistState();
    }

    function appendProducts(products) {
        if (!Array.isArray(products) || products.length === 0) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'chatbox-products';

        products.forEach(function (product) {
            wrap.appendChild(createProductCard(product));
        });

        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;

        pushTranscriptEntry({
            type: 'products',
            items: products.map(function (product) {
                return {
                    id: Number(product.id || 0),
                    name: String(product.name || ''),
                    url: String(product.url || '/products'),
                    category: String(product.category || ''),
                    price: Number(product.price || 0),
                    original_price: Number(product.original_price || 0),
                    stock: Number(product.stock || 0),
                    discount_percent: Number(product.discount_percent || 0),
                    is_sale: Boolean(product.is_sale || Number(product.discount_percent || 0) > 0),
                    image: String(product.image || product.thumbnail || ''),
                    slug: String(product.slug || ''),
                };
            }),
        });
    }

    function persistState() {
        try {
            const payload = {
                transcript: transcript,
                panelOpen: panel.classList.contains('open'),
            };
            window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch (error) {
            // Ignore storage quota/private mode failures.
        }
    }

    function pushTranscriptEntry(entry) {
        transcript.push(entry);
        persistState();
    }

    function seedInitialTranscript() {
        const initial = body.querySelector('.chatbox-message.bot');
        if (!initial) {
            return;
        }

        const text = String(initial.textContent || '').trim();
        if (text === '') {
            return;
        }

        transcript = [{
            type: 'message',
            role: 'bot',
            text: text,
        }];
        persistState();
    }

    function clearRenderedHistory() {
        const nodes = body.querySelectorAll('.chatbox-message, .chatbox-products, .chatbox-typing');
        nodes.forEach(function (node) {
            node.remove();
        });
    }

    function appendMessageHydrated(text, role, html) {
        const el = document.createElement('div');
        el.className = 'chatbox-message ' + role;
        const normalized = String(text || '').trim();
        renderMessageContent(el, normalized, html === true);
        body.appendChild(el);
        return el;
    }

    function appendProductsHydrated(products) {
        if (!Array.isArray(products) || products.length === 0) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'chatbox-products';

        products.forEach(function (product) {
            wrap.appendChild(createProductCard(product));
        });

        body.appendChild(wrap);
    }

    function hydrateFromStorage() {
        let raw = null;
        try {
            raw = window.sessionStorage.getItem(STORAGE_KEY);
        } catch (error) {
            raw = null;
        }

        if (!raw) {
            seedInitialTranscript();
            return;
        }

        let payload = null;
        try {
            payload = JSON.parse(raw);
        } catch (error) {
            payload = null;
        }

        if (!payload || !Array.isArray(payload.transcript) || payload.transcript.length === 0) {
            seedInitialTranscript();
            return;
        }

        transcript = payload.transcript;
        clearRenderedHistory();
        transcript.forEach(function (entry) {
            if (!entry || typeof entry !== 'object') {
                return;
            }

            if (entry.type === 'message') {
                appendMessageHydrated(String(entry.text || ''), String(entry.role || 'bot'), entry.html === true);
                return;
            }

            if (entry.type === 'products') {
                appendProductsHydrated(Array.isArray(entry.items) ? entry.items : []);
            }
        });

        if (payload.panelOpen === true) {
            openChatbox();
        }

        body.scrollTop = body.scrollHeight;
    }

    function renderQuickPrompts() {
        if (!quick) {
            return;
        }
        quick.innerHTML = '';
        quickPrompts.forEach(function (label) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chatbox-quick-btn';
            btn.textContent = label;
            btn.addEventListener('click', function () {
                input.value = label;
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            });
            quick.appendChild(btn);
        });
    }

    async function askBot(message) {
        const endpoint = '/chatbox/reply?q=' + encodeURIComponent(message);
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json().catch(function () { return null; });
        if (!response.ok || !data || data.ok !== true) {
            const serverMessage = data && typeof data.reply === 'string' && data.reply.trim() !== ''
                ? data.reply
                : 'Hiện tại chatbox đang bận. Bạn vui lòng thử lại sau ít phút.';
            removeTypingIndicators();
            await appendMessage(serverMessage, 'bot', { animate: true });
            return;
        }

        removeTypingIndicators();
        await appendMessage(String(data.reply || 'Mình đã nhận yêu cầu của bạn.'), 'bot', { animate: true, html: data.html === true });
        appendProducts(data.products || []);
    }

    async function askProductDetail(productId) {
        const endpoint = '/chatbox/product-detail?id=' + encodeURIComponent(String(productId || ''));
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json().catch(function () { return null; });
        if (!response.ok || !data || data.ok !== true) {
            await appendMessage('Mình chưa lấy được thông tin chi tiết sản phẩm này, bạn vui lòng thử lại.', 'bot', { animate: true });
            return;
        }

        await appendMessage(String(data.reply || 'Đây là thông tin chi tiết sản phẩm.'), 'bot', { animate: true });
    }

    toggleButton.addEventListener('click', function () {
        if (panel.classList.contains('open')) {
            closeChatbox();
        } else {
            openChatbox();
            persistState();
        }
    });

    closeButton.addEventListener('click', closeChatbox);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const message = input.value.trim();
        if (!message) {
            return;
        }

        await appendMessage(message, 'user');
        input.value = '';

        const typingEl = appendTyping();

        try {
            await askBot(message);
        } catch (error) {
            removeTypingIndicators();
            await appendMessage('Không kết nối được chatbox. Bạn kiểm tra mạng và thử lại sau giúp mình nhé.', 'bot', { animate: true });
        } finally {
            if (typingEl && typingEl.parentNode) {
                typingEl.parentNode.removeChild(typingEl);
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeChatbox();
        }
    });

    body.addEventListener('click', async function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const openLink = target.closest('.chatbox-product-open-btn');
        if (openLink instanceof HTMLAnchorElement) {
            event.preventDefault();
            const href = String(openLink.getAttribute('href') || '').trim();
            if (href !== '') {
                window.location.assign(href);
            }
            return;
        }

        const productId = target.getAttribute('data-chat-product-detail');
        if (!productId) {
            return;
        }

        target.setAttribute('disabled', 'disabled');
        const originalLabel = target.textContent;
        target.textContent = 'Đang lấy thông tin...';

        try {
            await askProductDetail(productId);
        } catch (error) {
            await appendMessage('Máy chủ đang bận, bạn thử lại sau giúp mình nhé.', 'bot', { animate: true });
        } finally {
            target.removeAttribute('disabled');
            target.textContent = originalLabel || 'Thông tin chi tiết sản phẩm';
        }
    });

    renderQuickPrompts();
    hydrateFromStorage();
})();
