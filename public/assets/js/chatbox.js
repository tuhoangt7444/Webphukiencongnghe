(function () {
    const root = document.querySelector('[data-chatbox]');
    if (!root) {
        return;
    }

    const toggle = root.querySelector('[data-chatbox-toggle]');
    const panel = root.querySelector('[data-chatbox-panel]');
    const body = root.querySelector('[data-chatbox-body]');
    const form = root.querySelector('[data-chatbox-form]');
    const input = root.querySelector('[data-chatbox-input]');

    function formatMoney(value) {
        const number = Number(value || 0);
        return number.toLocaleString('vi-VN') + 'đ';
    }

    function appendMessage(text, role) {
        const msg = document.createElement('div');
        msg.className = 'tg-chat-msg ' + role;
        msg.textContent = text;
        body.appendChild(msg);
        body.scrollTop = body.scrollHeight;
    }

    function appendProducts(products) {
        if (!Array.isArray(products) || products.length === 0) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'tg-chat-products';

        products.forEach((product) => {
            const item = document.createElement('div');
            item.className = 'tg-chat-product';

            const link = document.createElement('a');
            link.href = product.url || '/products';
            link.textContent = product.name || 'Sản phẩm';
            item.appendChild(link);

            const meta = document.createElement('div');
            meta.className = 'tg-chat-product-meta';

            const base = Number(product.original_price || 0);
            const sale = Number(product.price || 0);
            const discount = Number(product.discount_percent || 0);

            let line = formatMoney(sale);
            if (discount > 0 && base > sale) {
                line += ' (gốc ' + formatMoney(base) + ', giảm ' + discount + '%)';
            }
            if (product.category) {
                line += ' • ' + product.category;
            }
            meta.textContent = line;
            item.appendChild(meta);

            wrap.appendChild(item);
        });

        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;
    }

    async function askBot(message) {
        const url = '/chatbox/reply?q=' + encodeURIComponent(message);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data || data.ok !== true) {
            appendMessage(data.reply || 'Mình đang bận, bạn thử lại sau nhé.', 'bot');
            return;
        }

        appendMessage(data.reply || 'Mình đã tìm được một số gợi ý.', 'bot');
        appendProducts(data.products || []);
    }

    toggle.addEventListener('click', function () {
        panel.classList.toggle('is-open');
        if (panel.classList.contains('is-open')) {
            input.focus();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) {
            return;
        }

        appendMessage(message, 'user');
        input.value = '';

        try {
            await askBot(message);
        } catch (error) {
            appendMessage('Không kết nối được máy chủ, bạn thử lại giúp mình nhé.', 'bot');
        }
    });
})();
