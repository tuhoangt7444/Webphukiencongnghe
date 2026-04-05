# Webphukiencongnghe

Website bán phụ kiện công nghệ xây bằng PHP MVC thuần, dùng PostgreSQL và chạy sau Nginx. Dự án có đầy đủ các phân hệ chính để demo đồ án: trang chủ, sản phẩm, giỏ hàng, thanh toán, tài khoản người dùng, quản trị, báo cáo, voucher, tồn kho, đánh giá, bài viết, liên hệ, newsletter, đăng nhập Google và quên mật khẩu bằng OTP email.

## 1. Yêu cầu hệ thống

Trước khi chạy dự án, hãy chuẩn bị:

- PHP 8.1 hoặc mới hơn.
- Composer.
- Nginx.
- PostgreSQL 14 hoặc mới hơn.
- PHP extensions: `pdo_pgsql`, `mbstring`, `openssl`, `json`, `curl`, `gd`, `zip`, `fileinfo`.

Nếu bạn chạy trên Windows, có thể dùng bộ cài PHP/Nginx/PostgreSQL riêng hoặc XAMPP/Laragon kết hợp PostgreSQL độc lập. Nếu chạy trên Linux/WSL, các bước bên dưới có thể dùng gần như nguyên văn.

## 2. Cấu trúc dự án

- `app/Controllers`: controller cho người dùng và quản trị.
- `app/Models`: truy vấn dữ liệu và nghiệp vụ.
- `app/Core`: router, request/response, database, view.
- `app/Views`: giao diện frontend và admin.
- `config`: cấu hình ứng dụng, database, social login.
- `public`: document root của web server.
- `backup_full.sql`: dữ liệu mẫu đầy đủ để import nhanh.

## 3. Cài đặt mã nguồn

1. Giải nén hoặc clone dự án vào thư mục làm việc.
2. Mở terminal tại thư mục gốc của dự án.
3. Cài thư viện PHP bằng Composer:

```bash
composer install
```

4. Tạo file môi trường `.env` từ `.env.example`:

```bash
copy .env.example .env
```

Trên Linux hoặc WSL, dùng:

```bash
cp .env.example .env
```

## 4. Cấu hình file `.env`

Mở `.env` và điền tối thiểu các giá trị sau:

```env
APP_URL="http://localhost"

DB_DRIVER="pgsql"
DB_HOST="127.0.0.1"
DB_PORT="5432"
DB_DATABASE="phukien"
DB_USERNAME="postgres"
DB_PASSWORD="your_database_password"

GEMINI_API_KEY=""
GEMINI_MODEL="gemini-2.5-flash"

GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_REDIRECT_URI="http://localhost/auth/google/callback"

MAIL_HOST="smtp.gmail.com"
MAIL_PORT="587"
MAIL_USERNAME="your_email@gmail.com"
MAIL_PASSWORD="your_app_password"
MAIL_ENCRYPTION="tls"
MAIL_FROM_ADDRESS="your_email@gmail.com"
MAIL_FROM_NAME="TechGear"
```

Ghi chú:

- `DB_PASSWORD` là bắt buộc trong môi trường thật.
- Nếu chưa dùng Google login, Gemini chat hoặc OTP email, bạn có thể để trống các biến liên quan.
- `APP_URL` phải khớp với domain hoặc localhost mà Nginx đang phục vụ.

## 5. Thiết lập PostgreSQL

### 5.1 Tạo database và user

Đăng nhập PostgreSQL bằng tài khoản quản trị rồi chạy:

```sql
CREATE DATABASE phukien;
CREATE USER phukien_user WITH PASSWORD 'phukien_password';
GRANT ALL PRIVILEGES ON DATABASE phukien TO phukien_user;
```

Sau đó cập nhật `.env` cho khớp:

```env
DB_DATABASE="phukien"
DB_USERNAME="phukien_user"
DB_PASSWORD="phukien_password"
```

Nếu bạn muốn dùng user `postgres` thì vẫn được, nhưng không nên dùng mật khẩu mặc định trong môi trường triển khai thật.

### 5.2 Import dữ liệu mẫu

Dự án có sẵn file backup đầy đủ: `backup_full.sql`.

Trên Windows, chạy:

```bash
psql -U postgres -d phukien -f backup_full.sql
```

Trên Linux hoặc WSL, chạy tương tự:

```bash
psql -U postgres -d phukien -f backup_full.sql
```

Nếu lệnh `psql` chưa có trong PATH, hãy cài PostgreSQL client tools hoặc dùng pgAdmin để restore file SQL này.

### 5.3 Tài khoản admin mẫu để chạy thử

Sau khi import xong, chạy đoạn SQL sau để đảm bảo có một tài khoản quản trị dùng ngay:

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

UPDATE users
SET password_hash = crypt('Admin@123456', gen_salt('bf', 12)),
	status = 'active'
WHERE email = 'admin@techgear.local';
```

Thông tin đăng nhập mẫu:

- Email: `admin@techgear.local`
- Mật khẩu: `Admin@123456`

Đây là tài khoản để thầy cô mở web và kiểm tra nhanh khu vực admin.

## 6. Cấu hình Nginx

### 6.1 Nguyên tắc cấu hình

Web root của dự án phải trỏ vào thư mục `public`, không trỏ vào thư mục gốc dự án. Đây là điểm quan trọng nhất vì toàn bộ entry point nằm ở `public/index.php`.

### 6.2 Ví dụ cấu hình Nginx trên Windows

Mở file cấu hình site của Nginx và thêm server block như sau:

```nginx
server {
	listen 80;
	server_name localhost;

	root C:/Wednginx/nginx-1.28.0/html/Webphukiencongnghe/public;
	index index.php index.html;

	charset utf-8;

	location / {
		try_files $uri $uri/ /index.php?$query_string;
	}

	location ~ \.php$ {
		include fastcgi_params;
		fastcgi_pass 127.0.0.1:9000;
		fastcgi_index index.php;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	}

	location ~ /\.(?!well-known).* {
		deny all;
	}
}
```

Lưu ý:

- Nếu PHP-FPM của bạn không chạy ở `127.0.0.1:9000`, hãy đổi `fastcgi_pass` theo môi trường thực tế.
- Nếu bạn dùng Linux, đổi `root` thành đường dẫn kiểu `/var/www/Webphukiencongnghe/public`.
- Sau khi sửa cấu hình, nhớ reload Nginx.

### 6.3 Ví dụ cấu hình Nginx trên Linux/WSL

```nginx
server {
	listen 80;
	server_name localhost;

	root /var/www/Webphukiencongnghe/public;
	index index.php index.html;

	charset utf-8;

	location / {
		try_files $uri $uri/ /index.php?$query_string;
	}

	location ~ \.php$ {
		include fastcgi_params;
		fastcgi_pass unix:/run/php/php8.2-fpm.sock;
		fastcgi_index index.php;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	}

	location ~ /\.(?!well-known).* {
		deny all;
	}
}
```

## 7. Chạy ứng dụng

Sau khi đã cài Composer, cấu hình `.env`, import database và bật Nginx, mở trình duyệt tại:

```text
http://localhost
```

Nếu bạn đổi port hoặc domain trong Nginx thì cập nhật theo đúng `server_name` và `APP_URL`.

## 8. Kiểm tra nhanh sau khi chạy

1. Vào trang chủ để xem banner, sản phẩm, voucher và bài viết.
2. Vào trang sản phẩm để kiểm tra lọc và chi tiết sản phẩm.
3. Thử đăng nhập bằng tài khoản admin mẫu ở trên.
4. Vào khu vực admin để kiểm tra dashboard, sản phẩm, đơn hàng, tồn kho, voucher, review, báo cáo và phân quyền.
5. Thử form liên hệ, newsletter và quên mật khẩu bằng OTP nếu đã cấu hình mail.

## 9. Các cấu hình tùy chọn

### 9.1 Google login

Điền `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` trong `.env`. Sau đó cấu hình redirect URI trong Google Cloud Console trùng chính xác với giá trị này.

### 9.2 Gemini chat

Điền `GEMINI_API_KEY` nếu muốn bật chatbot tư vấn sản phẩm.

### 9.3 OTP email

Điền các biến `MAIL_*` nếu muốn bật chức năng quên mật khẩu qua email.

## 10. Lưu ý triển khai

- Không public thư mục gốc dự án, chỉ public thư mục `public`.
- Không để file `.env` lên repository.
- Sau khi import `backup_full.sql`, hãy kiểm tra lại quyền truy cập admin và trạng thái user.
- Nếu đổi cấu trúc thư mục, cần cập nhật lại `root` trong Nginx và `APP_URL` trong `.env`.

## 11. Tài khoản chạy thử nhanh

- Admin: `admin@techgear.local`
- Mật khẩu: `Admin@123456`

## 12. Hỗ trợ nhanh

Nếu không vào được trang:

- Kiểm tra Nginx đã trỏ vào `public` chưa.
- Kiểm tra PostgreSQL có import đủ dữ liệu chưa.
- Kiểm tra `.env` và quyền truy cập database.
- Kiểm tra PHP extension `pdo_pgsql` đã bật chưa.

Nếu đăng nhập admin không được:

- Chạy lại đoạn SQL reset mật khẩu ở phần 5.3.
- Xác nhận tài khoản có `status = 'active'`.