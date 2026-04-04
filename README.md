# Webphukiencongnghe

## Environment variables

Before running the app, set these variables in your shell, web server, or process manager.

### Database

- `DB_DRIVER` (default: `pgsql`)
- `DB_HOST` (default: `127.0.0.1`)
- `DB_PORT` (default: `5432`)
- `DB_DATABASE` (default: `phukien`)
- `DB_USERNAME` (default: `postgres`)
- `DB_PASSWORD` (required in real environments)

### Facebook login (optional)

- `FB_CLIENT_ID`
- `FB_CLIENT_SECRET`
- `FB_REDIRECT_URI` (default: `http://localhost/auth/facebook/callback`)

If Facebook variables are not set, the app will keep running and show a friendly warning when users try Facebook login.

### Gemini chat (optional)

- `GEMINI_API_KEY`
- `GEMINI_MODEL` (default recommended: `gemini-2.5-flash`)

### OTP email (forgot password)

- `MAIL_HOST` (example: `smtp.gmail.com`)
- `MAIL_PORT` (default recommended: `587`)
- `MAIL_USERNAME` (SMTP account)
- `MAIL_PASSWORD` (SMTP app password)
- `MAIL_ENCRYPTION` (`tls` or `ssl`, default behavior uses `tls`)
- `MAIL_FROM_ADDRESS` (example: `no-reply@your-domain.com`)
- `MAIL_FROM_NAME` (example: `TechGear`)

You can define these in a local `.env` file at project root.