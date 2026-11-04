<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Penggunaan - WA Bot NagariHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h2 {
            font-size: 1.875rem; /* 30px */
            font-weight: 700;
            margin-bottom: 1rem;
            color: #312e81; /* Indigo 800 */
        }
        h3 {
            font-size: 1.25rem; /* 20px */
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #4338ca; /* Indigo 700 */
        }
        p, ul, ol {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        ul, ol {
            margin-left: 1.5rem;
            list-style-type: disc;
        }
        ol {
            list-style-type: decimal;
        }
        code {
            background-color: #e0e7ff; /* Indigo 100 */
            padding: 0.2em 0.4em;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.875rem;
            color: #4f46e5; /* Indigo 600 */
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="glass-effect shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 text-gray-700 hover:text-purple-600 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="font-semibold hidden sm:inline">Kembali ke Dashboard</span>
                </a>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-book text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Panduan Penggunaan</h2>
                    </div>
                </div>
                <div></div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="glass-effect rounded-2xl shadow-2xl p-6 md:p-8 lg:p-10 mb-8">
            <h1 class="text-3xl font-extrabold text-indigo-800 mb-6">Panduan Penggunaan Aplikasi WA Bot NagariHub</h1>
            <p>Selamat datang di panduan penggunaan aplikasi WA Bot NagariHub. Dokumen ini akan membantu Anda memahami dan memanfaatkan fitur-fitur utama aplikasi untuk pelayanan publik digital Nagari Sungai Aua.</p>

            <h2 class="mt-8">1. Login dan Akses Dashboard</h2>
            <p>Untuk mengakses dashboard manajemen WA Bot, Anda perlu memasukkan kunci akses yang benar. Kunci akses standar adalah <code>sungaiaua2025</code>.</p>
            <ol>
                <li>Buka aplikasi di browser Anda.</li>
                <li>Pada halaman selamat datang, klik "Login Admin" atau "Masuk Dashboard".</li>
                <li>Masukkan kunci akses Anda di kolom yang tersedia.</li>
                <li>Klik "Buka Dashboard" untuk masuk.</li>
            </ol>

            <h2 class="mt-8">2. Dashboard Utama</h2>
            <p>Dashboard utama memberikan gambaran umum tentang aktivitas WA Bot Anda.</p>
            <ul>
                <li><strong>Statistik Pesan:</strong> Lihat total pesan terkirim, diterima, dan gagal.</li>
                <li><strong>Kotak Masuk:</strong> Daftar kontak terbaru dengan cuplikan pesan terakhir mereka. Klik pada kontak untuk melihat riwayat percakapan lengkap.</li>
                <li><strong>Menu Samping (Right Panel):</strong> Berisi berbagai fitur seperti pesan baru, kirim lokasi, auto reply, dll.</li>
            </ul>

            <h2 class="mt-8">3. Mengelola Percakapan</h2>
            <h3>3.1. Melihat Riwayat Percakapan</h3>
            <ol>
                <li>Dari dashboard, klik pada salah satu kontak di "Kotak Masuk".</li>
                <li>Anda akan diarahkan ke halaman percakapan yang menampilkan semua pesan masuk dan keluar dengan kontak tersebut.</li>
                <li>Anda bisa me-refresh percakapan dengan menekan tombol refresh di kanan atas.</li>
            </ol>
            <h3>3.2. Mengirim Balasan Pesan</h3>
            <ol>
                <li>Di halaman percakapan, ketik pesan Anda di kolom "Ketik pesan...".</li>
                <li>Tekan Enter atau klik ikon pesawat kertas untuk mengirim pesan.</li>
                <li>Anda juga bisa menyertakan emoji menggunakan tombol emoji.</li>
            </ol>

            <h2 class="mt-8">4. Fitur-fitur Utama</h2>
            <h3>4.1. Pesan Terjadwal</h3>
            <p>Fitur ini memungkinkan Anda mengirim pesan secara otomatis di waktu yang telah ditentukan.</p>
            <ol>
                <li>Klik ikon menu (tiga titik vertikal) di sidebar kiri, lalu pilih "Pesan Terjadwal", atau klik tombol "Jadwal Baru" di panel Pesan Terjadwal.</li>
                <li>Isi detail: Nomor/ID Grup, Jenis (Teks, Lokasi, Link Preview), Pesan/Data Lokasi/URL, dan Waktu Pengiriman.</li>
                <li>Klik "Jadwalkan" untuk menyimpan.</li>
                <li>Untuk membatalkan, buka panel "Pesan Terjadwal" dan klik ikon silang di samping pesan yang ingin dibatalkan.</li>
            </ol>
            <h3>4.2. Auto Reply (Balasan Otomatis)</h3>
            <p>Atur balasan otomatis berdasarkan keyword pesan masuk.</p>
            <ol>
                <li>Buka panel "Auto Reply" dari menu sidebar.</li>
                <li>Aktifkan Auto Reply dengan toggle switch.</li>
                <li>Tambahkan/Edit Aturan: Masukkan Keyword, pilih tipe cocok (Mengandung, Persis, Diawali), dan tulis Balasan.</li>
                <li>Klik "Simpan" untuk menambahkan atau memperbarui aturan.</li>
            </ol>
            <h3>4.3. Import Kontak</h3>
            <p>Memasukkan banyak kontak sekaligus menggunakan file CSV.</p>
            <ol>
                <li>Buka panel "Import Kontak" dari menu sidebar.</li>
                <li>Pilih file CSV Anda (format: <code>nomor,nama</code> per baris).</li>
                <li>(Opsional) Tambahkan Tag Grup untuk mengelompokkan kontak.</li>
                <li>Klik "Import".</li>
            </ol>
            <h3>4.4. Kirim ke Grup Kontak</h3>
            <p>Mengirim broadcast pesan ke semua kontak dengan tag grup tertentu.</p>
            <ol>
                <li>Buka panel "Kirim ke Grup Kontak" dari menu sidebar.</li>
                <li>Pilih Tag Grup yang sudah ada.</li>
                <li>Ketik Pesan yang ingin di-broadcast.</li>
                <li>Klik "Kirim Broadcast".</li>
            </ol>
            <h3>4.5. Webhook</h3>
            <p>Melihat URL webhook dan log aktivitas webhook.</p>
            <ol>
                <li>Buka panel "Webhook" dari menu sidebar.</li>
                <li>Salin URL Webhook untuk dipasang di pengaturan Wablas Anda.</li>
                <li>Aktifkan/nonaktifkan Webhook Tracking Status.</li>
                <li>Lihat log terbaru dari aktivitas webhook.</li>
            </ol>

            <h2 class="mt-8">5. Sinkronisasi Pesan</h2>
            <p>Aplikasi melakukan sinkronisasi pesan secara berkala, tetapi Anda bisa memicu sinkronisasi manual.</p>
            <ol>
                <li>Klik ikon sinkronisasi <i class="fas fa-sync-alt"></i> di header sidebar.</li>
                <li>Pesan terbaru dari API Wablas akan ditarik dan diperbarui di database Anda.</li>
            </ol>

            <h2 class="mt-8">6. Laporan dan Export Data (Menyusul)</h2>
            <p>Fitur untuk mengunduh laporan data dalam format tertentu (misalnya CSV atau PDF) akan segera tersedia di menu dashboard.</p>
        </div>
    </main>
</body>
</html>
