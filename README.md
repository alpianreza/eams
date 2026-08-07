# EAMS — Enterprise Asset Management System

Aplikasi internal berbasis CodeIgniter 4 untuk pengelolaan asset, compliance checklist, monitoring progress, notifikasi, dan laporan.

## Persyaratan

- PHP 8.1 atau lebih baru
- MySQL/MariaDB
- Extension `intl`, `mbstring`, `json`, `mysqlnd`, dan `curl`

Setelah memperbarui aplikasi, jalankan:

```bash
php spark migrate
```

## Reminder checklist WhatsApp

Command WhatsApp yang sudah tersedia:

```bash
php spark notify:weekly-checklist
```

Opsi:

```bash
php spark notify:weekly-checklist --dry-run
php spark notify:weekly-checklist --username=reza
php spark notify:weekly-checklist --date=2026-03-26 --max-items=10
```

Konfigurasi `.env`:

```dotenv
whatsapp.enabled = true
whatsapp.provider = fonnte
whatsapp.fonnteEndpoint = https://api.fonnte.com/send
whatsapp.fonnteToken = <TOKEN_API_FONNTE>
whatsapp.timeout = 20
```

Cron mingguan, contoh Senin pukul 08:00:

```cron
0 8 * * 1 cd /path/to/eams && php spark notify:weekly-checklist
```

## Reminder checklist Email

1. Isi email setiap user melalui **Manajemen User → Tambah/Edit User**.
2. Aktifkan **Kirim juga melalui email** pada **Pengaturan Perusahaan → Kanal notifikasi global**.
3. Isi konfigurasi SMTP di `.env`.

Contoh SMTP TLS:

```dotenv
email.fromEmail = eams@perusahaan.com
email.fromName = EAMS
email.protocol = smtp
email.SMTPHost = smtp.perusahaan.com
email.SMTPUser = eams@perusahaan.com
email.SMTPPass = <PASSWORD_SMTP>
email.SMTPPort = 587
email.SMTPCrypto = tls
email.SMTPTimeout = 20
email.mailType = text
email.charset = UTF-8
email.newline = "\r\n"
email.CRLF = "\r\n"
```

> Jangan commit password SMTP ke repository. Simpan hanya di `.env` server.

Uji dengan mode simulasi:

```bash
php spark notify:weekly-checklist-email --dry-run
php spark notify:weekly-checklist-email --dry-run --username=reza
```

Kirim email reminder:

```bash
php spark notify:weekly-checklist-email
```

Opsi lengkap:

```bash
php spark notify:weekly-checklist-email --date=2026-08-07 --username=reza --max-items=10
```

Cron mingguan, contoh Senin pukul 08:05:

```cron
5 8 * * 1 cd /path/to/eams && php spark notify:weekly-checklist-email
```

Command email membuat notifikasi `reminder` di Notification Center dan mengirim email, tetapi tidak mengirim ulang WhatsApp. Pengiriman dengan tanggal dan user yang sama memakai deduplikasi agar tidak terkirim dua kali.

## PIC Inventory

PIC inventory disimpan sebagai relasi ke user login melalui tabel `compliance_inventory_pics`. Maksimal dua PIC: satu PIC utama dan satu PIC kedua. Kolom teks lama tetap dipertahankan sementara untuk kompatibilitas modul lama.
