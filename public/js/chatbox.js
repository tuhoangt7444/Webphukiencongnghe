(function () {
    const root = document.querySelector('[data-chatbox]');
    if (!root) {
        return;
    }

    const STORAGE_KEY = 'techgear_chatbox_v1';

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

        const shouldAnimate = settings.animate === true && role === 'bot';
        const renderPromise = shouldAnimate
            ? animateMessageText(el, normalized)
            : Promise.resolve().then(function () {
                el.innerHTML = formatMessageHtml(normalized);
                body.scrollTop = body.scrollHeight;
            });

        return renderPromise.then(function () {
            pushTranscriptEntry({
                type: 'message',
                role: role,
                text: normalized,
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
            const item = document.createElement('div');
            item.className = 'chatbox-product';

            const top = document.createElement('div');
            top.className = 'chatbox-product-top';

            const name = document.createElement('a');
            name.className = 'chatbox-product-name';
            name.href = product.url || '/products';
            name.textContent = product.name || 'Sản phẩm';
            top.appendChild(name);

            if (Number(product.discount_percent || 0) > 0) {
                const discount = document.createElement('span');
                discount.className = 'chatbox-discount';
                discount.textContent = '-' + Number(product.discount_percent || 0) + '%';
                top.appendChild(discount);
            }
            item.appendChild(top);

            const price = document.createElement('div');
            price.className = 'chatbox-product-meta';
            const amount = Number(product.price || 0).toLocaleString('vi-VN') + 'đ';
            const category = product.category ? (' • ' + product.category) : '';
            let text = 'Giá: ' + amount;
            if (Number(product.original_price || 0) > Number(product.price || 0)) {
                text += ' (gốc ' + Number(product.original_price || 0).toLocaleString('vi-VN') + 'đ)';
            }
            text += category;
            price.textContent = text;
            item.appendChild(price);

            const stock = document.createElement('div');
            stock.className = 'chatbox-product-stock ' + (Number(product.stock || 0) > 0 ? 'in-stock' : 'out-stock');
            stock.textContent = Number(product.stock || 0) > 0 ? 'Còn hàng' : 'Tạm hết hàng';
            item.appendChild(stock);

            const actions = document.createElement('div');
            actions.className = 'chatbox-product-actions';

            const openButton = document.createElement('a');
            openButton.className = 'chatbox-product-open-btn';
            openButton.href = product.url || '/products';
            openButton.textContent = 'Mở trang sản phẩm';
            actions.appendChild(openButton);

            const detailButton = document.createElement('button');
            detailButton.type = 'button';
            detailButton.className = 'chatbox-product-detail-btn';
            detailButton.setAttribute('data-chat-product-detail', String(product.id || 0));
            detailButton.textContent = 'Thông tin chi tiết';
            actions.appendChild(detailButton);

            item.appendChild(actions);

            wrap.appendChild(item);
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

    function appendMessageHydrated(text, role) {
        const el = document.createElement('div');
        el.className = 'chatbox-message ' + role;
        const normalized = String(text || '').trim();
        el.innerHTML = formatMessageHtml(normalized);
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
            const item = document.createElement('div');
            item.className = 'chatbox-product';

            const top = document.createElement('div');
            top.className = 'chatbox-product-top';

            const name = document.createElement('a');
            name.className = 'chatbox-product-name';
            name.href = product.url || '/products';
            name.textContent = product.name || 'Sản phẩm';
            top.appendChild(name);

            if (Number(product.discount_percent || 0) > 0) {
                const discount = document.createElement('span');
                discount.className = 'chatbox-discount';
                discount.textContent = '-' + Number(product.discount_percent || 0) + '%';
                top.appendChild(discount);
            }
            item.appendChild(top);

            const price = document.createElement('div');
            price.className = 'chatbox-product-meta';
            const amount = Number(product.price || 0).toLocaleString('vi-VN') + 'đ';
            const category = product.category ? (' • ' + product.category) : '';
            let text = 'Giá: ' + amount;
            if (Number(product.original_price || 0) > Number(product.price || 0)) {
                text += ' (gốc ' + Number(product.original_price || 0).toLocaleString('vi-VN') + 'đ)';
            }
            text += category;
            price.textContent = text;
            item.appendChild(price);

            const stock = document.createElement('div');
            stock.className = 'chatbox-product-stock ' + (Number(product.stock || 0) > 0 ? 'in-stock' : 'out-stock');
            stock.textContent = Number(product.stock || 0) > 0 ? 'Còn hàng' : 'Tạm hết hàng';
            item.appendChild(stock);

            const actions = document.createElement('div');
            actions.className = 'chatbox-product-actions';

            const openButton = document.createElement('a');
            openButton.className = 'chatbox-product-open-btn';
            openButton.href = product.url || '/products';
            openButton.textContent = 'Mở trang sản phẩm';
            actions.appendChild(openButton);

            const detailButton = document.createElement('button');
            detailButton.type = 'button';
            detailButton.className = 'chatbox-product-detail-btn';
            detailButton.setAttribute('data-chat-product-detail', String(product.id || 0));
            detailButton.textContent = 'Thông tin chi tiết';
            actions.appendChild(detailButton);

            item.appendChild(actions);

            wrap.appendChild(item);
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
                appendMessageHydrated(String(entry.text || ''), String(entry.role || 'bot'));
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
        await appendMessage(String(data.reply || 'Mình đã nhận yêu cầu của bạn.'), 'bot', { animate: true });
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
