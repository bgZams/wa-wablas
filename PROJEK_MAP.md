# Project Map: WA Bot NagariHub

> **Terakhir Diperbarui:** 2026-11-04  
> **Status Gateway:** Aktif & Berhasil Dimigrasi ke **WAHA (WhatsApp HTTP API)**  
> **Versi Dokumentasi:** 2.0 (Post-WAHA Migration & Realtime Media Enhancement)

---

## 1. Ringkasan Eksekutif & Karakteristik Proyek

* **Nama Proyek:** WA Bot NagariHub / Wabot Pelayanan Nagari Sungai Aua
* **Tujuan Utama:** Aplikasi otomatisasi dan manajemen komunikasi WhatsApp dua arah (inbox, auto-reply, broadcast, scheduling) untuk pelayanan publik digital Nagari Sungai Aua.
* **Tech Stack:**
  * **Framework Backend:** Laravel 12.x (PHP 8.3+)
  * **Database:** MySQL (`nagarihu_whasbotsungaiaur` pada `127.0.0.1:3306`)
  * **Frontend / UI:** Blade Templates, Tailwind CSS (CDN), FontAwesome 6, WhatsApp Web UI Design Pattern
  * **Export Engine:** Barryvdh DomPDF
* **Penyedia Gateway WhatsApp:** **WAHA (WhatsApp HTTP API)**
  * **Base URL:** `https://traveler-call-substantially-defeat.trycloudflare.com`
  * **Session:** `session_01m6g9rqt6f26rjmvaaxrj8np4`
  * **Status Sesi:** `WORKING` (Engine: `WEBJS`, State: `CONNECTED`)
  * **Nomor Bot Resmi:** `6282284571236`
* **Mekanisme Autentikasi Admin:** Proteksi berbasis sesi kustom menggunakan Access Key (`sungaiaua2025`) melalui middleware `AccessKeyMiddleware`.

---

## 2. Arsitektur & Struktur Direktori

```
wabot/
├── app/
│   ├── Console/Commands/
│   │   ├── DebugMessagesCommand.php             # messages:debug (inspeksi data pesan)
│   │   ├── DispatchScheduledMessagesCommand.php # schedule:dispatch (eksekusi pesan terjadwal)
│   │   ├── SyncMessagesCommand.php              # messages:sync (sinkronisasi manual via CLI)
│   │   └── VerifyConversationCommand.php        # verify:conversation (verifikasi integritas chat)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AutoReplyController.php          # [BARU] Halaman penuh & API CRUD/simulator Auto-Reply
│   │   │   ├── DashboardController.php          # Manajemen inbox, kontak, auto-reply, broadcast, setting, PDF
│   │   │   └── WebhookController.php            # Handler penerima webhook WAHA (/webhook/waha & /webhook/wablas)
│   │   └── Middleware/
│   │       └── AccessKeyMiddleware.php          # Proteksi akses dashboard via session key
│   ├── Models/
│   │   ├── AutoReply.php                        # Aturan kata kunci dan template balasan otomatis
│   │   ├── Contact.php                          # Kontak warga / nomor pengirim & grup
│   │   ├── Message.php                          # Riwayat pesan masuk & keluar beserta metadata media
│   │   ├── ScheduledMessage.php                 # Antrean pesan terjadwal
│   │   ├── Setting.php                          # Konfigurasi sistem dinamis (key-value)
│   │   └── WebhookLog.php                       # Riwayat log payload mentah webhook
│   └── Services/
│       ├── WahaService.php                      # Core client API WAHA (sendText, media download, LID resolver)
│       ├── WablasService.php                    # Legacy wrapper kompatibilitas (turunan WahaService)
│       └── MessageSyncService.php               # Sinkronisasi percakapan WAHA ke database lokal
├── config/
│   ├── waha.php                                 # Konfigurasi WAHA (base_url, api_key, session, bot_number)
│   └── wablas.php                               # Konfigurasi fallback legacy
├── database/migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 2025_08_07_051043_create_message_logs_table.php
│   ├── 2025_11_07_015648_create_contacts_and_messages_tables.php
│   └── 2026_04_24_000000_create_wa_features_tables.php
├── resources/views/
│   ├── autoreply/
│   │   └── index.blade.php                      # [BARU] Halaman penuh manajemen auto-reply & simulator uji pesan
│   ├── dashboard/
│   │   ├── index.blade.php                      # Tampilan utama WhatsApp Web (Chat, Panel, Media Viewer)
│   │   ├── inbox.blade.php
│   │   └── conversation.blade.php
│   ├── reports/
│   │   └── contacts_messages.blade.php          # Template cetak PDF riwayat kontak & chat
│   ├── layouts/
│   │   └── app.blade.php
│   ├── usage.blade.php                          # Panduan penggunaan sistem
│   └── welcome.blade.php                        # Halaman login / input Access Key
├── routes/
│   ├── console.php                              # Scheduler cron Laravel
│   └── web.php                                  # Rute dashboard dan endpoint webhook
└── storage/app/public/whatsapp/                 # Direktori penyimpanan lokal media gambar/dokumen WA
```

---

## 3. Komponen Inti & Alur Kerja (Core Workflows)

### A. Layanan Gateway WAHA (`WahaService`)
1. **Resolusi LID WhatsApp ke Nomor HP Asli (`resolveToPhone`)**:
   * WhatsApp menggunakan ID internal Linked Identity (`@lid`, misal: `26732413337743@lid`) saat user mengirim pesan dari perangkat tertaut.
   * `WahaService` menyelesaikan LID ke nomor telepon asli via endpoint `/api/{session}/lids/{lid}` dan menyimpannya dalam in-memory cache agar nomor kontak di database tetap nomor telepon asli (format `628xxx`).
2. **Download & Manajemen Media (`downloadMessageMedia`)**:
   * Saat menerima pesan bergambar/dokumen, service mengunduh file biner dari WAHA (`/api/{session}/chats/{chatId}/messages/{msgId}/media`).
   * File disimpan di `storage/app/public/whatsapp/wa_{hash}.ext`.
   * Mengembalikan metadata berisi URL absolut `http://127.0.0.1:8000/storage/whatsapp/...`, mimetype, dan ukuran file.
3. **Pengiriman Pesan**:
   * Teks (`sendMessage` via `POST /api/sendText`).
   * Lokasi (`sendLocation` via `POST /api/sendLocation`).
   * Tautan (`sendLinkPreview` via `POST /api/sendLinkPreview` dengan fallback `sendText`).
   * Cek keberadaan nomor di WhatsApp (`checkNumberExists`).

---

### B. Webhook Engine (`WebhookController`)
* **Endpoint:** `POST /webhook/waha` (dan alias `/webhook/wablas`).
* **Event `message`:**
  1. Menerima payload JSON dari WAHA.
  2. Resolusi pengirim (`from`) & penerima (`to`) menggunakan `WahaService::resolveToPhone()`.
  3. Menentukan arah pesan:
     * `outgoing`: Jika `fromMe == true` atau pengirim adalah nomor bot (`6282284571236`).
     * `incoming`: Jika pengirim adalah pelanggan/warga.
  4. Unduh otomatis media jika pesan memiliki lampiran gambar, audio, video, atau dokumen.
  5. Simpan / perbarui data ke tabel `contacts` dan `messages`.
  6. Catat payload ke `webhook_logs` jika setting `webhook_tracking` aktif.
  7. **Auto-Reply Trigger**: Jika pesan masuk (`incoming`) dan `auto_reply_enabled` aktif, jalankan `maybeAutoReply()`.
* **Event `message.ack`:**
  * Memperbarui status pengiriman pesan (`sent`, `pending`, `failed`) di tabel `messages`.

---

### C. Sistem Balas Otomatis (Auto-Reply System)
* **Model:** `AutoReply` (`auto_replies` table).
* **Mode Pencocokan (`match_type`):**
  * `exact`: Teks pesan harus sama persis (case-insensitive, trimmed). Contoh: `"menu"`.
  * `contains`: Mengandung kata kunci. Contoh: `"halo"`.
  * `starts_with`: Diawali dengan kata kunci.
* **Alur Eksekusi:**
  1. Pesan masuk diterima webhook.
  2. Sistem mencari aturan pertama yang cocok dari daftar aturan `active = 1`.
  3. Bot mengirim balasan teks via `WahaService::sendMessage()`.
  4. Pesan balasan bot langsung dicatat ke tabel `messages` sebagai pesan `outgoing` dengan timestamp yang diurutkan rapi setelah pesan masuk.

---

### D. Manajemen Pesan Terjadwal & Siaran (Broadcast)
* **Pesan Terjadwal (`ScheduledMessage`)**:
  * Admin dapat menjadwalkan pesan teks, lokasi, atau tautan untuk dikirim pada waktu tertentu.
  * Command `php artisan schedule:dispatch` memeriksa antrean berstatus `pending` dan mengeksekusinya saat waktu tiba.
* **Siaran Berdasarkan Tag Grup (`sendByGroup`)**:
  * Mengirim pesan massal ke seluruh kontak yang memiliki `group_tag` tertentu.

---

## 4. Struktur Basis Data (Database Schema)

| Tabel | Kolom Utama | Deskripsi |
|---|---|---|
| **`contacts`** | `id`, `phone`, `name`, `group_tag`, `is_group`, `last_message_at` | Data kontak warga / nomor pengirim chat |
| **`messages`** | `id`, `contact_id`, `wablas_message_id`, `message`, `kind`, `metadata`, `type`, `status`, `message_timestamp` | Riwayat pesan teks/media (incoming / outgoing) |
| **`auto_replies`** | `id`, `keyword`, `reply`, `match_type`, `active` | Daftar kata kunci pemicu dan respon otomatis bot |
| **`scheduled_messages`** | `id`, `phone`, `name`, `is_group`, `kind`, `message`, `latitude`, `longitude`, `link_url`, `scheduled_at`, `status` | Antrean pesan yang dijadwalkan |
| **`settings`** | `id`, `key`, `value` | Pengaturan dinamis (`auto_reply_enabled`, `webhook_tracking`) |
| **`webhook_logs`** | `id`, `event`, `phone`, `status`, `payload` | Log inspeksi webhook untuk monitoring |

---

## 5. Antarmuka Dashboard Admin (`dashboard/index.blade.php`)

* **Tampilan WhatsApp Web Responsif:**
  * Panel percakapan kiri (daftar kontak, pesan terakhir, unread badge, waktu).
  * Panel chat aktif kanan (header info kontak, balon chat bergaya WA hijau/putih, form kirim pesan).
* **Pembaruan Realtime (Polling Non-Blocking):**
  * Polling otomatis setiap 2.5 detik untuk memperbarui daftar chat aktif dan percakapan.
  * Menggunakan algoritma **smart-diffing** pada elemen DOM sehingga halaman **tidak flicker**, scroll tidak melompat, dan input form admin tidak terganggu.
* **Dukungan Media Rich:**
  * Penampilan gambar / foto dengan modal popup resolusi penuh.
  * Audio player tersemat untuk pesan suara / voice note.
  * Video player terintegrasi.
  * Tautan unduh dokumen dengan ikon tipe file.
  * Penanda pin koordinat lokasi Google Maps.
* **Panel Samping Akses Cepat:**
  1. Tab Obrolan (Inbox).
  2. Tab Pesan Terjadwal (Form penjadwalan & daftar antrean).
  3. Tab Auto-Reply (Tambah, edit, hapus, dan uji coba kata kunci otomatis).
  4. Tab Broadcast / Kirim Grup (Kirim pesan berdasarkan tag kategori warga).
  5. Tab Pengaturan & Webhook Log (Toggle auto-reply, webhook tracker, dan sinkronisasi data).
  6. Ekspor PDF (Unduh rekapitulasi data kontak dan riwayat chat per periode).

---

## 6. Daftar Endpoint & Rute Web

### Rute Publik & Webhook
* `GET /` : Halaman input Access Key (`welcome.blade.php`).
* `POST /verify-access` : Verifikasi kunci akses admin.
* `POST /webhook/waha` : Webhook resmi penerima pesan WAHA.
* `POST /webhook/wablas` : Webhook legacy fallback.

### Rute Terproteksi (`middleware: check.access`)
* `GET /dashboard` : Halaman utama antarmuka WhatsApp Web.
* `GET /autoreply/manage` : [BARU] Halaman penuh manajemen aturan Auto-Reply & simulator realtime.
* `POST /autoreply/manage` : Tambah aturan auto-reply baru.
* `PUT /autoreply/manage/{id}` : Perbarui aturan auto-reply.
* `DELETE /autoreply/manage/{id}` : Hapus aturan auto-reply.
* `POST /autoreply/manage/{id}/toggle` : Toggle status aktif/nonaktif per aturan.
* `POST /autoreply/manage/toggle-global` : Sakelar master aktif/nonaktif Auto-Reply bot.
* `POST /autoreply/manage/test` : Simulator uji kecocokan pesan warga.
* `GET /contacts` : Endpoint JSON daftar kontak terbaru.
* `GET /conversation/{phone}` : Endpoint JSON riwayat chat nomor tertentu.
* `POST /reply` : Kirim pesan teks manual dari chatbox.
* `POST /send-location` : Kirim koordinat lokasi GPS.
* `POST /send-link` : Kirim tautan dengan pratinjau link.
* `POST /send-group` : Kirim broadcast pesan berdasarkan `group_tag`.
* `POST /import-contacts` : Impor kontak dari file CSV/TXT.
* `GET /autoreply` : Ambil daftar aturan auto-reply.
* `POST /autoreply` : Buat atau ubah aturan auto-reply.
* `DELETE /autoreply/{id}` : Hapus aturan auto-reply.
* `POST /settings` : Simpan preferensi bot (`auto_reply_enabled`, `webhook_tracking`).
* `GET /webhook/logs` : Inspeksi log webhook terakhir.
* `POST /dashboard/sync` : Sinkronisasi manual riwayat pesan dari WAHA.
* `GET /report/contacts-messages` : Unduh dokumen PDF rekap percakapan.

---

## 7. Catatan Pembaruan & Solusi Isu Penting

1. **Migrasi Gateway Wablas ke WAHA**:
   * Berhasil mengganti seluruh ketergantungan API Wablas ke WAHA endpoint (`/api/sendText`, `/api/sessions`, dll.).
2. **Isu LID (Nomor Kontak Acak/Tidak Dikenal)**:
   * **Masalah:** Nomor pengirim yang masuk dari WAHA adalah ID perangkat WhatsApp (`@lid`) bukan nomor telepon seluler.
   * **Solusi:** Diimplementasikan `resolveToPhone()` pada `WahaService` yang menerjemahkan LID menjadi format nomor standar `628xxx` sehingga kontak tersinkron dengan benar.
3. **Penyimpanan & Tampilan Gambar**:
   * **Masalah:** Gambar di database menggunakan prefix `localhost` dan gambar dari nomor warga tidak terunduh.
   * **Solusi:** Diperbaiki `APP_URL=http://127.0.0.1:8000` di `.env` dan `downloadMessageMedia()` otomatis mengunduh biner gambar saat webhook masuk lalu menyimpan ke path penyimpanan publik.
4. **Pencegahan Balasan Ganda (Double Reply Prevention)**:
   * **Masalah:** Bot sempat membalas 2 kali karena WAHA memancarkan event `message` dan `message.any` secara bersamaan untuk pesan yang sama.
   * **Solusi:** Menghapus event `message.any` dari langganan webhook sesi WAHA serta memasang cache deduplication lock pada `maybeAutoReply()` di `WebhookController.php`.
5. **Optimasi Loading Dashboard Admin**:
   * **Masalah:** Halaman admin loading sangat lama / macet saat pertama dibuka.
   * **Solusi:** Menghilangkan pemanggilan API eksternal yang memblokir proses rendering awal (seperti sync sinkronus dan pemangkasan pesan lawas). Memindahkan pengambilan data chat ke AJAX asynchronous.
5. **Real-time Chat Polling**:
   * Mengganti kebutuhan refresh manual dengan background polling setiap 2.5 detik menggunakan DOM reconciliation (smart diff) agar tidak mengganggu fokus pengguna saat mengetik pesan.
6. **Auto-Reply Keyword Trigger**:
   * Menambahkan seed kata kunci `"menu"` untuk respon daftar layanan nagari serta penanganan otomatis respon bot saat sesi WAHA aktif.
