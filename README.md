# KostPro

**Aplikasi manajemen kost/rumah kontrakan berbasis web — SaaS multi-bisnis & multi-cabang.**

KostPro membantu pemilik dan pengelola kost menjalankan operasional harian dalam satu tempat: manajemen kamar, penyewa, booking, front desk, tagihan berulang, pembayaran (gateway online & transfer manual), deposit, laundry, arus kas, dan notifikasi jatuh tempo. Penyewa mendapat **portal mandiri** untuk melihat kamar, tagihan, riwayat pembayaran, saldo deposit, dan bayar online/upload bukti transfer.

Satu platform bisa menampung banyak **bisnis (tenant)**, tiap bisnis punya banyak **cabang (branch)**, dan tiap cabang mengisolasi datanya sendiri.

> Dokumen fondasi: [PRD.md](./PRD.md) · [DESIGN.md](./DESIGN.md) · [CHANGELOG.md](./CHANGELOG.md)

---

## Open Source & Dukungan

KostPro **gratis dan open source** ([lisensi MIT](./LICENSE)) — bebas kamu self-host, pelajari, dan kembangkan sendiri.

- 💻 **Self-host** — ikuti panduan [Pengembangan](#pengembangan) di bawah untuk menjalankan sendiri.
- ❤️ **Dukung** — kalau proyek ini membantu, kamu bisa traktir lewat Saweria (tautan di landing page).
- 🛟 **Dibantu pasang** — males ribet install atau butuh hosting? Kami bisa setup-kan. Hubungi kontak di landing page.

> Tautan GitHub, Saweria, dan kontak diatur lewat konstanta di `resources/js/pages/welcome.tsx`.

---

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia 2 + React 19 + TypeScript, Vite |
| Styling | Tailwind CSS v4, shadcn/ui, komponen modal `inertiaui/modal` |
| Auth | Laravel Fortify (+ passkeys, 2FA) |
| RBAC | `spatie/laravel-permission` (role & permission global) |
| Media | `spatie/laravel-medialibrary` (KTP, bukti bayar, foto kamar) |
| Audit | `spatie/laravel-activitylog` |
| Monitoring | Laravel Pulse |
| Testing | Pest / PHPUnit |

---

## Fitur Utama

- **Multi-tenant & multi-cabang** — isolasi data per cabang lewat scope query + branch switcher; owner lihat semua cabang, admin/staff dibatasi cabang yang ditugaskan.
- **Master data** — Tipe Kamar, Kamar (status available/booked/occupied/maintenance), Fasilitas, Penyewa (identitas + KTP).
- **Booking & Front Desk** — reservasi date-range dengan engine anti double-booking, kalender timeline per tipe kamar, check-in/out, perpanjang sewa, walk-in.
- **Tagihan (Invoice)** — generate otomatis dari booking, tagihan berulang bulanan, denda telat otomatis, void.
- **Pembayaran** — gateway online **agnostic** (driver Midtrans/Null, ganti cukup ubah config) + **transfer manual** (penyewa upload bukti → staff konfirmasi/tolak, preview thumbnail bukti).
- **Deposit** — saldo ditahan per penyewa dengan ledger potongan/refund.
- **Laundry** — order dibebankan ke deposit atau jadi item tagihan.
- **Arus Kas (Cashflow)** — buku kas per cabang, pemasukan otomatis dari pembayaran lunas, pengeluaran manual terkategori, tampilan kalender.
- **Notifikasi** — reminder jatuh tempo H-7/H-3/H-1 (in-app + email) + notification center.
- **Portal Penyewa** — dashboard, tagihan & bayar, riwayat, deposit, laundry, notifikasi.
- **Dashboard & Laporan** — analitik okupansi/keuangan per cabang + ekspor CSV.

Rincian per fase pengembangan ada di [CHANGELOG.md](./CHANGELOG.md) dan roadmap di [PRD.md](./PRD.md) §8.

---

## Persyaratan

- PHP **8.4** + ekstensi umum Laravel
- Composer 2
- Node.js 20+ dan npm
- SQLite (default) atau MySQL/PostgreSQL

---

## Setup

### Cara cepat

```bash
git clone <repo-url> kostpro
cd kostpro
composer setup      # install, salin .env, key:generate, migrate, npm install, build
```

`composer setup` menyiapkan semua kecuali data awal. Lanjut isi database demo:

```bash
php artisan db:seed
```

### Cara manual

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default) — buat file DB-nya
touch database/database.sqlite

php artisan migrate --seed        # skema + role/permission + data demo
php artisan storage:link          # akses media (KTP, bukti bayar) via URL publik

npm install
npm run build
```

---

## Menjalankan

```bash
composer run dev
```

Menjalankan server, queue listener, log tailing (Pail), dan Vite sekaligus. Aplikasi bisa diakses di `http://localhost:8000`.

Kalau mau manual (dua terminal):

```bash
php artisan serve
npm run dev
```

---

## Akun Demo

Semua akun memakai password **`password`** (di-seed oleh `DemoSeeder`).

| Peran | Email | Akses |
|---|---|---|
| Super Admin | `superadmin@kostpro.test` | Seluruh platform, lintas tenant |
| Owner | `owner@kostpro.test` | Semua cabang di tenant-nya, kelola staff & kebijakan |
| Admin Cabang | `admin@kostpro.test` | CRUD operasional di cabang yang ditugaskan |
| Staff / Front Desk | `staff@kostpro.test` | Operasional harian (booking, bayar, laundry) |
| Penyewa | `penyewa@kostpro.test` | Portal penyewa |

---

## Role & Akses

RBAC global via `spatie/laravel-permission`; scope cabang ditegakkan di layer query + otorisasi (bukan sekadar sembunyikan menu). Ringkasan (detail di [PRD.md](./PRD.md) §4):

| Domain | super-admin | owner | admin | staff | penyewa |
|---|:--:|:--:|:--:|:--:|:--:|
| Tenant/Bisnis | CRUD | R (sendiri) | – | – | – |
| Cabang | CRUD | CRUD | R | R | – |
| Staff/User | CRUD | CRUD | R | – | – |
| Operasional (kamar, penyewa, booking, tagihan, kas, deposit, laundry) | – | CRUD | CRUD | terbatas | R (self) |
| Portal | – | – | – | – | ✓ |

- **Super Admin** bypass semua lewat `Gate::before`.
- **Owner** = admin tingkat tenant: otomatis akses semua cabang miliknya.
- **Admin Cabang** & **Staff** dibatasi cabang lewat pivot `branch_user` + branch switcher.

---

## Testing & Kualitas

```bash
php artisan test              # jalankan test suite (Pest)
php artisan test --compact    # output ringkas
composer test                 # lint + types + test (dipakai CI)

vendor/bin/pint               # format PHP (Laravel Pint)
npm run types:check           # cek TypeScript (tsc)
npm run lint:check            # ESLint
npm run build                 # build produksi Vite
```

---

## Konfigurasi Penting (`.env`)

| Variabel | Fungsi |
|---|---|
| `PAYMENT_GATEWAY` | Driver pembayaran online: `midtrans` atau `null` (lokal/test) |
| `MIDTRANS_SERVER_KEY` / `MIDTRANS_CLIENT_KEY` | Kredensial Midtrans (jika dipakai) |
| `BILLING_LATE_FEE_ENABLED` / `_TYPE` / `_VALUE` | Kebijakan denda telat |
| `BILLING_INVOICE_LEAD_DAYS` | Jeda pembuatan tagihan sebelum periode |
| `PORTAL_DEFAULT_PASSWORD` | Password default akun penyewa baru |

Ganti gateway pembayaran cukup ubah `PAYMENT_GATEWAY` — domain hanya bicara ke kontrak `PaymentGateway`, jadi tidak terkunci ke satu vendor.

---

## Struktur Singkat

```
app/
├── Actions/            # business logic (Billing, Booking, Deposit, Laundry, ...)
├── Enums/              # UserRole, InvoiceStatus, PaymentStatus, ...
├── Http/Controllers/   # per domain (Master, FrontDesk, Billing, Portal, Settings, ...)
├── Models/             # + Concerns/BelongsToBranch (scope cabang)
└── Support/            # BranchContext (branch aktif per-request)
resources/js/
├── pages/              # halaman Inertia (React)
├── components/         # komponen UI (shadcn + kustom)
├── layouts/            # app (sidebar staf) / portal (header penyewa) / settings
└── routes/             # Wayfinder (route helper ter-generate)
database/
├── migrations/  factories/  seeders/   # RolePermissionSeeder + DemoSeeder
```

---

Dibangun sebagai penerus `kontrakan-beneran` (Laravel + Livewire) dengan stack modern dan cakupan fitur lebih lengkap.
