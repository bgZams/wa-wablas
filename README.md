# 🤖 WhatsApp Bot Dashboard with Laravel 12 & Wablas

Dashboard ini adalah aplikasi web yang dirancang untuk mengelola dan memonitor bot WhatsApp Anda dengan mudah. Dibangun menggunakan **Laravel 12** sebagai framework utama dan terintegrasi dengan **Wablas API** untuk komunikasi dengan WhatsApp.

## ✨ Fitur Unggulan

- **Dasbor Ringkasan**: Lihat statistik penting seperti jumlah pesan terkirim, diterima, dan gagal.
- **Inbox Interaktif**: Tampilkan riwayat percakapan dengan kontak secara real-time (menggunakan API Wablas).
- **Balas Chat**: Kirim balasan langsung ke kontak dari halaman percakapan spesifik.
- **Kirim Pesan Manual**: Kirim pesan ke nomor WhatsApp mana pun langsung dari dasbor.
- **Tanpa Database**: Semua data riwayat pesan diambil langsung dari API Wablas saat dibutuhkan.

## 🛠️ Persyaratan Sistem

Pastikan server Anda memenuhi persyaratan berikut:

- PHP >= 8.1
- Composer
- Node.js & NPM

## 🚀 Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan proyek secara lokal.

### 1. Klon Repositori

```bash
git clone [https://github.com/bgZams/wa-wablas.git](https://github.com/bgZams/wa-wablas.git)
cd wa-wablas
```

### 2. Instalasi Dependensi
Instal dependensi PHP dengan Composer dan dependensi JavaScript dengan NPM.

```bash
composer install
npm install
npm run dev
```

### 3. Konfigurasi Lingkungan
Salin file .env.example menjadi .env dan konfigurasikan kunci API Wablas Anda.

```bash
cp .env.example .env
```

Buka file .env dan tambahkan kredensial Wablas Anda:

# Wablas API Credentials
WABLAS_TOKEN=your_token_from_wablas
WABLAS_SECRET_KEY=your_secret_key_from_wablas


### 4. Jalankan Aplikasi
Jalankan server pengembangan Laravel.

```bash
php artisan serve
```
Aplikasi Anda sekarang dapat diakses di http://127.0.0.1:8000.

⚙️ Integrasi Wablas
1. Hubungkan Perangkat
Ikuti petunjuk di dokumentasi Wablas untuk memindai kode QR dan menghubungkan perangkat WhatsApp Anda.

2. Konfigurasi API
Aplikasi ini menggunakan API GET report/message untuk mengambil riwayat pesan. Pastikan kredensial Anda memiliki izin yang diperlukan.

📝 Penggunaan
Halaman Utama (/dashboard): Menampilkan ringkasan statistik dan daftar kontak terbaru.

Halaman Percakapan (/conversation/{phone}): Klik salah satu kontak di halaman utama untuk melihat riwayat percakapan dan membalasnya.

🤝 Kontribusi
Kami sangat menyambut kontribusi Anda! Jika Anda menemukan bug atau ingin menambahkan fitur baru, silakan buka issue atau kirim pull request.

📜 Lisensi
Proyek ini dilisensikan di bawah MIT License.
