# 🛡️ BACKUP & SAFETY CHECKLIST

## ✅ SEBELUM Implementasi Attendance Feature

### 1. Backup Database (WAJIB!)
```bash
# Via Command Line
mysqldump -u root -p edoc > backup_edoc_before_attendance.sql

# Atau via phpMyAdmin:
# 1. Pilih database 'edoc'
# 2. Klik tab 'Export'
# 3. Pilih 'Quick' export method
# 4. Format: SQL
# 5. Klik 'Go' dan simpan file
```

### 2. Backup Files (Recommended)
```bash
# Backup seluruh folder
cd c:\laragon\www
xcopy sskinv0.5.9 sskinv0.5.9_backup_20260111 /E /I

# Atau backup files spesifik yang akan dimodifikasi
cd c:\laragon\www\sskinv0.5.9
mkdir backup_files
copy patient\index.php backup_files\
copy patient\request-appointment.php backup_files\
copy admin\schedule.php backup_files\
copy doctor\index.php backup_files\
copy doctor\schedule.php backup_files\
```

### 3. Test di Environment Development Dulu
- [ ] Jangan langsung implementasi di production
- [ ] Test di local/development server terlebih dahulu
- [ ] Verifikasi semua fungsi bekerja dengan baik

### 4. Dokumentasi State Saat Ini
```sql
-- Catat struktur tabel schedule saat ini
DESCRIBE schedule;

-- Catat jumlah data
SELECT COUNT(*) FROM schedule;
SELECT COUNT(*) FROM doctor;
SELECT COUNT(*) FROM appointment;
```

---

## 🚀 LANGKAH IMPLEMENTASI AMAN

### Step 1: Backup (sudah dilakukan di atas)
✅ Database backup
✅ Files backup
✅ Dokumentasi state

### Step 2: Implementasi Database Migration
```bash
# Test di development dulu!
mysql -u root -p edoc_dev < migrations/add_attendance_status.sql

# Jika berhasil, baru ke production
mysql -u root -p edoc < migrations/add_attendance_status.sql
```

### Step 3: Deploy Files Baru
Upload/copy file-file baru:
- `doctor/attendance.php`
- `doctor/update_attendance.php`

### Step 4: Update Files yang Dimodifikasi
Backup dulu, baru update:
- `patient/index.php`
- `patient/request-appointment.php`
- `admin/schedule.php`
- `doctor/index.php`
- `doctor/schedule.php`

### Step 5: Testing
- [ ] Login sebagai dokter - test "Kelola Kehadiran"
- [ ] Login sebagai pasien - test "Dokter Tersedia Hari Ini"
- [ ] Test form request appointment
- [ ] Test admin add session
- [ ] Check browser console untuk error JavaScript
- [ ] Check server error logs

---

## 🔄 ROLLBACK CEPAT (Jika Ada Masalah)

### Opsi 1: Rollback Database Only
```sql
DROP INDEX IF EXISTS idx_schedule_attendance ON schedule;
DROP INDEX IF EXISTS idx_schedule_date_docid ON schedule;
ALTER TABLE schedule DROP COLUMN attendance_status;
```

### Opsi 2: Restore dari Backup
```bash
# Restore database
mysql -u root -p edoc < backup_edoc_before_attendance.sql

# Restore files
cd c:\laragon\www\sskinv0.5.9
xcopy ..\sskinv0.5.9_backup_20260111\* . /E /Y
```

### Opsi 3: Rollback Script Lengkap
```bash
mysql -u root -p edoc < migrations/rollback_attendance.sql
# + hapus file baru
# + restore file yang dimodifikasi
```

Detail lengkap: lihat [ROLLBACK_GUIDE.md](ROLLBACK_GUIDE.md)

---

## 📊 Monitoring Post-Implementation

### Hal yang Perlu Dimonitor (24-48 jam pertama):

#### Performance:
```sql
-- Check query performance
EXPLAIN SELECT DISTINCT doctor.* 
FROM doctor 
INNER JOIN schedule ON doctor.docid = schedule.docid 
WHERE schedule.scheduledate = CURDATE() 
AND schedule.attendance_status = 'present';
```

#### Data Integrity:
```sql
-- Pastikan tidak ada NULL values yang unexpected
SELECT COUNT(*) FROM schedule WHERE attendance_status IS NULL;

-- Cek distribusi status
SELECT attendance_status, COUNT(*) 
FROM schedule 
GROUP BY attendance_status;
```

#### User Behavior:
- [ ] Apakah dokter menggunakan fitur "Kelola Kehadiran"?
- [ ] Apakah pasien melihat dokter tersedia dengan benar?
- [ ] Apakah ada error reports dari user?

---

## 🎯 Success Criteria

Implementasi dianggap sukses jika:
- [x] Migration database berjalan tanpa error
- [x] Semua file ter-deploy dengan benar
- [x] Dokter bisa update attendance status
- [x] Dashboard pasien menampilkan dokter tersedia hari ini
- [x] Form booking filter dokter dengan benar
- [x] Tidak ada error di browser console
- [x] Tidak ada error di server logs
- [x] Performance tetap baik (response time < 2 detik)

---

## ⚠️ Red Flags (Tanda Harus Rollback)

Segera rollback jika:
- ❌ Error 500 di halaman manapun
- ❌ Database query error yang persistent
- ❌ Data corruption (appointment/schedule hilang)
- ❌ User tidak bisa login
- ❌ Performance degradation signifikan (>5x lebih lambat)
- ❌ JavaScript error yang menghalangi fungsi utama

---

## 📞 Emergency Contacts

Jika terjadi masalah serius:
1. **Segera rollback** menggunakan backup
2. Dokumentasikan error message
3. Check error logs: `c:\laragon\www\sskinv0.5.9\error_log`
4. Contact development team

---

## 📝 Post-Implementation Checklist

Setelah 48 jam implementasi stabil:
- [ ] Hapus backup files yang tidak diperlukan
- [ ] Update dokumentasi production
- [ ] Training user (jika diperlukan)
- [ ] Monitor terus selama 1 minggu
- [ ] Collect user feedback

---

**Remember:** 
> "Better safe than sorry. Always backup before making changes!"

**Version:** 0.5.9  
**Created:** Januari 2026
