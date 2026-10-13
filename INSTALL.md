# Paket Dashboard TTE Laravel

Paket ini ditujukan untuk repository `ajung5/DashTTELaravel` pada Laravel 13 dan PHP 8.3+. Tidak ada dependency Composer atau npm baru.

## File yang disalin

Salin isi folder sesuai struktur berikut ke root project Laravel. `routes/web.php` dan `routes/console.php` menggantikan file yang sudah ada. Tempel `.env.example.additions` di bagian bawah `.env` lokal dan `.gitignore.additions` di bagian bawah `.gitignore` project.

```text
app/Console/Commands/CreateTteAdminCommand.php
app/Console/Commands/SyncTteSpreadsheetCommand.php
app/Http/Controllers/Admin/TteRecordController.php
app/Http/Controllers/AdminAuthController.php
app/Http/Controllers/DashboardController.php
app/Models/TteRecord.php
app/Services/GoogleSheetsClient.php
config/tte.php
database/migrations/2026_10_13_000000_create_tte_records_table.php
public/tte.css
resources/views/tte/admin/records.blade.php
resources/views/tte/auth/login.blade.php
resources/views/tte/dashboard.blade.php
resources/views/tte/layout.blade.php
routes/console.php
routes/web.php
tests/Feature/TteDashboardAccessTest.php
```

## Hubungkan Google Spreadsheet

1. Buat Google service account dan aktifkan Google Sheets API di project Google Cloud.
2. Unduh file JSON key service account. Simpan secara lokal sebagai `storage/app/private/google-service-account.json`; jangan masukkan key ke GitHub.
3. Bagikan spreadsheet kepada alamat `client_email` pada file JSON dengan akses **Viewer**.
4. Isi `TTE_GOOGLE_SPREADSHEET_ID` dengan ID spreadsheet. Isi range, misalnya `Data!A1:Z`, sesuai nama tab dan lebar kolom.
5. Pastikan baris pertama sheet berisi header. Sesuaikan `TTE_COL_*` di `.env` agar cocok dengan header. Header yang tersedia pada contoh sudah diselaraskan dengan kolom yang terlihat di dashboard Data Studio.
6. Cek terutama format kolom tanggal. Default parser menganggap `d/m/Y`; ubah `TTE_SHEET_DATE_FORMAT` bila spreadsheet menggunakan format berbeda.

Dashboard publik hanya membaca angka agregat dan progres per unit kerja. Daftar nama/email berada di `/admin/users`, dan hanya bisa dibuka setelah login. NIK sudah disamarkan saat diimpor ke database aplikasi dan juga saat ditampilkan.

## Migrasi dan akun admin

Dari root project:

```bash
php artisan optimize:clear
php artisan migrate
php artisan tte:admin:create
php artisan tte:sync-google-sheet
```

Command pembuatan admin meminta email, nama, dan password melalui prompt tersembunyi. Tidak ada akun atau password default.

## Sinkronisasi berkala

`routes/console.php` menjadwalkan impor setiap 15 menit. Pada server Linux, tambahkan cron Laravel scheduler berikut:

```cron
* * * * * cd /path/ke/DashTTELaravel && php artisan schedule:run >> /dev/null 2>&1
```

Jika berjalan di komputer lokal, gunakan `php artisan schedule:work` selama proses development. Data hanya diganti setelah seluruh sheet berhasil dibaca dan impor database selesai; kegagalan impor tidak menghapus salinan terakhir.

## Definisi status

Nilai status pada `config/tte.php` adalah pemetaan awal dari label dashboard yang terlihat. Cocokkan dengan teks status asli di Google Spreadsheet. Angka ringkasan dihitung sebagai berikut:

- **User terdaftar:** nilai status pengguna cocok dengan `registered`.
- **Sudah aktivasi:** nilai status pengguna cocok dengan `activated`.
- **TTE terbit:** nilai status sertifikat cocok dengan `issued`.
- **Expired:** nilai status sertifikat cocok dengan `expired`.
- **Progress penerbitan:** TTE terbit dibagi user terdaftar.

Setelah impor pertama, bandingkan angka hasil Laravel dengan sumber spreadsheet. Dashboard Data Studio yang ditinjau memiliki perbedaan angka pada beberapa ringkasan dan daftar; pastikan label serta rumus status disepakati sebelum dipakai sebagai pelaporan resmi.
