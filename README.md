---
title: OCR App
emoji: 📄
colorFrom: blue
colorTo: indigo
sdk: docker
app_port: 7860
pinned: false
---

# OCR App — Project Akhir

Aplikasi OCR berbasis Laravel + Tesseract + OpenCV.

## Stack

- Laravel 11 (PHP 8.4)
- Tesseract OCR (ind + eng)
- Python 3 + OpenCV (preprocessing)
- MySQL (Aiven)
- Google OAuth login (Socialite)

## Menjalankan secara lokal

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Aplikasi ini dideploy sebagai Docker Space di Hugging Face — konfigurasi build ada di `Dockerfile`.
