# Study Twin — Admin Panel

## Files
- `admin.php` — main admin dashboard (login + all tabs)
- `export.php` — CSV / Excel / PDF export handler

## Setup

### 1. Copy files to your server
Place both files in your web root (e.g. `/var/www/html/studytwin/`).

### 2. Change the default credentials
Open `admin.php` and update near the top:

```php
$ADMIN_USER = 'your_username';
$ADMIN_PASS = 'your_secure_password';
```

For production, store credentials in your database with `password_hash()` / `password_verify()`.

### 3. Connect your database
Replace the **mock data arrays** in `admin.php` with real PDO queries:

```php
$pdo = new PDO('mysql:host=localhost;dbname=studytwin', 'db_user', 'db_pass');
$users = $pdo->query("SELECT * FROM users ORDER BY joined DESC")->fetchAll(PDO::FETCH_ASSOC);
```

### 4. Enable Excel exports
```bash
composer require phpoffice/phpspreadsheet
```
Then update `export.php` with the PhpSpreadsheet integration.

### 5. Enable PDF exports
```bash
composer require tecnickcom/tcpdf
# or
composer require dompdf/dompdf
```

## Tabs included
| Tab | What it shows |
|-----|---------------|
| Dashboard | Stats, bar chart, top subjects, recent sessions |
| Users | Full user table — edit / deactivate |
| Matched pairs | Compatibility scores, pair management |
| Study sessions | Session log with scores and notes |
| Reports & exports | CSV / PDF / Excel export buttons |

## Default login (demo)
- Username: `admin`
- Password: `studytwin2024`

**Change these before going live.**
