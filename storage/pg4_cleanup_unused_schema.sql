-- Schema cleanup candidates generated from code usage audit.
-- Review on a backup database first.
-- Each statement runs in autocommit mode (no BEGIN/COMMIT wrapper).
-- IF EXISTS ensures failed drops do not block subsequent statements.

-- Xóa bất kỳ transaction nào bị kẹt từ lần chạy trước.
-- Nếu không có transaction nào: PostgreSQL chỉ cảnh báo, không lỗi.
ROLLBACK;


-- =========================================================
-- SAFE COLUMN DROPS
-- High-confidence: no references found in app code.
-- =========================================================

ALTER TABLE IF EXISTS public.option_values
    DROP COLUMN IF EXISTS extra;

ALTER TABLE IF EXISTS public.payments
    DROP COLUMN IF EXISTS provider;

ALTER TABLE IF EXISTS public.payments
    DROP COLUMN IF EXISTS provider_ref;

ALTER TABLE IF EXISTS public.employee_profiles
    DROP COLUMN IF EXISTS phone;

ALTER TABLE IF EXISTS public.employee_profiles
    DROP COLUMN IF EXISTS employee_code;

-- =========================================================
-- SAFE TABLE DROPS
-- High-confidence: no references found in app code.
-- Child tables are dropped before parent tables.
-- =========================================================

DROP TABLE IF EXISTS public.return_items;
DROP TABLE IF EXISTS public.refunds;
DROP TABLE IF EXISTS public.shipments;
DROP TABLE IF EXISTS public.order_item_discounts;
DROP TABLE IF EXISTS public.promotion_variants;
DROP TABLE IF EXISTS public.role_permissions;
DROP TABLE IF EXISTS public.category_attributes;
DROP TABLE IF EXISTS public.category_option_types;
DROP TABLE IF EXISTS public.variant_attribute_values;
DROP TABLE IF EXISTS public.variant_images;
DROP TABLE IF EXISTS public.audit_logs;
DROP TABLE IF EXISTS public.event_logs;
DROP TABLE IF EXISTS public.inventory_movements;
DROP TABLE IF EXISTS public.notifications;
DROP TABLE IF EXISTS public.returns;
DROP TABLE IF EXISTS public.shipping_methods;
DROP TABLE IF EXISTS public.promotions;
DROP TABLE IF EXISTS public.permissions;
DROP TABLE IF EXISTS public.role_change_logs;
DROP TABLE IF EXISTS public.attributes;

-- =========================================================
-- REVIEW ONLY
-- Current code does not use these directly, but they are part of
-- order/catalog flows or likely future features. Uncomment only if
-- you intentionally want to remove those features from the schema.
-- =========================================================

-- ALTER TABLE IF EXISTS public.products
--     DROP COLUMN IF EXISTS product_line_id;

-- DROP TABLE IF EXISTS public.product_lines;
-- DROP TABLE IF EXISTS public.order_cancellations;

-- order_status_history.note is currently written but not read.
-- Keep it unless you want to lose free-text audit notes.
-- ALTER TABLE IF EXISTS public.order_status_history
--     DROP COLUMN IF EXISTS note;