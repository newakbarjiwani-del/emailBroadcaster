# Email Broadcaster (PHP Native)

Antrian email penagihan & konfirmasi pembayaran sekolah.

- API masukkan pesan penagihan (nama, NIS, kelas, list tagihan, VA, link bayar, outstanding/Open VA)
- API notifikasi konfirmasi pembayaran
- Cron kirim email tiap **2 menit**
- Dashboard pantau status `PENDING` / `SENT` / `FAILED`
- Kredensial SMTP & token di `.env` (tanpa framework, tanpa Composer)

## Requirement

- PHP 8.0+ dengan ekstensi `pdo_sqlite` (default) atau `pdo_mysql`
- OpenSSL (untuk SMTP STARTTLS)

## Setup

```bash
copy .env.example .env
# isi SMTP_USER, SMTP_PASS, MAIL_FROM_EMAIL
```

Jalankan server development:

```bash
php -S localhost:8080 router.php
```

Dashboard: http://localhost:8080/dashboard.html

Database SQLite dibuat otomatis di `storage/database.sqlite` saat request pertama.

### MySQL (opsional)

Di `.env`:

```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=email_broadcaster
DB_USER=root
DB_PASS=
```

Import `sql/schema.mysql.sql` atau biarkan auto-create saat pertama kali jalan.

## Cron tiap 2 menit

**Windows Task Scheduler**

- Program: `php.exe`
- Arguments: `"D:\PROJECT\ICT\emailBroadcaster\cron\send_emails.php"`
- Trigger: setiap 2 menit

**Linux crontab**

```cron
*/2 * * * * php /path/to/emailBroadcaster/cron/send_emails.php >> /path/to/emailBroadcaster/storage/cron.log 2>&1
```

## API

Jika `API_TOKEN` di `.env` diisi, kirim header:

```http
Authorization: Bearer <API_TOKEN>
```

atau `X-Api-Token: <API_TOKEN>`.

### 1) Antri email penagihan

`POST /api/billing`

Single:

```json
{
  "nama": "Gaza Solahudin Mubarok",
  "nis": "2026001",
  "kelas": "Daycare A",
  "email": "ortu@gmail.com",
  "va": "900362125117",
  "orderNumber": "DAYCARE02/262701-0006",
  "paymentLink": "https://example.com/pay/xxx",
  "outstandingAmount": null,
  "items": [
    { "name": "SPP DAYCARE JULI 2026", "amount": 247500 },
    { "name": "Ekskul Renang", "amount": 150000 }
  ],
  "paymentGuide": "Via ATM BSI (Tipe Close Payment)"
}
```

Bulk (hasil parse Excel dari sistem lain):

```json
{
  "messages": [
    {
      "nama": "Siswa A",
      "email": "a@mail.com",
      "kelas": "1A",
      "nis": "001",
      "va": "9001",
      "paymentLink": "https://pay.example/a",
      "items": [
        { "name": "SPP", "amount": 500000 },
        { "name": "Ekskul Futsal", "amount": 75000 }
      ]
    },
    {
      "nama": "Siswa B",
      "email": "b@mail.com",
      "kelas": "1B",
      "va": "9002",
      "outstandingAmount": 7320000,
      "items": [
        { "name": "Uang Pangkal", "amount": 2708000 }
      ]
    }
  ]
}
```

Isi email penagihan mencakup: Nama & Kelas, item + total, outstanding (jika Open VA/cicilan), Nomor VA, tata cara, dan tombol/link pembayaran.

Alias field: `studentName`/`nama`/`name`, `recipientEmail`/`email`, `studentNis`/`nis`, `studentClass`/`kelas`, `vaNumber`/`va`, `items`/`tagihan`, `outstandingAmount`/`sisaCicilan`.

### 2) Antri konfirmasi pembayaran

`POST /api/payment-confirmation`

```json
{
  "nama": "IBRAHIM ARFA GUNAWAN",
  "email": "ortu@gmail.com",
  "kelas": "TK B",
  "nis": "2025123",
  "orderNumber": "QUBAP-DFU-262701-221",
  "paymentAmount": 2708000,
  "billAmount": 10028000,
  "outstandingAmount": 7320000,
  "referenceNumber": "1787702013506227",
  "paymentMethod": "Permata",
  "paidAt": "2026-08-26T06:53:44+07:00",
  "items": [{ "name": "Daftar Ulang Tahun 2026/2027", "amount": 10028000 }]
}
```

### 3) Lainnya

| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/api/messages` | List + summary (`?status=&type=&q=`) |
| GET | `/api/messages/:id` | Detail |
| POST | `/api/messages/:id/retry` | Ulang kirim yang FAILED |
| POST | `/api/process-now` | Paksa proses antrian sekarang |
| GET | `/health` | Status service + SMTP |

## Alur kerja

1. Sistem billing / parser Excel memanggil `POST /api/billing` atau `POST /api/payment-confirmation`
2. Data masuk tabel `email_messages` status `PENDING`
3. Cron tiap 2 menit (atau tombol **Proses Sekarang**) mengirim via SMTP
4. Dashboard menampilkan status terkirim / gagal

## Env penting

| Key | Fungsi |
|-----|--------|
| `SMTP_HOST/PORT/USER/PASS` | Akun pengirim |
| `SMTP_SECURE` | `tls` (587) atau `ssl` (465) |
| `MAIL_FROM_NAME/EMAIL` | Nama & alamat From |
| `EMAIL_BATCH_SIZE` | Jumlah email per batch |
| `API_TOKEN` | Opsional proteksi API |
| `DEFAULT_PAYMENT_GUIDE` | Default tatacara pembayaran |
| `DB_DRIVER` | `sqlite` (default) atau `mysql` |
