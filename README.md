# Central Notification Service 🔔

**CNS** (Central Notification Service) adalah sistem pengiriman notifikasi terpusat yang memungkinkan aplikasi mengirim pesan notifikasi ke berbagai saluran, seperti WhatsApp, email, atau sistem pesan lainnya, melalui antarmuka yang mudah digunakan.

## Fitur Utama ✨
- **Pengiriman Pesan WhatsApp**: Kirim pesan ke nomor telepon menggunakan WhatsApp API.
- **Manajemen Notifikasi Terpusat**: Pengelolaan notifikasi dengan kemampuan untuk mengatur pesan berdasarkan berbagai kondisi dan waktu.
- **Dukungan untuk Format Pesan**: Mendukung HTML yang diubah menjadi format teks (misalnya, **bold**, *italic*, ~strikethrough~) yang dapat dikirimkan melalui WhatsApp.
- **Integrasi dengan Sistem Pihak Ketiga**: Mudah diintegrasikan dengan aplikasi lainnya melalui API.

## Teknologi yang Digunakan ⚙️

CNS (Central Notification Service) dibangun menggunakan berbagai teknologi untuk memastikan kinerja yang optimal dan pengembangan yang mudah. Berikut adalah teknologi yang digunakan:

- **[Laravel: v10.0+](https://laravel.com/)**
- **[Livewire: v3.0+](https://laravel-livewire.com/)**
- **[filamentphp](https://filamentphp.com/)**
- **[Queue System](https://laravel.com/docs/queues)**
- **WhatsApp API**
- **[PHP: 8.1+](https://php.net/)**


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

Kami menyambut kontribusi dari semua pengembang internal dan mitra kerja sama. Untuk memulai, bacalah [CONTRIBUTING.md](CONTRIBUTING.md).

## Lisensi 🔐

CNS (Central Notification Service) dilisensikan di bawah **[MIT License](LICENSE)**. Lihat file LICENSE untuk informasi lebih lanjut.