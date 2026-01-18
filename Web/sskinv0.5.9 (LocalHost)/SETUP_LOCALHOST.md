# 🚀 Panduan Setup di Localhost Baru

Panduan ini akan membantu Anda memindahkan dan menjalankan aplikasi SSkIN di localhost atau server lokal yang berbeda.

---

## 📋 Persyaratan

### Untuk Setup Manual (Laragon/XAMPP/WAMP):
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB 5.7 atau lebih tinggi
- Composer (untuk dependencies)
- Web Server (Apache/Nginx)

### Untuk Setup Docker:
- Docker Desktop
- Docker Compose

---

## 🔧 Setup Manual (Laragon/XAMPP/WAMP)

### Langkah 1: Copy Files
```bash
# Copy seluruh folder aplikasi ke direktori web server Anda
# Contoh Laragon: C:\laragon\www\sskin
# Contoh XAMPP: C:\xampp\htdocs\sskin
```

### Langkah 2: Install Dependencies
```bash
# Buka terminal/command prompt di folder aplikasi
cd C:\laragon\www\sskin  # sesuaikan path Anda

# Install dependencies menggunakan Composer
composer install
```

### Langkah 3: Konfigurasi Database

#### 3a. Buat File .env
```bash
# Copy file .env.example menjadi .env
copy .env.example .env
```

#### 3b. Edit File .env
Buka file `.env` dan sesuaikan dengan konfigurasi MySQL Anda:

```env
# Konfigurasi Database Anda
DB_HOST=localhost
DB_USERNAME=root          # Username MySQL Anda
DB_PASSWORD=              # Password MySQL Anda (kosongkan jika tidak ada)
DB_NAME=edoc              # Nama database
DB_PORT=3306              # Port MySQL (biasanya 3306)

# URL Aplikasi
APP_URL=http://localhost/sskin   # Sesuaikan dengan path Anda
APP_ENV=development
```

**Catatan Penting:**
- Jika menggunakan Laragon dengan port khusus, contoh: `http://localhost:8080`
- Jika menggunakan XAMPP: `http://localhost/sskin`
- Pastikan **tidak ada spasi** di sekitar tanda `=`

#### 3c. Import Database
```bash
# Buka phpMyAdmin atau MySQL client Anda
# Atau gunakan command line:

# Untuk MySQL:
mysql -u root -p
CREATE DATABASE edoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edoc;
SOURCE edoc.sql;
exit;

# Atau jika menggunakan PostgreSQL:
psql -U postgres
CREATE DATABASE edoc;
\c edoc
\i edoc.psql
\q
```

### Langkah 4: Set Permissions (Penting!)
```bash
# Pastikan folder berikut writable:
# - folder root (untuk email_log.txt jika ada)
# - folder uploads (jika ada)

# Di Windows, klik kanan folder > Properties > Security
# Berikan Full Control untuk user yang menjalankan web server
```

### Langkah 5: Test Aplikasi
1. Buka browser
2. Akses: `http://localhost/sskin` (sesuaikan path Anda)
3. Anda akan melihat halaman login/index

---

## 🐳 Setup dengan Docker

### Langkah 1: Pastikan Docker Running
```bash
# Check Docker status
docker --version
docker-compose --version
```

### Langkah 2: Buat File .env
```bash
copy .env.example .env
```

### Langkah 3: Edit .env untuk Docker
```env
# Database Configuration (Docker)
DB_HOST=db                    # PENTING: gunakan 'db' bukan 'localhost'
DB_USERNAME=user              # Sesuai docker-compose.yml
DB_PASSWORD=password          # Sesuai docker-compose.yml
DB_NAME=edoc
DB_PORT=3306

APP_URL=http://localhost:8080
APP_ENV=development
```

### Langkah 4: Jalankan Docker
```bash
# Build dan jalankan containers
docker-compose up -d

# Check status containers
docker-compose ps

# Lihat logs jika ada error
docker-compose logs -f
```

### Langkah 5: Import Database ke Docker
```bash
# Import database ke container
docker-compose exec db mysql -u user -ppassword edoc < edoc.sql
```

### Langkah 6: Test Aplikasi
- Akses: `http://localhost:8080`

---

## ⚠️ Troubleshooting - Error Umum

### Error 1: "Database Connection Failed"
**Penyebab:**
- Kredensial database salah
- MySQL service tidak running
- Port konflik

**Solusi:**
```bash
# Cek MySQL service
# Laragon: Start MySQL dari Laragon panel
# XAMPP: Start MySQL dari XAMPP Control Panel

# Verifikasi kredensial di .env
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=       # Kosongkan jika memang tidak ada password

# Test koneksi manual
mysql -u root -p -h localhost
```

### Error 2: "Access Denied for User"
**Solusi:**
```bash
# Reset password MySQL atau buat user baru
mysql -u root -p
CREATE USER 'newuser'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON edoc.* TO 'newuser'@'localhost';
FLUSH PRIVILEGES;

# Update .env
DB_USERNAME=newuser
DB_PASSWORD=password123
```

### Error 3: "Table doesn't exist"
**Solusi:**
```bash
# Import ulang database
mysql -u root -p edoc < edoc.sql
```

### Error 4: "Composer not found"
**Solusi:**
```bash
# Install Composer dari https://getcomposer.org/download/
# Atau di Laragon, gunakan built-in Composer
# Restart terminal setelah install
```

### Error 5: "Permission Denied" / Cannot Write Files
**Solusi Windows:**
- Klik kanan folder aplikasi > Properties > Security
- Edit > Add > Everyone > Full Control > OK

**Solusi Linux/Mac:**
```bash
chmod -R 755 /path/to/application
chmod -R 777 /path/to/uploads  # jika ada folder uploads
```

### Error 6: Docker "Port already allocated"
**Solusi:**
```bash
# Ubah port di docker-compose.yml
# Ganti 8080:80 menjadi 8081:80
# Atau stop service yang menggunakan port tersebut
```

### Error 7: "Class 'mysqli' not found"
**Solusi:**
```bash
# Enable mysqli extension di php.ini
# Cari: ;extension=mysqli
# Ubah menjadi: extension=mysqli
# Restart web server
```

---

## 🧪 Verifikasi Setup

### Cek Koneksi Database:
Buat file `test_connection.php` di root folder:

```php
<?php
require_once 'connection.php';

echo "<h2>Database Connection Test</h2>";

if ($database) {
    echo "<p style='color: green;'>✅ Connected successfully to database: " . $dbname . "</p>";
    
    // Test query
    $result = $database->query("SHOW TABLES");
    echo "<p>Tables in database:</p><ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Connection failed</p>";
}
?>
```

Akses: `http://localhost/sskin/test_connection.php`

**Setelah verifikasi berhasil, HAPUS file test_connection.php untuk keamanan!**

---

## 📁 Struktur File Penting

```
sskin/
├── .env                    # ← BUAT FILE INI! (copy dari .env.example)
├── .env.example            # Template konfigurasi
├── connection.php          # File koneksi database (sudah updated)
├── composer.json           # Dependencies
├── docker-compose.yml      # Docker configuration
├── edoc.sql               # Database MySQL
├── edoc.psql              # Database PostgreSQL
└── config/
    └── email.php          # Konfigurasi email (optional)
```

---

## 🔐 Keamanan

1. **JANGAN commit file .env ke Git**
   - File `.env` berisi kredensial sensitif
   - Sudah ditambahkan ke `.gitignore`

2. **Ganti password default** setelah setup
3. **Disable error reporting** di production:
   ```php
   // Di php.ini atau .htaccess
   display_errors = Off
   log_errors = On
   ```

---

## 📞 Bantuan Lebih Lanjut

Jika masih ada masalah:

1. **Cek error log:**
   - Laragon: `C:\laragon\bin\apache\logs\error.log`
   - XAMPP: `C:\xampp\apache\logs\error.log`
   - Docker: `docker-compose logs`

2. **Aktifkan debug mode:** (sementara)
   ```php
   // Tambahkan di awal connection.php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. **Verifikasi PHP version:**
   ```bash
   php -v
   ```

---

## ✅ Checklist Setup

- [ ] PHP 7.4+ terinstall
- [ ] MySQL/MariaDB running
- [ ] Composer dependencies installed
- [ ] File `.env` dibuat dan dikonfigurasi
- [ ] Database `edoc` dibuat
- [ ] Database di-import (edoc.sql)
- [ ] Permissions folder sudah di-set
- [ ] Test koneksi database berhasil
- [ ] Aplikasi bisa diakses via browser

---

**Selamat! Aplikasi Anda sekarang siap digunakan di localhost baru! 🎉**
