<div align="center">
  <img src="public/logo2.webp" alt="Schlemmer Logo" width="120" />
  <h1 align="center">Schlemmer Core — PFMEA Management System</h1>
  <p align="center">
    Sistem Manajemen <strong>PFMEA (Process Failure Mode & Effects Analysis)</strong> berbasis web<br />
    untuk industri manufaktur otomotif — <em>IATF 16949 & APQP Ready</em>
  </p>
  <p align="center">
    <img src="https://img.shields.io/badge/PHP-8.4-%23777BB4?logo=php&logoColor=white" alt="PHP 8.4" />
    <img src="https://img.shields.io/badge/Laravel-12-%23FF2D20?logo=laravel&logoColor=white" alt="Laravel 12" />
    <img src="https://img.shields.io/badge/Vue.js-3.5-%234FC08D?logo=vuedotjs&logoColor=white" alt="Vue 3" />
    <img src="https://img.shields.io/badge/Inertia.js-3.1-%239555E9?logo=inertia&logoColor=white" alt="Inertia" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-4-%2306B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4" />
    <img src="https://img.shields.io/badge/Docker-Ready-%232496ED?logo=docker&logoColor=white" alt="Docker Ready" />
    <img src="https://img.shields.io/badge/MySQL-8.0-%234479A1?logo=mysql&logoColor=white" alt="MySQL" />
  </p>
  <hr />
</div>

## 📋 Daftar Isi

- [Tentang Project](#tentang-project)
- [Fitur Utama](#fitur-utama)
- [Arsitektur Sistem](#arsitektur-sistem)
- [Tech Stack](#tech-stack)
- [Struktur Direktori](#struktur-direktori)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi & Setup](#instalasi--setup)
  - [Docker (Recommended)](#docker-recommended)
  - [Manual Setup](#manual-setup)
- [Konfigurasi](#konfigurasi)
- [Penggunaan](#penggunaan)
- [Modul & Fungsionalitas](#modul--fungsionalitas)
- [API Endpoints](#api-endpoints)
- [Database & Migrasi](#database--migrasi)
- [Permission & Role](#permission--role)
- [Coding Standards](#coding-standards)
- [Docker Environment](#docker-environment)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 Tentang Project

**Schlemmer Core** adalah sistem informasi manajemen PFMEA (*Process Failure Mode & Effects Analysis*) yang dibangun khusus untuk kebutuhan industri manufaktur otomotif, khususnya proses *plastic injection molding*. Sistem ini dirancang untuk memenuhi standar **IATF 16949** dan mendukung metodologi **APQP (Advanced Product Quality Planning)**.

### Tujuan Utama

1. **Digitalisasi PFMEA** — Menggantikan proses PFMEA manual/Excel dengan sistem terintegrasi berbasis web yang memungkinkan kolaborasi tim secara *real-time*.
2. **IATF 16949 Compliance** — Memastikan seluruh dokumentasi PFMEA memenuhi persyaratan standar otomotif internasional.
3. **Audit Readiness** — Menyediakan *audit trail* lengkap dengan sistem *revision control* dan *activity logging* untuk kesiapan audit internal maupun eksternal.
4. **Integrasi APQP** — Menghubungkan PFMEA dengan proyek-proyek APQP mulai dari fase *Planning* hingga *Production*.
5. **Role-Based Access Control** — Mengelola hak akses pengguna dengan sistem permission yang granular.

---

## ✨ Fitur Utama

### 🔐 Authentication & Security
- Login dengan proteksi *throttle* (5 percobaan per menit)
- Fitur *Forgot Password* dengan notifikasi email *queue-based*
- *Reset Password* via token
- Session management dengan Inertia.js
- *Account lockout* otomatis setelah gagal login berulang kali

### 📊 Dashboard
- Tampilan utama setelah login dengan ringkasan data
- Statistik dan metrik kinerja (dapat dikembangkan lebih lanjut)

### 🔬 PFMEA (Process Failure Mode & Effects Analysis)
- **PFMEA Header** — Informasi utama PFMEA: kode unik, tanggal, scope (prototype/pre-launch/containment/mass production), departemen, project, material
- **Core Team** — Tim inti PFMEA yang terlibat dalam proses analisis
- **Process Mapping** — Memilih dan mengurutkan process function yang relevan untuk dianalisis
- **Severity, Occurrence, Detection (SOD)** — Penilaian risiko untuk setiap mode kegagalan
- **Risk Priority Number (RPN)** — Perhitungan otomatis RPN = Severity × Occurrence × Detection
- **Recommended Actions** — Rekomendasi tindakan perbaikan untuk mengurangi risiko
- **Re-evaluation** — Penilaian ulang setelah tindakan perbaikan dilakukan
- **Versioning** — Setiap perubahan terekam dalam versi baru

### ⚙️ Process Management
- **Process Template** — Template proses yang dapat digunakan kembali di berbagai PFMEA
- **Process Details** — Analisis FMEA lengkap untuk setiap langkah proses:
  - *Requirements* — Spesifikasi kebutuhan proses
  - *Previous Problem* — Catatan masalah sebelumnya
  - *Potential Failure Mode* — Mode kegagalan potensial
  - *Potential Effect of Failure* — Dampak kegagalan
  - *Potential Cause of Failure* — Penyebab kegagalan
  - *Controls Prevention & Detection* — Kontrol pencegahan dan deteksi
  - *Classification* — Klasifikasi karakteristik (CC/SC/None)
  - *Severity, Occurrence, Detection & RPN* — Penilaian risiko
  - *Recommended Action* — Tindakan yang direkomendasikan
  - *Action Taken & Results* — Tindakan yang telah dilakukan dan hasil re-evaluasi
- **Revision Control** — Riwayat perubahan lengkap dengan *change log*
- **Queue Processing** — Operasi *background job* untuk proses berat

### 👥 Customer Management
- Data pelanggan dengan *tier level* (OEM, Tier-1, Tier-2, Aftermarket)
- Informasi pajak (*tax number*)
- Alamat penagihan dan pengiriman
- *Risk profile* pelanggan
- *CSR Reference Document* (Customer Specific Requirements)
- *Soft delete* dengan alasan penghapusan

### 📁 Project Management
- Manajemen proyek dengan fase APQP (5 fase):
  - **Phase 1:** Planning
  - **Phase 2:** Product Design
  - **Phase 3:** Process Design (PFMEA Wajib)
  - **Phase 4:** Validation
  - **Phase 5:** Production
- *Milestone tracking*: Kick-off, Prototype, PPAP, SOP
- Kaitkan dengan data pelanggan dan material
- *Confidentiality level*: Internal, Customer Confidential, Strictly Restricted
- Status proyek: Development, Mass Production, Hold, End of Production (EOP)

### 🧪 Material Management
- Kategori material: Raw Material, Purchased Parts, Chemical Additive, Tooling/Consumable, Packaging, SFG, FG
- Spesifikasi teknis detail untuk *plastic injection molding*:
  - *Density* (g/cm³)
  - *Melt Flow Index* (g/10 min)
  - *Shrinkage Rate*
  - *Color*
  - *Gross Weight, Net Weight, Sprue Weight*
- Kepatuhan RoHS (Restriction of Hazardous Substances)
- Nomor IMDS (International Material Data System)
- Dokumen MSDS (Material Safety Data Sheet)

### 📐 Unit of Measure (UoM)
- **Kategori UoM** — Pengelompokan unit pengukuran
- **Daftar Unit** — Unit dengan kode, simbol, dan faktor konversi
- *Base unit* dan *conversion factor* untuk konversi otomatis
- *Decimal places* untuk presisi perhitungan

### 🧑‍💼 User & Role Management
- **User Management** — CRUD pengguna dengan avatar, status aktif/locked
- **Role Management** — Role-based access control (RBAC) menggunakan Spatie Permission
- **Permission Sync** — Artisan command untuk sinkronisasi permission otomatis
- **Login Attempt Tracking** — Monitoring percobaan login gagal

### 🗑️ Recycle Bin
- Manajemen data yang di-*soft delete*
- Fitur *restore* data yang telah dihapus
- Alasan penghapusan wajib diisi

### 📝 Activity Logs
- *Audit trail* lengkap untuk semua modul
- Mencatat perubahan data (*before* & *after*)
- *Event logging* untuk setiap aksi CRUD

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Vue 3)                      │
│  Inertia.js · TailwindCSS 4 · PrimeVue · Vue Sonner     │
└────────────────────────┬────────────────────────────────┘
                         │ Inertia Protocol (JSON)
┌────────────────────────▼────────────────────────────────┐
│                   Backend (Laravel 12)                   │
│  Service Layer · Repository Pattern · Spatie Permission │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│                    Database (MySQL)                      │
│  Migrations · Seeders · UUID v7 · Soft Deletes          │
└─────────────────────────────────────────────────────────┘
```

### Pattern Arsitektur

- **Service-Repository Pattern**: Pemisahan logic bisnis (Service) dan data access (Repository)
- **Blameable Trait**: Otomatis mencatat `created_by` dan `updated_by` pada setiap model
- **Activity Log Trait**: Logging otomatis menggunakan Spatie Activity Log
- **UUID v7**: Semua entitas menggunakan UUID versi 7 untuk keamanan dan performa indexing
- **Soft Deletes**: Data tidak pernah dihapus permanen, hanya ditandai dengan `deleted_at`

---

## 🛠️ Tech Stack

### Backend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| PHP | 8.4 | Bahasa pemrograman |
| Laravel | 12.x | Framework backend |
| Spatie Laravel Permission | 8.x | Role & permission management |
| Spatie Laravel Activity Log | 4.x | Activity logging & audit trail |
| Laravel Pulse | 1.x | Monitoring performa aplikasi |
| Inertia Laravel | 3.x | Server-side adapter untuk Inertia |

### Frontend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| Vue.js | 3.5 | Framework frontend |
| Inertia.js Vue 3 | 3.x | Client-side adapter untuk Inertia |
| Tailwind CSS | 4.x | Utility-first CSS framework |
| PrimeVue | 4.x | UI component library |
| Vue Advanced Cropper | 2.x | Image cropping untuk avatar |
| Vue Sonner | 2.x | Toast notifications |
| Vue3 Toastify | 0.2.x | Notifikasi tambahan |
| Day.js | 1.x | Date formatting & manipulation |
| Vite | 8.x | Build tool & dev server |

### Infrastructure
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| Docker | Latest | Containerization |
| Nginx | Stable-Alpine | Web server |
| PHP-FPM | 8.4 | PHP process manager |
| Redis | 7.x | Caching & queue |
| MySQL | 8.x | Database |
| Composer | 2.x | PHP package manager |

---

## 📁 Struktur Direktori

```
SCHLEMMER_CORE/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SyncPermissions.php      # Artisan command: permission:sync
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ActivityLog/             # Activity log controller
│   │   │   ├── api/v1/                  # API controllers untuk dropdown
│   │   │   ├── AppRole/                 # Role management controller
│   │   │   ├── Auth/                    # Authentication controller
│   │   │   ├── Customer/                # Customer CRUD controller
│   │   │   ├── Material/                # Material CRUD controller
│   │   │   ├── Pfmea/                   # PFMEA controller
│   │   │   ├── Process/                 # Process template controller
│   │   │   ├── Project/                 # Project controller
│   │   │   ├── RecycleBin/              # Recycle bin controller
│   │   │   ├── UnitCategory/            # UoM category controller
│   │   │   ├── Units/                   # Unit controller
│   │   │   └── Users/                   # User management controller
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   ├── Requests/
│   │   │   └── StoreUserRequest.php
│   │   └── Resources/                   # API Resources
│   ├── Jobs/
│   │   └── ProcessFunctionQueueJob.php  # Background job processing
│   ├── Models/                          # Eloquent Models
│   │   ├── ChangeLogs.php
│   │   ├── Customer.php
│   │   ├── Department.php
│   │   ├── Material.php
│   │   ├── MaterialLogs.php
│   │   ├── PfmeaCoreTeam.php
│   │   ├── PfmeaDetail.php
│   │   ├── PfmeaHeader.php
│   │   ├── ProcessChangeLogRevision.php
│   │   ├── ProcessDetail.php
│   │   ├── ProcessDetailRevision.php
│   │   ├── ProcessHeader.php
│   │   ├── ProcessHeaderRevision.php
│   │   ├── Project.php
│   │   ├── ProjectMaterial.php
│   │   ├── Unit.php
│   │   ├── UnitCategory.php
│   │   └── User.php
│   ├── Notifications/Auth/              # Email notifications
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Repositories/                    # Repository pattern
│   │   ├── AppRole/
│   │   ├── Contracts/
│   │   ├── Customer/
│   │   ├── Department/
│   │   ├── Material/
│   │   ├── PFMEA/
│   │   ├── ProcessRevision/
│   │   ├── ProcessTemplate/
│   │   ├── Project/
│   │   ├── UnitCategories/
│   │   ├── Units/
│   │   └── User/
│   ├── Services/                        # Service layer
│   │   ├── AppRole/
│   │   ├── Auth/
│   │   ├── ChangeLogs/
│   │   ├── Customer/
│   │   ├── Department/
│   │   ├── GenerateCode/
│   │   ├── MaterialService/
│   │   ├── PFMEA/
│   │   ├── ProcessRevision/
│   │   ├── ProcessTemplate/
│   │   ├── Project/
│   │   ├── UnitCategory/
│   │   ├── Units/
│   │   └── Users/
│   ├── Blameable.php                    # Created/Updated by trait
│   └── HasActivityLogs.php              # Activity logging trait
├── bootstrap/
│   ├── app.php                          # App configuration
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── permission.php                   # Permission & modules config
│   ├── pulse.php                        # Laravel Pulse config
│   └── ...
├── database/
│   ├── factories/
│   ├── migrations/                      # Database migrations
│   ├── seeders/                         # Database seeders
│   │   └── data/                        # Seed data files
│   ├── dump-*.sql                       # Database dumps
│   └── Book2.xlsx                       # Reference data
├── lang/                                # Localization
├── nginx/
│   └── default.conf                     # Nginx configuration
├── php/
│   ├── Containerfile                    # PHP Docker build
│   ├── php.ini
│   └── opcache.ini
├── public/
│   ├── index.php
│   ├── logo.webp / logo1.webp / logo2.webp  # Brand logos
│   └── .htaccess
├── resources/
│   ├── css/app.css                      # Global CSS (Tailwind)
│   ├── js/
│   │   ├── app.js                       # Vue app entry point
│   │   ├── bootstrap.js                 # Bootstrap config
│   │   ├── Components/                  # Vue components
│   │   ├── Composables/                 # Vue composables
│   │   ├── Layouts/                     # Layout components
│   │   ├── Pages/                       # Vue pages (Inertia)
│   │   │   ├── Dashboard/
│   │   │   ├── Users/
│   │   │   └── Errors/
│   │   └── Utils/
│   └── views/
│       ├── app.blade.php                # Root template
│       └── welcome.blade.php
├── routes/
│   ├── web.php                          # Web routes
│   ├── api.php                          # API routes
│   └── console.php                      # Console commands
├── storage/                             # Storage (logs, cache)
├── tests/
│   ├── Feature/
│   └── Unit/
├── compose.yaml                         # Docker Compose config
├── composer.json                        # PHP dependencies
├── package.json                         # Node dependencies
├── vite.config.js                       # Vite build config
└── artisan                              # Laravel CLI
```

---

## 🔧 Persyaratan Sistem

### Untuk Docker (Recommended)
- Docker Engine 24+
- Docker Compose V2
- Git

### Untuk Manual Setup
- PHP 8.2+
- Composer 2.x
- Node.js 20+ & NPM
- MySQL 8.0+
- Redis 7+
- Nginx / Apache

---

## 🚀 Instalasi & Setup

### Docker (Recommended)

```bash
# 1. Clone repository
git clone <repository-url> schlemmer_core
cd schlemmer_core

# 2. Copy environment file
cp .env.example .env

# 3. Build & jalankan container
docker compose up -d --build

# 4. Install PHP dependencies
docker compose exec php composer install

# 5. Generate application key
docker compose exec php php artisan key:generate

# 6. Jalankan migrasi database
docker compose exec php php artisan migrate

# 7. Seed data awal
docker compose exec php php artisan db:seed

# 8. Sinkronisasi permissions
docker compose exec php php artisan permission:sync

# 9. Install Frontend dependencies
docker compose exec php npm install

# 10. Build frontend assets
docker compose exec php npm run build

# 11. Akses aplikasi
#    URL: http://localhost:8007
```

### Manual Setup

```bash
# 1. Clone repository
git clone <repository-url> schlemmer_core
cd schlemmer_core

# 2. Copy environment file
cp .env.example .env

# 3. Atur konfigurasi database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schlemmer_core
DB_USERNAME=root
DB_PASSWORD=

# 4. Install dependencies
composer install
npm install

# 5. Generate key & migrate
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan permission:sync

# 6. Build frontend
npm run build

# 7. Development mode (menjalankan semua service)
#    Jalankan perintah berikut di terminal terpisah:
npm run dev       # Vite dev server
php artisan serve # Laravel dev server
```

> **Catatan**: Untuk development, gunakan `npm run dev` (dari `composer.json` scripts) yang akan menjalankan Vite, Queue Listener, Pail log, dan PHP Artisan Serve secara bersamaan via `concurrently`.

---

## ⚙️ Konfigurasi

### Environment Variables (.env)

```env
APP_NAME=Schlemmer Core
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8007
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schlemmer_core
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Permission Config (`config/permission.php`)

```php
'modules' => [
    'users',
    'roles',
    // Tambahkan modul lain di sini
],

'actions' => [
    'view',
    'create',
    'edit',
    'delete',
    'mass_delete',
    'export',
],
```

Permissions akan otomatis dibuat dengan format: `{action} {module}`
Contoh: `view users`, `create users`, `edit users`, `delete users`

---

## 💻 Penggunaan

### Development

```bash
# Terminal 1: Frontend (Vite)
npm run dev

# Atau gunakan one-command development:
npm run dev  # Dari composer.json scripts
             # Menjalankan: serve + queue:listen + pail + vite
```

### Production Build

```bash
# Build aset frontend
npm run build

# Optimasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Artisan Commands

```bash
# Sinkronisasi permission dari config
php artisan permission:sync

# Lihat daftar route
php artisan route:list

# Queue worker untuk background job
php artisan queue:work

# Monitoring (Laravel Pulse)
php artisan pulse:check
```

---

## 📦 Modul & Fungsionalitas

### 1. 🔐 Authentication
- **Login**: `GET/POST /login` — Dengan proteksi throttle (5x/menit)
- **Forgot Password**: `GET /forgot-password`, `POST /forgot-password`
- **Reset Password**: `GET /reset-password/{token}`, `POST /reset-password`
- **Logout**: `POST /logout`

### 2. 📊 Dashboard
- **Route**: `GET /dashboard`
- Halaman utama setelah login, menampilkan ringkasan data dan metrik

### 3. 🔬 PFMEA
- **List**: `GET /pfmea` — Daftar semua PFMEA
- **Create**: `GET/POST /pfmea/create` — Buat PFMEA baru dengan memilih project, material, scope
- **View**: `GET /pfmea/{id}/view` — Detail PFMEA lengkap dengan core team & process mapping
- **Update**: `PUT /pfmea/{id}` — Update data PFMEA
- **Logs**: `GET /pfmea/{id}/logs` — Riwayat perubahan PFMEA

### 4. ⚙️ Process Management
- **List**: `GET /process` — Daftar process template
- **Create**: `GET/POST /process/create` — Buat process template baru dengan detail FMEA
- **View**: `GET /process/{process}` — Detail process dengan analisis FMEA
- **Update**: `PUT /process/{process}` — Update process & detail
- **Delete**: `POST /process/delete`, `POST /process/mass-delete`
- **Change Logs**: `GET /process/logs/{process}` — Riwayat perubahan

### 5. 👥 Customer Management
- **List**: `GET /customer` — Daftar customer
- **Create**: `POST /customer` — Tambah customer baru
- **View**: `GET /customer/{customer}` — Detail customer
- **Update**: `PUT /customer/{customer}` — Update data customer
- **Delete**: `POST /customer/delete`, `POST /customer/mass-delete`

### 6. 📁 Project Management
- **List**: `GET /projects` — Daftar proyek
- **Create**: `GET/POST /projects/create` — Buat proyek baru
- **View**: `GET /projects/view/{id}` — Detail proyek dengan material terkait
- **Update**: `PUT /projects/{project}` — Update data proyek
- **Delete**: `POST /projects/delete`, `POST /projects/mass-delete`

### 7. 🧪 Material Management
- **List**: `GET /materials` — Daftar material
- **Create**: `POST /materials` — Tambah material baru
- **Update**: `PUT /materials/{material}` — Update data material
- **Delete**: `POST /materials/mass-delete`

### 8. 📐 Unit of Measure
- **Kategori**: `GET/POST /unit_category`, `PUT /unit_category/{category}`
- **Unit**: `GET/POST /units`, `PUT /units/{unit}`
- Dua level: Kategori (misal: Mass, Length, Volume) dan Unit (misal: kg, g, mg)

### 9. 👤 User Management
- **List**: `GET /users` — Daftar user (permission: `view users`)
- **Create**: `POST /users` — Register user baru (permission: `create users`)
- **Update**: `PUT /users/{user}` — Update security settings (permission: `edit users`)
- **Bulk Delete**: `POST /users/bulk-delete` — Hapus massal (permission: `delete users`)

### 10. 🔑 Role Management
- **List**: `GET /roles` — Daftar role & permission
- **Create**: `POST /roles` — Buat role baru
- **Update**: `PUT /roles/{id}` — Update permission pada role
- **Delete**: `POST /roles/mass-delete`

### 11. 🗑️ Recycle Bin
- **List**: `GET /recycle-bin` — Lihat data yang di-soft delete
- **Restore**: `POST /recycle-bin/restore` — Kembalikan data yang dihapus

### 12. 📋 Activity Logs
- **List**: `GET /activity-log` — Log aktivitas seluruh sistem

---

## 🌐 API Endpoints

Semua API endpoint di-prefix dengan `/api/v1` dan memerlukan autentikasi.

### Dropdown API (Untuk Select2 / Autocomplete)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/units_category` | Daftar kategori unit |
| GET | `/api/v1/units_category/list` | Daftar kategori unit (alias) |
| GET | `/api/v1/units` | Daftar unit |
| GET | `/api/v1/units/list` | Daftar unit (alias) |
| GET | `/api/v1/customers` | Daftar customer (dropdown) |
| GET | `/api/v1/customers/list` | Daftar customer (list) |
| GET | `/api/v1/materials` | Daftar material (dropdown) |
| GET | `/api/v1/materials/list` | Daftar material (list) |
| GET | `/api/v1/projects` | Daftar proyek (dropdown) |
| GET | `/api/v1/projects/{project_id}/material` | Material pada proyek |
| GET | `/api/v1/process-template` | Daftar process template (dropdown) |
| GET | `/api/v1/users` | Daftar user (dropdown) |

---

## 🗄️ Database & Migrasi

### Entity Relationship Overview

```
┌──────────┐     ┌──────────────┐     ┌─────────────┐
│   Users  │────→│  Projects    │────→│  Customers  │
└──────────┘     └──────┬───────┘     └─────────────┘
                        │
┌──────────┐     ┌──────┴───────┐     ┌─────────────┐
│  Units   │←────│  Materials   │←────│UnitCategory │
└──────────┘     └──────┬───────┘     └─────────────┘
                        │
┌──────────┐     ┌──────┴───────┐     ┌─────────────┐
│Departments│    │  PFMEA       │────→│  Process    │
└──────────┘     └──────┬───────┘     └─────────────┘
                        │
               ┌────────┴────────┐
          ┌────┴────┐      ┌────┴────┐
          │CoreTeam │      │ Details │
          └─────────┘      └─────────┘
```

### Key Migrations

| No | Migration | Tabel | Deskripsi |
|----|-----------|-------|-----------|
| 1 | 0001_01_01_000000 | users, password_reset_tokens, sessions | Users & auth |
| 2 | 0001_01_01_000001 | cache, cache_locks | Cache |
| 3 | 0001_01_01_000002 | jobs, job_batches, failed_jobs | Queue |
| 4 | 2026_06_12_031605 | activity_log | Activity log |
| 5 | 2026_06_15_131849 | process_functions, process_function_details | Process template |
| 6 | 2026_06_23_100325 | customers | Customer master |
| 7 | 2026_06_23_103610 | change_logs_data | Change logs |
| 8 | 2026_06_24_151018 | projects | Project management |
| 9 | 2026_06_24_200200 | unit_categories | UoM categories |
| 10 | 2026_06_24_212848 | units | Units of measure |
| 11 | 2026_06_29_103744 | materials | Material master |
| 12 | 2026_07_02_112748 | departments | Department master |
| 13 | 2026_07_02_141937 | pfmea, pfmea_core_teams, pfmea_details | PFMEA |
| 14 | 2026_07_07_131942 | project_materials | Project-Material pivot |
| 15 | 2026_07_24_113440 | pulse_* | Laravel Pulse |
| 16 | 2026_07_25_191710 | permissions, roles, model_has_* | Spatie Permission |

### Seeder (Data Awal)
- `UserSeeder` — Akun admin default
- `DepartmentSeeder` — Data departemen
- `UnitCategoriesSeeder` — Kategori unit pengukuran
- `SysCounterSeeder` — Counter sistem untuk auto-generate kode
- `UnitSeeder` — Unit-unit pengukuran
- `MaterialSeeder` — Data material awal
- `CustomerSeeder` — Data customer awal

---

## 🔑 Permission & Role

Sistem menggunakan **Spatie Laravel Permission** untuk manajemen hak akses.

### Modul Permission

| Modul | Actions | Contoh Permission |
|-------|---------|-------------------|
| users | view, create, edit, delete, mass_delete, export | `view users`, `create users` |
| roles | view, create, edit, delete, mass_delete, export | `view roles`, `edit roles` |

> **Catatan**: Daftar modul dan actions dapat ditambahkan di `config/permission.php`

### Auto-Sync Permissions

```bash
# Sinkronisasi permission dari konfigurasi
php artisan permission:sync
```

Command ini akan membaca konfigurasi `modules` dan `actions` dari `config/permission.php`, lalu membuat permission yang belum ada di database secara otomatis.

### Frontend Permission Check

Di Vue.js, permission dapat dicek menggunakan global helper `$can`:

```vue
<template>
  <div v-if="$can('view users')">
    <!-- Konten hanya untuk user dengan permission view users -->
  </div>
</template>
```

Permission juga di-share ke Inertia melalui `HandleInertiaRequests` middleware, tersedia di `$page.props.auth.permissions`.

---

## 📐 Coding Standards

### Backend (PHP/Laravel)
- **Service-Repository Pattern** — Logic bisnis di Service, data access di Repository
- **PSR-4 Autoloading** — Namespace sesuai struktur direktori
- **Type Hinting** — Semua method menggunakan strict type declaration
- **Custom Traits** — `Blameable` untuk audit trail, `HasActivityLogs` untuk logging
- **UUID v7** — Generate UUID pada `creating` event model
- **Soft Deletes** — Data tidak pernah dihapus permanen
- **Form Requests** — Validasi terpisah di `app/Http/Requests/`
- **API Resources** — Transformasi response API

### Frontend (Vue 3)
- **Composition API** — Menggunakan `<script setup>` syntax
- **Inertia.js** — Routing dan form submission via Inertia
- **Tailwind CSS 4** — Utility-first CSS tanpa file CSS kustom
- **Component-based** — UI terpecah menjadi komponen-komponen reusable
- **Composables** — Shared state dan logic via composables

---

## 🐳 Docker Environment

### Service Architecture
```
┌─────────────────────────────────────────────────────┐
│                     Docker Network                    │
│                  schlemmer-network                    │
│                                                       │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐         │
│  │   PHP    │   │  Nginx   │   │  Redis   │          │
│  │  8.4-fpm │──→│stable-   │   │  7-alpine│          │
│  │          │   │ alpine   │   │          │          │
│  └──────────┘   └──────────┘   └──────────┘         │
│       │                                              │
│  ┌────┴──────────────────────────────────────────┐   │
│  │           Volume: /var/www/html                │   │
│  │         (Codebase + Dependencies)              │   │
│  └────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

### Service Details

| Service | Image | Port | Container Name |
|---------|-------|------|----------------|
| PHP | Custom build (php:8.4-fpm) | - | namaapp-php |
| Nginx | nginx:stable-alpine | 8007:80 | namaapp-nginx |
| Redis | redis:7-alpine | - | namaapp-redis |

### Custom PHP Docker Image
- Berbasis `php:8.4-fpm`
- Ekstensi tambahan: pdo_mysql, mysqli, mbstring, intl, zip, gd, exif, bcmath, opcache
- Composer 2 terinstal
- Timezone: Asia/Jakarta
- OPCache dioptimalkan untuk production

---

## 🧪 Testing

```bash
# Jalankan semua test
php artisan test

# Atau via PHPUnit
./vendor/bin/phpunit
```

---

## 🤝 Contributing

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'feat: Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Commit Convention
Gunakan [Conventional Commits](https://www.conventionalcommits.org/):
- `feat:` — Fitur baru
- `fix:` — Perbaikan bug
- `refactor:` — Refactoring kode
- `docs:` — Perubahan dokumentasi
- `style:` — Perubahan formatting
- `chore:` — Tugas maintenance

---

## 📄 License

Project ini dikembangkan untuk kebutuhan internal **PT. Schlemmer Automotive Indonesia** dan dilindungi oleh lisensi MIT.

---

<div align="center">
  <hr />
  <p>
    <strong>Schlemmer Core</strong> — PFMEA Management System<br />
    Dibangun dengan ❤️ menggunakan Laravel 12, Vue 3, Inertia.js & Docker
  </p>
  <p>
    <sub>© 2026 PT. Schlemmer. All rights reserved.</sub>
  </p>
</div>

