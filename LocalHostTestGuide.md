# 🚀 Quick Start - Pindah ke Localhost Baru

## ⚡ Setup Cepat (5 Menit)

### 1️⃣ Copy File & Install Dependencies
```bash
# Pindah ke folder aplikasi
cd C:\laragon\www\sskinv0.5.9Uni

# Install dependencies
composer install
```

### 2️⃣ Setup Database
```bash
# Di phpMyAdmin atau MySQL client:
# 1. Buat database baru: edoc
# 2. Import file: edoc.sql

# Atau via command line:
mysql -u root -p
CREATE DATABASE edoc;
USE edoc;
SOURCE edoc.sql;
exit;
```

### 3️⃣ Konfigurasi .env
```bash
# File .env sudah ada! Cek dan sesuaikan:
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=           # Isi jika ada password
DB_NAME=edoc
DB_PORT=3306
```

### 4️⃣ Test Koneksi
```bash
# Buka di browser:
http://localhost/sskinv0.5.9Uni/test_connection.php

# Jika berhasil, hapus file test_connection.php
```

### 5️⃣ Akses Aplikasi
```
http://localhost/sskinv0.5.9Uni
```

---

## ❌ Error yang Sering Terjadi & Solusi Cepat

### "Database Connection Failed"
```bash
# Cek MySQL running
# Laragon: Klik Start MySQL
# XAMPP: Start MySQL di Control Panel

# Cek kredensial di .env
DB_USERNAME=root
DB_PASSWORD=          # Pastikan sesuai dengan MySQL Anda
```

### "Access Denied"
```bash
# Password salah - cek di .env
# Atau reset password MySQL
```

### "Table doesn't exist"
```bash
# Import ulang database
mysql -u root -p edoc < edoc.sql
```

### "Composer not found"
```bash
# Install Composer: https://getcomposer.org
# Atau gunakan Composer bawaan Laragon
```

---

## 📚 Dokumentasi Lengkap
Lihat [SETUP_LOCALHOST.md](SETUP_LOCALHOST.md) untuk panduan detail.

---

## ✅ Checklist
- [ ] Composer install
- [ ] Database dibuat
- [ ] Database di-import
- [ ] File .env dikonfigurasi
- [ ] Test koneksi berhasil

**Done! 🎉**
