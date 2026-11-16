# KasKu (versi Laravel)

Aplikasi web pencatat pemasukan dan pengeluaran dengan rekap bulanan, dibuat dengan **Laravel 12**.
Dibuat sebagai Tugas Project mata kuliah **Cyber Security** (Teknik Informatika, Universitas Kuningan).

> Aplikasi ini untuk keperluan tugas. Gunakan **data dummy**, jangan memasukkan data keuangan atau pribadi yang sebenarnya.

- **URL aplikasi:** `https://ISI-DENGAN-URL-APLIKASI-ANDA`
- **Repository:** `https://github.com/ISI-DENGAN-REPO-ANDA`

## Fitur

**Pengguna:** registrasi, login, logout, ganti password; CRUD transaksi dan kategori milik sendiri;
dashboard (saldo, pemasukan dan pengeluaran bulan ini, grafik per kategori); laporan per bulan dengan ekspor PDF dan CSV.

**Admin** (dibuat lewat seeder, bukan registrasi): dashboard statistik agregat, kelola pengguna
(aktifkan/nonaktifkan akun), log aktivitas. Admin **tidak dapat melihat isi transaksi** pengguna.

## Teknologi

Laravel 12 (PHP 8.2+), MySQL/MariaDB, Blade, Bootstrap 5 (hanya CSS, CDN dengan SRI), Chart.js (CDN dengan SRI).
Tanpa paket tambahan di luar Laravel: tidak memakai Breeze, Jetstream, atau Vite/Node.

## Tampilan (UI/UX)

Tema "buku kas": tinta navy, biru kobalt untuk aksi, kunyit/saffron sebagai aksen, dengan panel saldo bergaya halaman buku kas.
- **Layout responsif:** sidebar di desktop; di HP berganti tab bawah dengan tombol tambah transaksi di tengah.
- **Halaman:** masuk/daftar (panel dua kolom), beranda (saldo, bar pemasukan vs pengeluaran, grafik, transaksi terbaru),
  transaksi (dikelompokkan per tanggal), form transaksi, kategori, laporan (navigasi bulan, bar porsi), admin, dan halaman galat 403/404/419/429/500/503.
- **UX:** dialog konfirmasi sendiri (bukan `window.confirm`), pesan toast, tombol tampil/sembunyi password,
  daftar syarat password yang menyala saat mengetik, pratinjau nominal, tombol tanggal "Hari ini/Kemarin", keadaan kosong dengan ajakan bertindak.
- **Aksesibilitas:** tautan lewati-ke-konten, fokus keyboard terlihat, `aria-label` pada tombol ikon, gerak dikurangi jika pengguna memilih `prefers-reduced-motion`.
- **Sesuai CSP:** tanpa inline script/style. Ikon berupa SVG inline (`resources/views/components/icon.blade.php`),
  font di-host sendiri (`public/assets/fonts`, lisensi SIL OFL: Bricolage Grotesque dan Figtree), dan Bootstrap JS tidak dipakai lagi.
- Semua gaya ada di `public/assets/css/style.css`, perilaku di `public/assets/js/app.js`.

## Instalasi lokal

Lihat `CARA-PASANG.md` (XAMPP). Ringkasnya:

```bash
composer create-project laravel/laravel:^12.0 kasku-laravel
# salin isi folder files/ ke dalam kasku-laravel (timpa file yang ada)
cp .env.example .env        # lalu isi DB_*
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
php artisan test
```

## Konfigurasi `.env`

| Variabel | Keterangan |
|---|---|
| `APP_ENV`, `APP_DEBUG` | Di hosting: `production` dan `false` (wajib) |
| `APP_URL` | URL aplikasi, di hosting memakai `https://` |
| `DB_*` | Koneksi database |
| `SESSION_DRIVER=database` | Sesi di tabel `sessions` (bisa dicabut dari server) |
| `SESSION_LIFETIME` | Batas tidak aktif sesi dalam **menit** |
| `SESSION_SECURE_COOKIE` | Di hosting HTTPS: `true` |
| `SEED_ADMIN_PASSWORD`, `SEED_USER_PASSWORD` | Opsional, password akun demo |
| `LOGIN_MAX_ATTEMPTS`, `LOGIN_WINDOW_SECONDS` | Rate limit login (default 5 / 900 detik) |
| `REGISTER_MAX_ATTEMPTS`, `REGISTER_WINDOW_SECONDS` | Rate limit registrasi (default 5 / 3600 detik) |

## Deploy ke Vercel (gratis) dengan database TiDB

Langkah lengkap ada di `CARA-DEPLOY-VERCEL.md`. Ringkasnya: Vercel menjalankan PHP lewat runtime `vercel-php`
(titik masuk `api/index.php`, konfigurasi di `vercel.json`). Filesystem Vercel hanya-baca, jadi cache, view terkompilasi,
dan log dipindah ke `/tmp` dan `stderr`. Rahasia (`APP_KEY`, kredensial database) hanya diisi sebagai Environment Variables
di dasbor Vercel, koneksi database memakai TLS (sertifikat CA publik di `certs/`), dan `TRUSTED_PROXIES=*` membuat Laravel
membaca HTTPS dan IP klien dari proxy Vercel dengan benar.

## Deploy ke shared hosting (cPanel)

Syarat: **PHP 8.2+**, ekstensi `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `json`, `tokenizer`, `xml`, `fileinfo`.

1. **Subdomain:** set Document Root ke folder **`kasku-laravel/public`** (bukan folder proyeknya). Dengan begitu `.env`, `app/`, `vendor/`, `storage/` tidak terjangkau web.
2. **Upload** seluruh proyek **tanpa** file `.env`, `node_modules`, dan `.git`.
3. **Dependency:** lewat Terminal cPanel/SSH jalankan
   `composer install --no-dev --optimize-autoloader`.
   Jika hosting tidak punya Composer/SSH, jalankan perintah itu di komputer lokal lalu upload folder `vendor/` ikut.
4. **Database:** buat database + user di cPanel > MySQL Databases.
5. **`.env` produksi** (dibuat langsung di server): `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL=https://subdomainmu`, `SESSION_SECURE_COOKIE=true`, kredensial database, lalu `php artisan key:generate --force`.
6. **Izin folder:** `storage/` dan `bootstrap/cache/` harus dapat ditulis (775).
7. **Migrasi dan akun demo:** `php artisan migrate --force` lalu `php artisan db:seed --force`.
8. **Optimasi:** `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
9. **HTTPS:** aktifkan AutoSSL / Let's Encrypt. `public/.htaccess` mengalihkan HTTP ke HTTPS.

Jika setelah upload muncul error 500 dari `.htaccess`, beri `#` pada baris `Options -MultiViews -Indexes`
dan `ServerSignature Off` (sebagian hosting melarangnya), lalu aktifkan satu per satu.

## Penerapan keamanan

| Kontrol | Implementasi di Laravel | File |
|---|---|---|
| Hashing password | Cast `hashed` (bcrypt), `Auth::attempt` memverifikasi | `app/Models/User.php` |
| Kebijakan password | `Password::min(8)->letters()->numbers()`, maks 72 | `RegisterController`, `PasswordController` |
| SQL Injection | Eloquent / Query Builder (parameter di-bind), tanpa string SQL dari input | semua controller |
| XSS | Blade `{{ }}` (escape otomatis), tidak ada `{!! !!}`, CSP tanpa inline script | `resources/views/*` |
| Validasi input | Form Request dan `validate()` di sisi server | `StoreTransactionRequest` |
| CSRF | Middleware CSRF bawaan, `@csrf` di semua form, hapus lewat `DELETE` | `resources/views/*`, `routes/web.php` |
| Sesi aman | Cookie HttpOnly, SameSite=Lax, Secure di production, `session()->regenerate()` saat login, `invalidate()` saat logout, sesi di database | `.env`, `LoginController` |
| Kontrol akses | Middleware `auth`, `active`, `role:admin/user`; peran dibaca dari database | `EnsureRole`, `EnsureAccountActive` |
| IDOR | Query selalu lewat `$request->user()->transactions()` dan hasilnya 404 jika milik orang lain | `TransactionController`, `CategoryController` |
| Mass assignment | `$fillable` User hanya `username, email, password`; `role` diisi lewat seeder | `app/Models/User.php` |
| Rate limiting | `RateLimiter` per IP dan per username (login); `throttle:register` (registrasi) | `LoginController`, `AppServiceProvider` |
| Pesan login | Sama untuk username salah, password salah, akun nonaktif; hash dummy menyamakan waktu respons | `LoginController` |
| Security headers | CSP, X-Frame-Options, nosniff, Referrer-Policy, HSTS, Permissions-Policy, no-store | `SecurityHeaders`, `public/.htaccess` |
| Halaman galat | Tampilan 403/404/419/429/500 kustom, tanpa detail teknis | `resources/views/errors/*` |
| Permukaan serangan front-end | Tanpa Bootstrap JS, font dan ikon lokal, hanya Bootstrap CSS dan Chart.js dari CDN dengan SRI | `layouts/app.blade.php` |
| Secret | Hanya di `.env` (di `.gitignore` bawaan), config dibaca lewat `config()` | `.env`, `config/kasku.php` |
| Error handling | `APP_DEBUG=false` di production, error masuk `storage/logs/laravel.log` | `.env` |
| Least privilege admin | Dashboard admin hanya memakai `count()` | `Admin/DashboardController` |
| Admin tidak bisa menonaktifkan diri | Dicek di server dan dicatat di log | `Admin/UserController` |
| Pencabutan sesi | Nonaktifkan akun / ganti password mencabut sesi aktif pengguna itu | `SessionPurge` |
| CSV injection | Sel berawalan `= + - @` diberi awalan `'` | `Format::csvSafe` |
| Injeksi isi PDF dan SSRF | PDF dibuat langsung oleh kode PHP (tanpa konverter HTML ke PDF, tanpa akses jaringan/berkas); teks pengguna di-escape `( ) \` | `PdfDocument::escape`, `ReportPdf` |
| Ekspor hanya data sendiri | Query ekspor memakai relasi `$user->transactions()`; format dibatasi daftar putih `pdf`/`csv` | `ReportController::export` |
| Log aktivitas | Login berhasil/gagal, perubahan data, aksi admin (tanpa password) | `ActivityLog` |

### Perbedaan dengan versi PHP native
- Rate limiting memakai `RateLimiter` (cache), sehingga tabel `login_attempts` tidak dibutuhkan.
- Kolom password bernama `password` (konvensi Laravel), berisi hash bcrypt.
- Sesi disimpan di database sehingga ganti password / nonaktifkan akun **langsung mencabut sesi aktif**
  (keterbatasan yang ada di versi native).

### Keterbatasan yang diketahui
- Penguncian per username bisa dipakai untuk membuat target sementara tidak bisa login (trade-off rate limiting).
- Belum ada verifikasi email saat registrasi.
- Belum ada fitur pemulihan password (lupa password / reset oleh admin) dan belum ada verifikasi email; pengguna yang lupa password tidak punya jalur pemulihan di dalam aplikasi.
- Di belakang proxy/CDN (Vercel, Cloudflare), isi `TRUSTED_PROXIES=*` agar IP klien dan deteksi HTTPS benar (diatur di `bootstrap/app.php`).
- Batas absolut sesi (selain batas tidak aktif) belum diterapkan.

## Pengujian otomatis

```bash
php artisan test --filter=SecurityTest
```
`tests/Feature/SecurityTest.php` memuat 35 skenario: autentikasi, rate limit, mass assignment, IDOR (lihat/ubah/hapus),
akses admin, nonaktifkan akun, XSS, SQL injection pada filter, CSRF (419), CSV injection, ekspor PDF/CSV, security headers, validasi input,
halaman galat yang tidak membocorkan detail, dan kepatuhan CSP (tanpa inline script/style di semua halaman).

## Penggunaan AI agent

> Lengkapi dengan jujur sesuai ketentuan tugas.

- **Alat AI:** Claude (Anthropic)
- **Dipakai untuk:** merancang struktur, menulis kode Laravel, migrasi, test, dan dokumentasi.
- **Contoh prompt utama:** `ISI-DENGAN-PROMPT-ANDA`
- **Cara meninjau dan memverifikasi:** `ISI-DENGAN-CARA-ANDA` (mis. membaca tiap file, `php artisan test`, uji manual, dan pengujian di hosting).

## Hasil self-testing keamanan

> Lengkapi dengan tangkapan layar / keluaran tools untuk laporan PDF.

| # | Skenario | Cara uji | Hasil | Perbaikan |
|---|---|---|---|---|
| 1 | SQL Injection pada login/filter | Payload `' OR '1'='1` | ISI | ISI |
| 2 | XSS tersimpan | Keterangan `<script>alert(1)</script>` | ISI | ISI |
| 3 | IDOR transaksi | User A membuka `/transactions/{id milik B}/edit` | ISI | ISI |
| 4 | Akses admin oleh user biasa | Buka `/admin/users` | ISI | ISI |
| 5 | CSRF | POST tanpa token (419) | ISI | ISI |
| 6 | Rate limiting login | 6 kali salah berurutan | ISI | ISI |
| 7 | Password lemah | Registrasi `abcdefgh` | ISI | ISI |
| 8 | HTTPS dan header | securityheaders.com / SSL Labs | ISI | ISI |
| 9 | Secret di repository | `git log -p`, cari `DB_PASSWORD` | ISI | ISI |
| 10 | Kerentanan dependency | `composer audit` | ISI | ISI |

## Akun uji coba

Dibuat oleh seeder. Tulis kredensial di laporan PDF (bukan di repository ini): `admin_demo` dan `user_demo`.
