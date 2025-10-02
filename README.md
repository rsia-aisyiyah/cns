# Central Notification Service 🔔  

**CNS (Central Notification Service)** adalah sistem **pengiriman notifikasi terpusat** yang dirancang untuk memudahkan aplikasi atau sistem lain dalam menyampaikan pesan ke berbagai saluran komunikasi (multi-channel), seperti **WhatsApp, email, SMS, atau platform pesan lainnya**.  

Dengan CNS, developer maupun user bisnis tidak perlu membangun integrasi notifikasi secara terpisah untuk tiap layanan, cukup sekali integrasi lalu semua notifikasi dapat dikelola melalui satu pintu.  

---

## 🎯 Manfaat Utama
- **Satu Pusat Kendali Notifikasi**  
  Semua notifikasi dari berbagai aplikasi terkumpul di satu layanan, sehingga mudah dipantau dan dikelola.  
- **Hemat Waktu & Biaya**  
  Tidak perlu lagi membuat integrasi terpisah untuk WhatsApp, email, dan saluran lain.  
- **Skalabilitas Tinggi**  
  Didukung sistem antrian (queue), sehingga pesan dapat diproses secara massal tanpa mengganggu performa aplikasi utama.  
- **Fleksibel & Mudah Diintegrasikan**  
  Menyediakan **API sederhana** yang bisa digunakan berbagai aplikasi pihak ketiga.  

---

## ✨ Fitur Utama
- **📱 Pengiriman Pesan WhatsApp**  
  Menggunakan WhatsApp API untuk mengirim pesan ke nomor telepon dengan aman dan cepat.  

- **🗂 Manajemen Notifikasi Terpusat**  
  Setiap notifikasi dapat dikategorikan, dijadwalkan, dan dikirim sesuai kondisi tertentu (misalnya notifikasi pembayaran, reminder janji temu, dan sebagainya.).  

- **📝 Dukungan Format Pesan**  
  Mendukung **markup sederhana** (seperti **bold**, *italic*, ~strikethrough~) yang otomatis disesuaikan dengan format WhatsApp.  

- **🔗 Integrasi dengan Sistem Lain**  
  Mudah dihubungkan dengan aplikasi eksternal melalui **REST API** atau **webhook**, cocok untuk ERP, HIS, CRM, maupun aplikasi custom.  

- **⏱ Notifikasi Terjadwal & Berulang**  
  Mendukung pengiriman notifikasi berdasarkan waktu tertentu (reminder harian, mingguan, dan sebagainya.).  

- **📊 Monitoring & Logging**  
  Setiap pesan tercatat statusnya (queued, sent, delivered, failed) untuk memudahkan pelacakan dan troubleshooting.  

---

## ⚙️ Teknologi yang Digunakan
CNS dibangun dengan teknologi modern agar **stabil, aman, dan mudah dikembangkan**:

- **[Laravel 10+](https://laravel.com/)** → Framework backend utama.  
- **[Livewire 3+](https://laravel-livewire.com/)** → Membuat antarmuka interaktif tanpa perlu banyak JavaScript.  
- **[FilamentPHP](https://filamentphp.com/)** → Panel admin untuk monitoring notifikasi & manajemen data.  
- **[Laravel Queue System](https://laravel.com/docs/queues)** → Memastikan pengiriman notifikasi berjalan teratur & scalable.  
- **WhatsApp API** → Untuk integrasi pesan WhatsApp.  
- **[PHP 8.1+](https://php.net/)** → Bahasa utama pengembangan.  

---

## Instalasi 💻

1. Clone Repository

    Clone repository ke mesin lokal Anda:
    ```bash
    git clone https://github.com/username/cns.git
    ```

2. Install Dependensi

    Setelah meng-clone repository, masuk ke direktori proyek dan jalankan perintah berikut untuk menginstal dependensi yang diperlukan:
    ```bash
    composer install
    ```

3. Konfigurasi `.env`

    Salin file `.env.example` ke `.env`:
    ```bash
    cp .env.example .env
    ```
    Kemudian, atur konfigurasi di dalam file `.env` sesuai dengan kebutuhan Anda:
    ```env
    # Database Connection
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=database
    DB_USERNAME=root
    DB_PASSWORD=

    # WhatsApp API Configurations
    API_WHATSAPP_URL=
    API_WHATSAPP_SESSION_NAME=
    ```
    
4. Jalankan Migrasi Database

    Pastikan database sudah dikonfigurasi dengan benar, kemudian jalankan migrasi untuk membuat tabel yang diperlukan:
    ```bash
    php artisan migrate
    ```

5. Menjalankan Queue Worker
    
    Pastikan worker queue berjalan untuk memproses antrian pesan:

    ```bash
    php artisan queue:work
    ```

## Panduan Kontribusi ⚒️

Kami menyambut kontribusi dari semua pengembang internal dan mitra kerja sama. Untuk memulai, bacalah [CONTRIBUTING](CONTRIBUTING.md).

## Lisensi 🔐

CNS (Central Notification Service) dilisensikan di bawah **[MIT License](LICENSE)**. Lihat file LICENSE untuk informasi lebih lanjut.
