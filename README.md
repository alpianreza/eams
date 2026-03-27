# CodeIgniter 4 Framework

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds the distributable version of the framework.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Contributing

We welcome contributions from the community.

Please read the [*Contributing to CodeIgniter*](https://github.com/codeigniter4/CodeIgniter4/blob/develop/CONTRIBUTING.md) section in the development repository.

## Server Requirements

PHP version 8.1 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - If you are still using PHP 7.4 or 8.0, you should upgrade immediately.
> - The end of life date for PHP 8.1 will be December 31, 2025.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## WhatsApp Weekly Checklist Reminder

Project ini punya command untuk kirim reminder checklist mingguan via WhatsApp:

```bash
php spark notify:weekly-checklist
```

### Opsi command

```bash
php spark notify:weekly-checklist --dry-run
php spark notify:weekly-checklist --username=reza
php spark notify:weekly-checklist --date=2026-03-26 --max-items=10
```

### Konfigurasi `.env`

Isi konfigurasi berikut:

```dotenv
whatsapp.enabled = true
whatsapp.provider = fonnte
whatsapp.fonnteEndpoint = https://api.fonnte.com/send
whatsapp.fonnteToken = <TOKEN_API_FONNTE>
whatsapp.timeout = 20
```

Jika tabel `users` belum punya kolom nomor WA, bisa pakai fallback mapping:

```dotenv
whatsapp.namePhoneMap = REZA ALPIAN:62812xxxxxxx,FITRI HANDAYANI:62813xxxxxxx
```

Command otomatis mencoba ambil nomor dari kolom berikut jika tersedia di tabel `users`:
`wa_number`, `whatsapp_number`, `phone`, `phone_number`, `mobile`, `mobile_number`, `no_hp`, `no_telp`, `telp`.

### Menjalankan seminggu sekali

- Linux cron (contoh Senin jam 08:00):

```cron
0 8 * * 1 cd /path/to/eams && php spark notify:weekly-checklist
```

- Windows Task Scheduler:
  jalankan `php C:\xampp\htdocs\eams\spark notify:weekly-checklist` dengan trigger mingguan.
