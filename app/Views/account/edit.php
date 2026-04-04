<?php use App\Core\View; ?>

<?php
$status = (string)($status ?? '');
$isAdmin = (bool)($isAdmin ?? false);
$avatarUrl = trim((string)($avatarUrl ?? ''));
$avatarFallback = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" rx="60" fill="#dbeafe"/><circle cx="60" cy="46" r="22" fill="#93c5fd"/><path d="M20 106c8-20 24-30 40-30s32 10 40 30" fill="#93c5fd"/></svg>');
$profile = $profile ?? [
    'full_name' => '',
    'phone' => '',
    'address_line' => '',
    'ward' => '',
    'district' => '',
    'city' => '',
];
?>

<section class="py-5 bg-light" style="min-height:60vh">
    <div class="container px-3 px-lg-4">
        <div class="mx-auto card border-0 shadow-sm" style="max-width:900px;border-radius:18px;">
            <div class="card-body p-4 p-lg-5">
                <p class="text-uppercase fw-bold mb-1" style="letter-spacing:.16em;font-size:.72rem;color:#0e7490">Tài khoản</p>
                <h1 class="mt-2 mb-0 h3 fw-bold text-dark">Chỉnh sửa thông tin cá nhân</h1>
                <p class="mt-2 mb-0 text-muted small">Email đăng nhập: <b><?= View::e((string)$userEmail) ?></b></p>

            <?php if ($status === 'profile-invalid'): ?>
                <div class="alert alert-warning mt-4 mb-0">Vui lòng nhập đầy đủ thông tin hợp lệ (bao gồm số điện thoại).</div>
            <?php elseif ($status === 'profile-failed'): ?>
                <div class="alert alert-danger mt-4 mb-0">Không thể cập nhật thông tin, vui lòng thử lại.</div>
            <?php elseif ($status === 'phone-required'): ?>
                <div class="alert alert-warning mt-4 mb-0">Vui lòng cập nhật số điện thoại trước khi đặt hàng.</div>
            <?php elseif ($status === 'address-required'): ?>
                <div class="alert alert-warning mt-4 mb-0">Vui lòng cập nhật địa chỉ giao hàng trước khi đặt hàng.</div>
            <?php elseif ($status === 'profile-required'): ?>
                <div class="alert alert-warning mt-4 mb-0">Vui lòng cập nhật đầy đủ thông tin cá nhân trước khi đặt hàng.</div>
            <?php elseif ($status === 'avatar-updated'): ?>
                <div class="alert alert-success mt-4 mb-0">Đã cập nhật avatar thành công.</div>
            <?php elseif ($status === 'avatar-empty'): ?>
                <div class="alert alert-warning mt-4 mb-0">Vui lòng chọn ảnh avatar trước khi tải lên.</div>
            <?php elseif ($status === 'avatar-too-large'): ?>
                <div class="alert alert-warning mt-4 mb-0">Ảnh avatar vượt quá dung lượng 2MB.</div>
            <?php elseif ($status === 'avatar-invalid'): ?>
                <div class="alert alert-warning mt-4 mb-0">Avatar không hợp lệ. Chỉ chấp nhận JPG, PNG, WEBP hoặc GIF.</div>
            <?php elseif ($status === 'avatar-failed'): ?>
                <div class="alert alert-danger mt-4 mb-0">Không thể cập nhật avatar, vui lòng thử lại.</div>
            <?php endif; ?>

            <div class="mt-4 p-3 border rounded-3 bg-white d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                <img
                    src="<?= View::e($avatarUrl !== '' ? $avatarUrl : $avatarFallback) ?>"
                    onerror="this.onerror=null;this.src='<?= View::e($avatarFallback) ?>';"
                    alt="Avatar hiện tại"
                    style="width:72px;height:72px;border-radius:999px;object-fit:cover;border:2px solid rgba(14,116,144,0.35);"
                >
                <form method="POST" action="/account/avatar" enctype="multipart/form-data" class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 w-100">
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" class="form-control form-control-sm" required>
                    <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap">Cập nhật avatar</button>
                </form>
            </div>

            <form method="POST" action="/account/update" class="mt-4 row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Họ và tên *</label>
                    <input type="text" name="full_name" value="<?= View::e((string)$profile['full_name']) ?>" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Số điện thoại *</label>
                    <input type="text" name="phone" value="<?= View::e((string)$profile['phone']) ?>" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Tỉnh/Thành phố *</label>
                    <select id="city-select" name="city" class="form-select" required data-selected="<?= View::e((string)$profile['city']) ?>">
                        <option value="">Đang tải tỉnh/thành...</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Số nhà, tên đường *</label>
                    <input type="text" name="address_line" value="<?= View::e((string)$profile['address_line']) ?>" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Quận/Huyện *</label>
                    <select id="district-select" name="district" class="form-select" required data-selected="<?= View::e((string)$profile['district']) ?>" disabled>
                        <option value="">Chọn tỉnh/thành trước</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Phường/Xã *</label>
                    <select id="ward-select" name="ward" class="form-select" required data-selected="<?= View::e((string)$profile['ward']) ?>" disabled>
                        <option value="">Chọn quận/huyện trước</option>
                    </select>
                </div>
                <input type="hidden" id="full-address-input" name="full_address" value="<?= View::e((string)($profile['full_address'] ?? '')) ?>">
                <div class="col-12 d-flex flex-column flex-sm-row gap-2 mt-2">
                    <?php if ($isAdmin): ?>
                        <a href="/admin" class="btn btn-dark">Vào trang admin</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Lưu cập nhật</button>
                    <a href="/account" class="btn btn-outline-secondary">Quay lại thông tin tài khoản</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(() => {
    const API_BASE = 'https://provinces.open-api.vn/api';

    const citySelect = document.getElementById('city-select');
    const districtSelect = document.getElementById('district-select');
    const wardSelect = document.getElementById('ward-select');
    const fullAddressInput = document.getElementById('full-address-input');
    const form = citySelect ? citySelect.closest('form') : null;

    if (!citySelect || !districtSelect || !wardSelect || !form) {
        return;
    }

    const selectedCity = citySelect.dataset.selected || '';
    const selectedDistrict = districtSelect.dataset.selected || '';
    const selectedWard = wardSelect.dataset.selected || '';

    const optionMarkup = (value, label, selected = false) =>
        `<option value="${escapeHtml(value)}" ${selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function fetchJson(url) {
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error('Fetch failed: ' + res.status);
        }
        return res.json();
    }

    function findCodeByName(items, name) {
        const hit = items.find(item => (item.name || '').trim() === name.trim());
        return hit ? String(hit.code) : '';
    }

    async function loadProvinces() {
        citySelect.disabled = true;
        citySelect.innerHTML = optionMarkup('', 'Chọn Tỉnh/Thành phố', false);

        try {
            const provinces = await fetchJson(`${API_BASE}/p/`);
            provinces.forEach(p => {
                citySelect.insertAdjacentHTML(
                    'beforeend',
                    optionMarkup(p.name, p.name, p.name === selectedCity)
                );
            });
            citySelect.disabled = false;

            if (selectedCity) {
                const cityCode = findCodeByName(provinces, selectedCity);
                if (cityCode) {
                    await loadDistricts(cityCode, true);
                }
            }
        } catch (e) {
            citySelect.innerHTML = optionMarkup('', 'Không tải được dữ liệu tỉnh/thành', false);
        }
    }

    async function loadDistricts(cityCode, isInitial = false) {
        districtSelect.disabled = true;
        districtSelect.innerHTML = optionMarkup('', 'Đang tải quận/huyện...', false);
        wardSelect.disabled = true;
        wardSelect.innerHTML = optionMarkup('', 'Chọn quận/huyện trước', false);

        try {
            const city = await fetchJson(`${API_BASE}/p/${cityCode}?depth=2`);
            const districts = city.districts || [];

            districtSelect.innerHTML = optionMarkup('', 'Chọn Quận/Huyện', false);
            districts.forEach(d => {
                const shouldSelect = isInitial && d.name === selectedDistrict;
                districtSelect.insertAdjacentHTML(
                    'beforeend',
                    optionMarkup(d.name, d.name, shouldSelect)
                );
            });
            districtSelect.disabled = false;

            if (isInitial && selectedDistrict) {
                const districtCode = findCodeByName(districts, selectedDistrict);
                if (districtCode) {
                    await loadWards(districtCode, true);
                }
            }
        } catch (e) {
            districtSelect.innerHTML = optionMarkup('', 'Không tải được quận/huyện', false);
        }
    }

    async function loadWards(districtCode, isInitial = false) {
        wardSelect.disabled = true;
        wardSelect.innerHTML = optionMarkup('', 'Đang tải phường/xã...', false);

        try {
            const district = await fetchJson(`${API_BASE}/d/${districtCode}?depth=2`);
            const wards = district.wards || [];

            wardSelect.innerHTML = optionMarkup('', 'Chọn Phường/Xã', false);
            wards.forEach(w => {
                const shouldSelect = isInitial && w.name === selectedWard;
                wardSelect.insertAdjacentHTML(
                    'beforeend',
                    optionMarkup(w.name, w.name, shouldSelect)
                );
            });
            wardSelect.disabled = false;
        } catch (e) {
            wardSelect.innerHTML = optionMarkup('', 'Không tải được phường/xã', false);
        }
    }

    citySelect.addEventListener('change', async () => {
        const cityName = citySelect.value;
        districtSelect.innerHTML = optionMarkup('', 'Chọn tỉnh/thành trước', false);
        wardSelect.innerHTML = optionMarkup('', 'Chọn quận/huyện trước', false);

        if (!cityName) {
            districtSelect.disabled = true;
            wardSelect.disabled = true;
            return;
        }

        try {
            const provinces = await fetchJson(`${API_BASE}/p/`);
            const cityCode = findCodeByName(provinces, cityName);
            if (cityCode) {
                await loadDistricts(cityCode, false);
            }
        } catch (e) {
            districtSelect.disabled = true;
            wardSelect.disabled = true;
        }
    });

    districtSelect.addEventListener('change', async () => {
        const districtName = districtSelect.value;
        wardSelect.innerHTML = optionMarkup('', 'Chọn quận/huyện trước', false);

        if (!districtName) {
            wardSelect.disabled = true;
            return;
        }

        try {
            const cityName = citySelect.value;
            const provinces = await fetchJson(`${API_BASE}/p/`);
            const cityCode = findCodeByName(provinces, cityName);
            if (!cityCode) {
                wardSelect.disabled = true;
                return;
            }

            const city = await fetchJson(`${API_BASE}/p/${cityCode}?depth=2`);
            const districtCode = findCodeByName(city.districts || [], districtName);
            if (districtCode) {
                await loadWards(districtCode, false);
            }
        } catch (e) {
            wardSelect.disabled = true;
        }
    });

    form.addEventListener('submit', () => {
        const detail = String(form.querySelector('input[name="address_line"]').value || '').trim();
        const ward = String(wardSelect.value || '').trim();
        const district = String(districtSelect.value || '').trim();
        const city = String(citySelect.value || '').trim();
        fullAddressInput.value = [detail, ward, district, city].filter(Boolean).join(', ');
    });

    loadProvinces();
})();
</script>
