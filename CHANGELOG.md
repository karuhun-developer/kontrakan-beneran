# Changelog

Semua perubahan penting pada proyek **KostPro** dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini menganut [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

Roadmap fitur (lihat [`PRD.md`](./PRD.md) §8). Item dipindahkan ke versi rilis saat selesai.

### Direncanakan
- **Fase 0 — Fondasi:** auth staff & penyewa, RBAC (spatie/permission), multi-tenant core (tenant → cabang), branch switcher, layout admin & portal.
- **Fase 1 — Master data:** Room Type, Room, Facility, Penyewa (+ media KTP).
- **Fase 2 — Booking & Front Desk:** availability engine (anti double-booking), booking date-range, grid front desk, check-in/out, perpanjang sewa + log.
- **Fase 3 — Billing & Kas:** Invoice/Tagihan (recurring bulanan + auto denda telat), Payment (Midtrans + manual), Cashflow + kategori (auto-income).
- **Fase 4 — Deposit & Laundry:** Deposit + ledger (potong/refund/topup), LaundryOrder (potong deposit / bayar sendiri).
- **Fase 5 — Notifikasi:** scheduler reminder jatuh tempo H-7/H-3/H-1, in-app + email, notification center, preferensi.
- **Fase 6 — Portal Penyewa:** dashboard, tagihan + bayar online, riwayat bayar & kamar, deposit, laundry, notifikasi.
- **Fase 7 — Analitik & Polish:** dashboard/laporan, ekspor CSV, hardening & optimisasi.

---

## [0.1.0] - 2026-07-29

Rilis awal: fondasi proyek & dokumen perencanaan.

### Added
- Scaffold proyek Laravel 13 (PHP 8.4) dengan **React Starter Kit**: Inertia 3 + React 19 + TypeScript + Vite.
- UI foundation: **shadcn/ui** (gaya new-york, base neutral, lucide) + Tailwind CSS v4; 26 komponen UI dasar.
- Skill design **UI/UX Pro Max** dipasang via `uipro init --ai claude` (`.claude/skills/`).
- Auth bawaan Fortify (login, register, reset password, verifikasi email, 2FA) + halaman settings (profil, keamanan, appearance).
- Paket domain terpasang: `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/laravel-activitylog`, `midtrans/midtrans-php`.
- Dokumen fondasi: **`PRD.md`** (kebutuhan produk + roadmap berfase), **`DESIGN.md`** (design system & arsitektur frontend), **`CHANGELOG.md`**.

### Changed
- `APP_NAME` → `KostPro`, `APP_LOCALE` → `id`.

### Fixed
- `APP_URL` yang malformed (`http://localhost:8000:8000` → `http://localhost:8000`) yang menyebabkan gagalnya `package:discover` dan build type Wayfinder.

[Unreleased]: ./PRD.md
[0.1.0]: #010---2026-07-29
