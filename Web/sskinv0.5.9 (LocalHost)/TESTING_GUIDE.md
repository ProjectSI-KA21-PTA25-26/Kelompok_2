# 🎯 Panduan Testing Aplikasi Setelah Pindah Localhost

Gunakan checklist ini untuk memastikan semua fitur berjalan dengan baik setelah dipindahkan ke localhost baru.

---

## ✅ Pre-Testing Checklist

### 1. Verifikasi Setup Dasar
- [ ] MySQL/MariaDB service running
- [ ] Apache/Nginx web server running  
- [ ] PHP version 7.4+ installed
- [ ] Composer dependencies installed (`composer install`)
- [ ] Database `edoc` sudah dibuat
- [ ] Database sudah di-import (`edoc.sql`)
- [ ] File `.env` sudah dikonfigurasi dengan benar
- [ ] Test koneksi database berhasil (test_connection.php)

---

## 🧪 Testing Langkah demi Langkah

### 2. Test Halaman Utama
```
URL: http://localhost/sskinv0.5.9Uni
atau: http://localhost:8080 (jika pakai Docker)
```

**Expected:**
- ✅ Halaman index/login tampil
- ✅ Tidak ada error PHP
- ✅ CSS loading dengan baik

**Jika Error:**
- Cek error log: `C:\laragon\bin\apache\logs\error.log`
- Cek path aplikasi benar

---

### 3. Test Login Admin
```
URL: http://localhost/sskinv0.5.9Uni/login.php
```

**Test Account:** (default dari database)
```
Email: admin@edoc.com
Password: 123
```

**Expected:**
- ✅ Bisa login
- ✅ Redirect ke admin dashboard
- ✅ Sidebar dan menu tampil

**Jika Error:**
- Cek database table `webuser` dan `admin`
- Verifikasi session PHP bekerja

---

### 4. Test Login Doctor
```
URL: http://localhost/sskinv0.5.9Uni/login.php
```

**Test Account:** (cek di database table doctor & webuser)
```
Email: [email dokter dari database]
Password: [password dokter]
```

**Expected:**
- ✅ Bisa login sebagai dokter
- ✅ Dashboard dokter tampil
- ✅ Bisa lihat appointments

---

### 5. Test Login Patient
```
URL: http://localhost/sskinv0.5.9Uni/login.php
```

**Test Account:** (cek di database table patient & webuser)
```
Email: [email patient dari database]
Password: [password patient]
```

**Expected:**
- ✅ Bisa login sebagai patient
- ✅ Dashboard patient tampil
- ✅ Bisa booking appointment

---

### 6. Test Database Operations

#### A. Create (Insert)
**Test:** Tambah appointment baru
```
Login as Patient > Booking > Pilih dokter & jadwal > Submit
```

**Expected:**
- ✅ Appointment tersimpan
- ✅ Muncul di history
- ✅ Tampil di admin panel

**Verify di Database:**
```sql
SELECT * FROM appointment ORDER BY appodate DESC LIMIT 5;
```

#### B. Read (Select)
**Test:** Lihat daftar doctors
```
Login as Patient > Doctors
```

**Expected:**
- ✅ List dokter tampil
- ✅ Foto dan info benar

#### C. Update
**Test:** Edit doctor profile
```
Login as Doctor > Settings > Edit profile > Save
```

**Expected:**
- ✅ Data ter-update
- ✅ Perubahan tersimpan di database

#### D. Delete
**Test:** Cancel appointment
```
Login as Patient > History > Cancel appointment
```

**Expected:**
- ✅ Status berubah jadi cancelled
- ✅ Data tidak hilang, hanya status berubah

---

### 7. Test Email Notifications

**Setup:** Pastikan SMTP enabled di `.env`
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

**Test:** Booking appointment (trigger email)

**Expected:**
- ✅ Email terkirim ke patient
- ✅ Email terkirim ke admin
- ✅ Email template tampil dengan baik
- ✅ Cek `email_log.txt` untuk log

**Jika Email Tidak Terkirim:**
- SMTP_ENABLED=0 → Email dimatikan (normal untuk local dev)
- SMTP_ENABLED=1 tapi gagal → Cek kredensial SMTP
- Gunakan Gmail App Password, bukan password biasa

---

### 8. Test File Uploads (Jika Ada)

**Test:** Upload foto profile dokter/patient

**Expected:**
- ✅ File ter-upload
- ✅ Tersimpan di folder yang benar
- ✅ Tampil di profile

**Check Permissions:** Folder upload harus writable

---

### 9. Test Responsive Design

**Test di berbagai ukuran:**
- Desktop (1920x1080)
- Tablet (768x1024)
- Mobile (375x667)

**Expected:**
- ✅ Layout responsive
- ✅ Menu mobile bekerja
- ✅ Table scrollable di mobile

---

### 10. Test Multi-Role Access

**Test:** Login dengan role berbeda dan pastikan:
- ✅ Admin tidak bisa akses `/doctor/`
- ✅ Doctor tidak bisa akses `/admin/`
- ✅ Patient tidak bisa akses admin/doctor pages
- ✅ Logout bekerja untuk semua role

---

## 🔍 Performance Testing

### 11. Load Time
**Test:**
```
- Halaman utama < 2 detik
- Dashboard < 3 detik
- List data < 2 detik
```

**Jika Lambat:**
- Cek query database (gunakan EXPLAIN)
- Optimize images
- Enable caching

---

### 12. Database Query Testing

**Run slow query test:**
```sql
-- Check appointment dengan join
SELECT a.*, d.docname, p.pname 
FROM appointment a
JOIN doctor d ON a.docid = d.docid
JOIN patient p ON a.pid = p.pid
LIMIT 100;
```

**Expected:** < 100ms untuk 100 rows

---

## 🐛 Common Issues & Solutions

### Issue 1: "Headers already sent"
**Cause:** Output sebelum header()
**Fix:** Cek BOM di file PHP, pastikan tidak ada echo sebelum header

### Issue 2: Session tidak persist
**Cause:** session_start() tidak dipanggil
**Fix:** Tambahkan session_start() di awal file

### Issue 3: CSS/JS tidak load
**Cause:** Path relatif salah
**Fix:** Gunakan absolute path atau base URL

### Issue 4: Query lambat
**Cause:** Index database kurang
**Fix:** Tambahkan index di kolom yang sering di-query

---

## 📊 Testing Checklist Summary

| Feature | Status | Notes |
|---------|--------|-------|
| Database Connection | ⬜ |  |
| Halaman Index | ⬜ |  |
| Admin Login | ⬜ |  |
| Doctor Login | ⬜ |  |
| Patient Login | ⬜ |  |
| Booking Appointment | ⬜ |  |
| View Appointments | ⬜ |  |
| Cancel Appointment | ⬜ |  |
| Edit Profile | ⬜ |  |
| Email Notifications | ⬜ |  |
| Responsive Design | ⬜ |  |
| File Uploads | ⬜ |  |
| Role-based Access | ⬜ |  |
| Performance | ⬜ |  |

---

## 🎯 Testing Tools

### Browser DevTools
```
F12 → Console: Cek JavaScript errors
F12 → Network: Cek API calls dan load time
F12 → Application: Cek cookies & session storage
```

### Database Tools
```
phpMyAdmin: Visual database management
MySQL Workbench: Query optimization
```

### PHP Debugging
```php
// Tambahkan di awal file untuk debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log variabel
error_log(print_r($variable, true));
```

---

## ✅ Sign-off

**Tested by:** _______________  
**Date:** _______________  
**Environment:** _______________  
**Result:** ⬜ PASS  ⬜ FAIL

**Notes:**
```
______________________________________
______________________________________
______________________________________
```

---

**Jika semua test PASS, aplikasi siap digunakan! 🎉**

Untuk production deployment, lihat dokumentasi tambahan tentang:
- Security hardening
- Performance optimization
- Backup strategy
- Monitoring setup
