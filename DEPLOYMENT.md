# 🚀 Panduan Penerapan Peladen Produksi (Production Deployment Guide)
### **Sistem Laporan Kinerja Pegawai (SLKP) — KPU Kota Bukittinggi**

Panduan ini ditujukan bagi Administrator Sistem dan Tim TI KPU Kota Bukittinggi untuk melakukan instalasi dan pemeliharaan aplikasi di lingkungan peladen produksi (*production server*) berbasis Linux (Ubuntu 22.04 / 24.04 LTS) atau infrastruktur cloud/on-premise pemerintah.

---

## 🖥️ 1. Arsitektur Peladen yang Direkomendasikan

| Komponen | Spesifikasi Rekomendasi | Keterangan |
|---|---|---|
| **Sistem Operasi** | Ubuntu Server 22.04 LTS / 24.04 LTS | Sistem operasi standar SPBE |
| **Processor** | 2 vCPU atau lebih | Untuk konkurensi jam sibuk (07:30 - 08:00 WIB) |
| **RAM** | 4 GB atau lebih | Minimal 2 GB |
| **Penyimpanan** | 40 GB SSD / NVMe | Penyimpanan database dan berkas lampiran |
| **Web Server** | Nginx versi 1.18+ | Performa tinggi untuk request statis dan reverse proxy |
| **PHP Runtime** | PHP 8.2 atau PHP 8.3 FPM | Ekstensi: `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `fileinfo`, `gd` |
| **Database** | PostgreSQL Server 14+ | Relational DBMS berstandar ACID |
| **Process Manager** | Supervisor (Systemd daemon) | Menjaga antrean Queue Worker Google Sheets selalu berjalan |
| **Sertifikat SSL** | Let's Encrypt (Certbot) / Kominfo SSL | Enkripsi HTTPS wajib untuk PWA dan keamanan transmisi |

---

## 📦 2. Persiapan Paket Perangkat Lunak di Server

Masuk ke server via SSH dan perbarui repositori paket:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql postgresql-contrib git curl unzip supervisor certbot python3-certbot-nginx
```

Instalasi PHP 8.3 dan ekstensi pendukung:
```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-fileinfo php8.3-gd
```

Instalasi Composer (Package Manager):
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 🗄️ 3. Konfigurasi Database PostgreSQL

Buat pengguna dan basis data untuk aplikasi:
```bash
sudo -u postgres psql
```
Di dalam terminal PostgreSQL, jalankan perintah berikut:
```sql
CREATE DATABASE slkp_kpu;
CREATE USER slkp_user WITH ENCRYPTED PASSWORD 'PASSWORD_SANGAT_KUAT_ANDA';
GRANT ALL PRIVILEGES ON DATABASE slkp_kpu TO slkp_user;
ALTER DATABASE slkp_kpu OWNER TO slkp_user;
\q
```

---

## 📂 4. Kloning Proyek & Pengaturan Hak Akses

Tempatkan proyek di direktori web standar (`/var/www/slkp-kpu`):
```bash
sudo git clone https://github.com/<organisasi>/SLKP-KPUBukittinggi.git /var/www/slkp-kpu
cd /var/www/slkp-kpu
```

Atur kepemilikan dan hak akses direktori:
```bash
sudo chown -R www-data:www-data /var/www/slkp-kpu
sudo chmod -R 775 /var/www/slkp-kpu/storage
sudo chmod -R 775 /var/www/slkp-kpu/bootstrap/cache
```

Instalasi dependensi PHP produksi (tanpa dependensi development):
```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

---

## ⚙️ 5. Konfigurasi Berkas Lingkungan (`.env`)

Salin dan edit berkas `.env`:
```bash
sudo cp .env.example .env
sudo nano .env
```

Pastikan variabel-variabel penting diatur untuk lingkungan **produksi**:
```ini
APP_NAME="SLKP KPU Kota Bukittinggi"
APP_ENV=production
APP_KEY=                    # Dihasilkan pada langkah berikutnya
APP_DEBUG=false
APP_URL=https://slkp.kpu-bukittinggikota.go.id

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=slkp_kpu
DB_USERNAME=slkp_user
DB_PASSWORD=PASSWORD_SANGAT_KUAT_ANDA

SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database

# Pengaturan Jam Dinas
KPU_JAM_DINAS_ENFORCE=true
KPU_JAM_DINAS_START="06:30"
KPU_JAM_DINAS_END="19:00"
KPU_AGENDA_KHUSUS_PEMILU=false

# Google Apps Script Web App Endpoint
GOOGLE_SHEET_HADIR_URL="https://script.google.com/macros/s/.../exec"
GOOGLE_SHEET_TIDAKHADIR_URL="https://script.google.com/macros/s/.../exec"
```

Generate application key:
```bash
sudo -u www-data php artisan key:generate
```

Jalankan migrasi skema dan seeder master data:
```bash
sudo -u www-data php artisan migrate --force --seed
```

Hubungkan direktori berkas publik:
```bash
sudo -u www-data php artisan storage:link
```

Optimalisasi cache Laravel untuk kecepatan maksimal di produksi:
```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

---

## 🌐 6. Konfigurasi Web Server Nginx

Buat berkas konfigurasi virtual host baru:
```bash
sudo nano /etc/nginx/sites-available/slkp-kpu.conf
```

Tambahkan konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name slkp.kpu-bukittinggikota.go.id;
    root /var/www/slkp-kpu/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    # Batas ukuran upload foto lampiran (maksimal 10MB)
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Caching statis untuk performa PWA
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|webmanifest|json)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan muat ulang Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/slkp-kpu.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Pasang sertifikat SSL gratis (HTTPS):
```bash
sudo certbot --nginx -d slkp.kpu-bukittinggikota.go.id
```

---

## 🔄 7. Menjalankan Queue Worker dengan Supervisor

Aplikasi SLKP menggunakan background queue untuk sinkronisasi Google Sheets. Gunakan **Supervisor** agar worker otomatis berjalan dan melakukan *restart* jika terjadi kegagalan.

Buat berkas konfigurasi Supervisor:
```bash
sudo nano /etc/supervisor/conf.d/slkp-worker.conf
```

Isikan konfigurasi berikut:
```ini
[program:slkp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/slkp-kpu/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/slkp-kpu/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Aktifkan proses Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start slkp-worker:*
sudo supervisorctl status
```

---

## ⏰ 8. Pengaturan Penjadwal Otomatis (Cron Job)

Buka crontab untuk pengguna `www-data`:
```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut agar Laravel Scheduler berjalan setiap menit:
```cron
* * * * * cd /var/www/slkp-kpu && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 9. Prosedur Pembaruan Aplikasi (Deployment Update Script)

Jika terdapat pembaruan kode di kemudian hari, jalankan langkah pemeliharaan berikut:

```bash
cd /var/www/slkp-kpu
sudo -u www-data php artisan down --render="errors.503" --secret="rahasia-admin"

# Tarik kode terbaru
sudo git pull origin main

# Update dependensi
sudo -u www-data composer install --no-dev --optimize-autoloader

# Jalankan migrasi database
sudo -u www-data php artisan migrate --force

# Segarkan cache produksi
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart antrean worker
sudo supervisorctl restart slkp-worker:*

# Buka kembali aplikasi
sudo -u www-data php artisan up
```
