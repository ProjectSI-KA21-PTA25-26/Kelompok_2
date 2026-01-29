# 🚀 Render Deployment Guide - SSkin Beauty Clinic

## 📋 Overview

Render adalah platform hosting modern yang support:
- ✅ PHP 8.x (gratis)
- ✅ PostgreSQL (gratis untuk 90 hari, lalu $7/bulan)
- ✅ Automatic SSL/HTTPS
- ✅ Easy deployment dari GitHub

---

## 🎯 Step-by-Step Deployment

### **STEP 1: Setup Database (PostgreSQL)**

1. **Buat Akun Render**
   - Daftar di: https://render.com
   - Connect dengan GitHub

2. **Create PostgreSQL Database**
   - Dashboard → "New +" → "PostgreSQL"
   - Isi form:
     - **Name:** `sskin-database` (atau nama lain)
     - **Database:** `edoc`
     - **User:** (auto-generated)
     - **Region:** `Singapore` (pilih terdekat)
     - **Plan:** `Free` (atau Starter $7/month)
   - Click **"Create Database"**

3. **Simpan Database Credentials**
   Setelah database dibuat, Anda akan lihat:
   ```
   Internal Database URL: postgres://user:pass@host/dbname
   External Database URL: postgres://user:pass@host:port/dbname
   Host: xxx.oregon-postgres.render.com
   Port: 5432
   Database: edoc
   Username: sskin_user
   Password: xxxxxxxxxxxxx
   ```
   **PENTING:** Simpan semua info ini!

4. **Import Database Schema**
   
   **Option A: Via psql (Terminal)**
   ```bash
   # Install PostgreSQL client jika belum ada
   # Windows: Download dari postgresql.org
   
   # Connect ke database
   psql "postgres://user:pass@host:port/dbname"
   
   # Copy paste content dari edoc_postgresql.sql
   # Atau:
   psql "postgres://user:pass@host:port/dbname" < edoc_postgresql.sql
   ```

   **Option B: Via TablePlus/DBeaver (GUI)**
   - Download TablePlus: https://tableplus.com
   - Create new connection → PostgreSQL
   - Paste connection details dari Render
   - Connect
   - Run SQL file: `edoc_postgresql.sql`

   **Option C: Via Render SQL Console**
   - Di dashboard database Render
   - Go to "Query" tab
   - Copy-paste content dari `edoc_postgresql.sql`
   - Execute

---

### **STEP 2: Deploy Web Application**

1. **Upload Code ke GitHub** (jika belum)
   ```bash
   cd c:\laragon\www\sskinv0.6
   
   # Init git jika belum
   git init
   git add .
   git commit -m "Initial commit for Render"
   
   # Create repo di GitHub, lalu:
   git remote add origin https://github.com/username/sskin-app.git
   git push -u origin main
   ```

2. **Create Web Service di Render**
   - Dashboard → "New +" → "Web Service"
   - Connect your GitHub repository
   - Pilih repository `sskin-app`
   - Isi form:
     - **Name:** `sskin-beauty-clinic`
     - **Region:** `Singapore`
     - **Branch:** `main`
     - **Runtime:** `Docker` atau `Native Environment`
     - **Build Command:** (kosongkan atau `composer install`)
     - **Start Command:** (kosongkan, otomatis detect PHP)
     - **Plan:** `Free`

3. **Set Environment Variables**
   
   Di bagian "Environment", tambahkan:
   
   **Untuk PostgreSQL (Dari Render Database):**
   ```
   DATABASE_URL = <paste Internal Database URL dari step 1>
   
   # Format: postgres://user:pass@host:port/dbname
   ```

   **Untuk Email (PHPMailer):**
   ```
   SMTP_HOST = smtp.gmail.com
   SMTP_PORT = 587
   SMTP_USERNAME = your-email@gmail.com
   SMTP_PASSWORD = your-app-password
   SMTP_FROM_EMAIL = your-email@gmail.com
   SMTP_FROM_NAME = SSkin Beauty Clinic
   ```

   **Application Settings:**
   ```
   APP_URL = https://sskin-beauty-clinic.onrender.com
   ```

4. **Deploy!**
   - Click **"Create Web Service"**
   - Render akan otomatis build & deploy
   - Tunggu 5-10 menit untuk first deployment

---

### **STEP 3: Verify Deployment**

1. **Check Database Connection**
   ```
   https://sskin-beauty-clinic.onrender.com/debug_connection.php
   ```
   
   Harus muncul:
   - ✅ DATABASE_URL exists: YES
   - ✅ pgsql extension: YES
   - ✅ Connection successful
   - ✅ 8 tables found

2. **Test Login**
   ```
   https://sskin-beauty-clinic.onrender.com/login.php
   ```
   
   Login dengan:
   - Email: `admin@edoc.com`
   - Password: `123`

3. **Delete Debug Files** (setelah berhasil)
   ```bash
   git rm debug_connection.php test_connection.php test_email.php
   git commit -m "Remove debug files"
   git push
   ```
   Render otomatis re-deploy!

---

## 🔧 Render Configuration Files

### `render.yaml` (Optional - Auto Deploy Config)

Buat file `render.yaml` di root project:

```yaml
services:
  - type: web
    name: sskin-beauty-clinic
    runtime: docker
    plan: free
    region: singapore
    branch: main
    healthCheckPath: /
    envVars:
      - key: DATABASE_URL
        fromDatabase:
          name: sskin-database
          property: connectionString
      - key: APP_URL
        value: https://sskin-beauty-clinic.onrender.com
    
databases:
  - name: sskin-database
    databaseName: edoc
    plan: free
    region: singapore
```

---

## 📝 Connection String Format

Render PostgreSQL connection string:
```
postgres://user:password@host:port/database
```

Contoh:
```
postgres://sskin_user:AbCd1234XyZ@dpg-abc123.oregon-postgres.render.com:5432/edoc
```

File `connection.php` sudah support format ini! ✅

---

## 🔐 Security Checklist

- [ ] Database imported successfully
- [ ] Environment variables set correctly  
- [ ] Login works with test account
- [ ] Delete debug files (debug_connection.php, test_*.php)
- [ ] Change default passwords:
  - Admin: admin@edoc.com
  - Doctor: doctor@edoc.com
  - Patient: patient@edoc.com
- [ ] Setup custom domain (optional)
- [ ] Enable automatic deployments from GitHub

---

## 🆚 Render vs Heroku vs Others

| Feature | Render | Heroku | Railway |
|---------|--------|--------|---------|
| PHP Support | ✅ Native | ✅ Buildpack | ✅ Native |
| PostgreSQL | ✅ Free 90 days | ✅ Paid only | ✅ $5/month |
| Auto SSL | ✅ Free | ✅ Free | ✅ Free |
| Custom Domain | ✅ Free | ✅ Paid | ✅ Free |
| Deploy Time | 5-10 min | 3-5 min | 5-10 min |
| Free Tier | ✅ 750h/month | ❌ Removed | ✅ $5 credit |

**Rekomendasi:** Render untuk Production! 🎯

---

## ⚡ Troubleshooting

### Problem: Build Failed
**Solution:** Check build logs di Render dashboard
- Pastikan `composer.json` ada
- Pastikan PHP version compatible

### Problem: Database connection failed
**Solution:**
1. Check DATABASE_URL di environment variables
2. Pastikan database sudah created
3. Pastikan schema sudah di-import

### Problem: Blank screen
**Solution:**
1. Check logs: `Dashboard → Logs`
2. Enable error reporting temporarily
3. Check debug_connection.php

### Problem: Application Error
**Solution:**
```bash
# Check logs
# Di Render Dashboard → Your Service → Logs tab
```

---

## 🎨 Custom Domain Setup (Optional)

1. **Buy Domain** (dari Namecheap, GoDaddy, etc)

2. **Add to Render**
   - Dashboard → Your Web Service → Settings
   - Scroll ke "Custom Domain"
   - Add domain: `yourdomain.com`

3. **Update DNS**
   - Di domain registrar Anda
   - Add CNAME record:
     - Name: `www`
     - Value: `sskin-beauty-clinic.onrender.com`
   - Add A record:
     - Name: `@`
     - Value: (IP dari Render)

4. **Wait for SSL** (automatic, 5-10 menit)

---

## 💰 Pricing

### Free Tier:
- ✅ Web Service: 750 hours/month (cukup 1 app 24/7)
- ✅ PostgreSQL: Free 90 hari, lalu $7/month
- ✅ 100GB bandwidth
- ✅ Auto SSL
- ⚠️ Sleep after 15 min inactivity (free tier)

### Starter Plan ($7/month):
- ✅ Always-on (no sleep)
- ✅ More resources
- ✅ Better performance

---

## 📞 Next Steps

1. ✅ Create Render account
2. ✅ Create PostgreSQL database
3. ✅ Import edoc_postgresql.sql
4. ✅ Push code to GitHub
5. ✅ Create Web Service
6. ✅ Set environment variables (DATABASE_URL)
7. ✅ Deploy!
8. ✅ Test with debug_connection.php
9. ✅ Login and verify
10. ✅ Delete debug files

---

**Last Updated:** January 14, 2026
**Status:** Production Ready for Render ✅

**Official Docs:** https://render.com/docs
**Support:** support@render.com
