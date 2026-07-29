# Changelog

Semua perubahan penting pada proyek **KostPro** dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini menganut [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

Roadmap fitur (lihat [`PRD.md`](./PRD.md) §8). Item dipindahkan ke versi rilis saat selesai.

### Sedang dikerjakan
- **Fase 2 — Domain booking (inti):** enum `BookingStatus` (pending/confirmed/checked_in/checked_out/cancelled + label/warna/`blocksAvailability`), model & migrasi `Booking` (date-range, snapshot harga, relasi room/penyewa, scope `active`/`forRoom`/`overlapping`), engine anti double-booking `RoomAvailability` (overlap half-open `[start, end)` per kamar, dukung `ignoreBookingId` untuk perpanjang), factory + 8 test.
- **Fase 2 — Action & histori booking:** enum `BookingEvent` + model/migrasi `BookingLog` (histori per booking, `meta` json), action `CreateBooking` (enforce availability, snapshot harga efektif kamar, sinkron `room_status` reserved/occupied, tulis log `created`, transaksi DB), relasi `logs()` di `Booking`, +6 test. Front desk/UI, check-in/out & perpanjang menyusul.
- **Fase 2 — Front Desk (MVP booking):** menu **Operasional › Booking** — daftar reservasi per cabang + form slideover buat booking (pilih kamar/penyewa, rentang tanggal, status, catatan) dengan cek ketersediaan otomatis dan tampilan snapshot harga. Permission `booking.view`/`booking.manage` (Owner/Admin/Staff), `BookingController` (index/create/store) + `BookingRequest`, rute resource `bookings`, halaman Inertia `bookings/index` & `bookings/form`, helper `formatDate`, +3 test controller.
- **Fase 2 — Tampilan kalender (timeline Gantt):** halaman Booking kini punya 2 tab — **Kalender** (default) & **Tabel**. Tab kalender menampilkan timeline per kamar (baris = kamar, kolom = tanggal, bar = booking sesuai durasi & warna status) dengan navigasi bulan (partial reload `only: ['calendar']`), penanda akhir pekan/hari ini, legenda status, dan tooltip detail. Backend `buildCalendar()` (window per bulan, hitung `start_index`/`span`/`continues_before/after`), komponen `BookingTimeline` + `Tabs` ringan (tanpa dependensi baru), demo booking bulan berjalan di `DemoSeeder`, +1 test posisi bar.
- **Fase 2 — Lifecycle booking (front desk dari booking):** guard transisi di `BookingStatus` (`canCheckIn`/`canCheckOut`/`canCancel`/`canExtend`), action `CheckInBooking`/`CheckOutBooking`/`CancelBooking`/`ExtendBooking` (sinkron `room_status` occupied/available, tulis log event, transaksi DB; extend cek ketersediaan rentang perpanjangan). Endpoint `bookings.show` (modal detail: info, tombol aksi kontekstual, timeline riwayat) + `bookings.check-in`/`check-out`/`cancel`/`extend` (branch-scoped, permission `booking.manage`). Bar timeline & baris tabel kini klik → modal detail. +10 test (8 lifecycle, 2 endpoint).
- **Fase 2 — Shortcut penyewa di form booking:** tombol **Penyewa baru** langsung di form booking membuka panel quick-create inline (nama, no. HP, gender) — simpan → penyewa baru langsung auto-terpilih tanpa keluar dari form. Endpoint JSON `penyewa.quick-store` (di-gate `booking.manage` supaya staff tanpa `penyewa.manage` tetap bisa), helper `postJson` (fetch + XSRF, parsing error 422), +2 test.
- **Fase 2 — Walk-in (buat di tempat):** form booking kini punya toggle **Reservasi / Walk-in**. Mode walk-in men-set tanggal masuk = hari ini, status = check-in langsung (kamar auto-terisi), sembunyikan pilihan status, dan tombol jadi **Check-in Sekarang**. `CreateBooking` kini menulis log event `checked_in` untuk walk-in (bukan cuma `created`), jadi timeline riwayat lengkap. +1 test.
- **Fase 2 — Kalender per tipe kamar:** timeline booking kini mengelompokkan kamar berdasarkan **tipe kamar** (baris header per tipe, kamar terindentasi di bawahnya). Payload `buildCalendar()` berubah dari `rooms` flat → `groups` (`name` + `rooms[]`, urut per nama tipe), komponen `BookingTimeline` menyusun baris grid dinamis (header tipe + kamar) dengan posisi `gridRow` eksplisit.
- **Fase 3 — Domain Invoice (inti):** enum `InvoiceStatus` (unpaid/partial/paid/overdue/void + label/warna/`isOutstanding`) & `InvoiceItemType` (rent/deposit/laundry/fine/other), model & migrasi `Invoice` (nomor unik per tenant, periode, due date, subtotal/discount/fine/total, relasi penyewa/booking/items, scope `outstanding`, `recalculateTotals()`) + `InvoiceItem` (tipe, qty, unit price, amount). Action `GenerateInvoiceForBooking` (snapshot harga sewa jadi item rent, nomor urut `INV-YYYYMM-0001` per tenant, idempotent per booking+periode, transaksi DB), relasi `invoices()` di `Booking`/`Penyewa`, factory + 4 test.
- **Fase 3 — Domain Payment (inti):** enum `PaymentMethod` (cash/transfer/qris/virtual_account/midtrans + `isManual`) & `PaymentStatus` (pending/completed/failed + label/warna/`isSettled`), model & migrasi `Payment` (relasi invoice/penyewa/`recordedBy`, amount, method, status, reference, `paid_at`, scope `settled`). Invoice dapat `payments()`, `paidAmount()`, `outstandingAmount()`, `refreshPaymentStatus()` (unpaid→partial→paid, jaga flag overdue, skip void). Action `RecordPayment` (setel pembayaran lunas, tolak bila void/lunas/nominal ≤0/melebihi sisa, sinkron status invoice, transaksi DB), factory + 5 test. Midtrans menyusul.
- **Fase 3 — Domain Cashflow (inti):** enum `CashflowType` (income/expense + label/warna/`sign`), model & migrasi `CashflowCategory` (scope per cabang, unik nama+tipe per cabang, flag `is_system`, scope `ofType`) & `Cashflow` (kategori, `recordedBy`, amount, `occurred_on`, `source` morphTo, scope `ofType`). Action `RecordCashflowFromPayment` (mirror pembayaran lunas jadi pemasukan, idempotent per payment via `source`, find-or-create kategori sistem "Pendapatan Sewa" per cabang) di-wire ke `RecordPayment` dalam transaksi. Factory + 4 test.
- **Fase 3 — UI Tagihan (invoice):** menu **Operasional › Tagihan** — daftar tagihan per cabang dengan kartu ringkasan (nilai tertunggak, jatuh tempo, diterima bulan ini), filter status, dan modal detail (rincian item, total, riwayat pembayaran). Aksi di detail: **Catat Pembayaran** (jumlah/metode/referensi/catatan → `RecordPayment`, otomatis catat kas pemasukan) & **Batalkan** (`VoidInvoice`, hanya tagihan tanpa pembayaran). Tombol **Buat Tagihan** (bulan ini) di detail booking. Permission `invoice.view`/`invoice.manage` (Owner/Admin/Staff), `InvoiceController` (index/show/pay/void/from-booking), `PaymentMethod::manualCases()`, halaman Inertia `billing/invoices/index` & `detail`, +7 test controller.

### Direncanakan
- **Fase 2 — Booking & Front Desk:** availability engine (anti double-booking), booking date-range, grid front desk, check-in/out, perpanjang sewa + log.
- **Fase 3 — Billing & Kas:** Invoice/Tagihan (recurring bulanan + auto denda telat), Payment (Midtrans + manual), Cashflow + kategori (auto-income).
- **Fase 4 — Deposit & Laundry:** Deposit + ledger (potong/refund/topup), LaundryOrder (potong deposit / bayar sendiri).
- **Fase 5 — Notifikasi:** scheduler reminder jatuh tempo H-7/H-3/H-1, in-app + email, notification center, preferensi.
- **Fase 6 — Portal Penyewa:** dashboard, tagihan + bayar online, riwayat bayar & kamar, deposit, laundry, notifikasi.
- **Fase 7 — Analitik & Polish:** dashboard/laporan, ekspor CSV, hardening & optimisasi.

---

## [0.3.0] - 2026-07-29

**Fase 1 — Master data.** CRUD Tipe Kamar, Fasilitas, Kamar, dan Penyewa dengan branch scoping ketat.

### Added
- **Enum domain:** `RoomStatus` (available/reserved/occupied/maintenance + label/warna/`isBookable`).
- **Model & migrasi master:** `RoomType` (kapasitas + harga dasar), `Facility`, `Room` (nomor unik per cabang, lantai, status hunian, harga khusus opsional), pivot `facility_room`; `Penyewa` kini `HasMedia` dengan koleksi KTP (`id_card`, konversi thumb).
- **Branch scoping model:** trait `BelongsToBranch` (`tenant()`/`branch()` + scope `forBranch`/`forCurrentBranch`); `Room::effectiveMonthlyPrice` (fallback ke harga tipe).
- **CRUD backend:** controller `RoomType`/`Facility`/`Room`/`Penyewa` (trait `ResolvesCurrentBranch`), Form Request tervalidasi + tersegmentasi cabang (exists/unique per `branch_id`), rute grup `master` dengan permission `*.view`/`*.manage` per aksi.
- **Frontend:** 8 halaman Inertia (index + form) untuk tiap master, komponen bersama (`PageHeader`, `Pagination`, `RowActions`, `StatusBadge`/`ColorBadge`, `Table`, `Textarea`), hook `useCan`/`useCanManageMaster`, `formatRupiah`, upload KTP (preview + method spoofing), grup nav sidebar "Master Data".
- **Form modal flyover:** integrasi `inertiaui/modal` — form create/edit master dibuka sebagai slideover kanan (bukan navigasi halaman penuh). Backend `create()`/`edit()` mengembalikan `Inertia::modal(...)->baseRoute(...)`; frontend `ModalStackProvider` + `ModalRoot` (di app & portal layout), `ModalLink` di `PageHeader`/`RowActions`, shell `ModalForm`, dan default slideover via `putConfig` (tema `bg-background`, tutup otomatis setelah simpan).
- **Data demo & pengujian:** factory `RoomType`/`Facility`/`Room`, `DemoSeeder` mengisi master per cabang (fasilitas, tipe, kamar), 7 test fitur (CRUD, isolasi cabang, unik nomor kamar, upload KTP).

### Changed
- `Branch` model: relasi `roomTypes`/`facilities`/`rooms`.
- `RolePermissionSeeder`: permission `roomtype`/`facility`/`room` (`.view`/`.manage`).
- Shared props Inertia menyertakan `permissions` user untuk cek ability di frontend.

---

## [0.2.0] - 2026-07-29

**Fase 0 — Fondasi.** Auth berbasis peran, RBAC, multi-tenant core, dan kerangka portal penyewa.

### Added
- **Enum domain:** `CommonStatus` (active/inactive + label/warna), `UserRole` (super-admin, owner, admin, staff, penyewa + helper peran staff).
- **Multi-tenant core:** model & migrasi `Tenant` (bisnis) → `Branch` (cabang) → `User` (staff, `tenant_id`) dengan pivot `branch_user`, plus `Penyewa` tertaut ke tenant/cabang/user.
- **RBAC:** `spatie/laravel-permission` (trait `HasRoles`), 5 peran + baseline permission per domain (`RolePermissionSeeder`); super-admin lolos semua ability via `Gate::before`.
- **Branch scoping:** `BranchContext` request-scoped + middleware `SetCurrentBranch` (resolusi cabang aktif dari session, validasi terhadap cabang yang bisa diakses) + `BranchSwitchController` (ganti cabang teautorisasi).
- **Redirect berbasis peran:** `LoginResponse`/`TwoFactorLoginResponse` mengarahkan staff → `/dashboard`, penyewa → `/portal`.
- **Rute tersegmentasi:** grup admin (`role:super-admin|owner|admin|staff`) & grup portal (`role:penyewa`).
- **Frontend:** `BranchSwitcher` (dropdown cabang di sidebar), `PortalLayout` + halaman `portal/dashboard`, shared props Inertia (auth+peran, tenant, konteks cabang, flash), tipe TypeScript pendukung.
- **Data demo & pengujian:** factory `Tenant`/`Branch`/`Penyewa`, `DemoSeeder` (1 tenant, 2 cabang, akun tiap peran), 9 test fitur (redirect peran, akses portal/admin, switch cabang terautorisasi).

### Changed
- `User` model: relasi `tenant`/`branches`/`penyewa`, helper `primaryRole`/`isStaff`/`isSuperAdmin`/`accessibleBranches`/`canAccessBranch`; `tenant_id` fillable.
- `DatabaseSeeder` memanggil `RolePermissionSeeder` + `DemoSeeder`.

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
[0.2.0]: #020---2026-07-29
[0.1.0]: #010---2026-07-29
