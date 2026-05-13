| Student Name | ID | Username |
|--------------|----|----------|
| Yerosan Bekele     | ETS1449/16 | yer0san |
| Yoseph Asrat   | ETS1535/16 | yoseph777 |
| Yonas Demise  | ETS1495/16 | yonas99 |
| Yonas Zegeye     | ETS1503/16 | yonidevco |
| Yidnekachew Zerihun   | ETS1455/16 | ofx-yd |
| Yonas Begashaw  | ETS1494/16 | yonas189 |

# PennyWise — Backend API

PHP REST API for PennyWise. Uses nginx + PHP-FPM, MariaDB (or XAMPP instead) and PHPMailer for email verification.

## Project structure

```
pennywise_api/
├── .env                     
├── .env.example              
├── composer.json
├── vendor/
├── public/
│   └── index.php                 ← router
└── src/
    ├── utils.php                 ← shared helpers (json, validation, auth guard)
    ├── core/
    │   └── db.php                ← database connection
    ├── registrationAndLogging/
    │   ├── login.php
    │   ├── register.php
    │   ├── logout.php
    │   ├── verify.php
    │   └── check_auth.php
    └── controllers/
        ├── mailService.php
        ├── expenseController.php
        ├── incomeController.php
        ├── transferController.php
        └── debtController.php
```

## Requirements

- PHP 8.x
- nginx + php-fpm (or XAMPP)
- MariaDB
- Composer

## Setup

**1. Clone the repo and install dependencies:**
```bash
composer install
```

**2. Create your `.env` file:**
```bash
cp .env.example .env
```

Then fill in your values:
```dotenv
DB_HOST=127.0.0.1
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASS=your_db_password
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_google_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM=your_email@gmail.com
MAIL_FROM_NAME="PennyWise"
```

**3. Create the database and run the schema (example with mariadb):**
```bash
mariadb -u root -p your_db_name < full_db_schema.sql
```

**4. Start nginx and php-fpm (or XAMPP) example for nginx adn php-fpm below:**
```bash
sudo systemctl start nginx
sudo systemctl start php-fpm
```

The API will be available at `http://localhost`.

## Auth endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | Create a new account |
| POST | `/login` | Sign in |
| POST | `/logout` | Sign out |
| GET | `/verify?token=...` | Verify email address |
| GET | `/check-auth` | Check if session is valid |

## Email verification

Registration sends a verification email via Gmail SMTP. The `MAIL_PASSWORD` is a **Google App Password**, not your regular Gmail password.

To generate one: Google Account → Security → 2-Step Verification → App Passwords.

## CORS

The API allows requests from the frontend origins:
- `http://localhost:5500`
- `http://127.0.0.1:5500`

To allow a different origin, update the `$allowedOrigins` array at the top of `public/index.php`.

