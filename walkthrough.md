# Walkthrough - Login CSRF Token Fix

This walkthrough documents the issue and the solution implemented to resolve the "Security token mismatch" error when logging into the system.

## The Issue
When trying to log in, the application displayed a **Security token mismatch. Please try again.** error. 

### Root Cause
In [database.php](config/database.php), the session cookie configuration specified:
```php
'domain' => $_SERVER['HTTP_HOST'] ?? ''
```
When running the development server on `127.0.0.1:8000`, `$_SERVER['HTTP_HOST']` evaluates to `127.0.0.1:8000`. According to RFC 6265, cookie domains cannot contain port numbers. The browser rejected the cookie due to the invalid domain format. This prevented the PHP session ID from persisting between the login page GET request and the form submission POST request, thereby causing the CSRF verification check to fail on every submission.

Similarly, the `remember_user` cookie was also using `$_SERVER['HTTP_HOST']` which caused the same issue for automatic login storage.

## Changes Made

### 1. Updated Database Configuration
Modified [database.php](config/database.php) to remove the `'domain'` parameter from `session_set_cookie_params` configuration. When omitted, the browser automatically restricts the cookie to the request host name, correctly excluding the port number.

### 2. Updated Authentication Handlers
- Modified [login.php](auth/login.php) to set the `remember_user` cookie domain to an empty string `''`.
- Modified [logout.php](auth/logout.php) to clear the `remember_user` cookie using an empty string `''` domain.

## Validation & Verification

A browser subagent was used to:
1. Reload `http://127.0.0.1:8000/auth/login.php` to clear previous states and confirm the mismatch error was resolved.
2. Enter the administrator credentials:
   - **Email**: `admin@smartums.com`
   - **Password**: `admin123`
3. Click the **Log In** button.
4. Verify redirection to `http://127.0.0.1:8000/dashboard.php` showing the logged-in administrator dashboard interface.

Below is the screenshot of the loaded admin dashboard showing the successful login:

![Admin Dashboard](file:///C:/Users/RO-HIT%2045/.gemini/antigravity-ide/brain/93607975-d31d-4105-9c71-8abc60a4a82d/admin_dashboard_1780741524460.png)
