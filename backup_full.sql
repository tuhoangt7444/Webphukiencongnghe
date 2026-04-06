--
-- PostgreSQL database dump
--

\restrict BfkJAJ0Uuo7Jd9ocJ7o62sJOdtyTuAesf1Rru1SukwEr3v3vgBQzTG7SVrcubuf

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: banners; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.banners (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    image text NOT NULL,
    link text,
    "position" character varying(20) NOT NULL,
    status character varying(10) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT banners_position_check CHECK ((("position")::text = ANY ((ARRAY['home_slider'::character varying, 'category_banner'::character varying, 'promo_banner'::character varying, 'sidebar_banner'::character varying])::text[]))),
    CONSTRAINT banners_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'hidden'::character varying])::text[])))
);


ALTER TABLE public.banners OWNER TO postgres;

--
-- Name: banners_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.banners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.banners_id_seq OWNER TO postgres;

--
-- Name: banners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.banners_id_seq OWNED BY public.banners.id;


--
-- Name: brands; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.brands (
    id bigint NOT NULL,
    name text NOT NULL,
    slug text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.brands OWNER TO postgres;

--
-- Name: brands_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.brands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.brands_id_seq OWNER TO postgres;

--
-- Name: brands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.brands_id_seq OWNED BY public.brands.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name text NOT NULL,
    slug text NOT NULL,
    parent_id bigint,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    icon text,
    description text,
    status text DEFAULT 'active'::text NOT NULL
);


ALTER TABLE public.categories OWNER TO postgres;

--
-- Name: COLUMN categories.icon; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.categories.icon IS 'FontAwesome icon class, example fa-microchip';


--
-- Name: COLUMN categories.description; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.categories.description IS 'Admin description for category';


--
-- Name: COLUMN categories.status; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.categories.status IS 'active or hidden';


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO postgres;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contacts (
    id bigint NOT NULL,
    name text NOT NULL,
    email text NOT NULL,
    phone text DEFAULT ''::text NOT NULL,
    message text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    subject text DEFAULT ''::text NOT NULL,
    is_handled boolean DEFAULT false NOT NULL,
    handled_at timestamp with time zone
);


ALTER TABLE public.contacts OWNER TO postgres;

--
-- Name: contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contacts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.contacts_id_seq OWNER TO postgres;

--
-- Name: contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contacts_id_seq OWNED BY public.contacts.id;


--
-- Name: coupons; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.coupons (
    id bigint NOT NULL,
    code character varying(40) NOT NULL,
    discount_type character varying(10) NOT NULL,
    discount_value integer NOT NULL,
    min_order integer DEFAULT 0 NOT NULL,
    usage_limit integer DEFAULT 0 NOT NULL,
    used_count integer DEFAULT 0 NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    status character varying(10) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT coupons_discount_type_check CHECK (((discount_type)::text = ANY ((ARRAY['percent'::character varying, 'fixed'::character varying])::text[]))),
    CONSTRAINT coupons_discount_value_check CHECK ((discount_value > 0)),
    CONSTRAINT coupons_min_order_check CHECK ((min_order >= 0)),
    CONSTRAINT coupons_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'expired'::character varying, 'disabled'::character varying])::text[]))),
    CONSTRAINT coupons_usage_limit_check CHECK ((usage_limit >= 0)),
    CONSTRAINT coupons_used_count_check CHECK ((used_count >= 0))
);


ALTER TABLE public.coupons OWNER TO postgres;

--
-- Name: coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coupons_id_seq OWNER TO postgres;

--
-- Name: coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.coupons_id_seq OWNED BY public.coupons.id;


--
-- Name: customer_profiles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.customer_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    full_name text NOT NULL,
    phone text NOT NULL,
    address_line text NOT NULL,
    ward text NOT NULL,
    district text NOT NULL,
    city text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    full_address text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.customer_profiles OWNER TO postgres;

--
-- Name: customer_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.customer_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.customer_profiles_id_seq OWNER TO postgres;

--
-- Name: customer_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.customer_profiles_id_seq OWNED BY public.customer_profiles.id;


--
-- Name: employee_profiles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.employee_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    full_name text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.employee_profiles OWNER TO postgres;

--
-- Name: employee_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.employee_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.employee_profiles_id_seq OWNER TO postgres;

--
-- Name: employee_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.employee_profiles_id_seq OWNED BY public.employee_profiles.id;


--
-- Name: inventory_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.inventory_logs (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    type character varying(10) NOT NULL,
    note text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT inventory_logs_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT inventory_logs_type_check CHECK (((type)::text = ANY ((ARRAY['import'::character varying, 'export'::character varying])::text[])))
);


ALTER TABLE public.inventory_logs OWNER TO postgres;

--
-- Name: inventory_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.inventory_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventory_logs_id_seq OWNER TO postgres;

--
-- Name: inventory_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.inventory_logs_id_seq OWNED BY public.inventory_logs.id;


--
-- Name: newsletter_subscribers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.newsletter_subscribers (
    id bigint NOT NULL,
    email text NOT NULL,
    source_page text DEFAULT ''::text NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    subscribed_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.newsletter_subscribers OWNER TO postgres;

--
-- Name: newsletter_subscribers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.newsletter_subscribers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.newsletter_subscribers_id_seq OWNER TO postgres;

--
-- Name: newsletter_subscribers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.newsletter_subscribers_id_seq OWNED BY public.newsletter_subscribers.id;


--
-- Name: option_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.option_types (
    id bigint NOT NULL,
    code text NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.option_types OWNER TO postgres;

--
-- Name: option_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.option_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.option_types_id_seq OWNER TO postgres;

--
-- Name: option_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.option_types_id_seq OWNED BY public.option_types.id;


--
-- Name: option_values; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.option_values (
    id bigint NOT NULL,
    option_type_id bigint NOT NULL,
    value text NOT NULL
);


ALTER TABLE public.option_values OWNER TO postgres;

--
-- Name: option_values_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.option_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.option_values_id_seq OWNER TO postgres;

--
-- Name: option_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.option_values_id_seq OWNED BY public.option_values.id;


--
-- Name: order_addresses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_addresses (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    full_name text NOT NULL,
    phone text NOT NULL,
    address_line text NOT NULL,
    ward text,
    district text,
    city text NOT NULL
);


ALTER TABLE public.order_addresses OWNER TO postgres;

--
-- Name: order_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_addresses_id_seq OWNER TO postgres;

--
-- Name: order_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_addresses_id_seq OWNED BY public.order_addresses.id;


--
-- Name: order_approvals; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_approvals (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    request_type text NOT NULL,
    requested_by bigint NOT NULL,
    requested_at timestamp with time zone DEFAULT now() NOT NULL,
    decision text DEFAULT 'pending'::text NOT NULL,
    decided_by bigint,
    decided_at timestamp with time zone,
    note text,
    CONSTRAINT order_approvals_decision_check CHECK ((decision = ANY (ARRAY['pending'::text, 'approved'::text, 'rejected'::text]))),
    CONSTRAINT order_approvals_request_type_check CHECK ((request_type = ANY (ARRAY['approve_order'::text, 'cancel_order'::text])))
);


ALTER TABLE public.order_approvals OWNER TO postgres;

--
-- Name: order_approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_approvals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_approvals_id_seq OWNER TO postgres;

--
-- Name: order_approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_approvals_id_seq OWNED BY public.order_approvals.id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_items (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    variant_id bigint NOT NULL,
    product_name text NOT NULL,
    variant_name text NOT NULL,
    sku text,
    base_price integer NOT NULL,
    sale_price integer NOT NULL,
    discount_pct numeric(6,4) DEFAULT 0.0000 NOT NULL,
    unit_price integer NOT NULL,
    qty integer NOT NULL,
    line_total integer NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    product_id bigint,
    cost_price integer DEFAULT 0 NOT NULL,
    selling_price integer DEFAULT 0 NOT NULL,
    vat_percent numeric(5,2) DEFAULT 0 NOT NULL,
    import_tax_percent numeric(5,2) DEFAULT 0 NOT NULL,
    profit_percent numeric(5,2) DEFAULT 0 NOT NULL,
    profit_amount integer DEFAULT 0 NOT NULL,
    CONSTRAINT order_items_base_price_check CHECK ((base_price >= 0)),
    CONSTRAINT order_items_discount_pct_check CHECK (((discount_pct >= (0)::numeric) AND (discount_pct <= (1)::numeric))),
    CONSTRAINT order_items_line_total_check CHECK ((line_total >= 0)),
    CONSTRAINT order_items_qty_check CHECK ((qty > 0)),
    CONSTRAINT order_items_sale_price_check CHECK ((sale_price >= 0)),
    CONSTRAINT order_items_unit_price_check CHECK ((unit_price >= 0))
);


ALTER TABLE public.order_items OWNER TO postgres;

--
-- Name: COLUMN order_items.product_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.product_id IS 'ID sản phẩm tại thời điểm bán';


--
-- Name: COLUMN order_items.cost_price; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.cost_price IS 'Giá vốn snapshot tại thời điểm bán';


--
-- Name: COLUMN order_items.selling_price; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.selling_price IS 'Giá bán snapshot theo 1 đơn vị tại thời điểm bán';


--
-- Name: COLUMN order_items.vat_percent; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.vat_percent IS 'VAT snapshot tại thời điểm bán (0-100)';


--
-- Name: COLUMN order_items.import_tax_percent; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.import_tax_percent IS 'Thuế nhập snapshot tại thời điểm bán (0-100)';


--
-- Name: COLUMN order_items.profit_percent; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.profit_percent IS 'Tỷ lệ lợi nhuận snapshot tại thời điểm bán (0-100)';


--
-- Name: COLUMN order_items.profit_amount; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.order_items.profit_amount IS 'Lợi nhuận snapshot của dòng hàng = (selling_price - cost_price) * qty';


--
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_items_id_seq OWNER TO postgres;

--
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- Name: order_status_history; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_status_history (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    old_status text NOT NULL,
    new_status text NOT NULL,
    changed_by bigint,
    note text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.order_status_history OWNER TO postgres;

--
-- Name: order_status_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_status_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_status_history_id_seq OWNER TO postgres;

--
-- Name: order_status_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_status_history_id_seq OWNED BY public.order_status_history.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    status text DEFAULT 'pending_approval'::text NOT NULL,
    subtotal integer DEFAULT 0 NOT NULL,
    discount_total integer DEFAULT 0 NOT NULL,
    shipping_fee integer DEFAULT 0 NOT NULL,
    total integer DEFAULT 0 NOT NULL,
    customer_note text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT orders_discount_total_check CHECK ((discount_total >= 0)),
    CONSTRAINT orders_shipping_fee_check CHECK ((shipping_fee >= 0)),
    CONSTRAINT orders_status_check CHECK ((status = ANY (ARRAY['pending_approval'::text, 'approved'::text, 'rejected'::text, 'shipping'::text, 'done'::text, 'cancel_requested'::text, 'cancelled'::text]))),
    CONSTRAINT orders_subtotal_check CHECK ((subtotal >= 0)),
    CONSTRAINT orders_total_check CHECK ((total >= 0))
);


ALTER TABLE public.orders OWNER TO postgres;

--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orders_id_seq OWNER TO postgres;

--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: password_reset_otps; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_otps (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    email text NOT NULL,
    otp_hash text NOT NULL,
    attempts smallint DEFAULT 0 NOT NULL,
    expires_at timestamp with time zone NOT NULL,
    used_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.password_reset_otps OWNER TO postgres;

--
-- Name: password_reset_otps_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_reset_otps_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_reset_otps_id_seq OWNER TO postgres;

--
-- Name: password_reset_otps_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_reset_otps_id_seq OWNED BY public.password_reset_otps.id;


--
-- Name: payment_methods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_methods (
    id bigint NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.payment_methods OWNER TO postgres;

--
-- Name: payment_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_methods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_methods_id_seq OWNER TO postgres;

--
-- Name: payment_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_methods_id_seq OWNED BY public.payment_methods.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    method_id bigint NOT NULL,
    amount integer NOT NULL,
    status text DEFAULT 'pending'::text NOT NULL,
    paid_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT payments_amount_check CHECK ((amount >= 0)),
    CONSTRAINT payments_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'paid'::text, 'failed'::text, 'refunded'::text, 'cancelled'::text])))
);


ALTER TABLE public.payments OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payments_id_seq OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: post_related_products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.post_related_products (
    id bigint NOT NULL,
    post_id bigint NOT NULL,
    product_id bigint NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.post_related_products OWNER TO postgres;

--
-- Name: post_related_products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.post_related_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.post_related_products_id_seq OWNER TO postgres;

--
-- Name: post_related_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.post_related_products_id_seq OWNED BY public.post_related_products.id;


--
-- Name: posts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.posts (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    excerpt text,
    content text,
    cover_image text,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    published_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT posts_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying, 'hidden'::character varying])::text[])))
);


ALTER TABLE public.posts OWNER TO postgres;

--
-- Name: posts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.posts_id_seq OWNER TO postgres;

--
-- Name: posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.posts_id_seq OWNED BY public.posts.id;


--
-- Name: pricing_settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pricing_settings (
    id bigint NOT NULL,
    rent_pct numeric(6,4) DEFAULT 0.0002 NOT NULL,
    labor_pct numeric(6,4) DEFAULT 0.0004 NOT NULL,
    tax_pct numeric(6,4) DEFAULT 0.0000 NOT NULL,
    other_pct numeric(6,4) DEFAULT 0.0000 NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT pricing_settings_labor_pct_check CHECK (((labor_pct >= (0)::numeric) AND (labor_pct <= (1)::numeric))),
    CONSTRAINT pricing_settings_other_pct_check CHECK (((other_pct >= (0)::numeric) AND (other_pct <= (1)::numeric))),
    CONSTRAINT pricing_settings_rent_pct_check CHECK (((rent_pct >= (0)::numeric) AND (rent_pct <= (1)::numeric))),
    CONSTRAINT pricing_settings_tax_pct_check CHECK (((tax_pct >= (0)::numeric) AND (tax_pct <= (1)::numeric)))
);


ALTER TABLE public.pricing_settings OWNER TO postgres;

--
-- Name: pricing_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pricing_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pricing_settings_id_seq OWNER TO postgres;

--
-- Name: pricing_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pricing_settings_id_seq OWNED BY public.pricing_settings.id;


--
-- Name: product_discount_campaigns; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_discount_campaigns (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    discount_percent integer NOT NULL,
    start_at timestamp without time zone NOT NULL,
    end_at timestamp without time zone NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT product_discount_campaigns_discount_percent_check CHECK (((discount_percent >= 1) AND (discount_percent <= 90))),
    CONSTRAINT product_discount_campaigns_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'disabled'::character varying])::text[]))),
    CONSTRAINT product_discount_campaigns_time_chk CHECK ((start_at < end_at))
);


ALTER TABLE public.product_discount_campaigns OWNER TO postgres;

--
-- Name: product_discount_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_discount_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_discount_campaigns_id_seq OWNER TO postgres;

--
-- Name: product_discount_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_discount_campaigns_id_seq OWNED BY public.product_discount_campaigns.id;


--
-- Name: product_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_images (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    image_url text NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    CONSTRAINT product_images_sort_order_check CHECK ((sort_order >= 0))
);


ALTER TABLE public.product_images OWNER TO postgres;

--
-- Name: product_images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_images_id_seq OWNER TO postgres;

--
-- Name: product_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_images_id_seq OWNED BY public.product_images.id;


--
-- Name: product_variants; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_variants (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    sku text,
    combination_key text NOT NULL,
    base_price integer DEFAULT 0 NOT NULL,
    sale_price integer DEFAULT 0 NOT NULL,
    stock integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT product_variants_base_price_check CHECK ((base_price >= 0)),
    CONSTRAINT product_variants_sale_price_check CHECK ((sale_price >= 0)),
    CONSTRAINT product_variants_stock_check CHECK ((stock >= 0))
);


ALTER TABLE public.product_variants OWNER TO postgres;

--
-- Name: product_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_variants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_variants_id_seq OWNER TO postgres;

--
-- Name: product_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_variants_id_seq OWNED BY public.product_variants.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    category_id bigint,
    brand_id bigint,
    name text NOT NULL,
    slug text,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    short_description text,
    highlights text,
    technical_specs text,
    shipping_info text,
    warranty_months integer DEFAULT 0 NOT NULL,
    cost_price integer DEFAULT 0 NOT NULL,
    import_tax_percent numeric(5,2) DEFAULT 0 NOT NULL,
    vat_percent numeric(5,2) DEFAULT 0 NOT NULL,
    profit_percent numeric(5,2) DEFAULT 0 NOT NULL,
    price integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.products OWNER TO postgres;

--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO postgres;

--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: rate_limits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rate_limits (
    key text NOT NULL,
    hits integer DEFAULT 0 NOT NULL,
    window_start timestamp with time zone NOT NULL,
    blocked_until timestamp with time zone,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.rate_limits OWNER TO postgres;

--
-- Name: reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reviews (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    user_id bigint NOT NULL,
    rating integer NOT NULL,
    comment text NOT NULL,
    status character varying(10) DEFAULT 'visible'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT reviews_rating_check CHECK (((rating >= 1) AND (rating <= 5))),
    CONSTRAINT reviews_status_check CHECK (((status)::text = ANY ((ARRAY['visible'::character varying, 'hidden'::character varying, 'spam'::character varying])::text[])))
);


ALTER TABLE public.reviews OWNER TO postgres;

--
-- Name: reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reviews_id_seq OWNER TO postgres;

--
-- Name: reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reviews_id_seq OWNED BY public.reviews.id;


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_permissions (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL
);


ALTER TABLE public.role_permissions OWNER TO postgres;

--
-- Name: role_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.role_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.role_permissions_id_seq OWNER TO postgres;

--
-- Name: role_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.role_permissions_id_seq OWNED BY public.role_permissions.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: user_vouchers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_vouchers (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    voucher_id bigint NOT NULL,
    used boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.user_vouchers OWNER TO postgres;

--
-- Name: user_vouchers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_vouchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_vouchers_id_seq OWNER TO postgres;

--
-- Name: user_vouchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_vouchers_id_seq OWNED BY public.user_vouchers.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    email text NOT NULL,
    password_hash text NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    avatar_url text,
    google_avatar_url text,
    CONSTRAINT users_status_check CHECK ((status = ANY (ARRAY['active'::text, 'banned'::text])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: variant_option_values; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.variant_option_values (
    id bigint NOT NULL,
    variant_id bigint NOT NULL,
    option_value_id bigint NOT NULL
);


ALTER TABLE public.variant_option_values OWNER TO postgres;

--
-- Name: variant_option_values_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.variant_option_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.variant_option_values_id_seq OWNER TO postgres;

--
-- Name: variant_option_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.variant_option_values_id_seq OWNED BY public.variant_option_values.id;


--
-- Name: vouchers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vouchers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(40) NOT NULL,
    discount_amount integer NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    quantity integer DEFAULT 0 NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    apply_category_id bigint,
    customer_type text DEFAULT 'all'::text NOT NULL,
    CONSTRAINT vouchers_discount_amount_check CHECK ((discount_amount > 0)),
    CONSTRAINT vouchers_quantity_check CHECK ((quantity >= 0)),
    CONSTRAINT vouchers_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'disabled'::character varying, 'expired'::character varying])::text[])))
);


ALTER TABLE public.vouchers OWNER TO postgres;

--
-- Name: vouchers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vouchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.vouchers_id_seq OWNER TO postgres;

--
-- Name: vouchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vouchers_id_seq OWNED BY public.vouchers.id;


--
-- Name: banners id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.banners ALTER COLUMN id SET DEFAULT nextval('public.banners_id_seq'::regclass);


--
-- Name: brands id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands ALTER COLUMN id SET DEFAULT nextval('public.brands_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: contacts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id SET DEFAULT nextval('public.contacts_id_seq'::regclass);


--
-- Name: coupons id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons ALTER COLUMN id SET DEFAULT nextval('public.coupons_id_seq'::regclass);


--
-- Name: customer_profiles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_profiles ALTER COLUMN id SET DEFAULT nextval('public.customer_profiles_id_seq'::regclass);


--
-- Name: employee_profiles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_profiles ALTER COLUMN id SET DEFAULT nextval('public.employee_profiles_id_seq'::regclass);


--
-- Name: inventory_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory_logs ALTER COLUMN id SET DEFAULT nextval('public.inventory_logs_id_seq'::regclass);


--
-- Name: newsletter_subscribers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.newsletter_subscribers ALTER COLUMN id SET DEFAULT nextval('public.newsletter_subscribers_id_seq'::regclass);


--
-- Name: option_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_types ALTER COLUMN id SET DEFAULT nextval('public.option_types_id_seq'::regclass);


--
-- Name: option_values id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_values ALTER COLUMN id SET DEFAULT nextval('public.option_values_id_seq'::regclass);


--
-- Name: order_addresses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_addresses ALTER COLUMN id SET DEFAULT nextval('public.order_addresses_id_seq'::regclass);


--
-- Name: order_approvals id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_approvals ALTER COLUMN id SET DEFAULT nextval('public.order_approvals_id_seq'::regclass);


--
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- Name: order_status_history id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history ALTER COLUMN id SET DEFAULT nextval('public.order_status_history_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: password_reset_otps id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_otps ALTER COLUMN id SET DEFAULT nextval('public.password_reset_otps_id_seq'::regclass);


--
-- Name: payment_methods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods ALTER COLUMN id SET DEFAULT nextval('public.payment_methods_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: post_related_products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post_related_products ALTER COLUMN id SET DEFAULT nextval('public.post_related_products_id_seq'::regclass);


--
-- Name: posts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.posts ALTER COLUMN id SET DEFAULT nextval('public.posts_id_seq'::regclass);


--
-- Name: pricing_settings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pricing_settings ALTER COLUMN id SET DEFAULT nextval('public.pricing_settings_id_seq'::regclass);


--
-- Name: product_discount_campaigns id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_discount_campaigns ALTER COLUMN id SET DEFAULT nextval('public.product_discount_campaigns_id_seq'::regclass);


--
-- Name: product_images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images ALTER COLUMN id SET DEFAULT nextval('public.product_images_id_seq'::regclass);


--
-- Name: product_variants id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN id SET DEFAULT nextval('public.product_variants_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: reviews id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews ALTER COLUMN id SET DEFAULT nextval('public.reviews_id_seq'::regclass);


--
-- Name: role_permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions ALTER COLUMN id SET DEFAULT nextval('public.role_permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: user_vouchers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_vouchers ALTER COLUMN id SET DEFAULT nextval('public.user_vouchers_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: variant_option_values id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_option_values ALTER COLUMN id SET DEFAULT nextval('public.variant_option_values_id_seq'::regclass);


--
-- Name: vouchers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vouchers ALTER COLUMN id SET DEFAULT nextval('public.vouchers_id_seq'::regclass);


--
-- Data for Name: banners; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.banners (id, title, image, link, "position", status, created_at) FROM stdin;
7	Gaming gear chính hãng - giá tốt mỗi ngày	https://images.unsplash.com/photo-1593305841991-05c297ba4575?auto=format&fit=crop&w=1920&q=80	/products	home_slider	active	2026-03-14 10:15:01.02254+07
6	Build PC hiệu năng cao - ưu đãi cuối tuần	https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=1920&q=80	/products	home_slider	active	2026-03-14 10:15:01.02254+07
8	Nâng cấp SSD RAM nhanh gọn - giao hàng toàn quốc	https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1920&q=80	http://phukien.localhost/products?q=&category=phu-kien-setup&price_range=&sort=newest	home_slider	active	2026-03-14 10:15:01.02254+07
\.


--
-- Data for Name: brands; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.brands (id, name, slug, created_at) FROM stdin;
1	Logitech	logitech	2026-01-08 23:00:01.093433+07
2	BUBM	bubm	2026-03-18 18:13:50.388579+07
3	Tomtoc	tomtoc	2026-03-18 18:16:39.847568+07
4	Marshall	marshall	2026-03-18 18:26:53.402238+07
5	JBL	jbl	2026-03-18 18:29:05.286473+07
6	Apple	apple	2026-03-18 18:33:48.652835+07
7	Anker	anker	2026-03-18 18:46:02.078378+07
8	Belkin	belkin	2026-03-18 18:48:58.424056+07
9	Baseus	baseus	2026-03-18 18:55:16.362044+07
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categories (id, name, slug, parent_id, created_at, icon, description, status) FROM stdin;
26	Lưu Trữ	luu-tru	\N	2026-03-14 14:30:19.908495+07	\N	\N	active
45	Thiết bị nhà thông minh	smart-home	\N	2026-03-14 14:35:43.340645+07	fa-house-signal	\N	active
44	Túi chống sốc & Balo	tui-chong-soc-balo	\N	2026-03-14 14:35:43.340645+07	fa-bag-shopping	\N	active
43	Đồ chơi công nghệ	do-choi-cong-nghe	\N	2026-03-14 14:35:43.340645+07	fa-gamepad	\N	active
42	Đèn LED Decor	den-led-decor	39	2026-03-14 14:35:43.340645+07	fa-lightbulb	\N	active
41	Webcam & Camera	webcam-camera	39	2026-03-14 14:35:43.340645+07	fa-camera	\N	active
40	Microphone	microphone-thu-am	39	2026-03-14 14:35:43.340645+07	fa-microphone-lines	\N	active
39	Livestream & Studio	livestream-studio	\N	2026-03-14 14:35:43.340645+07	fa-video	\N	active
38	Phụ kiện bàn phím cơ	phu-kien-phim-co	35	2026-03-14 14:35:43.340645+07	fa-keyboard	\N	active
37	Tay cầm chơi game	tay-cam-choi-game	35	2026-03-14 14:35:43.340645+07	fa-gamepad	\N	active
36	Lót chuột (Mousepad)	lot-chuot-mousepad	35	2026-03-14 14:35:43.340645+07	fa-computer-mouse	\N	active
35	Thiết bị Pro Gaming	thiet-bi-pro-gaming	\N	2026-03-14 14:35:43.340645+07	fa-bolt	\N	active
34	Cường lực & Bảo vệ	cuong-luc-bao-ve	31	2026-03-14 14:35:43.340645+07	fa-shield	\N	active
33	Dây đeo Apple Watch	day-deo-apple-watch	31	2026-03-14 14:35:43.340645+07	fa-clock	\N	active
32	Ốp lưng iPhone	op-lung-iphone	31	2026-03-14 14:35:43.340645+07	fa-shield	\N	active
31	Phụ kiện Apple	phu-kien-apple	\N	2026-03-14 14:35:43.340645+07	fa-puzzle-piece	\N	active
30	Phụ Kiện Setup	phu-kien-setup	\N	2026-03-14 14:30:19.908495+07	fa-puzzle-piece	\N	active
29	Sạc & Cáp	sac-cap	\N	2026-03-14 14:30:19.908495+07	fa-plug	\N	active
28	Âm Thanh	am-thanh	\N	2026-03-14 14:30:19.908495+07	fa-volume-high	\N	active
27	Chuột Gaming	chuot-gaming	\N	2026-03-14 14:30:19.908495+07	fa-computer-mouse	\N	active
\.


--
-- Data for Name: contacts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.contacts (id, name, email, phone, message, created_at, subject, is_handled, handled_at) FROM stdin;
1	Le Van A	leva@example.com	0909123456	Can tu van phu kien may tinh	2026-03-14 11:55:50.097604+07		f	\N
2	Le Van A	leva@example.com	0909123456	Can tu van phu kien may tinh	2026-03-14 11:56:23.877645+07		f	\N
3	Le Van B	levanb@example.com	0909000111	Toi can ho tro don hang online	2026-03-14 12:00:22.429732+07	Hoi ve don hang	t	2026-03-18 13:57:24.006097+07
4	Lê Hoàng Tú	tututu7444@gmail.com	0326754284	qewewdw	2026-03-18 13:57:41.966583+07	tuhoang7444	f	\N
5	Lê Hoàng Tú	tututu7444@gmail.com	0326754284	dfđ	2026-03-28 13:12:10.36987+07	tuhoang7444	f	\N
6	<script>alert(1)</script>	tututu7444@gmail.com	0326754284	êre	2026-04-05 21:15:47.84325+07	tuhoang7444	f	\N
\.


--
-- Data for Name: coupons; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.coupons (id, code, discount_type, discount_value, min_order, usage_limit, used_count, start_date, end_date, status, created_at) FROM stdin;
2	TUHOANG7444	percent	10	20000	100	0	2026-03-14	2026-04-13	active	2026-03-14 19:17:20.274823+07
\.


--
-- Data for Name: customer_profiles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.customer_profiles (id, user_id, full_name, phone, address_line, ward, district, city, created_at, updated_at, full_address) FROM stdin;
2	1	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên	2026-03-10 10:25:49.838839+07	2026-03-14 11:32:45.663753+07	Kinh ông kiệt, Xã Phương Giao, Huyện Võ Nhai, Tỉnh Thái Nguyên
1	2	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang	2026-03-10 10:08:38.352157+07	2026-03-29 17:25:21.716721+07	Kinh ông kiệt, Xã Đông Hòa, Huyện An Minh, Tỉnh Kiên Giang
13	6	Khôi Nguyễn	0792862535	660	Xã Bình Phú	Huyện Thăng Bình	Tỉnh Quảng Nam	2026-04-03 19:26:57.989339+07	2026-04-03 19:27:59.171501+07	660, Xã Bình Phú, Huyện Thăng Bình, Tỉnh Quảng Nam
9	4	Le Hoang Tu B2306648						2026-03-29 16:27:52.965678+07	2026-04-04 14:53:28.411007+07	
12	5	Lê Hoàng Tú	0326754284					2026-04-03 18:51:56.172462+07	2026-04-04 14:55:15.995924+07	
8	3	Tú Lê Hoàng						2026-03-28 17:35:09.848+07	2026-04-04 14:56:00.941314+07	
18	7	Lê Hoàng Tú	02334343433434					2026-04-05 21:12:33.207189+07	2026-04-05 21:12:33.207189+07	
\.


--
-- Data for Name: employee_profiles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.employee_profiles (id, user_id, full_name, created_at) FROM stdin;
\.


--
-- Data for Name: inventory_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.inventory_logs (id, product_id, quantity, type, note, created_at) FROM stdin;
9	27	5	export	Xuat kho cho don #11	2026-03-14 15:04:11.579095+07
10	76	4	export	Xuat kho cho don #12	2026-03-14 15:30:18.796948+07
11	76	1	export	Xuat kho cho don #13	2026-03-14 20:11:01.037235+07
12	19	1	export	Xuat kho cho don #14	2026-03-14 20:33:12.317322+07
13	76	2	export	Xuat kho cho don #15	2026-03-14 22:44:19.545021+07
14	19	2	export	Xuat kho cho don #20	2026-03-14 22:51:00.081831+07
15	76	1	export	Xuat kho cho don #23	2026-03-14 22:58:50.082409+07
17	27	1	export	Xuat kho cho don #25	2026-03-20 18:34:33.312521+07
18	69	1	export	Xuat kho cho don #26	2026-03-24 10:14:37.63565+07
19	69	1	export	Xuat kho cho don #27	2026-03-24 10:17:26.983532+07
20	69	1	export	Xuat kho cho don #28	2026-03-27 12:25:08.381819+07
21	69	1	export	Xuat kho cho don #29	2026-03-27 12:29:48.188438+07
22	69	1	export	Xuat kho cho don #30	2026-03-28 10:55:10.612527+07
23	69	1	export	Xuat kho cho don #31	2026-03-28 11:00:15.635538+07
24	69	1	export	Xuat kho cho don #32	2026-03-28 11:05:32.472552+07
25	69	1	import	Hoan kho do thanh toan that bai don #32	2026-03-28 11:05:37.693709+07
26	27	1	export	Xuat kho cho don #34	2026-03-28 11:07:07.017456+07
27	27	1	export	Xuat kho cho don #35	2026-03-28 11:07:33.003693+07
28	27	1	export	Xuat kho cho don #36	2026-03-28 11:16:19.501062+07
29	69	1	export	Xuat kho cho don #38	2026-03-28 11:17:21.767378+07
30	27	1	export	Xuat kho cho don #39	2026-03-28 11:25:30.079666+07
31	27	1	import	Hoan kho khi admin huy don #39	2026-03-28 11:26:07.061616+07
32	69	1	export	Xuat kho cho don #40	2026-03-28 11:26:39.504336+07
33	27	1	export	Xuat kho cho don #41	2026-03-28 12:43:56.176852+07
34	27	1	export	Xuat kho cho don #42	2026-03-28 12:44:25.096926+07
35	27	1	export	Xuat kho cho don #43	2026-03-28 12:45:32.284643+07
36	27	1	import	Hoan kho khi admin huy don #43	2026-03-28 12:45:52.451185+07
37	27	1	import	Hoan kho khi admin huy don #42	2026-03-28 12:46:07.292879+07
38	68	1	export	Xuat kho cho don #44	2026-03-28 14:10:58.472041+07
39	69	1	export	Xuat kho cho don #45	2026-03-28 14:35:47.035474+07
40	69	1	export	Xuat kho cho don #46	2026-03-28 15:22:01.432134+07
41	19	1	export	Xuat kho cho don #48	2026-03-28 15:30:54.211252+07
42	69	1	export	Xuat kho cho don #49	2026-03-28 16:05:33.18249+07
43	69	1	export	Xuat kho cho don #50	2026-03-28 16:17:44.310229+07
44	69	1	export	Xuat kho cho don #51	2026-03-28 16:18:10.463439+07
45	69	2	export	Xuat kho cho don #52	2026-03-28 16:30:51.095823+07
46	69	2	import	Hoan kho khi khach huy don #52	2026-03-28 16:31:15.883994+07
47	27	1	export	Xuat kho cho don #53	2026-04-03 19:28:15.142737+07
48	70	1	export	Xuat kho cho don #54	2026-04-04 15:46:37.317256+07
49	73	1	export	Xuat kho cho don #55	2026-04-04 15:47:57.333878+07
50	70	1	export	Xuat kho cho don #56	2026-04-04 21:29:31.333651+07
51	70	1	import	Hoan kho khi khach huy don #56	2026-04-04 21:31:33.681263+07
52	69	1	export	Xuat kho cho don #58	2026-04-04 21:34:49.397979+07
53	70	1	export	Xuat kho cho don #59	2026-04-04 22:17:23.355522+07
\.


--
-- Data for Name: newsletter_subscribers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.newsletter_subscribers (id, email, source_page, status, subscribed_at, updated_at) FROM stdin;
1	tututu7444@gmail.com	/contact	active	2026-03-18 13:56:52.295978+07	2026-03-18 13:56:52.295978+07
\.


--
-- Data for Name: option_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.option_types (id, code, name) FROM stdin;
1	color	Màu
\.


--
-- Data for Name: option_values; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.option_values (id, option_type_id, value) FROM stdin;
1	1	Đen
2	1	Trắng
\.


--
-- Data for Name: order_addresses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_addresses (id, order_id, full_name, phone, address_line, ward, district, city) FROM stdin;
1	2	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
2	3	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
3	4	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
4	5	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
5	6	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
6	7	Test User	0901234567	123 Street	Ward 1	District 1	Can Tho
7	8	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
8	9	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
9	10	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
10	11	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
11	12	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
12	13	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
13	14	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
14	15	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
19	20	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
22	23	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
24	25	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
25	26	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
26	27	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
27	28	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
28	29	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
29	30	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
30	31	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
31	32	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
33	34	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
34	35	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
35	36	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
37	38	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
38	39	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
39	40	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
40	41	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
41	42	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
42	43	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
43	44	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
44	45	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
45	46	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
47	48	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
48	49	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
49	50	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
50	51	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
51	52	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
52	53	Khôi Nguyễn	0792862535	660	Xã Bình Phú	Huyện Thăng Bình	Tỉnh Quảng Nam
53	54	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
54	55	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
55	56	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
57	58	Lê Hoàng Tú	0326754283	Kinh ông kiệt	Xã Phương Giao	Huyện Võ Nhai	Tỉnh Thái Nguyên
58	59	Lê Hoàng Tú	0326754284	Kinh ông kiệt	Xã Đông Hòa	Huyện An Minh	Tỉnh Kiên Giang
\.


--
-- Data for Name: order_approvals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_approvals (id, order_id, request_type, requested_by, requested_at, decision, decided_by, decided_at, note) FROM stdin;
1	1	approve_order	1	2026-03-09 09:26:33.130776+07	approved	2	2026-03-09 09:27:21.688324+07	\N
2	2	approve_order	1	2026-03-10 10:26:01.656063+07	approved	2	2026-03-10 10:26:37.766346+07	\N
3	3	approve_order	2	2026-03-13 19:32:15.058409+07	pending	\N	\N	\N
4	4	approve_order	1	2026-03-14 11:13:40.592702+07	pending	\N	\N	\N
5	5	approve_order	1	2026-03-14 11:14:17.749017+07	pending	\N	\N	\N
6	6	approve_order	1	2026-03-14 11:33:55.481111+07	pending	\N	\N	\N
7	7	approve_order	1	2026-03-14 11:35:27.96446+07	pending	\N	\N	\N
8	8	approve_order	1	2026-03-14 11:36:18.468675+07	pending	\N	\N	\N
9	9	approve_order	1	2026-03-14 11:37:18.501105+07	pending	\N	\N	\N
10	10	approve_order	1	2026-03-14 11:37:35.655617+07	pending	\N	\N	\N
11	11	approve_order	2	2026-03-14 15:04:11.579095+07	pending	\N	\N	\N
12	12	approve_order	2	2026-03-14 15:30:18.796948+07	pending	\N	\N	\N
13	13	approve_order	2	2026-03-14 20:11:01.037235+07	pending	\N	\N	\N
14	14	approve_order	2	2026-03-14 20:33:12.317322+07	pending	\N	\N	\N
15	15	approve_order	2	2026-03-14 22:44:19.545021+07	pending	\N	\N	\N
16	20	approve_order	2	2026-03-14 22:51:00.081831+07	pending	\N	\N	\N
17	23	approve_order	2	2026-03-14 22:58:50.082409+07	pending	\N	\N	\N
18	25	approve_order	1	2026-03-20 18:34:33.312521+07	pending	\N	\N	\N
19	26	approve_order	1	2026-03-24 10:14:37.63565+07	pending	\N	\N	\N
20	27	approve_order	1	2026-03-24 10:17:26.983532+07	pending	\N	\N	\N
21	28	approve_order	1	2026-03-27 12:25:08.381819+07	pending	\N	\N	\N
22	29	approve_order	1	2026-03-27 12:29:48.188438+07	pending	\N	\N	\N
23	30	approve_order	2	2026-03-28 10:55:10.612527+07	pending	\N	\N	\N
24	31	approve_order	2	2026-03-28 11:00:15.635538+07	pending	\N	\N	\N
25	32	approve_order	2	2026-03-28 11:05:32.472552+07	pending	\N	\N	\N
26	34	approve_order	2	2026-03-28 11:07:07.017456+07	pending	\N	\N	\N
27	35	approve_order	2	2026-03-28 11:07:33.003693+07	pending	\N	\N	\N
28	36	approve_order	2	2026-03-28 11:16:19.501062+07	pending	\N	\N	\N
29	38	approve_order	2	2026-03-28 11:17:21.767378+07	pending	\N	\N	\N
30	39	approve_order	2	2026-03-28 11:25:30.079666+07	pending	\N	\N	\N
31	40	approve_order	2	2026-03-28 11:26:39.504336+07	pending	\N	\N	\N
32	41	approve_order	2	2026-03-28 12:43:56.176852+07	pending	\N	\N	\N
33	42	approve_order	2	2026-03-28 12:44:25.096926+07	pending	\N	\N	\N
34	43	approve_order	2	2026-03-28 12:45:32.284643+07	pending	\N	\N	\N
35	44	approve_order	2	2026-03-28 14:10:58.472041+07	pending	\N	\N	\N
36	45	approve_order	2	2026-03-28 14:35:47.035474+07	pending	\N	\N	\N
37	46	approve_order	2	2026-03-28 15:22:01.432134+07	pending	\N	\N	\N
38	48	approve_order	2	2026-03-28 15:30:54.211252+07	pending	\N	\N	\N
39	49	approve_order	2	2026-03-28 16:05:33.18249+07	pending	\N	\N	\N
40	50	approve_order	2	2026-03-28 16:17:44.310229+07	pending	\N	\N	\N
41	51	approve_order	2	2026-03-28 16:18:10.463439+07	pending	\N	\N	\N
42	52	approve_order	2	2026-03-28 16:30:51.095823+07	pending	\N	\N	\N
43	53	approve_order	6	2026-04-03 19:28:15.142737+07	pending	\N	\N	\N
44	54	approve_order	2	2026-04-04 15:46:37.317256+07	pending	\N	\N	\N
45	55	approve_order	2	2026-04-04 15:47:57.333878+07	pending	\N	\N	\N
46	56	approve_order	1	2026-04-04 21:29:31.333651+07	pending	\N	\N	\N
47	58	approve_order	1	2026-04-04 21:34:49.397979+07	pending	\N	\N	\N
48	59	approve_order	2	2026-04-04 22:17:23.355522+07	pending	\N	\N	\N
\.


--
-- Data for Name: order_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_items (id, order_id, variant_id, product_name, variant_name, sku, base_price, sale_price, discount_pct, unit_price, qty, line_total, created_at, product_id, cost_price, selling_price, vat_percent, import_tax_percent, profit_percent, profit_amount) FROM stdin;
12	11	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1450000	5	7250000	2026-03-14 15:04:11.579095+07	27	800000	1450000	10.00	10.00	50.00	3250000
13	12	18	Thẻ định vị Apple AirTag (1 Pack)	default	1123	650000	1007500	0.0000	1007500	4	4030000	2026-03-14 15:30:18.796948+07	76	650000	1007500	10.00	10.00	35.00	1430000
14	13	18	Thẻ định vị Apple AirTag (1 Pack)	default	1123	650000	1007500	0.0000	1007500	1	1007500	2026-03-14 20:11:01.037235+07	76	650000	1007500	10.00	10.00	35.00	357500
15	14	12	Tai nghe Sony WH-1000XM5	default	SONY-XM5-BLK	8490000	7990000	0.0000	8490000	1	8490000	2026-03-14 20:33:12.317322+07	19	6500000	8490000	10.00	10.00	20.00	1990000
16	15	18	Thẻ định vị Apple AirTag (1 Pack)	default	1123	650000	1007500	0.0000	1007500	2	2015000	2026-03-14 22:44:19.545021+07	76	650000	1007500	10.00	10.00	35.00	715000
21	20	12	Tai nghe Sony WH-1000XM5	default	SONY-XM5-BLK	6500000	7039500	0.0000	7800000	2	15600000	2026-03-14 22:51:00.081831+07	19	6500000	7800000	0.00	0.00	20.00	2600000
24	23	18	Thẻ định vị Apple AirTag (1 Pack)	default	1123	650000	1007500	0.3000	705250	1	705250	2026-03-14 22:58:50.082409+07	76	650000	1007500	10.00	10.00	35.00	55250
27	25	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-20 18:34:33.312521+07	27	800000	1350000	10.00	10.00	50.00	550000
28	26	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-24 10:14:37.63565+07	69	2500000	4000000	10.00	10.00	40.00	700000
29	27	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-24 10:17:26.983532+07	69	2500000	4000000	10.00	10.00	40.00	700000
30	28	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-27 12:25:08.381819+07	69	2500000	4000000	10.00	10.00	40.00	700000
31	29	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-27 12:29:48.188438+07	69	2500000	4000000	10.00	10.00	40.00	700000
32	30	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 10:55:10.612527+07	69	2500000	4000000	10.00	10.00	40.00	700000
33	31	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 11:00:15.635538+07	69	2500000	4000000	10.00	10.00	40.00	700000
34	32	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 11:05:32.472552+07	69	2500000	4000000	10.00	10.00	40.00	700000
36	34	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 11:07:07.017456+07	27	800000	1350000	10.00	10.00	50.00	550000
37	35	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 11:07:33.003693+07	27	800000	1350000	10.00	10.00	50.00	550000
38	36	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 11:16:19.501062+07	27	800000	1350000	10.00	10.00	50.00	550000
40	38	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 11:17:21.767378+07	69	2500000	4000000	10.00	10.00	40.00	700000
41	39	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 11:25:30.079666+07	27	800000	1350000	10.00	10.00	50.00	550000
42	40	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 11:26:39.504336+07	69	2500000	4000000	10.00	10.00	40.00	700000
43	41	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 12:43:56.176852+07	27	800000	1350000	10.00	10.00	50.00	550000
44	42	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 12:44:25.096926+07	27	800000	1350000	10.00	10.00	50.00	550000
45	43	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-03-28 12:45:32.284643+07	27	800000	1350000	10.00	10.00	50.00	550000
46	44	26	Sạc MagSafe không dây Apple	default	MHXH3VN/A	850000	1275000	0.2000	1020000	1	1020000	2026-03-28 14:10:58.472041+07	68	850000	1275000	10.00	10.00	30.00	170000
47	45	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 14:35:47.035474+07	69	2500000	4000000	10.00	10.00	40.00	700000
48	46	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 15:22:01.432134+07	69	2500000	4000000	10.00	10.00	40.00	700000
50	48	12	Tai nghe Sony WH-1000XM5	default	SONY-XM5-BLK	6500000	7039500	0.0000	7039500	1	7039500	2026-03-28 15:30:54.211252+07	19	6500000	7039500	0.00	0.00	20.00	539500
51	49	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 16:05:33.18249+07	69	2500000	4000000	10.00	10.00	40.00	700000
52	50	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 16:17:44.310229+07	69	2500000	4000000	10.00	10.00	40.00	700000
53	51	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	1	3200000	2026-03-28 16:18:10.463439+07	69	2500000	4000000	10.00	10.00	40.00	700000
54	52	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.2000	3200000	2	6400000	2026-03-28 16:30:51.095823+07	69	2500000	4000000	10.00	10.00	40.00	1400000
55	53	14	Ốp lưng MagSafe iPhone 15 Pro Max	Mau: Blue	IP15PM-SIL-BLU	1450000	1350000	0.0000	1350000	1	1350000	2026-04-03 19:28:15.142737+07	27	800000	1350000	10.00	10.00	50.00	550000
56	54	24	Pin dự phòng MagSafe Anker 622 MagGo - 5000mAh	default	ANKER-622-BLK	800000	1120000	0.2000	896000	1	896000	2026-04-04 15:46:37.317256+07	70	800000	1120000	10.00	5.00	25.00	96000
57	55	21	Tai nghe không dây Marshall Major IV Bluetooth	default	MRS-MAJOR4-BLK	2500000	3625000	0.0000	3625000	1	3625000	2026-04-04 15:47:57.333878+07	73	2500000	3625000	10.00	10.00	25.00	1125000
58	56	24	Pin dự phòng MagSafe Anker 622 MagGo - 5000mAh	default	ANKER-622-BLK	800000	1120000	0.2000	896000	1	896000	2026-04-04 21:29:31.333651+07	70	800000	1120000	10.00	5.00	25.00	96000
60	58	25	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	default	BELKIN-WIZ017-WHT	2500000	4000000	0.0000	4000000	1	4000000	2026-04-04 21:34:49.397979+07	69	2500000	4000000	10.00	10.00	40.00	1500000
61	59	24	Pin dự phòng MagSafe Anker 622 MagGo - 5000mAh	default	ANKER-622-BLK	800000	1120000	0.2000	896000	1	896000	2026-04-04 22:17:23.355522+07	70	800000	1120000	10.00	5.00	25.00	96000
\.


--
-- Data for Name: order_status_history; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_status_history (id, order_id, old_status, new_status, changed_by, note, created_at) FROM stdin;
1	2	shipping	done	2	Updated by admin dashboard	2026-03-11 23:18:37.394288+07
2	3	pending_approval	done	2	Updated by admin dashboard	2026-03-13 19:32:40.035772+07
3	5	pending_approval	shipping	2	Updated by admin dashboard	2026-03-14 12:26:19.557533+07
4	5	shipping	cancelled	2	Updated by admin dashboard	2026-03-14 12:26:29.658111+07
5	5	cancelled	cancelled	2	Updated by admin dashboard	2026-03-14 12:26:55.526601+07
6	10	pending_approval	approved	2	Updated by admin dashboard	2026-03-14 12:27:12.16991+07
7	4	pending_approval	done	2	Updated by admin dashboard	2026-03-14 12:27:23.169989+07
8	11	pending_approval	shipping	2	Updated by admin dashboard	2026-03-14 15:04:42.173174+07
9	12	pending_approval	done	2	Updated by admin dashboard	2026-03-14 15:30:37.281933+07
10	13	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-14 20:24:14.35743+07
11	14	pending_approval	done	2	Updated by admin dashboard	2026-03-14 20:34:37.007098+07
12	14	done	pending_approval	2	Updated by admin dashboard	2026-03-14 20:34:52.874631+07
13	14	pending_approval	done	2	Updated by admin dashboard	2026-03-14 20:36:12.816576+07
14	25	pending_approval	approved	2	Updated by admin dashboard	2026-03-21 17:27:52.347836+07
15	29	pending_approval	approved	\N	Updated by admin dashboard	2026-03-27 12:30:34.456292+07
16	30	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 10:59:25.049962+07
17	32	pending_approval	cancelled	\N	Auto cancel do thanh toan VNPAY that bai	2026-03-28 11:05:37.693709+07
18	34	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 11:07:17.31588+07
19	35	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 11:07:53.730531+07
20	36	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 11:16:28.207801+07
21	38	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 11:17:32.887215+07
22	38	cancelled	cancelled	2	Updated by admin dashboard	2026-03-28 11:24:50.630121+07
23	39	pending_approval	cancelled	2	Admin huy don va hoan kho	2026-03-28 11:26:07.061616+07
24	40	pending_approval	cancelled	2	Updated by admin dashboard	2026-03-28 11:39:54.189751+07
25	39	cancelled	cancelled	2	Updated by admin dashboard	2026-03-28 12:40:08.988368+07
26	41	pending_approval	approved	2	Updated by admin dashboard	2026-03-28 12:44:12.187861+07
27	43	pending_approval	cancelled	2	Admin huy don va hoan kho	2026-03-28 12:45:52.451185+07
28	42	pending_approval	cancelled	2	Admin huy don va hoan kho	2026-03-28 12:46:07.292879+07
29	43	cancelled	pending_approval	2	Updated by admin dashboard	2026-03-28 13:01:01.276466+07
30	44	pending_approval	done	2	Updated by admin dashboard	2026-03-28 14:11:29.883606+07
31	45	pending_approval	approved	2	Updated by admin dashboard	2026-03-28 14:36:01.273221+07
32	46	pending_approval	approved	\N	Updated by admin dashboard	2026-03-28 15:23:23.554451+07
41	50	pending_approval	cancelled	2	Khách hàng hủy trong 10 phút đầu	2026-03-28 16:17:48.431299+07
42	51	pending_approval	cancelled	2	Khách hàng hủy trong 10 phút đầu	2026-03-28 16:18:14.725396+07
43	52	pending_approval	cancelled	2	Khách hàng hủy trong 10 phút đầu	2026-03-28 16:31:15.883994+07
44	56	pending_approval	cancelled	1	Khách hàng hủy trong 10 phút đầu	2026-04-04 21:31:33.681263+07
\.


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.orders (id, user_id, status, subtotal, discount_total, shipping_fee, total, customer_note, created_at, updated_at) FROM stdin;
1	1	shipping	3380000	0	0	3380000	\N	2026-03-09 09:26:33.130776+07	2026-03-09 09:26:33.130776+07
2	1	done	1690000	0	0	1690000	Phuong thuc thanh toan: Chuyen khoan ngan hang	2026-03-10 10:26:01.656063+07	2026-03-11 23:18:37.394288+07
3	2	done	34790000	0	0	34790000	Phuong thuc thanh toan: Thanh toan khi nhan hang (COD)	2026-03-13 19:32:15.058409+07	2026-03-13 19:32:40.035772+07
6	1	pending_approval	5180000	0	0	5180000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 11:33:55.481111+07	2026-03-14 11:33:55.481111+07
7	1	pending_approval	2590000	0	0	2590000	test stock reduce	2026-03-14 11:35:27.96446+07	2026-03-14 11:35:27.96446+07
8	1	pending_approval	2590000	0	0	2590000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 11:36:18.468675+07	2026-03-14 11:36:18.468675+07
9	1	pending_approval	2590000	0	0	2590000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-14 11:37:18.501105+07	2026-03-14 11:37:18.501105+07
5	1	cancelled	10360000	0	0	10360000	Phuong thuc thanh toan: Thanh toan khi nhan hang (COD)	2026-03-14 11:14:17.749017+07	2026-03-14 12:26:55.526601+07
10	1	approved	20550000	0	0	20550000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-14 11:37:35.655617+07	2026-03-14 12:27:12.16991+07
4	1	done	2590000	0	0	2590000	Phuong thuc thanh toan: Thanh toan khi nhan hang (COD)	2026-03-14 11:13:40.592702+07	2026-03-14 12:27:23.169989+07
11	2	shipping	7250000	0	0	7250000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-14 15:04:11.579095+07	2026-03-14 15:04:42.173174+07
12	2	done	4030000	0	0	4030000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 15:30:18.796948+07	2026-03-14 15:30:37.281933+07
13	2	cancelled	1007500	50000	0	957500	[Phiếu MB123 - giảm 50,000đ] Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 20:11:01.037235+07	2026-03-14 20:24:14.35743+07
14	2	done	8490000	50000	0	8440000	[Phiếu TUHOANG7444 - giảm 50,000đ] Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 20:33:12.317322+07	2026-03-14 20:36:12.816576+07
15	2	pending_approval	2015000	0	0	2015000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 22:44:19.545021+07	2026-03-14 22:44:19.545021+07
20	2	pending_approval	15600000	0	0	15600000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 22:51:00.081831+07	2026-03-14 22:51:00.081831+07
23	2	pending_approval	705250	0	0	705250	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-14 22:58:50.082409+07	2026-03-14 22:58:50.082409+07
25	1	approved	1350000	50000	0	1300000	[Phiếu MB123 - giảm 50,000đ] Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-20 18:34:33.312521+07	2026-03-21 17:27:52.347836+07
26	1	pending_approval	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-24 10:14:37.63565+07	2026-03-24 10:14:37.63565+07
27	1	pending_approval	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-24 10:17:26.983532+07	2026-03-24 10:17:26.983532+07
28	1	pending_approval	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-27 12:25:08.381819+07	2026-03-27 12:25:08.381819+07
29	1	approved	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-27 12:29:48.188438+07	2026-03-27 12:30:34.456292+07
30	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-28 10:55:10.612527+07	2026-03-28 10:59:25.049962+07
31	2	pending_approval	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-28 11:00:15.635538+07	2026-03-28 11:00:15.635538+07
32	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-28 11:05:32.472552+07	2026-03-28 11:05:37.693709+07
34	2	cancelled	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:07:07.017456+07	2026-03-28 11:07:17.31588+07
35	2	cancelled	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:07:33.003693+07	2026-03-28 11:07:53.730531+07
36	2	cancelled	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:16:19.501062+07	2026-03-28 11:16:28.207801+07
38	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:17:21.767378+07	2026-03-28 11:24:50.630121+07
40	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:26:39.504336+07	2026-03-28 11:39:54.189751+07
39	2	cancelled	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 11:25:30.079666+07	2026-03-28 12:40:08.988368+07
41	2	approved	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 12:43:56.176852+07	2026-03-28 12:44:12.187861+07
42	2	cancelled	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 12:44:25.096926+07	2026-03-28 12:46:07.292879+07
43	2	pending_approval	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 12:45:32.284643+07	2026-03-28 13:01:01.276466+07
44	2	done	1020000	0	0	1020000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 14:10:58.472041+07	2026-03-28 14:11:29.883606+07
45	2	approved	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 14:35:47.035474+07	2026-03-28 14:36:01.273221+07
46	2	approved	3200000	0	0	3200000	Phương thức thanh toán: Chuyển khoản ngân hàng	2026-03-28 15:22:01.432134+07	2026-03-28 15:23:23.554451+07
48	2	pending_approval	7039500	0	0	7039500	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 15:30:54.211252+07	2026-03-28 15:30:54.211252+07
49	2	pending_approval	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 16:05:33.18249+07	2026-03-28 16:05:33.18249+07
50	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 16:17:44.310229+07	2026-03-28 16:17:48.431299+07
51	2	cancelled	3200000	0	0	3200000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 16:18:10.463439+07	2026-03-28 16:18:14.725396+07
52	2	cancelled	6400000	0	0	6400000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-03-28 16:30:51.095823+07	2026-03-28 16:31:15.883994+07
53	6	pending_approval	1350000	0	0	1350000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-03 19:28:15.142737+07	2026-04-03 19:28:15.142737+07
54	2	pending_approval	896000	0	0	896000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-04 15:46:37.317256+07	2026-04-04 15:46:37.317256+07
55	2	pending_approval	3625000	50000	0	3575000	[Phiếu VB67 - giảm 50,000đ] Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-04 15:47:57.333878+07	2026-04-04 15:47:57.333878+07
56	1	cancelled	896000	50000	0	846000	[Phiếu TUHOANG7444 - giảm 50,000đ] Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-04 21:29:31.333651+07	2026-04-04 21:31:33.681263+07
58	1	pending_approval	4000000	0	0	4000000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-04 21:34:49.397979+07	2026-04-04 21:34:49.397979+07
59	2	pending_approval	896000	0	0	896000	Phương thức thanh toán: Thanh toán khi nhận hàng (COD)	2026-04-04 22:17:23.355522+07	2026-04-04 22:17:23.355522+07
\.


--
-- Data for Name: password_reset_otps; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_otps (id, user_id, email, otp_hash, attempts, expires_at, used_at, created_at) FROM stdin;
1	1	tututu7444@gmail.com	$2y$12$DKTwWpAQyLcTVYBU/kbbR.uJl6yX3otMdWnFIN.7FlGUM6H/amOlW	0	2026-04-03 18:52:10.443029+07	2026-04-03 18:42:16.356371+07	2026-04-03 18:42:10.443029+07
2	1	tututu7444@gmail.com	$2y$12$tpQ8i89igbXfxuApW.JR2ODVrHtmYI2SRCwfJTy9OuWy2ppKCFHJK	0	2026-04-03 18:52:16.360976+07	2026-04-03 18:42:29.961586+07	2026-04-03 18:42:16.360976+07
3	1	tututu7444@gmail.com	$2y$12$jUWXBIBGlNSE6RS4ceWM..66eAmeWcNudQQ8pQgvEdsPY3VZB6ITa	0	2026-04-03 18:52:29.973235+07	2026-04-03 18:49:52.117712+07	2026-04-03 18:42:29.973235+07
4	1	tututu7444@gmail.com	$2y$12$KtCwv6f55WIaX0PTSbePleHKLGATHG6.QAvXoMG/dIFy9C1IOTCtS	0	2026-04-03 18:59:52.132553+07	2026-04-03 18:50:01.491907+07	2026-04-03 18:49:52.132553+07
5	1	tututu7444@gmail.com	$2y$12$GTOUgTQVr3cF0f9ROEN/3.zyWhIsjy67o.eGsB9Fxog/dxzbsT96C	0	2026-04-03 19:00:01.504449+07	2026-04-03 19:01:38.218127+07	2026-04-03 18:50:01.504449+07
7	1	tututu7444@gmail.com	$2y$12$y5YgnrnwQPHVI9Dyvvuby.YW4bXOTc3sjpkBWHIxvtB/RORg3GvVS	0	2026-04-03 19:11:38.221167+07	2026-04-03 19:02:18.287522+07	2026-04-03 19:01:38.221167+07
8	1	tututu7444@gmail.com	$2y$12$qprZmQ8W0GIFFwt/RAICZOLxjDxvdpY5JFSF3z72b25QhTLSTa2oC	0	2026-04-03 19:12:18.290063+07	2026-04-03 19:13:41.550664+07	2026-04-03 19:02:18.290063+07
9	1	tututu7444@gmail.com	$2y$12$3Uz3elr7iJDibcl6lopx5eGtMF4POWjY6Nx4VmPCqItwL4gn/y85e	0	2026-04-03 19:23:41.557239+07	2026-04-03 19:13:53.137467+07	2026-04-03 19:13:41.557239+07
10	1	tututu7444@gmail.com	$2y$12$tM5oTKSbXyc6adZYfdMijO.WbXWPIKtRs32GDnVQOCYjnooxmutfS	0	2026-04-03 19:23:53.149513+07	2026-04-03 19:15:02.606974+07	2026-04-03 19:13:53.149513+07
11	1	tututu7444@gmail.com	$2y$12$yNPA4j355nubWzxiK13fX.6JaWRS/JExy2PTJpC/Y8Vxbt16zqfP.	0	2026-04-03 19:26:13.084139+07	2026-04-03 19:16:20.666392+07	2026-04-03 19:16:13.084139+07
6	5	tututu@gmail.com	$2y$12$KAPn9DpQuYJFJWiDAQ78z.kLTO.cD5/HOYaeLN0DIGDr6P.SwAvT2	0	2026-04-03 19:02:04.134072+07	2026-04-03 19:16:31.82005+07	2026-04-03 18:52:04.134072+07
13	5	tututu@gmail.com	$2y$12$PQIkG8q.6LGUajwMh6fsSuObvRtFb9ttzaNuc0mHzvsZSMwt0PdTK	0	2026-04-03 19:26:31.832512+07	\N	2026-04-03 19:16:31.832512+07
12	1	tututu7444@gmail.com	$2y$12$SYPDJ6EGkObs4BrCbk81K.jBrfz3hP17j9zsgfzXvMNflU4QelXIe	0	2026-04-03 19:26:20.678745+07	2026-04-03 19:16:46.269237+07	2026-04-03 19:16:20.678745+07
14	1	tututu7444@gmail.com	$2y$12$gNlouAlzt7etkSV3jQj05.qMYDrlqBu.aTuPbeLs5kWs4iP.OVEEG	0	2026-04-03 19:26:46.272236+07	2026-04-03 19:19:21.510207+07	2026-04-03 19:16:46.272236+07
15	1	tututu7444@gmail.com	$2y$12$mBcwd/yJpkYrEGZR2p4zSeDLzkicZ4uqbWeJMOWrhlQdbmKulIuti	0	2026-04-03 19:29:21.511365+07	2026-04-03 19:20:31.66238+07	2026-04-03 19:19:21.511365+07
16	1	tututu7444@gmail.com	$2y$12$7tLVADOqpn3ajhhM/3Fe5e.mWbTBmm3twJJCTzf367NUAXbdFnZwG	0	2026-04-03 19:30:31.663758+07	2026-04-03 19:24:28.003548+07	2026-04-03 19:20:31.663758+07
18	3	tub2306648@gmail.com	$2y$12$XzbsWKcGQ14sCHNgw3Q2v.0ez.x.rtLJ9z1nDn9qUKOTZYqVWrLGe	0	2026-04-03 19:40:53.599981+07	2026-04-03 19:43:31.229998+07	2026-04-03 19:30:53.599981+07
17	1	tututu7444@gmail.com	$2y$12$I3TchCXMlRHQIACLhnMb8.Cl2D1rzAH2Jb3ZyUqndJYzm3ugNe5MK	0	2026-04-03 19:34:28.004737+07	2026-04-04 13:41:09.727906+07	2026-04-03 19:24:28.004737+07
20	1	tututu7444@gmail.com	$2y$12$Zuct4zKhbIts6Vr8oyjyVe6X5EweA7FmuE2crwC25PvFiIAmoFbP6	0	2026-04-04 13:51:09.743917+07	2026-04-04 13:43:14.526105+07	2026-04-04 13:41:09.743917+07
21	1	tututu7444@gmail.com	$2y$12$MmUkfEJ1BagL7gumR0.tOuI7/lZxVtK3OoDQ0IwoPVbY4QO/eHGJW	0	2026-04-04 13:53:53.539358+07	2026-04-04 13:49:05.716472+07	2026-04-04 13:43:53.539358+07
22	1	tututu7444@gmail.com	$2y$12$yZkBwiwNs0L.S0auOCr./.D55SupqMFWzIOzns4oj7dp9k6rBOttu	0	2026-04-04 13:59:05.727879+07	2026-04-04 13:55:55.318216+07	2026-04-04 13:49:05.727879+07
23	1	tututu7444@gmail.com	$2y$12$5e5qYi2bLoMObecO02NESu1KfnsCMcki6L12KPPY76JNhHeh9PHx2	0	2026-04-04 14:05:55.319383+07	2026-04-04 13:57:37.050162+07	2026-04-04 13:55:55.319383+07
24	1	tututu7444@gmail.com	$2y$12$Hp18Egnl7h3EaUNMleAnd.TU6UhKecdgqx/yzVw7DQYV91ewooXsa	0	2026-04-04 14:07:37.060924+07	2026-04-04 14:03:09.594282+07	2026-04-04 13:57:37.060924+07
19	3	tub2306648@gmail.com	$2y$12$TwwxVKqtc62ynwh94ZMo7eMlaj7YcL4NRXXz/kDyNHE/a7.OIO07i	0	2026-04-03 19:53:31.231029+07	2026-04-04 14:05:04.034071+07	2026-04-03 19:43:31.231029+07
26	3	tub2306648@gmail.com	$2y$12$3FeVtwc3fdzymZFyZYHwMeoi2CdidLfhePtg0UIXj4QMbXfCXinyi	0	2026-04-04 14:15:04.045115+07	2026-04-04 14:07:55.000345+07	2026-04-04 14:05:04.045115+07
27	3	tub2306648@gmail.com	$2y$12$8thlUSYzIgxlK0sw8dMYFOjitbGiGvPPxkVsRIkNN0dcn.4FFj0gO	0	2026-04-04 14:17:55.012354+07	2026-04-04 14:09:00.822471+07	2026-04-04 14:07:55.012354+07
28	3	tub2306648@gmail.com	$2y$12$4UpHajd.XJddy5v4cOWedOYHHKZgIzCLfo/keYbuYg66KV0Bx563K	0	2026-04-04 14:19:00.83478+07	\N	2026-04-04 14:09:00.83478+07
25	1	tututu7444@gmail.com	$2y$12$kPreOUZe8WpRkhw7igUU/u7M97gjux9ZLD5HsVrcymTOnC9HfnF0q	0	2026-04-04 14:13:09.605293+07	2026-04-04 14:09:25.793641+07	2026-04-04 14:03:09.605293+07
29	1	tututu7444@gmail.com	$2y$12$hYvPBV3gthOVNEhcduLZD.wNkffFG9A.XIpCCUu4v.DCoNESUXzH.	0	2026-04-04 14:19:25.804153+07	2026-04-04 14:18:40.073493+07	2026-04-04 14:09:25.804153+07
30	1	tututu7444@gmail.com	$2y$12$k6FFQP8gra6jRnS/sZmWueKigwbSSBoNmNLlV1/Qu.EfuIdGonGm2	0	2026-04-04 14:28:40.075769+07	2026-04-04 14:22:59.533551+07	2026-04-04 14:18:40.075769+07
31	1	tututu7444@gmail.com	$2y$12$GY4S2ORO005HfOFc7jbPNeSxRTAvtlOmi96S/lYSMAmKJjY9ZpsuG	3	2026-04-04 14:32:59.544705+07	2026-04-04 14:26:18.830869+07	2026-04-04 14:22:59.544705+07
\.


--
-- Data for Name: payment_methods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payment_methods (id, code, name, is_active) FROM stdin;
1	cod	Thanh toán khi nhận hàng (COD)	t
2	bank	Chuyển khoản ngân hàng	t
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payments (id, order_id, method_id, amount, status, paid_at, created_at) FROM stdin;
1	26	2	3200000	pending	\N	2026-03-24 10:14:37.717384+07
2	27	2	3200000	failed	\N	2026-03-24 10:17:27.016858+07
3	28	2	3200000	failed	\N	2026-03-27 12:25:08.436465+07
4	29	2	3200000	paid	2026-03-27 12:30:34.453533+07	2026-03-27 12:29:48.218418+07
5	30	2	3200000	failed	\N	2026-03-28 10:55:10.661641+07
6	31	2	3200000	failed	\N	2026-03-28 11:00:15.661004+07
7	32	2	3200000	failed	\N	2026-03-28 11:05:32.498722+07
8	34	1	1350000	pending	\N	2026-03-28 11:07:07.036721+07
9	35	1	1350000	pending	\N	2026-03-28 11:07:33.025651+07
10	36	1	1350000	pending	\N	2026-03-28 11:16:19.523137+07
11	38	1	3200000	pending	\N	2026-03-28 11:17:21.787069+07
12	39	1	1350000	pending	\N	2026-03-28 11:25:30.109623+07
13	40	1	3200000	pending	\N	2026-03-28 11:26:39.530609+07
14	41	1	1350000	pending	\N	2026-03-28 12:43:56.202742+07
15	42	1	1350000	pending	\N	2026-03-28 12:44:25.122985+07
16	43	1	1350000	pending	\N	2026-03-28 12:45:32.309021+07
17	44	1	1020000	pending	\N	2026-03-28 14:10:58.49921+07
18	45	1	3200000	pending	\N	2026-03-28 14:35:47.061705+07
19	46	2	3200000	paid	2026-03-28 15:23:23.551851+07	2026-03-28 15:22:01.474129+07
20	48	1	7039500	pending	\N	2026-03-28 15:30:54.238067+07
21	49	1	3200000	pending	\N	2026-03-28 16:05:33.242927+07
22	50	1	3200000	pending	\N	2026-03-28 16:17:44.351379+07
23	51	1	3200000	pending	\N	2026-03-28 16:18:10.489156+07
24	52	1	6400000	pending	\N	2026-03-28 16:30:51.121217+07
25	53	1	1350000	pending	\N	2026-04-03 19:28:15.200875+07
26	54	1	896000	pending	\N	2026-04-04 15:46:37.370707+07
27	55	1	3625000	pending	\N	2026-04-04 15:47:57.384051+07
28	56	1	896000	pending	\N	2026-04-04 21:29:31.375748+07
29	58	1	4000000	pending	\N	2026-04-04 21:34:49.424809+07
30	59	1	896000	pending	\N	2026-04-04 22:17:23.380867+07
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permissions (id, code, name, created_at) FROM stdin;
1	admin.dashboard	Dashboard	2026-04-04 14:50:59.310111+07
2	admin.products	Quản lý sản phẩm	2026-04-04 14:50:59.314554+07
3	admin.orders	Quản lý đơn hàng	2026-04-04 14:50:59.315468+07
4	admin.users	Quản lý người dùng	2026-04-04 14:50:59.316025+07
5	admin.product_discounts	Giảm giá sản phẩm	2026-04-04 14:50:59.31644+07
6	admin.categories	Quản lý danh mục	2026-04-04 14:50:59.317053+07
7	admin.vouchers	Quản lý phiếu giảm giá	2026-04-04 14:50:59.317595+07
8	admin.inventory	Quản lý tồn kho	2026-04-04 14:50:59.318064+07
9	admin.reviews	Quản lý đánh giá	2026-04-04 14:50:59.318499+07
10	admin.banners	Quản lý banner	2026-04-04 14:50:59.318904+07
11	admin.posts	Quản lý bài viết	2026-04-04 14:50:59.319281+07
12	admin.contacts	Quản lý liên hệ	2026-04-04 14:50:59.319667+07
13	admin.newsletters	Nhận ưu đãi	2026-04-04 14:50:59.320025+07
14	admin.reports	Xuất báo cáo	2026-04-04 14:50:59.320387+07
15	admin.roles	Phân quyền quản trị	2026-04-04 14:50:59.320793+07
\.


--
-- Data for Name: post_related_products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.post_related_products (id, post_id, product_id, sort_order, created_at) FROM stdin;
14	2	75	1	2026-03-28 13:22:12.608368+07
15	2	32	2	2026-03-28 13:22:12.608368+07
16	2	31	3	2026-03-28 13:22:12.608368+07
17	2	27	4	2026-03-28 13:22:12.608368+07
18	2	23	5	2026-03-28 13:22:12.608368+07
19	1	74	1	2026-03-28 13:22:38.862981+07
20	1	56	2	2026-03-28 13:22:38.862981+07
21	1	45	3	2026-03-28 13:22:38.862981+07
\.


--
-- Data for Name: posts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.posts (id, title, slug, excerpt, content, cover_image, status, published_at, created_at, updated_at) FROM stdin;
3	Tối ưu nhiệt độ case để bộ máy hoạt động bền bỉ	toi-uu-nhiet-do-case-de-bo-may-ben-bi	Hướng dẫn chi tiết cách tối ưu luồng gió, sắp xếp quạt và quản lý dây để giảm nhiệt độ CPU, GPU, giúp máy tính hoạt động ổn định và bền bỉ hơn.	📌 Tại sao cần tối ưu nhiệt độ case?\r\n\r\nNếu bạn từng thấy máy tính bị nóng lên khi chơi game hoặc làm việc nặng, đó là điều bình thường. Nhưng nếu nhiệt độ quá cao trong thời gian dài, máy sẽ tự giảm hiệu năng để bảo vệ linh kiện. Điều này khiến máy chạy chậm hơn và có thể làm giảm tuổi thọ phần cứng.\r\n\r\nNói đơn giản: máy càng mát → chạy càng ổn định → dùng càng lâu.\r\n\r\n🌬️ Hiểu đơn giản về luồng gió trong case\r\n\r\nHãy tưởng tượng bên trong case giống như một căn phòng kín. Nếu không có không khí lưu thông, nhiệt sẽ bị giữ lại và ngày càng nóng lên.\r\n\r\nNguyên tắc cơ bản:\r\n\r\nHút không khí mát từ bên ngoài vào\r\n\r\nĐẩy khí nóng ra ngoài\r\n\r\nLuồng gió lý tưởng thường đi theo hướng:\r\n➡️ Trước → Sau\r\n⬆️ Dưới → Trên\r\n\r\n🌀 Bố trí quạt sao cho hợp lý\r\n\r\nBạn không cần quá nhiều quạt, chỉ cần đặt đúng vị trí là đã rất hiệu quả.\r\n\r\nGợi ý phổ biến:\r\n\r\n🔹 2–3 quạt phía trước → hút gió vào\r\n\r\n🔹 1 quạt phía sau → đẩy khí nóng ra\r\n\r\n🔹 1–2 quạt phía trên → thoát nhiệt\r\n\r\n👉 Đây là setup “chuẩn quốc dân”, phù hợp với hầu hết các bộ máy.\r\n\r\n🔧 Dọn gọn dây cáp – nhỏ nhưng rất quan trọng\r\n\r\nDây cáp lộn xộn sẽ cản luồng gió và làm máy nóng hơn.\r\n\r\nBạn nên:\r\n\r\nBuộc gọn dây bằng dây rút\r\n\r\nĐi dây ra phía sau mainboard\r\n\r\nGiữ khu vực trước quạt thông thoáng\r\n\r\n✨ Một bộ máy gọn gàng = đẹp + mát hơn rõ rệt.\r\n\r\n❄️ Chọn quạt và tản nhiệt phù hợp\r\n\r\nNếu bạn dùng máy để chơi game hoặc làm việc nặng, đừng bỏ qua phần này.\r\n\r\nMột vài lưu ý:\r\n\r\nChọn quạt có lưu lượng gió tốt\r\n\r\nƯu tiên quạt có thể điều chỉnh tốc độ\r\n\r\nDùng tản nhiệt CPU phù hợp với nhu cầu\r\n\r\n👉 Không cần quá đắt, chỉ cần đúng và đủ là được.\r\n\r\n🧼 Vệ sinh định kỳ\r\n\r\nBụi bẩn là “kẻ thù thầm lặng” của hệ thống tản nhiệt.\r\n\r\nBạn nên:\r\n\r\nVệ sinh máy mỗi 1–2 tháng\r\n\r\nLàm sạch quạt và lưới lọc bụi\r\n\r\nThay keo tản nhiệt nếu dùng lâu\r\n\r\n💡 Chỉ cần vệ sinh thôi cũng có thể giảm nhiệt đáng kể.\r\n\r\n💡 Một vài mẹo nhỏ nhưng hữu ích\r\n\r\nKhông đặt case sát tường\r\n\r\nĐặt máy ở nơi thoáng mát\r\n\r\nChọn case có mặt trước dạng lưới\r\n\r\nĐiều chỉnh tốc độ quạt nếu cần\r\n\r\n✅ Kết luận\r\n\r\nTối ưu nhiệt độ case không hề khó. Chỉ cần bố trí quạt hợp lý, giữ dây gọn gàng và vệ sinh định kỳ, bạn đã có thể giúp máy mát hơn rất nhiều.\r\n\r\n🔥 Một bộ máy mát mẻ sẽ:\r\n\r\nChạy ổn định hơn\r\n\r\nÍt lỗi hơn\r\n\r\nBền bỉ theo thời gian	https://images.unsplash.com/photo-1593642634524-b40b5baae6bb?auto=format&fit=crop&w=1200&q=80	published	2026-03-14 10:15:00+07	2026-03-14 10:15:01.02254+07	2026-03-18 13:33:19.70446+07
2	SSD NVMe và SATA: Chọn loại nào cho nhu cầu của bạn?	ssd-nvme-va-sata-chon-loai-nao	SSD NVMe nhanh vượt trội, phù hợp gaming và tác vụ nặng; trong khi SSD SATA chậm hơn nhưng giá rẻ và đủ dùng cho nhu cầu cơ bản.	Khi nâng cấp hoặc build PC, việc lựa chọn giữa SSD NVMe và SSD SATA ảnh hưởng trực tiếp đến hiệu năng tổng thể của hệ thống. Cả hai đều là ổ cứng thể rắn giúp tăng tốc độ xử lý so với HDD truyền thống, nhưng sự khác biệt về công nghệ khiến trải nghiệm sử dụng không giống nhau.\r\n\r\nSSD SATA là chuẩn phổ biến lâu đời, sử dụng giao tiếp SATA III với tốc độ tối đa khoảng 500–550MB/s. Với mức hiệu năng này, SATA đã đủ để đáp ứng tốt các nhu cầu như học tập, làm việc văn phòng, lướt web hay chạy các phần mềm cơ bản. Ngoài ra, giá thành của SSD SATA khá rẻ, dễ tiếp cận và tương thích với hầu hết các dòng máy, kể cả máy cũ.\r\n\r\nTrong khi đó, SSD NVMe sử dụng giao tiếp PCIe hiện đại, mang lại tốc độ vượt trội, có thể nhanh gấp nhiều lần so với SATA. Điều này giúp thời gian khởi động máy, mở ứng dụng hay load game được rút ngắn đáng kể. Đặc biệt, với các tác vụ nặng như chỉnh sửa video, thiết kế đồ họa, lập trình hoặc chơi game cấu hình cao, NVMe thể hiện rõ sự khác biệt về hiệu năng.\r\n\r\nTuy nhiên, không phải ai cũng cần đến tốc độ cao của NVMe. Với người dùng phổ thông, sự khác biệt giữa SATA và NVMe trong các tác vụ nhẹ là không quá lớn. Vì vậy, việc lựa chọn nên dựa vào nhu cầu thực tế và ngân sách. Nếu bạn muốn một hệ thống mạnh mẽ, tối ưu hiệu suất lâu dài thì NVMe là lựa chọn đáng đầu tư. Ngược lại, nếu chỉ cần một giải pháp ổn định, tiết kiệm chi phí thì SATA vẫn hoàn toàn đáp ứng tốt.\r\n\r\nNgoài ra, một giải pháp được nhiều người dùng lựa chọn là kết hợp cả hai: sử dụng NVMe để cài hệ điều hành và phần mềm quan trọng, trong khi dùng SATA để lưu trữ dữ liệu. Cách này giúp tận dụng được tốc độ của NVMe và dung lượng giá rẻ của SATA.	https://images.unsplash.com/photo-1591489378430-ef2f4c626b35?auto=format&fit=crop&w=1200&q=80	published	2026-03-14 10:15:00+07	2026-03-14 10:15:01.02254+07	2026-03-28 13:22:12.594761+07
1	Cách chọn PSU an toàn cho dân PC gaming	cach-chon-psu-an-toan-cho-dan-pc-gaming	Chọn PSU phù hợp giúp PC gaming hoạt động ổn định, cần đủ công suất và nên dư 20–30% để đảm bảo an toàn.	Khi xây dựng một bộ PC gaming, nhiều người thường tập trung vào CPU hay GPU mà quên mất rằng bộ nguồn (PSU) chính là “trái tim” cung cấp năng lượng cho toàn bộ hệ thống. Một PSU phù hợp không chỉ giúp máy hoạt động ổn định mà còn bảo vệ các linh kiện đắt tiền khỏi rủi ro hư hỏng.\r\n\r\nTrước tiên, bạn cần xác định công suất phù hợp với cấu hình máy. Công suất PSU phụ thuộc chủ yếu vào mức tiêu thụ điện của CPU và GPU. Bạn có thể tham khảo các công cụ tính công suất online hoặc cộng tổng công suất linh kiện rồi chọn PSU có công suất cao hơn khoảng 20–30% để đảm bảo dư tải. Việc này giúp nguồn hoạt động mát hơn, ổn định hơn và kéo dài tuổi thọ.\r\n\r\nTiếp theo, hãy chú ý đến chứng nhận hiệu suất 80 Plus. Các mức như Bronze, Silver, Gold hay Platinum thể hiện hiệu suất chuyển đổi điện năng của PSU. PSU có hiệu suất cao sẽ tiết kiệm điện, giảm nhiệt lượng tỏa ra và vận hành êm ái hơn. Đối với PC gaming, tối thiểu bạn nên chọn 80 Plus Bronze, còn nếu có điều kiện thì Gold là lựa chọn rất hợp lý.\r\n\r\nMột yếu tố quan trọng khác là thương hiệu và chất lượng linh kiện bên trong PSU. Nên ưu tiên các hãng uy tín như Corsair, Cooler Master, Seasonic,... vì họ thường sử dụng linh kiện tốt và có chế độ bảo hành rõ ràng. Ngoài ra, PSU chất lượng sẽ được trang bị các tính năng bảo vệ như chống quá áp (OVP), quá dòng (OCP), quá nhiệt (OTP) và ngắn mạch (SCP), giúp bảo vệ toàn bộ hệ thống khi có sự cố điện.\r\n\r\nBên cạnh đó, bạn cũng nên cân nhắc về thiết kế dây cáp. PSU dạng non-modular có dây cố định toàn bộ, trong khi semi-modular hoặc full-modular cho phép tháo rời dây không cần thiết. Với case nhỏ hoặc cần tối ưu luồng gió, PSU modular sẽ giúp việc đi dây gọn gàng và đẹp mắt hơn.\r\n\r\nCuối cùng, tuyệt đối không nên ham rẻ mà chọn các loại PSU không rõ nguồn gốc. Những bộ nguồn kém chất lượng có thể gây sụt áp, cháy nổ hoặc làm hỏng các linh kiện quan trọng như mainboard hay card đồ họa.	https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=1200&q=80	published	2026-03-14 10:15:00+07	2026-03-14 10:15:01.02254+07	2026-03-28 13:22:38.850188+07
\.


--
-- Data for Name: pricing_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pricing_settings (id, rent_pct, labor_pct, tax_pct, other_pct, updated_at) FROM stdin;
1	0.0002	0.0004	0.0000	0.0000	2026-01-08 23:00:01.093433+07
\.


--
-- Data for Name: product_discount_campaigns; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_discount_campaigns (id, product_id, discount_percent, start_at, end_at, status, created_at) FROM stdin;
4	69	20	2026-03-22 07:32:00	2026-03-29 07:32:00	active	2026-03-22 14:32:32.397895
6	70	20	2026-03-29 10:15:00	2026-04-05 10:15:00	active	2026-03-29 17:16:16.055245
7	70	20	2026-04-05 06:34:00	2026-04-12 06:34:00	active	2026-04-05 13:34:59.105906
\.


--
-- Data for Name: product_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_images (id, product_id, image_url, sort_order) FROM stdin;
6	17	/images/products/akko.jpg	1
7	18	/images/products/logi.jpg	1
8	19	/images/products/sony.jpg	1
9	20	/images/products/anker.jpg	1
10	21	/images/products/magsafe.jpg	1
11	22	/images/products/baseus.jpg	1
12	23	/images/products/samsung.jpg	1
13	24	/images/products/marshall.jpg	1
14	25	/images/products/stand.jpg	1
15	26	/images/products/sandisk.jpg	1
16	27	/images/products/iphone-case.jpg	1
17	29	/images/products/shure-mv7.jpg	1
18	30	/images/products/nanoleaf.jpg	1
19	32	/images/products/xbox.jpg	1
20	35	/images/products/benq.jpg	1
21	76	/uploads/products/20260314081353_c51676902dd7.webp	0
22	76	/uploads/products/20260314081353_0d85dcbba7fb.png	1
23	75	/uploads/products/20260318110859_523743cee09e.webp	0
24	75	/uploads/products/20260318110859_d7ad3d4dc363.webp	1
25	74	/uploads/products/20260318111639_4d3d9bb3b4b3.webp	0
26	73	/uploads/products/20260318112653_581617f4d3c3.webp	0
27	73	/uploads/products/20260318112653_121059111bda.webp	1
28	72	/uploads/products/20260318112905_ef54a4ac96c2.webp	0
29	72	/uploads/products/20260318112905_1ae31448faa3.webp	1
30	71	/uploads/products/20260318113443_7f4bf9cabecc.webp	0
31	71	/uploads/products/20260318113443_eea3bf368e16.webp	1
32	70	/uploads/products/20260318114602_7c7a7cd14952.webp	0
33	70	/uploads/products/20260318114602_9d962b255e2c.webp	1
34	69	/uploads/products/20260318114858_6e6385cf2579.webp	0
35	69	/uploads/products/20260318114858_2b0f0e5f2119.webp	1
36	68	/uploads/products/20260318115136_091c003dc833.webp	0
37	68	/uploads/products/20260318115136_af45abeff72a.webp	1
38	67	/uploads/products/20260318115516_4350591812d0.webp	0
39	67	/uploads/products/20260318115516_ed94044ea356.webp	1
\.


--
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_variants (id, product_id, sku, combination_key, base_price, sale_price, stock, is_active, created_at) FROM stdin;
9	17	AKKO-3068-PINK	Switch: Pink	1650000	1550000	20	t	2026-03-14 14:30:19.908495+07
10	18	LOGI-GPX-WHT	Mau: Trang	2990000	2850000	15	t	2026-03-14 14:30:19.908495+07
11	18	LOGI-GPX-BLK	Mau: Den	2990000	2790000	10	t	2026-03-14 14:30:19.908495+07
13	23	SS-980PRO-1TB	default	2550000	2390000	50	t	2026-03-14 14:30:19.908495+07
15	27	IP15PM-SIL-BLK	Mau: Black	1450000	1350000	30	t	2026-03-14 14:56:54.165782+07
16	31	DESKMAT-GRY	Mau: Xam	350000	290000	100	t	2026-03-14 14:56:54.165782+07
17	32	XBOX-CORE-WHT	Mau: White	3550000	3250000	10	t	2026-03-14 14:56:54.165782+07
19	75	BUBM-DL-GRY	default	180000	252000	30	t	2026-03-15 19:46:29.675984+07
20	74	TOMTOC-A13-GRY	default	650000	1105000	25	t	2026-03-18 18:16:39.858313+07
22	72	JBL-GO3-BLU	default	750000	975000	50	t	2026-03-18 18:29:05.299049+07
23	71	APPLE-APP2-USBC	default	4000000	5600000	20	t	2026-03-18 18:33:48.654706+07
27	67	BSS-CSC-100W-2M	default	150000	210000	10	t	2026-03-18 18:55:16.363482+07
18	76	1123	default	650000	1007500	0	t	2026-03-14 15:29:37.881807+07
26	68	MHXH3VN/A	default	850000	1275000	6	t	2026-03-18 18:51:36.940639+07
12	19	SONY-XM5-BLK	default	6500000	7039500	1	t	2026-03-14 14:30:19.908495+07
14	27	IP15PM-SIL-BLU	Mau: Blue	1450000	1350000	39	t	2026-03-14 14:56:54.165782+07
21	73	MRS-MAJOR4-BLK	default	2500000	3625000	4	t	2026-03-18 18:26:53.407151+07
25	69	BELKIN-WIZ017-WHT	default	2500000	4000000	1	t	2026-03-18 18:48:58.435206+07
24	70	ANKER-622-BLK	default	800000	1120000	18	t	2026-03-18 18:46:02.080622+07
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.products (id, category_id, brand_id, name, slug, description, is_active, created_at, short_description, highlights, technical_specs, shipping_info, warranty_months, cost_price, import_tax_percent, vat_percent, profit_percent, price) FROM stdin;
17	27	\N	Bàn phím Akko 3068B Plus	akko-3068b-plus	Thiết kế 65%, switch Akko CS.	t	2026-03-14 14:30:19.908495+07	Bàn phím không dây 3 chế độ	\N	\N	\N	12	1100000	5.00	10.00	30.00	1650000
18	27	\N	Chuột Logitech G Pro X Superlight	g-pro-x-superlight	Cảm biến HERO 25K.	t	2026-03-14 14:30:19.908495+07	Siêu nhẹ cho game thủ	\N	\N	\N	24	2100000	5.00	10.00	25.00	2990000
20	29	\N	Sạc Anker 737 140W	anker-737-140w	Sạc cùng lúc 3 thiết bị.	t	2026-03-14 14:30:19.908495+07	Sạc nhanh chuẩn GaNPrime	\N	\N	\N	18	2200000	0.00	10.00	35.00	3350000
21	29	\N	Cáp Apple MagSafe 3 (2m)	apple-magsafe-3-2m	Dây bện dù chắc chắn.	t	2026-03-14 14:30:19.908495+07	Dành cho MacBook Pro	\N	\N	\N	12	900000	10.00	10.00	40.00	1490000
22	30	\N	Hub Baseus 8-in-1	baseus-hub-8in1	Hỗ trợ HDMI 4K, LAN 1Gbps.	t	2026-03-14 14:30:19.908495+07	Bộ chuyển đổi đa năng	\N	\N	\N	12	550000	5.00	10.00	45.00	890000
23	26	\N	SSD Samsung 980 Pro 1TB	samsung-980-pro-1tb	Dành cho PC và PS5.	t	2026-03-14 14:30:19.908495+07	PCIe Gen4 tốc độ cao	\N	\N	\N	60	1800000	5.00	10.00	25.00	2550000
24	28	\N	Loa Marshall Emberton II	marshall-emberton-ii	Kháng nước IP67, pin 30h.	t	2026-03-14 14:30:19.908495+07	Âm thanh 360 độ	\N	\N	\N	12	2800000	10.00	10.00	25.00	3950000
25	30	\N	Giá đỡ Laptop Aluminum	gia-do-laptop-nhom	Tùy chỉnh 7 mức độ cao.	t	2026-03-14 14:30:19.908495+07	Chất liệu nhôm cao cấp	\N	\N	\N	6	150000	0.00	10.00	100.00	350000
26	26	\N	Thẻ nhớ SanDisk Extreme 128GB	sandisk-extreme-128gb	Chuẩn V30 quay phim 4K.	t	2026-03-14 14:30:19.908495+07	Tốc độ 190MB/s	\N	\N	\N	120	250000	0.00	10.00	60.00	450000
27	32	\N	Ốp lưng MagSafe iPhone 15 Pro Max	op-magsafe-iphone-15-pro-max	Ốp lưng chính hãng Apple với lớp lót microfiber bên trong bảo vệ máy tuyệt đối.	t	2026-03-14 14:56:54.165782+07	Chất liệu Silicon mịn, hỗ trợ MagSafe	\N	\N	\N	12	800000	10.00	10.00	50.00	1450000
28	33	\N	Dây đeo Trail Loop Apple Watch Ultra	day-trail-loop-ultra	Phù hợp cho các hoạt động thể thao cường độ cao, thiết kế khóa dán tiện lợi.	t	2026-03-14 14:56:54.165782+07	Dây vải dệt mỏng, nhẹ, co giãn tốt	\N	\N	\N	6	600000	10.00	10.00	60.00	1190000
29	40	\N	Microphone Shure MV7 Podcast	shure-mv7-podcast	Hỗ trợ cả kết nối USB và XLR, tính năng Auto Level Mode giúp giọng nói luôn ổn định.	t	2026-03-14 14:56:54.165782+07	Micro chuyên dụng cho Streamer/Podcaster	\N	\N	\N	24	5500000	5.00	10.00	25.00	7490000
30	42	\N	Đèn LED Nanoleaf Lines (9 Bars)	nanoleaf-lines-9-bars	Tạo không gian setup gaming cực chất với 16 triệu màu, điều khiển qua App.	t	2026-03-14 14:56:54.165782+07	Đèn decor thông minh nháy theo nhạc	\N	\N	\N	12	3500000	0.00	10.00	40.00	4900000
31	36	\N	Lót chuột Minimalist Felt Desk Mat	minimalist-felt-deskmat	Giúp bảo vệ mặt bàn và tạo cảm giác êm ái khi sử dụng chuột và bàn phím.	t	2026-03-14 14:56:54.165782+07	Chất liệu dạ len cao cấp, kích thước 90x40cm	\N	\N	\N	3	150000	0.00	10.00	100.00	350000
32	37	\N	Tay cầm Xbox Elite Series 2 Core	xbox-elite-series-2-core	Cần analog điều chỉnh độ nhạy, nút cò ngắn hơn, pin sạc 40 giờ.	t	2026-03-14 14:56:54.165782+07	Tay cầm cao cấp nhất từ Microsoft	\N	\N	\N	12	2500000	5.00	10.00	30.00	3550000
33	44	\N	Balo Tomtoc Navigator-T66 40L	tomtoc-navigator-t66	Thiết kế ngăn chứa Laptop riêng biệt, bảo vệ 360 độ chuẩn quân đội.	t	2026-03-14 14:56:54.165782+07	Balo du lịch, công nghệ chống nước	\N	\N	\N	12	1800000	5.00	10.00	35.00	2650000
34	40	\N	Microphone Elgato Wave:3	elgato-wave-3	Công nghệ Clipguard chống rè âm thanh khi hét quá to.	t	2026-03-14 14:56:54.165782+07	Micro USB chuẩn Studio với bộ trộn kỹ thuật số	\N	\N	\N	12	3200000	5.00	10.00	30.00	4490000
35	42	\N	Đèn màn hình BenQ ScreenBar Halo	benq-screenbar-halo	Thiết kế treo màn hình tiết kiệm diện tích, có remote không dây.	t	2026-03-14 14:56:54.165782+07	Chống mỏi mắt, cảm biến ánh sáng tự động	\N	\N	\N	12	3100000	5.00	10.00	35.00	4390000
36	32	\N	Ốp iPad Pro 11 inch Tomtoc Vertical	op-ipad-tomtoc-vertical	Chống sốc cực tốt, khe cắm Apple Pencil tiện lợi.	t	2026-03-14 14:56:54.165782+07	Hỗ trợ dựng dọc và ngang linh hoạt	\N	\N	\N	6	600000	0.00	10.00	60.00	990000
37	27	\N	Razer DeathAdder V3 Pro	razer-deathadder-v3-pro	Cảm biến Focus Pro 30K, Switch quang học Gen-3.	t	2026-03-14 15:02:24.266853+07	Chuột gaming không dây siêu nhẹ 63g	\N	\N	\N	24	2500000	5.00	10.00	30.00	3650000
38	27	\N	Zowie EC2-CW Wireless	zowie-ec2-cw	Thiết kế công thái học huyền thoại, kết nối không dây ổn định.	t	2026-03-14 15:02:24.266853+07	Chuột Esports chuyên dụng cho FPS	\N	\N	\N	12	2800000	5.00	10.00	20.00	3490000
39	27	\N	SteelSeries Rival 3	steelseries-rival-3	Mắt đọc TrueMove Core, LED RGB PrismSync.	t	2026-03-14 15:02:24.266853+07	Chuột gaming phân khúc phổ thông	\N	\N	\N	12	450000	5.00	10.00	50.00	790000
40	27	\N	ASUS ROG Keris Wireless AimPoint	asus-rog-keris-aimpoint	Cảm biến ROG AimPoint, hỗ trợ thay switch nhanh.	t	2026-03-14 15:02:24.266853+07	Chuột nhẹ 75g, 36.000 DPI	\N	\N	\N	24	1500000	5.00	10.00	40.00	2390000
41	27	\N	Corsair M65 RGB Ultra	corsair-m65-rgb-ultra	Cảm biến Marksman 26K DPI, công nghệ Quickstrike.	t	2026-03-14 15:02:24.266853+07	Chuột khung nhôm có tạ điều chỉnh	\N	\N	\N	24	1400000	5.00	10.00	35.00	2090000
42	38	\N	Keychron Q1 Pro Wireless	keychron-q1-pro	Layout 75%, kết nối Bluetooth 5.1, hỗ trợ QMK/VIA.	t	2026-03-14 15:02:24.266853+07	Bàn phím cơ Custom Full Nhôm	\N	\N	\N	12	3200000	5.00	10.00	25.00	4250000
43	38	\N	Bộ Keycap PBT Olivia Clone	keycap-olivia-pbt	Tone màu hồng đen sang trọng, phù hợp mọi loại phím cơ.	t	2026-03-14 15:02:24.266853+07	Keycap nhựa PBT Double-shot	\N	\N	\N	0	300000	0.00	10.00	100.00	650000
44	38	\N	Switch Cherry MX Blue (Gói 10 cái)	switch-cherry-blue-10	Độ bền 50 triệu lần nhấn, cảm giác gõ rõ rệt.	t	2026-03-14 15:02:24.266853+07	Switch cơ học clicky truyền thống	\N	\N	\N	0	80000	0.00	10.00	50.00	130000
45	38	\N	Bàn phím MonsGeek M1W	monsgeek-m1w-keyboard	Hotswap, lót sẵn foam tiêu âm, LED RGB từng phím.	t	2026-03-14 15:02:24.266853+07	Phím cơ 3 mode, case nhôm CNC	\N	\N	\N	12	1600000	5.00	10.00	30.00	2250000
46	38	\N	Dây cáp xoắn Aviator Custom	coiled-cable-aviator	Bọc dù paracord, kết nối GX16 chuyên nghiệp.	t	2026-03-14 15:02:24.266853+07	Dây cáp trang trí bàn phím cơ	\N	\N	\N	6	150000	0.00	10.00	150.00	390000
47	40	\N	Blue Yeti USB Microphone	blue-yeti-usb	4 chế độ thu âm khác nhau, cắm là chạy.	t	2026-03-14 15:02:24.266853+07	Micro quốc dân cho Streamer	\N	\N	\N	12	2200000	5.00	10.00	35.00	3190000
48	40	\N	Rode NT-USB Mini	rode-nt-usb-mini	Tích hợp bộ lọc pop và giá đỡ từ tính.	t	2026-03-14 15:02:24.266853+07	Micro thu âm nhỏ gọn chất lượng cao	\N	\N	\N	12	1900000	5.00	10.00	40.00	2850000
49	40	\N	HyperX QuadCast S	hyperx-quadcast-s	Giá chống sốc tích hợp, bộ lọc pop bên trong.	t	2026-03-14 15:02:24.266853+07	Micro chuyên nghiệp với LED RGB	\N	\N	\N	24	3100000	5.00	10.00	25.00	4100000
19	28	\N	Tai nghe Sony WH-1000XM5	sony-wh-1000xm5	Pin 30 giờ, âm thanh Hi-Res.	t	2026-03-14 14:30:19.908495+07	Chống ồn chủ động ANC				12	6500000	0.00	0.00	20.00	7800000
50	30	\N	Elgato Stream Deck MK.2	elgato-stream-deck-mk2	Tùy chỉnh vô hạn cho livestream và làm việc.	t	2026-03-14 15:02:24.266853+07	Bàn điều khiển 15 phím LCD	\N	\N	\N	12	2800000	5.00	10.00	35.00	3990000
51	30	\N	Webcam Razer Kiyo Pro	razer-kiyo-pro	Cảm biến ánh sáng thích ứng, kính Gorilla Glass 3.	t	2026-03-14 15:02:24.266853+07	Webcam HDR chất lượng 1080p 60FPS	\N	\N	\N	12	2500000	5.00	10.00	40.00	3750000
52	36	\N	Lót chuột Artisan Ninja FX Hayate Otsu	artisan-hayate-otsu	Bề mặt vải lai, độ bền cực cao, tốc độ mượt mà.	t	2026-03-14 15:02:24.266853+07	Lót chuột cao cấp từ Nhật Bản	\N	\N	\N	0	1200000	10.00	10.00	30.00	1750000
53	36	\N	Lót chuột Corsair MM350 Pro	corsair-mm350-pro	Bề mặt chống tràn nước, viền khâu dày dặn.	t	2026-03-14 15:02:24.266853+07	Kích thước Extended XL 93x40cm	\N	\N	\N	0	600000	5.00	10.00	50.00	950000
54	40	\N	Tay treo Microphone NB-35	tay-treo-mic-nb35	Chịu lực tốt, xoay 360 độ linh hoạt.	t	2026-03-14 15:02:24.266853+07	Giá đỡ micro kẹp bàn phổ thông	\N	\N	\N	3	120000	0.00	10.00	100.00	250000
55	30	\N	Arm màn hình HumanMotion T6 Pro	humanmotion-t6-pro	Hỗ trợ màn hình đến 32 inch, chuẩn VESA.	t	2026-03-14 15:02:24.266853+07	Giá đỡ màn hình lò xo trợ lực	\N	\N	\N	24	850000	0.00	10.00	50.00	1350000
56	30	\N	Thanh treo tai nghe kẹp bàn	gia-treo-tai-nghe	Kẹp chắc chắn vào cạnh bàn, đệm cao su êm ái.	t	2026-03-14 15:02:24.266853+07	Giá đỡ tai nghe hợp kim nhôm	\N	\N	\N	6	80000	0.00	10.00	150.00	220000
57	32	\N	Ốp lưng UAG Monarch iPhone 15 Pro	uag-monarch-iphone-15-pro	Thiết kế hầm hố, bảo vệ điện thoại khỏi rơi rớt ở độ cao 6m.	t	2026-03-14 15:08:57.514981+07	Chống sốc 5 lớp chuẩn quân đội	\N	\N	\N	12	1100000	5.00	10.00	40.00	1650000
58	32	\N	Cường lực KingKong 3D iPhone	cuong-luc-kingkong-iphone	Độ cứng 9H, chống bám vân tay và trầy xước cực tốt.	t	2026-03-14 15:08:57.514981+07	Kính cường lực full màn hình	\N	\N	\N	0	50000	0.00	10.00	200.00	150000
59	32	\N	Bút Apple Pencil 2 (Open Box)	apple-pencil-2-openbox	Hỗ trợ sạc không dây, cảm ứng lực nhấn và độ nghiêng.	t	2026-03-14 15:08:57.514981+07	Bút cảm ứng cho iPad Pro/Air	\N	\N	\N	6	1800000	5.00	10.00	35.00	2550000
60	33	\N	Dây đeo Apple Watch Alpine Loop	watch-band-alpine-loop	Thiết kế móc chữ G bằng titan chắc chắn.	t	2026-03-14 15:08:57.514981+07	Dây vải dệt cao cấp cho Watch Ultra	\N	\N	\N	6	250000	10.00	10.00	100.00	550000
61	33	\N	Dây đeo Silicone Sport Band	watch-band-silicone-sport	Chống nước, phù hợp đeo hằng ngày và tập thể thao.	t	2026-03-14 15:08:57.514981+07	Dây cao su mềm mại, nhiều màu	\N	\N	\N	3	50000	0.00	10.00	200.00	150000
62	32	\N	Ví MagSafe Leather Wallet	apple-magsafe-wallet	Làm từ da Châu Âu, hỗ trợ tính năng Find My.	t	2026-03-14 15:08:57.514981+07	Ví da hít nam châm sau lưng iPhone	\N	\N	\N	12	950000	5.00	10.00	45.00	1490000
63	32	\N	Kính bảo vệ Camera MIPOW	mipow-lens-protector	Kính Sapphire siêu cứng, không làm giảm chất lượng ảnh.	t	2026-03-14 15:08:57.514981+07	Bảo vệ ống kính camera iPhone	\N	\N	\N	0	150000	0.00	10.00	100.00	320000
64	29	\N	Củ sạc Anker 511 (Nano Pro) 20W	anker-511-nano-20w	Công nghệ ActiveShield giúp kiểm soát nhiệt độ.	t	2026-03-14 15:08:57.514981+07	Siêu nhỏ gọn, sạc nhanh cho iPhone	\N	\N	\N	18	220000	0.00	10.00	80.00	450000
65	29	\N	Trạm sạc Baseus GaN5 Pro 65W	baseus-gan5-65w-station	2 cổng Type-C, 1 USB-A và 2 ổ cắm AC tiện lợi.	t	2026-03-14 15:08:57.514981+07	Tích hợp ổ cắm điện và cổng sạc	\N	\N	\N	12	650000	5.00	10.00	40.00	990000
66	29	\N	Pin dự phòng Shargeek Storm 2	shargeek-storm-2	Dung lượng 25.600mAh, màn hình IPS hiển thị dòng điện.	t	2026-03-14 15:08:57.514981+07	Thiết kế trong suốt, sạc nhanh 100W	\N	\N	\N	12	3800000	10.00	10.00	30.00	5490000
74	44	3	Túi chống sốc Tomtoc 360° Protective Shoulder Bag (A13)	tui-chong-soc-tomtoc-360-shoulder-bag-a13	Tomtoc 360° Shoulder Bag là sự kết hợp hoàn hảo giữa túi chống sốc và túi đeo vai thời trang. Điểm ăn tiền nhất là lớp đệm CornerArmor™ ở các góc, bảo vệ máy tính khỏi những cú rơi rầm trọng như túi khí ô tô. Bên trong là lớp lót nhung mềm mại, bên ngoài là vải Cordura hoặc vật liệu tái chế chống thấm nước cực tốt. Túi có ngăn phụ lớn phía trước giúp bạn mang theo sạc, chuột, Hub chuyển đổi cực kỳ gọn gàng.	t	2026-03-14 15:08:57.514981+07	Túi đeo vai Tomtoc trang bị công nghệ CornerArmor™ 360 độ đạt tiêu chuẩn quân đội, chất liệu vải tái chế bảo vệ môi trường và ngăn chứa phụ kiện rộng rãi.	Công nghệ CornerArmor™ bảo vệ 360 độ chuẩn quân đội (MIL-STD-810H).\r\n\r\nKhóa kéo YKK Nhật Bản siêu bền và trơn tru.\r\n\r\nDây đeo vai có đệm êm ái, có thể tháo rời để dùng như túi chống sốc cầm tay.\r\n\r\nChất liệu vải cao cấp kháng nước và chống mài mòn.	Công nghệ bảo vệ # CornerArmor™ (4 góc túi khí)\r\n\r\nChất liệu vỏ # Vải Polyester/Nylon kháng nước\r\n\r\nLớp lót # Nhung nhân tạo (Fleece) chống trầy\r\n\r\nKhóa kéo # YKK (Nhật Bản)\r\n\r\nKích thước tương thích # 13" - 14" (Macbook/Laptop)\r\n\r\nSố ngăn # 1 ngăn chính + 1 ngăn phụ kiện lớn\r\n\r\nPhụ kiện đi kèm # Dây đeo vai rời		12	650000	0.00	10.00	60.00	1105000
67	29	9	Cáp sạc nhanh Baseus Crystal Shine Series USB-C sang USB-C 100W	cap-sac-nhanh-baseus-crystal-shine-c-to-c-100w	Cáp Baseus Crystal Shine Series là sự lựa chọn hàng đầu cho những ai đang tìm kiếm một sợi cáp sạc đa năng và bền bỉ. Với công suất tối đa lên đến 100W (20V/5A) và hỗ trợ chuẩn sạc nhanh Power Delivery (PD 3.0), sợi cáp này có thể sạc đầy MacBook Pro 16" chỉ trong khoảng 2 giờ, đồng thời tương thích hoàn hảo với các dòng smartphone cao cấp hiện nay.\r\n\r\nĐiểm nhấn của sản phẩm nằm ở phần đầu cáp được làm bằng hợp kim nhôm sáng bóng như pha lê, kết hợp với phần thân bọc dù nylon mật độ cao. Thiết kế này không chỉ mang lại vẻ ngoài sang trọng mà còn giúp sợi cáp chịu được hơn 10.000 lần uốn cong mà không bị đứt gãy hay bong tróc lớp vỏ.\r\n\r\nBên trong lõi cáp được trang bị Chip E-Marker thông minh. Con chip này có nhiệm vụ nhận diện thiết bị đầu cuối để điều chỉnh dòng điện phù hợp nhất, tránh tình trạng quá nhiệt hoặc gây hại cho pin. Ngoài sạc pin, cáp còn hỗ trợ truyền dữ liệu tốc độ cao 480Mbps, giúp bạn sao lưu hình ảnh và tài liệu nhanh chóng.	t	2026-03-14 15:08:57.514981+07	Cáp sạc Baseus Crystal Shine hỗ trợ công suất cực khủng lên đến 100W, thiết kế bọc dù chắc chắn chống đứt gãy và chip E-Marker thông minh giúp bảo vệ thiết bị sạc an toàn tuyệt đối. Phù hợp cho iPhone 15/16, iPad, MacBook và các dòng Laptop chuẩn C.	Công suất 100W siêu nhanh: Sạc tốt cho cả Laptop, Tablet và Smartphone.\r\n\r\nVỏ bọc dù Nylon bền bỉ: Chống rối, chống co giãn và cực kỳ khó đứt.\r\n\r\nĐầu cáp hợp kim nhôm: Tản nhiệt tốt và chống mài mòn theo thời gian.\r\n\r\nChip E-Marker an toàn: Tự động điều tiết dòng điện theo chuẩn sạc của thiết bị.\r\n\r\nĐộ dài đa dạng: Thoải mái sử dụng tại văn phòng, giường ngủ hoặc trên xe ô tô.	Kiểu kết nối # USB-C to USB-C (C to C)\r\n\r\nCông suất tối đa # 100W (20V/5A)\r\n\r\nTốc độ truyền dữ liệu # 480 Mbps\r\n\r\nCông nghệ sạc nhanh # Power Delivery (PD 3.0) / QC 4.0\r\n\r\nChất liệu # Hợp kim nhôm + Vải dù Nylon\r\n\r\nĐộ dài cáp # 1.2 mét / 2 mét\r\n\r\nChip bảo vệ # E-Marker thông minh\r\n\r\nTương thích # iPhone 15/16, MacBook, iPad, Laptop chuẩn C, Samsung, Xiaomi		12	150000	0.00	10.00	30.00	210000
73	28	4	Tai nghe không dây Marshall Major IV Bluetooth	tai-nghe-khong-day-marshall-major-iv-bluetooth	Major IV là thế hệ thứ tư của dòng tai nghe On-ear biểu tượng từ Marshall. Điểm nâng cấp đáng giá nhất chính là thời lượng pin cực khủng lên đến hơn 80 giờ chơi nhạc liên tục, giúp bạn có thể sử dụng cả tuần chỉ với một lần sạc. Tai nghe được trang bị Driver Dynamic 40mm tinh chỉnh, mang lại âm trầm sâu, âm trung mượt mà và âm cao rõ nét.\r\n\r\nVề thiết kế, Major IV sở hữu các kẹp gấp mới giúp tai nghe có thể gập lại gọn gàng hơn nữa, bảo vệ phần đệm tai khỏi sự mài mòn. Nút điều khiển đa hướng (Control Knob) bằng kim loại vàng đồng cực kỳ nhạy, cho phép điều chỉnh nhạc và cuộc gọi dễ dàng. Đặc biệt, tính năng chia sẻ âm nhạc qua cổng 3.5mm cho phép bạn kết nối thêm một tai nghe khác để cùng nghe nhạc với bạn bè cực kỳ tiện lợi.	t	2026-03-14 15:08:57.514981+07	Marshall Major IV mang đến sự kết hợp hoàn hảo giữa chất âm Rock 'n' Roll huyền thoại, thời lượng pin kỷ lục 80+ giờ và thiết kế không dây hiện đại hỗ trợ sạc chuẩn Qi.	Hơn 80 giờ chơi nhạc không dây.\r\n\r\nHỗ trợ sạc không dây chuẩn Qi và sạc nhanh USB-C.\r\n\r\nThiết kế công thái học cải tiến, đệm tai siêu mềm.\r\n\r\nNút điều khiển đa hướng thông minh.\r\n\r\nHỗ trợ Bluetooth 5.0 kết nối ổn định trong phạm vi 10m.	Công nghệ bảo vệ # CornerArmor™ (4 góc túi khí)\r\n\r\nTiêu chuẩn độ bền # MIL-STD-810H (Quân đội Mỹ)\r\n\r\nChất liệu # Vải Cordura tái chế kháng nước\r\n\r\nKhóa kéo # YKK Nhật Bản\r\n\r\nKích thước ngăn chứa # 32.51 x 23.22 x 1.7 cm\r\n\r\nTương thích # MacBook Pro 14", MacBook Air M1/M2/M3		12	2500000	10.00	10.00	25.00	3625000
75	44	2	Tên sản phẩm: Túi đựng phụ kiện công nghệ BUBM Double Layer	tui-dung-phu-kien-cong-nghe-bubm-double-layer	Túi BUBM là giải pháp hoàn hảo cho những người yêu công nghệ thường xuyên di chuyển. Với thiết kế 2 tầng tách biệt: tầng trên có các khe cắm chun co giãn cho dây cáp và thẻ nhớ, tầng dưới có ngăn lớn tùy chỉnh để chứa sạc dự phòng, chuột hoặc ổ cứng di động. Lớp lót đệm dày giúp chống sốc hiệu quả cho các thiết bị bên trong.	t	2026-03-14 15:08:57.514981+07	Túi đựng phụ kiện BUBM thiết kế 2 tầng thông minh, chất liệu vải Nylon chống thấm nước cao cấp, giúp bảo vệ và sắp xếp gọn gàng cáp sạc, tai nghe, ổ cứng và củ sạc.	Chất liệu vải Oxford cao cấp chống trầy xước và tia nước.\r\n\r\nThiết kế 2 tầng tối ưu không gian lưu trữ.\r\n\r\nKhóa kéo kép kim loại bền bỉ, trơn tru.\r\n\r\nKích thước nhỏ gọn, dễ dàng để trong balo hoặc vali.	Chất liệu # Nylon Oxford chống thấm\r\n\r\nSố tầng # 2 tầng (Double Layer)\r\n\r\nKích thước # 24 x 18 x 10 cm\r\n\r\nTrọng lượng # 200g\r\n\r\nMàu sắc # Xám (Grey) / Đen (Black)\r\n\r\nKhả năng chứa # Sạc dự phòng, chuột, cáp sạc, tai nghe, USB		3	180000	10.00	10.00	20.00	252000
72	28	5	Loa Bluetooth di động JBL Go 3 - Kháng nước IP67	loa-bluetooth-di-dong-jbl-go-3-khang-nuoc-ip67	Đừng để kích thước nhỏ bé của JBL Go 3 đánh lừa bạn. Đây là chiếc loa di động mang lại chất âm JBL Pro Sound kinh ngạc với âm bass mạnh mẽ và chi tiết, vượt xa mong đợi từ một thân hình "tí hon". Thiết kế của Go 3 đã được lột xác hoàn toàn so với các thế hệ trước, sử dụng lớp vải dệt cao cấp cùng các chi tiết dập nổi cá tính, lấy cảm hứng từ thời trang đường phố (streetwear).\r\n\r\nJBL Go 3 được trang bị tiêu chuẩn kháng nước và kháng bụi IP67, nghĩa là bạn có thể mang loa đến hồ bơi, bãi biển hay thậm chí là đi dưới mưa mà không cần lo lắng. Một cải tiến đáng giá khác là tích hợp thêm móc treo bằng dây bện chắc chắn, giúp bạn dễ dàng móc vào balo, thắt lưng hoặc tay lái xe đạp. Cổng sạc USB-C hiện đại giúp việc nạp năng lượng trở nên nhanh chóng và thuận tiện hơn bao giờ hết.	t	2026-03-14 15:08:57.514981+07	JBL Go 3 sở hữu phong cách thiết kế táo bạo cùng chất âm JBL Pro Sound nguyên bản đỉnh cao. Với thiết kế hình khối góc cạnh mới lạ, lớp vải bọc màu sắc rực rỡ và khả năng kháng nước, kháng bụi IP67, đây chính là phụ kiện không thể thiếu cho mọi chuyến đi.	Chất âm độc quyền JBL Pro Sound: Tối ưu hóa âm trầm cực tốt trong khung máy nhỏ gọn.\r\n\r\nKháng nước & bụi IP67: Hoạt động bền bỉ ở độ sâu 1m trong 30 phút.\r\n\r\nThời lượng pin ấn tượng: Cho phép phát nhạc liên tục trong 5 giờ chỉ với một lần sạc.\r\n\r\nCông nghệ Bluetooth 5.1: Đảm bảo kết nối không dây siêu tốc và không bị trễ âm thanh.\r\n\r\nThiết kế thời thượng: Nhiều phiên bản màu sắc (Đen, Đỏ, Xanh dương, Cam, Camo) phù hợp với cá tính giới trẻ.	Kiểu loa # Loa Bluetooth di động mini\r\n\r\nCông suất đầu ra # 4.2W RMS\r\n\r\nTrình điều khiển # 1.5 inch (43 x 47 mm)\r\n\r\nTần số đáp ứng # 110 Hz - 20 kHz\r\n\r\nTỷ lệ tín hiệu trên nhiễu # > 85 dB\r\n\r\nPhiên bản Bluetooth # 5.1\r\n\r\nLoại pin # Li-ion polymer 2.775Wh\r\n\r\nThời gian sạc pin # 2.5 giờ (5V/1A)\r\n\r\nKích thước # 8.75 x 7.5 x 4.13 cm\r\n\r\nTrọng lượng # 209g		12	750000	5.00	10.00	15.00	975000
71	28	6	Tai nghe Apple AirPods Pro Gen 2 (MagSafe USB-C) - Chính hãng VN/A	tai-nghe-apple-airpods-pro-gen-2-magsafe-usb-c	AirPods Pro Gen 2 (USB-C) được trang bị chip Apple H2 mạnh mẽ, giúp tái tạo âm thanh chi tiết hơn với độ méo thấp. Chip H2 cũng chính là "đầu não" đứng sau tính năng Chống ồn chủ động (ANC) thế hệ mới, có khả năng triệt tiêu tiếng ồn môi trường gấp đôi so với phiên bản tiền nhiệm. Ngoài ra, chế độ Xuyên âm thích ứng (Adaptive Audio) sẽ tự động điều chỉnh mức độ tiếng ồn xung quanh để bạn vẫn có thể nghe thấy những gì quan trọng mà không bị làm phiền bởi những âm thanh quá chói tai.\r\n\r\nĐiểm nâng cấp đáng giá nhất trên phiên bản này chính là việc thay thế cổng Lightning bằng cổng USB-C, cho phép bạn sạc tai nghe bằng cùng một sợi cáp với iPhone 15 series, iPad hay MacBook. Hộp sạc MagSafe đi kèm cũng được nâng cấp tiêu chuẩn kháng nước/bụi IP54 và tích hợp loa tìm kiếm Find My cực kỳ tiện lợi. Với tính năng Personalized Spatial Audio, tai nghe sẽ theo dõi chuyển động đầu của bạn để tạo ra không gian âm thanh vòm 3 chiều sống động như trong rạp hát.	t	2026-03-14 15:08:57.514981+07	AirPods Pro Gen 2 phiên bản mới nhất với cổng sạc USB-C mang đến khả năng chống ồn chủ động (ANC) gấp 2 lần, âm thanh thích ứng thông minh và thời lượng pin vượt trội. Đây là chuẩn mực mới cho trải nghiệm nghe nhạc không dây trên hệ sinh thái Apple.	Chip H2 độc quyền: Nâng tầm chất lượng âm thanh và khả năng xử lý thông minh.\r\n\r\nChống ồn ANC vượt trội: Loại bỏ tạp âm môi trường hiệu quả gấp 2 lần.\r\n\r\nCổng sạc USB-C: Đồng bộ hóa cáp sạc với các thiết bị Apple mới nhất.\r\n\r\nTính năng nhận biết cuộc hội thoại: Tự động giảm âm lượng nhạc khi bạn bắt đầu nói chuyện với người xung quanh.\r\n\r\nThời lượng pin ấn tượng: Lên đến 6 giờ nghe nhạc (có ANC) và tổng cộng 30 giờ khi kèm hộp sạc.\r\n\r\nĐiều khiển cảm ứng vuốt: Điều chỉnh âm lượng trực tiếp trên thân tai nghe bằng cách vuốt lên/xuống.	Chip xử lý # Apple H2 (Tai nghe) / Apple U1 (Hộp sạc)\r\n\r\nCông nghệ âm thanh # Chống ồn chủ động (ANC), Xuyên âm (Transparency), Spatial Audio\r\n\r\nKết nối # Bluetooth 5.3\r\n\r\nCổng sạc # USB-C / Sạc không dây MagSafe / Chuẩn Qi\r\n\r\nKháng nước & bụi # IP54 (Cả tai nghe và hộp sạc)\r\n\r\nMicro # Micro kép lọc gió (Beamforming)\r\n\r\nCảm biến # Cảm biến nhận biết da, Gia tốc kế, Cảm biến chạm\r\n\r\nThời lượng pin # 6 giờ (Single charge) / 30 giờ (With case)\r\n\r\nTrọng lượng # 5.3g (Mỗi tai nghe) / 50.8g (Hộp sạc)		12	4000000	5.00	10.00	25.00	5600000
70	29	7	Pin dự phòng MagSafe Anker 622 MagGo - 5000mAh	pin-du-phong-magsafe-anker-622-maggo-5000mah	Pin dự phòng Anker 622 (MagGo) được thiết kế dành riêng cho các dòng iPhone hỗ trợ MagSafe (từ iPhone 12 đến iPhone 16 series). Với lực hút nam châm cực mạnh, viên pin sẽ tự động hít chặt và căn chỉnh vị trí sạc tối ưu ngay khi bạn đặt vào mặt lưng điện thoại, đảm bảo quá trình sạc không dây diễn ra liên tục và ổn định.\r\n\r\nĐiểm độc đáo của dòng Anker 622 là tích hợp chân đế gập (Built-in Kickstand) linh hoạt. Bạn có thể dựng đứng iPhone để thực hiện cuộc gọi video FaceTime hoặc đặt nằm ngang để thưởng thức những bộ phim yêu thích trong khi điện thoại vẫn đang được nạp năng lượng. Phiên bản mới này đã chuyển cổng sạc USB-C sang cạnh bên, giúp bạn có thể sạc cho viên pin trong khi điện thoại vẫn đang được gắn trên giá đỡ.\r\n\r\nVề mặt an toàn, Anker trang bị công nghệ MultiProtect độc quyền, bao gồm kiểm soát nhiệt độ, bảo vệ quá tải và đo dòng điện liên tục để bảo vệ an toàn tuyệt đối cho cả viên pin và chiếc iPhone đắt giá của bạn. Lớp vỏ ngoài được hoàn thiện bằng chất liệu cao cấp, mang lại cảm giác cầm nắm mịn màng và không để lại dấu vân tay.	t	2026-03-14 15:08:57.514981+07	Anker 622 MagGo là sự kết hợp hoàn hảo giữa pin dự phòng không dây MagSafe và chân đế chống (kickstand) tiện lợi. Với thiết kế siêu mỏng chỉ 12.8mm, sản phẩm cho phép bạn vừa sạc pin vừa sử dụng iPhone một cách thoải mái bằng một tay hoặc đặt trên bàn để xem phim.	Thiết kế siêu mỏng nhẹ: Dễ dàng bỏ túi quần hoặc túi xách mà không gây cồng kềnh.\r\n\r\nChân đế đa năng: Tiện lợi cho việc rảnh tay xem video hoặc họp online.\r\n\r\nLực hút nam châm mạnh: Đảm bảo iPhone được giữ chắc chắn ngay cả khi di chuyển mạnh.\r\n\r\nCông nghệ sạc an toàn: Hệ thống kiểm soát nhiệt độ thông minh giúp thiết bị luôn mát mẻ khi sạc.\r\n\r\nTương thích hoàn hảo: Hoạt động tốt nhất với các dòng iPhone có MagSafe hoặc sử dụng ốp lưng MagSafe.	Dung lượng pin # 5000 mAh\r\n\r\nCông suất sạc không dây # 7.5W Max\r\n\r\nCổng sạc vào/ra (USB-C) # 5V=2.4A / 9V=2.22A (Max 12W khi sạc dây)\r\n\r\nKích thước # 105 x 66.5 x 12.8 mm\r\n\r\nTrọng lượng # 142g\r\n\r\nChất liệu # Nhựa PC-ABS cao cấp\r\n\r\nCông nghệ an toàn # Anker MultiProtect & Temperature Control\r\n\r\nTương thích # iPhone 12/13/14/15/16 Series		18	800000	5.00	10.00	25.00	1120000
69	31	8	Đế sạc không dây 3 trong 1 Belkin BoostCharge Pro MagSafe 15W	de-sac-khong-day-3-trong-1-belkin-boostcharge-pro-magsafe-15w	Đế sạc Belkin BoostCharge Pro 3-in-1 là phụ kiện sạc hiếm hoi đạt chứng nhận MFi (Made for iPhone) và Made for Apple Watch, cho phép sạc nhanh không dây chuẩn MagSafe lên đến 15W (thay vì 7.5W như các dòng sạc thường). Với thiết kế lấy cảm hứng từ kiến trúc hiện đại, sản phẩm sử dụng thép không gỉ cao cấp cho phần khung trụ, tạo nên vẻ ngoài cực kỳ sang trọng và chắc chắn.\r\n\r\nCấu trúc 3 vị trí sạc thông minh bao gồm: một mặt hít MagSafe lơ lửng cho iPhone, một module sạc nhanh chuyên dụng cho Apple Watch (hỗ trợ sạc nhanh từ Series 7 trở lên) và một khay sạc chuẩn Qi ở phần đế dành cho AirPods hoặc các thiết bị hỗ trợ sạc không dây khác. Nhờ lực hút từ tính mạnh mẽ, bạn có thể đặt iPhone theo chiều dọc để xem thông báo hoặc xoay ngang để sử dụng chế độ StandBy biến điện thoại thành một chiếc đồng hồ để bàn tinh tế.\r\n\r\nSản phẩm đi kèm bộ nguồn AC mạnh mẽ, đảm bảo dòng điện luôn ổn định cho cả 3 thiết bị cùng lúc mà không gây nóng máy. Đây là sự đầu tư xứng đáng cho những ai yêu thích sự gọn gàng, loại bỏ hoàn toàn mớ dây cáp lộn xộn trên bàn làm việc hay tab đầu giường.	t	2026-03-14 15:08:57.514981+07	Giải pháp sạc tất cả trong một đẳng cấp nhất từ Belkin (đối tác chiến lược của Apple). Sạc cùng lúc iPhone, Apple Watch và AirPods với công suất tối đa, thiết kế thanh lịch giúp nâng tầm không gian làm việc của bạn.	Sạc nhanh MagSafe 15W: Tốc độ sạc nhanh nhất cho iPhone từ dòng 12 trở lên.\r\n\r\nModule sạc nhanh Apple Watch: Rút ngắn thời gian sạc đáng kể cho các dòng Watch đời mới.\r\n\r\nThiết kế tối giản sang trọng: Chất liệu cao cấp chống bám vân tay, phù hợp decor mọi không gian.\r\n\r\nĐèn LED thông minh: Thông báo trạng thái sạc của AirPods mà không gây chói mắt ban đêm.\r\n\r\nAn toàn tuyệt đối: Tích hợp các cảm biến bảo vệ chống quá nhiệt, đoản mạch và vật thể lạ.	Tổng công suất # 40W (Đi kèm củ sạc rời)\r\n\r\nSạc không dây iPhone # MagSafe 15W\r\n\r\nSạc không dây Apple Watch # Sạc nhanh Magnetic Module\r\n\r\nSạc không dây AirPods # Chuẩn Qi 5W\r\n\r\nChất liệu # Thép không gỉ và Silicon cao cấp\r\n\r\nTương thích iPhone # iPhone 12/13/14/15/16 Series\r\n\r\nTương thích Watch # Mọi phiên bản Apple Watch (Sạc nhanh từ S7 trở lên)\r\n\r\nTương thích AirPods # AirPods Pro, AirPods Gen 2/3 (Wireless case)\r\n\r\nĐèn tín hiệu # LED báo trạng thái sạc Qi		24	2500000	10.00	10.00	40.00	4000000
68	29	\N	Sạc MagSafe không dây Apple	apple-magsafe-charger	Sạc MagSafe của Apple là tiêu chuẩn vàng cho trải nghiệm sạc không dây hiện đại. Điểm đột phá lớn nhất chính là hệ thống nam châm thông minh tích hợp bên trong đế sạc, giúp nó tự động "tìm" và "hít" đúng vị trí cuộn cảm trên mặt lưng iPhone (từ iPhone 12 trở lên). Điều này loại bỏ hoàn toàn tình trạng sạc không vào điện do đặt lệch vị trí – một vấn đề nhức nhối trên các bàn sạc Qi truyền thống.\r\n\r\nDù được tối ưu hóa cho MagSafe, đế sạc này vẫn duy trì khả năng tương thích ngược với chuẩn sạc Qi. Điều đó có nghĩa là bạn hoàn toàn có thể dùng nó để sạc cho AirPods (có hộp sạc không dây) hoặc các dòng iPhone cũ hơn (như iPhone 8 trở lên) giống như bất kỳ bàn sạc chuẩn Qi nào khác.\r\n\r\nSản phẩm có thiết kế cực kỳ tối giản với mặt tiếp xúc bằng đệm cao su mềm giúp bảo vệ mặt lưng kính của iPhone khỏi trầy xước, và phần vỏ ngoài bằng nhôm nguyên khối sang trọng. Cáp sạc được gắn liền với đế sạc, sử dụng đầu kết nối USB-C hiện đại, tương thích tốt nhất với củ sạc 20W của Apple để đạt hiệu suất tối ưu.	t	2026-03-14 15:08:57.514981+07	Sạc MagSafe giúp việc sạc không dây trở nên nhanh chóng và cực kỳ tiện lợi. Các nam châm được căn chỉnh hoàn hảo sẽ hít chặt vào iPhone của bạn, mang lại tốc độ sạc không dây lên đến 15W một cách ổn định nhất.	Hít nam châm tự động: Giữ iPhone chắc chắn, không lo bị xô lệch khi đang sạc.\r\n\r\nCông suất sạc 15W: Tốc độ sạc không dây nhanh gấp đôi so với các đế sạc chuẩn Qi thông thường (7.5W).\r\n\r\nTương thích rộng rãi: Sạc được cho mọi thiết bị hỗ trợ chuẩn Qi (iPhone, AirPods, Android).\r\n\r\nThiết kế mỏng nhẹ: Dễ dàng mang theo khi đi công tác hoặc du lịch.\r\n\r\nĐộ bền cao: Hoàn thiện tỉ mỉ từ vật liệu nhôm và nhựa cao cấp theo tiêu chuẩn Apple.	Kiểu kết nối đầu vào # USB-C\r\n\r\nCông suất sạc không dây # Tối đa 15W (Dành cho iPhone)\r\n\r\nCông suất sạc AirPods # Tối đa 5W\r\n\r\nChiều dài dây cáp # 1 mét\r\n\r\nChất liệu # Nhôm cao cấp & Silicon\r\n\r\nThiết bị hỗ trợ tốt nhất # iPhone 12/13/14/15/16 Series\r\n\r\nTương thích chuẩn Qi # iPhone 8 trở lên, AirPods Pro, AirPods Gen 2/3\r\n\r\nKhuyến nghị nguồn điện # Củ sạc USB-C 20W (Bán rời)		12	850000	10.00	10.00	30.00	1275000
76	42	1	Thẻ định vị Apple AirTag (1 Pack)	apple-airtag-1-pack	Apple AirTag là một giải pháp định vị đồ vật thông minh, kết hợp giữa sự tối giản đặc trưng của Apple và sức mạnh của mạng lưới hàng tỷ thiết bị toàn cầu. Dưới đây là mô tả chi tiết về các khía cạnh kỹ thuật và tính năng của sản phẩm:\r\n\r\n1. Thiết kế và Độ bền\r\nHình dáng: Có hình dạng như một chiếc cúc áo lớn, mặt trước là nhựa trắng bóng (có thể khắc nội dung), mặt sau là thép không gỉ in logo Apple.\r\n\r\nKích thước: Đường kính 31.9 mm, dày 8 mm và trọng lượng chỉ 11 gram, cực kỳ gọn nhẹ để gắn vào mọi vật dụng.\r\n\r\nChống nước và bụi: Đạt chuẩn IP67 (chống nước ở độ sâu 1 mét trong tối đa 30 phút), giúp thiết bị hoạt động ổn định ngay cả khi gặp mưa hoặc rơi vào vũng nước.\r\n\r\n2. Công nghệ kết nối và Định vị\r\nAirTag sử dụng kết hợp ba công nghệ cốt lõi để đảm bảo bạn luôn tìm thấy đồ đạc:\r\n\r\nBluetooth Low Energy (BLE): Phát tín hiệu bảo mật để các thiết bị Apple xung quanh có thể nhận diện.\r\n\r\nChip Apple U1 (Ultra Wideband): Cho phép tính năng Tìm kiếm chính xác (Precision Finding). Khi bạn ở gần, iPhone sẽ hiển thị mũi tên chỉ hướng và khoảng cách chính xác đến từng centimet (Hỗ trợ từ iPhone 11 trở lên).\r\n\r\nLoa tích hợp: Bạn có thể ra lệnh cho AirTag phát ra âm thanh thông qua ứng dụng Find My hoặc yêu cầu "Hey Siri, find my keys".\r\n\r\n3. Mạng lưới Find My (Tìm kiếm toàn cầu)\r\nĐây là điểm mạnh nhất của AirTag. Khi bạn bỏ quên ví ở quán cà phê cách xa hàng chục cây số:\r\n\r\nAirTag sẽ gửi một tín hiệu Bluetooth an toàn đến các thiết bị Apple của người lạ ở gần đó.\r\n\r\nCác thiết bị này sẽ gửi vị trí của AirTag lên iCloud của bạn.\r\n\r\nToàn bộ quá trình này đều được mã hóa đầu cuối và ẩn danh. Ngay cả Apple cũng không biết vị trí của AirTag hay danh tính của thiết bị hỗ trợ tìm kiếm.\r\n\r\n4. Tính năng Bảo mật và Riêng tư\r\nApple thiết kế AirTag để tìm đồ vật, không phải để theo dõi người:\r\n\r\nCảnh báo theo dõi không mong muốn: Nếu một AirTag của người khác bị lẫn vào đồ đạc của bạn và di chuyển cùng bạn, iPhone sẽ gửi thông báo cảnh báo. Nếu sau một thời gian bạn không tìm thấy nó, AirTag đó sẽ tự phát âm thanh để lộ diện.\r\n\r\nID Bluetooth xoay vòng: Để ngăn chặn việc bị theo dõi vị trí lịch sử, mã nhận diện Bluetooth của AirTag thay đổi thường xuyên.\r\n\r\n5. Pin và Quản lý\r\nLoại pin: Sử dụng pin đồng xu CR2032 phổ biến, có thể tự thay thế dễ dàng bằng cách xoay nắp lưng.\r\n\r\nThời lượng: Pin kéo dài hơn 1 năm với cường độ sử dụng thông thường. iPhone sẽ thông báo khi pin yếu để bạn chủ động thay thế.	t	2026-03-14 15:08:57.514981+07	Gemini đã nói\r\nApple AirTag là một thiết bị định vị thông minh nhỏ gọn, được thiết kế để giúp bạn dễ dàng tìm lại các vật dụng cá nhân như chìa khóa, ví tiền hay túi xách thông qua ứng dụng Find My trên hệ sinh thái Apple.\r\n\r\nDưới đây là những đặc điểm nổi bật của sản phẩm:\r\n\r\nThiết lập đơn giản: Chỉ cần một lần chạm để kết nối ngay lập tức với iPhone hoặc iPad.\r\n\r\nTìm kiếm chính xác (Precision Finding): Sử dụng công nghệ Ultra Wideband để dẫn đường cho bạn đến chính xác vị trí của AirTag (hỗ trợ từ iPhone 11 trở lên).\r\n\r\nMạng lưới Find My: Nếu vật dụng bị thất lạc ở xa, hàng trăm triệu thiết bị Apple trong mạng lưới sẽ giúp định vị AirTag một cách bảo mật và riêng tư.\r\n\r\nChế độ mất (Lost Mode): Tự động gửi thông báo khi AirTag được phát hiện trong mạng lưới hoặc khi có người chạm điện thoại hỗ trợ NFC vào để xem thông tin liên lạc của bạn.\r\n\r\nĐộ bền cao: Khả năng chống nước và bụi đạt chuẩn IP67, cùng viên pin đồng xu (CR2032) có thể thay thế dễ dàng và dùng được hơn một năm.	Dưới đây là 5 đặc điểm "đắt giá" nhất của sản phẩm này:\r\n\r\n1. Mạng lưới Tìm kiếm (Find My Network) khổng lồ\r\nĐây là "vũ khí" mạnh nhất của AirTag. Thay vì chỉ dựa vào Bluetooth của riêng điện thoại bạn (vốn chỉ có phạm vi khoảng 10-20m), AirTag tận dụng hàng tỷ thiết bị Apple (iPhone, iPad, Mac) trên toàn cầu.\r\n\r\nKhi bạn mất đồ ở một thành phố khác, chỉ cần một người dùng iPhone bất kỳ đi ngang qua món đồ đó, vị trí của AirTag sẽ được cập nhật âm thầm về máy bạn.\r\n\r\n2. Tìm kiếm chính xác (Precision Finding)\r\nNhờ con chip U1 (Ultra Wideband), AirTag không chỉ báo "đồ vật đang ở quanh đây" mà còn dẫn đường cho bạn như một chiếc la bàn:\r\n\r\nMàn hình iPhone sẽ hiển thị mũi tên chỉ hướng và khoảng cách cụ thể (ví dụ: "bên trái 2 mét").\r\n\r\nTính năng này cực kỳ hữu ích khi chìa khóa bị rơi vào kẽ sofa hoặc dưới gầm giường mà chuông báo khó nghe thấy.\r\n\r\n3. Quyền riêng tư và Bảo mật tuyệt đối\r\nApple thiết kế AirTag với triết lý "tìm đồ, không tìm người":\r\n\r\nMã hóa đầu cuối: Ngay cả Apple cũng không biết vị trí AirTag của bạn hay thiết bị nào đã giúp bạn tìm thấy nó.\r\n\r\nChống theo dõi lén: Nếu ai đó bí mật bỏ AirTag vào túi của bạn, iPhone sẽ phát hiện có một vật lạ đang di chuyển cùng bạn và gửi cảnh báo ngay lập tức. Sau một khoảng thời gian, AirTag đó cũng sẽ tự phát ra tiếng kêu để lộ diện.\r\n\r\n4. Chế độ Mất (Lost Mode) thông minh\r\nTương tự như iPhone, bạn có thể kích hoạt Lost Mode cho AirTag:\r\n\r\nNếu ai đó nhặt được, họ có thể chạm mặt sau điện thoại của họ (iPhone hoặc Android có NFC) vào AirTag để hiển thị số điện thoại hoặc thông tin liên lạc mà bạn đã cài đặt trước đó.\r\n\r\n5. Thiết kế "Vô tư" khi sử dụng\r\nSự tiện lợi đến từ những chi tiết nhỏ nhưng thực tế:\r\n\r\nPin thay thế được: Sử dụng pin CR2032 cực kỳ dễ mua ở bất kỳ cửa hàng điện nước hay đồng hồ nào (dùng được khoảng 1 năm).\r\n\r\nKết nối "Một chạm": Chỉ cần đưa AirTag lại gần iPhone, máy sẽ tự nhận diện và kết nối ngay lập tức (giống như AirPods).\r\n\r\nChống nước IP67: Bạn không cần lo lắng nếu móc khóa bị dính mưa hay vô tình rơi vào vũng nước.	Danh mục # Thiết bị định vị thông minh\r\nThương hiệu # Apple\r\nModel # MX532 (1 Pack)\r\nĐường kính # 31.9 mm\r\nĐộ dày # 8.0 mm\r\nTrọng lượng # 11 gram\r\nChống nước và bụi # Chuẩn IP67 (độ sâu tối đa 1 mét trong 30 phút)\r\nKết nối # Bluetooth LE, Chip U1 (Ultra Wideband), NFC\r\nLoa # Loa tích hợp phát âm thanh báo hiệu\r\nLoại pin # Pin đồng xu CR2032 (có thể thay thế)\r\nThời lượng pin # Hơn 1 năm (tùy cường độ sử dụng)\r\nTính năng tìm kiếm # Tìm kiếm chính xác (Precision Finding), Mạng lưới Find My\r\nTính năng bảo mật # Cảnh báo theo dõi không mong muốn, Chế độ mất (Lost Mode)\r\nYêu cầu hệ thống # iPhone/iPad chạy iOS/iPadOS 14.5 trở lên\r\nNhiệt độ hoạt động # -20°C đến 60°C		12	650000	10.00	10.00	35.00	1007500
\.


--
-- Data for Name: rate_limits; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rate_limits (key, hits, window_start, blocked_until, updated_at) FROM stdin;
login:c43a43213cff14e40aee60ed5d735a56ba762b0e	1	2026-04-05 21:13:19+07	\N	2026-04-05 21:13:19.718505+07
login:f584282d9ebe139535bf1700dc6421a38a99e20f	1	2026-04-05 21:13:22+07	\N	2026-04-05 21:13:22.137308+07
login:3aa2ceafee33338592d9747106a3d63817d991f0	1	2026-04-05 21:13:25+07	\N	2026-04-05 21:13:25.949273+07
login:43c9df729c9060605ac05b94167f0427b6d178cc	1	2026-04-05 21:13:29+07	\N	2026-04-05 21:13:29.898277+07
login:1bbd877cb40dedf01ff6def98bc7148d416bfd75	1	2026-04-05 21:13:33+07	\N	2026-04-05 21:13:33.83489+07
login:fda4afc34bcc75bee4460e69e785761bf7fb1437	1	2026-04-05 21:13:37+07	\N	2026-04-05 21:13:37.417261+07
login:69eaf68e7cb85803ee4b516868b10a506e6f8f93	1	2026-04-05 21:13:40+07	\N	2026-04-05 21:13:40.564914+07
login:80a713d2783f9a2f0737da9222f2f529410c70c0	1	2026-04-05 21:13:48+07	\N	2026-04-05 21:13:48.21521+07
login:f470e58742d6c863793e23835dbd6489c19b602e	1	2026-04-05 21:13:51+07	\N	2026-04-05 21:13:51.444762+07
login:e03cc729ad127d479ed9ac4d9682d28c514aa031	1	2026-04-05 21:13:54+07	\N	2026-04-05 21:13:54.923683+07
login:29fa0ffcb59ad99be0f3e0ef1d35b6188f96e325	2	2026-04-05 21:13:44+07	\N	2026-04-05 21:13:59.001005+07
login:7fb378591936e0cb4f4454e0552d4861249245f4	1	2026-04-05 21:14:01+07	\N	2026-04-05 21:14:01.852835+07
login:106c2277caced93566f3025a850f491bcc15689f	1	2026-04-05 21:14:05+07	\N	2026-04-05 21:14:05.748826+07
login:c64a6d2cf36bbe91be32aca72146864d58ecdd6a	1	2026-04-05 21:14:08+07	\N	2026-04-05 21:14:08.768656+07
login:71e0d384c47bbff5d1faaba4728af4bf6258459d	6	2026-04-05 21:19:38+07	2026-04-05 21:35:10+07	2026-04-05 21:20:10.373586+07
login-ip:4b84b15bff6ee5796152495a230e45e3d7e947d9	2	2026-04-06 12:08:22+07	\N	2026-04-06 12:12:23.925226+07
login:f795b68bfc1fa048f36d2e73f5fbd874850e84f4	2	2026-04-06 12:08:22+07	\N	2026-04-06 12:12:23.938962+07
\.


--
-- Data for Name: reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reviews (id, product_id, user_id, rating, comment, status, created_at) FROM stdin;
8	76	2	5	hay	visible	2026-03-21 19:20:34.598204+07
9	27	6	5	mmm	visible	2026-04-03 19:28:46.884119+07
\.


--
-- Data for Name: role_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_permissions (id, role_id, permission_id) FROM stdin;
12	3	2
13	3	3
14	3	5
15	3	6
16	3	7
17	3	10
18	3	13
23	4	9
24	4	11
25	4	12
26	4	13
27	5	8
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, code, name, is_system, created_at) FROM stdin;
1	admin	Admin	t	2026-01-08 22:43:15.505768+07
2	customer	Khách hàng	t	2026-01-08 22:43:15.505768+07
3	staff_sales	Nhân viên bán hàng	t	2026-01-08 22:43:15.505768+07
4	staff_cs	Nhân viên CSKH	t	2026-01-08 22:43:15.505768+07
5	staff_warehouse	Nhân viên kho	t	2026-01-08 22:43:15.505768+07
6	nhn_vin_bo_hng	Nhân viên bảo hàng	f	2026-04-04 15:01:48.42928+07
\.


--
-- Data for Name: user_vouchers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_vouchers (id, user_id, voucher_id, used, created_at) FROM stdin;
4	2	4	t	2026-03-14 20:10:32.105221
5	2	5	t	2026-03-14 20:23:26.354051
6	1	4	t	2026-03-20 18:33:59.942074
8	2	6	t	2026-04-04 15:46:09.937515
9	2	7	f	2026-04-04 21:02:12.939212
7	1	5	t	2026-03-29 16:18:29.174891
10	1	7	f	2026-04-04 22:01:21.025928
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, role_id, email, password_hash, status, created_at, avatar_url, google_avatar_url) FROM stdin;
2	1	admin@techgear.local	$2y$12$GVH/nqvkLL8FyxsQEVhwRu9TiM7FYC0izrMozQvKf9FEDTYZUYZw6	active	2026-03-08 22:31:43.764918+07	/uploads/avatars/user_2_bd54c418315f2501.jpg	\N
6	2	nguyenkhoi29112005@gmail.com	$2y$12$H33XR4QTQErkW3PcfCyYr.6J/djMwakGcrP8fM577Ces.63/XKj9C	active	2026-04-03 19:26:57.987303+07	\N	https://lh3.googleusercontent.com/a/ACg8ocLJvXjnRmdAGwwiiJTiLcZTERPCGmtxliTH_FLvWFZTTqjubVg=s96-c
4	3	tub2306648@student.ctu.edu.vn	$2y$12$umAngz4R0PYpvHVBcnZfMuULga/Kauj6KphYZd55RItKdPS5XXmaG	banned	2026-03-29 16:27:52.944108+07	\N	\N
5	3	tututu@gmail.com	$2y$12$T4oDrlDouJiCt7tsfKEnNejkPXAFvIKH4K4ySUtr57vhAzJN/fSA6	active	2026-04-03 18:51:56.166198+07	\N	\N
3	4	tub2306648@gmail.com	$2y$12$vnvICI6N1pZ2TzJMSsi2..jrYTA9j934l5fjdOB.7OzsFfxTgMy/S	active	2026-03-28 17:35:09.840478+07	\N	https://lh3.googleusercontent.com/a/ACg8ocIA-d0NpfNUGH2rCr6zaELCFw6TqsfdIx4PgA7hoMvRTKM11g=s96-c
1	2	tututu7444@gmail.com	$2y$12$AaAqZoLoQEf/QaYKrj1cqe2xbyVYSXUI0UGEl9oF5ewQNdbPFGHDC	active	2026-03-08 22:18:00.16866+07	\N	https://lh3.googleusercontent.com/a/ACg8ocKJM6UNEzMeMVeck29AwOca31uSXmKAeILEItKJ1VEcc0hWPw=s96-c
7	2	tututu74442@gmail.com	$2y$12$/6oi8i9WcmRf.Zu7Rmx5BOktMpjTCCc.hIe1c9/o2pR2aE517etku	active	2026-04-05 21:12:33.198879+07	\N	\N
\.


--
-- Data for Name: variant_option_values; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.variant_option_values (id, variant_id, option_value_id) FROM stdin;
\.


--
-- Data for Name: vouchers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vouchers (id, name, code, discount_amount, start_date, end_date, quantity, status, created_at, apply_category_id, customer_type) FROM stdin;
4	Lê Hoàng Tú	MB123	50000	2026-03-14	2026-04-13	98	active	2026-03-14 20:10:15.911199	\N	all
5	6768767	TUHOANG7444	50000	2026-03-14	2026-04-13	98	active	2026-03-14 20:23:16.280321	\N	all
6	Lê Hoàng Tú 3	VB67	50000	2026-04-04	2026-05-04	99	active	2026-04-04 15:45:49.87684	28	all
7	hihih	HIHI	50000	2026-04-04	2026-05-04	98	active	2026-04-04 21:02:01.380733	\N	vip
\.


--
-- Name: banners_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.banners_id_seq', 11, true);


--
-- Name: brands_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.brands_id_seq', 9, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categories_id_seq', 45, true);


--
-- Name: contacts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.contacts_id_seq', 6, true);


--
-- Name: coupons_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.coupons_id_seq', 2, true);


--
-- Name: customer_profiles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.customer_profiles_id_seq', 18, true);


--
-- Name: employee_profiles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.employee_profiles_id_seq', 1, false);


--
-- Name: inventory_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.inventory_logs_id_seq', 53, true);


--
-- Name: newsletter_subscribers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.newsletter_subscribers_id_seq', 1, true);


--
-- Name: option_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.option_types_id_seq', 1, true);


--
-- Name: option_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.option_values_id_seq', 2, true);


--
-- Name: order_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_addresses_id_seq', 58, true);


--
-- Name: order_approvals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_approvals_id_seq', 48, true);


--
-- Name: order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_items_id_seq', 61, true);


--
-- Name: order_status_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_status_history_id_seq', 44, true);


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.orders_id_seq', 59, true);


--
-- Name: password_reset_otps_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_reset_otps_id_seq', 31, true);


--
-- Name: payment_methods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payment_methods_id_seq', 2, true);


--
-- Name: payments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payments_id_seq', 30, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 3375, true);


--
-- Name: post_related_products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.post_related_products_id_seq', 21, true);


--
-- Name: posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.posts_id_seq', 3, true);


--
-- Name: pricing_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pricing_settings_id_seq', 1, true);


--
-- Name: product_discount_campaigns_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_discount_campaigns_id_seq', 7, true);


--
-- Name: product_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_images_id_seq', 39, true);


--
-- Name: product_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_variants_id_seq', 27, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.products_id_seq', 76, true);


--
-- Name: reviews_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reviews_id_seq', 9, true);


--
-- Name: role_permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.role_permissions_id_seq', 27, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 6, true);


--
-- Name: user_vouchers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_vouchers_id_seq', 10, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 7, true);


--
-- Name: variant_option_values_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.variant_option_values_id_seq', 2, true);


--
-- Name: vouchers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vouchers_id_seq', 7, true);


--
-- Name: banners banners_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.banners
    ADD CONSTRAINT banners_pkey PRIMARY KEY (id);


--
-- Name: brands brands_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_name_key UNIQUE (name);


--
-- Name: brands brands_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_pkey PRIMARY KEY (id);


--
-- Name: brands brands_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_slug_key UNIQUE (slug);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_key UNIQUE (slug);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id);


--
-- Name: coupons coupons_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_code_key UNIQUE (code);


--
-- Name: coupons coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_pkey PRIMARY KEY (id);


--
-- Name: customer_profiles customer_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_profiles
    ADD CONSTRAINT customer_profiles_pkey PRIMARY KEY (id);


--
-- Name: customer_profiles customer_profiles_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_profiles
    ADD CONSTRAINT customer_profiles_user_id_key UNIQUE (user_id);


--
-- Name: employee_profiles employee_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_profiles
    ADD CONSTRAINT employee_profiles_pkey PRIMARY KEY (id);


--
-- Name: employee_profiles employee_profiles_user_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_profiles
    ADD CONSTRAINT employee_profiles_user_id_key UNIQUE (user_id);


--
-- Name: inventory_logs inventory_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory_logs
    ADD CONSTRAINT inventory_logs_pkey PRIMARY KEY (id);


--
-- Name: newsletter_subscribers newsletter_subscribers_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.newsletter_subscribers
    ADD CONSTRAINT newsletter_subscribers_email_key UNIQUE (email);


--
-- Name: newsletter_subscribers newsletter_subscribers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.newsletter_subscribers
    ADD CONSTRAINT newsletter_subscribers_pkey PRIMARY KEY (id);


--
-- Name: option_types option_types_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_types
    ADD CONSTRAINT option_types_code_key UNIQUE (code);


--
-- Name: option_types option_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_types
    ADD CONSTRAINT option_types_pkey PRIMARY KEY (id);


--
-- Name: option_values option_values_option_type_id_value_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_values
    ADD CONSTRAINT option_values_option_type_id_value_key UNIQUE (option_type_id, value);


--
-- Name: option_values option_values_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_values
    ADD CONSTRAINT option_values_pkey PRIMARY KEY (id);


--
-- Name: order_addresses order_addresses_order_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_addresses
    ADD CONSTRAINT order_addresses_order_id_key UNIQUE (order_id);


--
-- Name: order_addresses order_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_addresses
    ADD CONSTRAINT order_addresses_pkey PRIMARY KEY (id);


--
-- Name: order_approvals order_approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_approvals
    ADD CONSTRAINT order_approvals_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: order_status_history order_status_history_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history
    ADD CONSTRAINT order_status_history_pkey PRIMARY KEY (id);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: password_reset_otps password_reset_otps_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_otps
    ADD CONSTRAINT password_reset_otps_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_code_key UNIQUE (code);


--
-- Name: payment_methods payment_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_code_key UNIQUE (code);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: post_related_products post_related_products_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post_related_products
    ADD CONSTRAINT post_related_products_pkey PRIMARY KEY (id);


--
-- Name: post_related_products post_related_products_post_id_product_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post_related_products
    ADD CONSTRAINT post_related_products_post_id_product_id_key UNIQUE (post_id, product_id);


--
-- Name: posts posts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_pkey PRIMARY KEY (id);


--
-- Name: posts posts_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_slug_key UNIQUE (slug);


--
-- Name: pricing_settings pricing_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pricing_settings
    ADD CONSTRAINT pricing_settings_pkey PRIMARY KEY (id);


--
-- Name: product_discount_campaigns product_discount_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_discount_campaigns
    ADD CONSTRAINT product_discount_campaigns_pkey PRIMARY KEY (id);


--
-- Name: product_images product_images_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_product_id_combination_key_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_combination_key_key UNIQUE (product_id, combination_key);


--
-- Name: product_variants product_variants_sku_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_sku_key UNIQUE (sku);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_slug_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_slug_key UNIQUE (slug);


--
-- Name: rate_limits rate_limits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rate_limits
    ADD CONSTRAINT rate_limits_pkey PRIMARY KEY (key);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_role_id_permission_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_permission_id_key UNIQUE (role_id, permission_id);


--
-- Name: roles roles_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_code_key UNIQUE (code);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: user_vouchers user_vouchers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_vouchers
    ADD CONSTRAINT user_vouchers_pkey PRIMARY KEY (id);


--
-- Name: user_vouchers user_vouchers_user_id_voucher_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_vouchers
    ADD CONSTRAINT user_vouchers_user_id_voucher_id_key UNIQUE (user_id, voucher_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: variant_option_values variant_option_values_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_option_values
    ADD CONSTRAINT variant_option_values_pkey PRIMARY KEY (id);


--
-- Name: variant_option_values variant_option_values_variant_id_option_value_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_option_values
    ADD CONSTRAINT variant_option_values_variant_id_option_value_id_key UNIQUE (variant_id, option_value_id);


--
-- Name: vouchers vouchers_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_code_key UNIQUE (code);


--
-- Name: vouchers vouchers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_pkey PRIMARY KEY (id);


--
-- Name: idx_banners_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_banners_created_at ON public.banners USING btree (created_at DESC);


--
-- Name: idx_banners_position; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_banners_position ON public.banners USING btree ("position");


--
-- Name: idx_banners_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_banners_status ON public.banners USING btree (status);


--
-- Name: idx_categories_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_categories_status ON public.categories USING btree (status);


--
-- Name: idx_contacts_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_contacts_created_at ON public.contacts USING btree (created_at DESC);


--
-- Name: idx_contacts_handled; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_contacts_handled ON public.contacts USING btree (is_handled);


--
-- Name: idx_coupons_code; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_coupons_code ON public.coupons USING btree (code);


--
-- Name: idx_coupons_end_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_coupons_end_date ON public.coupons USING btree (end_date);


--
-- Name: idx_coupons_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_coupons_status ON public.coupons USING btree (status);


--
-- Name: idx_inventory_logs_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_inventory_logs_created_at ON public.inventory_logs USING btree (created_at DESC);


--
-- Name: idx_inventory_logs_product_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_inventory_logs_product_id ON public.inventory_logs USING btree (product_id);


--
-- Name: idx_inventory_logs_type; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_inventory_logs_type ON public.inventory_logs USING btree (type);


--
-- Name: idx_newsletter_subscribers_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_newsletter_subscribers_status ON public.newsletter_subscribers USING btree (status);


--
-- Name: idx_newsletter_subscribers_subscribed_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_newsletter_subscribers_subscribed_at ON public.newsletter_subscribers USING btree (subscribed_at DESC);


--
-- Name: idx_order_approvals_decision; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_approvals_decision ON public.order_approvals USING btree (decision);


--
-- Name: idx_order_approvals_order_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_approvals_order_id ON public.order_approvals USING btree (order_id);


--
-- Name: idx_order_items_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_items_created_at ON public.order_items USING btree (created_at);


--
-- Name: idx_order_items_order_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_items_order_id ON public.order_items USING btree (order_id);


--
-- Name: idx_order_items_product_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_items_product_id ON public.order_items USING btree (product_id);


--
-- Name: idx_order_items_variant_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_items_variant_id ON public.order_items USING btree (variant_id);


--
-- Name: idx_order_status_history_order; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_order_status_history_order ON public.order_status_history USING btree (order_id);


--
-- Name: idx_orders_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_status ON public.orders USING btree (status);


--
-- Name: idx_orders_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_user_id ON public.orders USING btree (user_id);


--
-- Name: idx_password_reset_otps_email; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_password_reset_otps_email ON public.password_reset_otps USING btree (email, created_at DESC);


--
-- Name: idx_payments_order_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_payments_order_id ON public.payments USING btree (order_id);


--
-- Name: idx_payments_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_payments_status ON public.payments USING btree (status);


--
-- Name: idx_pdc_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pdc_product ON public.product_discount_campaigns USING btree (product_id);


--
-- Name: idx_pdc_status_time; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pdc_status_time ON public.product_discount_campaigns USING btree (status, start_at, end_at);


--
-- Name: idx_post_related_products_post; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_post_related_products_post ON public.post_related_products USING btree (post_id, sort_order, id);


--
-- Name: idx_post_related_products_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_post_related_products_product ON public.post_related_products USING btree (product_id);


--
-- Name: idx_posts_published_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_posts_published_at ON public.posts USING btree (published_at DESC);


--
-- Name: idx_posts_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_posts_status ON public.posts USING btree (status);


--
-- Name: idx_product_images_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_product_images_product ON public.product_images USING btree (product_id);


--
-- Name: idx_products_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_active ON public.products USING btree (is_active);


--
-- Name: idx_products_brand_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_brand_id ON public.products USING btree (brand_id);


--
-- Name: idx_products_category_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_category_id ON public.products USING btree (category_id);


--
-- Name: idx_products_price; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_price ON public.products USING btree (price);


--
-- Name: idx_reviews_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reviews_created_at ON public.reviews USING btree (created_at DESC);


--
-- Name: idx_reviews_product_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reviews_product_id ON public.reviews USING btree (product_id);


--
-- Name: idx_reviews_rating; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reviews_rating ON public.reviews USING btree (rating);


--
-- Name: idx_reviews_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reviews_status ON public.reviews USING btree (status);


--
-- Name: idx_reviews_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_reviews_user_id ON public.reviews USING btree (user_id);


--
-- Name: idx_user_vouchers_used; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_vouchers_used ON public.user_vouchers USING btree (used);


--
-- Name: idx_user_vouchers_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_vouchers_user ON public.user_vouchers USING btree (user_id);


--
-- Name: idx_user_vouchers_voucher; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_vouchers_voucher ON public.user_vouchers USING btree (voucher_id);


--
-- Name: idx_users_role_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_users_role_id ON public.users USING btree (role_id);


--
-- Name: idx_users_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_users_status ON public.users USING btree (status);


--
-- Name: idx_variant_option_values_variant; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_variant_option_values_variant ON public.variant_option_values USING btree (variant_id);


--
-- Name: idx_variants_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_variants_active ON public.product_variants USING btree (is_active);


--
-- Name: idx_variants_product_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_variants_product_id ON public.product_variants USING btree (product_id);


--
-- Name: idx_vouchers_code; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vouchers_code ON public.vouchers USING btree (code);


--
-- Name: idx_vouchers_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vouchers_date ON public.vouchers USING btree (start_date, end_date);


--
-- Name: idx_vouchers_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vouchers_status ON public.vouchers USING btree (status);


--
-- Name: customer_profiles customer_profiles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_profiles
    ADD CONSTRAINT customer_profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: employee_profiles employee_profiles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.employee_profiles
    ADD CONSTRAINT employee_profiles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: categories fk_categories_parent; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: inventory_logs inventory_logs_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory_logs
    ADD CONSTRAINT inventory_logs_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: option_values option_values_option_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.option_values
    ADD CONSTRAINT option_values_option_type_id_fkey FOREIGN KEY (option_type_id) REFERENCES public.option_types(id) ON DELETE CASCADE;


--
-- Name: order_addresses order_addresses_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_addresses
    ADD CONSTRAINT order_addresses_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_approvals order_approvals_decided_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_approvals
    ADD CONSTRAINT order_approvals_decided_by_fkey FOREIGN KEY (decided_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_approvals order_approvals_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_approvals
    ADD CONSTRAINT order_approvals_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_approvals order_approvals_requested_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_approvals
    ADD CONSTRAINT order_approvals_requested_by_fkey FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: order_items order_items_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: order_status_history order_status_history_changed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history
    ADD CONSTRAINT order_status_history_changed_by_fkey FOREIGN KEY (changed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_status_history order_status_history_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history
    ADD CONSTRAINT order_status_history_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: password_reset_otps password_reset_otps_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_otps
    ADD CONSTRAINT password_reset_otps_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payments payments_method_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_method_id_fkey FOREIGN KEY (method_id) REFERENCES public.payment_methods(id) ON DELETE RESTRICT;


--
-- Name: payments payments_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: post_related_products post_related_products_post_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post_related_products
    ADD CONSTRAINT post_related_products_post_id_fkey FOREIGN KEY (post_id) REFERENCES public.posts(id) ON DELETE CASCADE;


--
-- Name: post_related_products post_related_products_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post_related_products
    ADD CONSTRAINT post_related_products_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_discount_campaigns product_discount_campaigns_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_discount_campaigns
    ADD CONSTRAINT product_discount_campaigns_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_images product_images_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_variants product_variants_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_brand_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_brand_id_fkey FOREIGN KEY (brand_id) REFERENCES public.brands(id) ON DELETE SET NULL;


--
-- Name: products products_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: reviews reviews_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_vouchers user_vouchers_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_vouchers
    ADD CONSTRAINT user_vouchers_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_vouchers user_vouchers_voucher_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_vouchers
    ADD CONSTRAINT user_vouchers_voucher_id_fkey FOREIGN KEY (voucher_id) REFERENCES public.vouchers(id) ON DELETE CASCADE;


--
-- Name: users users_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE RESTRICT;


--
-- Name: variant_option_values variant_option_values_option_value_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_option_values
    ADD CONSTRAINT variant_option_values_option_value_id_fkey FOREIGN KEY (option_value_id) REFERENCES public.option_values(id) ON DELETE RESTRICT;


--
-- Name: variant_option_values variant_option_values_variant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_option_values
    ADD CONSTRAINT variant_option_values_variant_id_fkey FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict BfkJAJ0Uuo7Jd9ocJ7o62sJOdtyTuAesf1Rru1SukwEr3v3vgBQzTG7SVrcubuf

