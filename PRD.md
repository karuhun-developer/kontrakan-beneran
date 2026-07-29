# PRD — KostPro

**Product Requirements Document**
Versi dokumen: 1.0 · Status: Draft · Terakhir diperbarui: 2026-07-29

> Nama produk **KostPro** masih working title dan bisa diganti.

---

## 1. Ringkasan Produk

**KostPro** adalah aplikasi manajemen kost/rumah kontrakan berbasis web (SaaS multi-bisnis, multi-cabang) untuk membantu pemilik dan pengelola kost menjalankan operasional harian: manajemen kamar, penyewa, booking, front desk, tagihan, pembayaran, deposit, laundry, arus kas, dan komunikasi ke penyewa. Penyewa mendapatkan portal sendiri untuk melihat kamar, tagihan, riwayat pembayaran, saldo deposit, dan menerima notifikasi jatuh tempo.

Produk ini adalah penerus versi sebelumnya (`kontrakan-beneran`, Laravel + Livewire) yang baru ~50% jadi. KostPro dibangun ulang dengan stack modern (Laravel + Inertia + React + shadcn/ui "UI Pro") dan menutup gap fitur yang belum ada di versi lama: **deposit, laundry, notifikasi jatuh tempo (in-app + email), portal penyewa, invoice/tagihan berulang, dan booking date-range dengan cek ketersediaan.**

### 1.1 Visi
Menjadi sistem operasional kost paling praktis untuk pengelola di Indonesia — satu tempat untuk mengatur seluruh siklus penyewaan dari booking sampai checkout, transparan untuk penyewa, dan rapi secara keuangan.

### 1.2 Tujuan (Goals)
- Menyatukan operasional kost (kamar, penyewa, booking, tagihan, kas) dalam satu aplikasi.
- Mengurangi tunggakan lewat notifikasi jatuh tempo otomatis (H-7 / H-3 / H-1) via in-app & email.
- Memberi transparansi ke penyewa lewat portal mandiri.
- Menyediakan pembukuan arus kas yang akurat (pendapatan sewa/laundry otomatis, pengeluaran terkategori).
- Mendukung banyak cabang dalam satu bisnis, dan banyak bisnis dalam satu platform (SaaS).

### 1.3 Non-Goals (di luar cakupan awal)
- Aplikasi mobile native (portal penyewa cukup web responsif dulu).
- Integrasi akuntansi eksternal (Accurate/Jurnal) — cukup ekspor CSV.
- Marketplace pencarian kost publik / listing untuk calon penyewa umum.
- Smart lock / IoT pintu kamar.
- Multi-currency (fokus IDR).

---

## 2. Target Pengguna & Persona

| Persona | Peran | Kebutuhan utama |
|---|---|---|
| **Owner** | Pemilik bisnis kost | Lihat performa semua cabang, kelola staff, atur harga & kebijakan, laporan keuangan. |
| **Admin Cabang** | Pengelola satu cabang | Kelola kamar, penyewa, booking, tagihan, kas cabangnya. |
| **Staff / Front Desk** | Operasional harian | Proses booking, check-in/out, terima pembayaran, catat laundry. |
| **Penyewa** | Penghuni kamar | Lihat kamar, tagihan & bayar online, riwayat, saldo deposit, notifikasi. |
| **Super Admin** | Operator platform KostPro | Kelola akun bisnis (tenant), langganan, monitoring platform. |

---

## 3. Model Multi-Tenant & Isolasi Data

Arsitektur **SaaS multi-bisnis + multi-cabang**:

```
Platform (KostPro)
└── Tenant (bisnis kost, mis. "Kost Melati Group")
    ├── Branch / Cabang (mis. "Melati Depok", "Melati Bekasi")
    │   ├── Rooms, Penyewa, Booking, Invoice, Cashflow, Laundry, Deposit ...
    ├── Users (staff) dengan role & scope cabang
    └── Owner (pemilik)
```

- Setiap entitas operasional membawa `tenant_id` **dan** `branch_id` (keduanya wajib/non-null pada tabel operasional — perbaikan dari versi lama yang nullable).
- **Global scope** otomatis memfilter query berdasarkan tenant aktif, dan **branch switcher** memfilter cabang aktif yang dipilih user.
- Isolasi ditegakkan di layer query (scope) + otorisasi (policy/permission), bukan hanya di visibilitas menu.
- Super Admin bypass scope tenant; Owner melihat semua cabang miliknya; Admin/Staff dibatasi cabang yang ditugaskan.

---

## 4. RBAC — Role & Permission

Menggunakan `spatie/laravel-permission`. Permission dinamai per model dengan aksi: `view, show, create, update, delete, export, import, validate`.

### 4.1 Role bawaan
| Role | Cakupan | Ringkasan akses |
|---|---|---|
| **super-admin** | Platform | Semua tenant, kelola langganan & akun bisnis. |
| **owner** | 1 tenant | Semua cabang miliknya, kelola staff & kebijakan, semua laporan. |
| **admin** | Cabang ditugaskan | CRUD operasional penuh di cabangnya (kamar, penyewa, booking, tagihan, kas, deposit, laundry). |
| **staff** | Cabang ditugaskan | Front desk: booking, check-in/out, terima bayar, laundry. Tidak bisa ubah harga/kebijakan atau hapus data keuangan. |
| **penyewa** | Diri sendiri | Portal: lihat kamar/tagihan/history/deposit, bayar online, terima notifikasi. |

### 4.2 Matriks permission (ringkas)
| Domain | super-admin | owner | admin | staff | penyewa |
|---|:--:|:--:|:--:|:--:|:--:|
| Tenant/Bisnis | CRUD | R | – | – | – |
| Cabang | CRUD | CRUD | R | R | – |
| Staff/User | CRUD | CRUD | R | – | – |
| Room / RoomType / Facility | – | CRUD | CRUD | R | – |
| Penyewa | – | CRUD | CRUD | CR-U | R (self) |
| Booking / Front Desk | – | CRUD | CRUD | CRU | R (self) |
| Invoice / Tagihan | – | CRUD | CRUD | R + terima bayar | R (self) + bayar |
| Payment | – | CRUD | CRUD | CR + validate | R (self) + create |
| Deposit | – | CRUD | CRUD | R | R (self) |
| Laundry | – | CRUD | CRUD | CRU | R (self) + request |
| Cashflow + Kategori | – | CRUD | CRUD | R | – |
| Notifikasi | config | config | R | R | R (self) |
| Laporan/Dashboard | all | tenant | branch | branch (terbatas) | – |

---

## 5. Kebutuhan Fungsional per Domain

Format tiap fitur: **deskripsi → user story → acceptance criteria (AC)**.

### 5.1 Manajemen Kamar & Tipe Kamar
Kelola tipe kamar (kapasitas, harga dasar harian/bulanan, fasilitas standar) dan kamar individual (nomor, lantai, status, harga override, foto).

- *Sebagai admin, saya ingin menambah/ubah kamar & tipe agar katalog kamar akurat.*
- **AC:**
  - Kamar punya status: `available`, `booked`, `occupied`, `maintenance`.
  - Kamar terhubung ke satu tipe dan banyak fasilitas (many-to-many).
  - Harga bisa di-set di tipe (default) dan di-override per kamar.
  - Bisa unggah foto kamar (media library).
  - Kamar tidak bisa dihapus jika punya booking aktif.

### 5.2 Manajemen Penyewa (Penyewa)
Data identitas penyewa: nama, kontak, no. KTP, gender, alamat, alamat kantor, kontak darurat, unggah KTP. Bisa ditautkan ke akun `User` untuk login portal.

- *Sebagai admin, saya ingin menyimpan data penyewa lengkap termasuk kontak darurat.*
- **AC:**
  - Field kontak darurat (nama, relasi, telp, email).
  - Unggah dokumen (KTP) via media library.
  - Opsi "buat akun portal" → generate user + kirim undangan email.
  - Riwayat kamar penyewa terlihat (stay history).

### 5.3 Booking & Ketersediaan (Availability Engine)
Reservasi kamar untuk rentang tanggal tertentu dengan pencegahan double-booking.

- *Sebagai staff, saya ingin membooking kamar untuk tanggal tertentu tanpa bentrok dengan booking lain.*
- **AC:**
  - Booking punya `check_in_date`, `check_out_date` (atau durasi + unit), status: `pending`, `confirmed`, `checked_in`, `checked_out`, `cancelled`.
  - Sistem menolak booking yang tanggalnya overlap dengan booking aktif kamar yang sama.
  - Tampilan kalender/grid ketersediaan per kamar.
  - Booking menghasilkan/menyiapkan invoice sewa.

### 5.4 Front Desk & Check-in/Check-out
Panel operasional harian berbasis grid kamar untuk memproses tamu.

- *Sebagai staff, saya ingin melihat grid kamar dan memproses check-in/out cepat.*
- **AC:**
  - Grid kamar berwarna sesuai status.
  - Check-in: set tanggal masuk, kamar → `occupied`, mulai periode sewa.
  - Check-out: set tanggal keluar, kamar → `available`, tutup periode, hitung sisa/denda, proses refund deposit.
  - Semua aksi tercatat di **Booking Log** (history).

### 5.5 Perpanjang Sewa (Perpanjangan)
Memperpanjang periode sewa penyewa aktif dan menerbitkan tagihan baru.

- *Sebagai staff, saya ingin memperpanjang sewa penyewa dengan durasi & harga tertentu.*
- **AC:**
  - Mendukung unit `day`, `week`, `month`, `year` (perbaikan dari versi lama yang cuma day/month).
  - Menghasilkan invoice baru + entri Booking Log `activity = extend`.
  - Snapshot data lama & baru tersimpan di log.

### 5.6 Invoice / Tagihan
Entitas tagihan proper dengan item, diskon, denda, status, jatuh tempo. Mendukung **penerbitan bulanan otomatis** untuk sewa berjalan.

- *Sebagai admin, saya ingin tagihan bulanan terbit otomatis dan denda telat dihitung otomatis.*
- **AC:**
  - Invoice: `number`, `penyewa`, `booking`, `period_start/end`, `subtotal`, `discount`, `fine`, `total`, `due_date`, status (`unpaid`, `partial`, `paid`, `overdue`, `void`).
  - `InvoiceItem` tipe: `rent`, `laundry`, `deposit`, `fine`, `other`.
  - Scheduler menerbitkan invoice sewa untuk periode berikutnya.
  - Denda telat dihitung otomatis berdasarkan aturan cabang (mis. flat/hari atau persentase).
  - Invoice `overdue` ditandai otomatis lewat scheduler.

### 5.7 Pembayaran (Payment)
Pembayaran atas invoice via gateway (Midtrans: VA/bank transfer, QRIS) atau manual (cash/transfer dengan bukti).

- *Sebagai penyewa, saya ingin membayar tagihan online; sebagai staff, saya ingin mencatat pembayaran cash.*
- **AC:**
  - Payment polymorphic ke invoice; metode `cash`, `transfer`, `qris`, `va`.
  - Integrasi Midtrans (charge + callback webhook) untuk pembayaran online.
  - Pembayaran manual butuh validasi staff (upload bukti).
  - Saat pembayaran terverifikasi → invoice update status + **entri cashflow pendapatan otomatis**.
  - Pembayaran ditolak/refund → status invoice & cashflow disesuaikan (reversal).

### 5.8 Deposit
Saldo jaminan yang ditahan per penyewa/booking, dengan ledger potongan & refund.

- *Sebagai admin, saya ingin menahan deposit penyewa dan memotongnya untuk laundry/denda/kerusakan, lalu refund sisanya saat checkout.*
- **AC:**
  - `Deposit`: `amount`, `balance`, status (`held`, `partially_deducted`, `refunded`).
  - `DepositTransaction` (ledger): tipe `deduction`/`refund`/`topup`, alasan, referensi (mis. laundry order / denda).
  - Saldo berkurang saat potongan, bertambah saat topup, di-nol-kan saat refund penuh.
  - Riwayat transaksi deposit terlihat di admin & portal penyewa.

### 5.9 Laundry
Order laundry penyewa yang bisa **dipotong dari deposit** atau **dibayar sendiri** (jadi item tagihan).

- *Sebagai staff, saya ingin mencatat order laundry dan memilih sumber pembayaran (deposit atau tagihan).*
- **AC:**
  - `LaundryOrder`: penyewa, cabang, item/berat, harga, status (`received`, `processing`, `done`, `delivered`), `payment_source` (`deposit` | `self`).
  - Jika `deposit` → buat `DepositTransaction` potongan otomatis (validasi saldo cukup).
  - Jika `self` → tambah `InvoiceItem` tipe `laundry` ke tagihan berjalan / buat invoice baru.
  - Pendapatan laundry masuk cashflow saat terbayar.
  - (Opsional) daftar layanan/harga laundry (price list).

### 5.10 Cashflow (Arus Kas) & Kategori
Pencatatan pemasukan & pengeluaran terkategori.

- *Sebagai owner, saya ingin melihat pemasukan (sewa, laundry) dan pengeluaran (listrik, gaji, air, internet, maintenance) per cabang.*
- **AC:**
  - `CashflowCategory` di-scope per tenant/cabang (perbaikan dari versi lama yang global), tipe `debit`(masuk)/`kredit`(keluar), grup `rutin`/`non-rutin`/`investasi`.
  - `Cashflow`: kategori, tipe, jumlah, catatan, `happened_at`, status verifikasi, `source` polymorphic (mis. dari payment/laundry).
  - Pemasukan sewa & laundry tercatat otomatis dari pembayaran terverifikasi.
  - Pengeluaran dicatat manual dengan kategori.
  - Laporan ringkas: total masuk/keluar/saldo per periode & cabang.

### 5.11 Notifikasi & Reminder Jatuh Tempo
Pengingat otomatis tagihan/sewa yang akan jatuh tempo, via **in-app** dan **email**.

- *Sebagai penyewa, saya ingin diingatkan H-7, H-3, dan H-1 sebelum jatuh tempo.*
- **AC:**
  - Scheduler harian memindai invoice/booking dengan jatuh tempo dalam 7/3/1 hari.
  - Kirim **in-app notification** (database) + **email** (Mailable, ter-queue).
  - Notification center di UI (admin & portal) dengan status baca/belum.
  - Preferensi notifikasi per user (aktif/nonaktif channel email).
  - Anti-spam: satu reminder per ambang per invoice (idempoten).
  - (Roadmap) channel WhatsApp opsional.

### 5.12 Portal Penyewa
Antarmuka mandiri untuk penyewa setelah login.

- *Sebagai penyewa, saya ingin melihat kamar, tagihan, membayar, dan melihat riwayat saya.*
- **AC:**
  - Dashboard: kamar aktif, tagihan berjalan, saldo deposit, notifikasi terbaru.
  - Halaman Tagihan: daftar invoice + bayar online (Midtrans) + unduh.
  - Riwayat Pembayaran: semua payment + status.
  - Riwayat Kamar (stay history): daftar periode menginap.
  - Deposit: saldo + ledger transaksi.
  - Laundry: daftar order + (opsional) request laundry baru.

### 5.13 Dashboard & Laporan
Ringkasan operasional untuk owner/admin.

- **AC:**
  - Kartu: occupancy rate, total pemasukan, total pengeluaran, tunggakan (late payment), total deposit ditahan.
  - Filter per cabang & periode.
  - Ekspor CSV untuk cashflow, invoice, penyewa.

---

## 6. Kebutuhan Non-Fungsional

- **Keamanan:** otorisasi berlapis (policy + permission + scope tenant/cabang), password hashing, 2FA untuk staff, rate limiting login, validasi webhook Midtrans (signature).
- **Audit:** activity log (`spatie/laravel-activitylog`) pada entitas kunci (penyewa, booking, invoice, payment, deposit, cashflow).
- **Performa:** query ter-index (tenant_id, branch_id, status, due_date), eager loading, cache untuk data master, queue untuk email/notifikasi.
- **Skalabilitas:** siap multi-cabang & multi-tenant; job & notifikasi via queue (database/redis).
- **Keandalan:** operasi keuangan (bayar, potong deposit, generate invoice) dalam DB transaction; reminder idempoten.
- **i18n:** default Bahasa Indonesia, siap English (string ter-lokalisasi).
- **Aksesibilitas:** komponen shadcn/Radix (keyboard & screen-reader friendly), kontras WCAG AA, dark mode.
- **Observability:** Laravel Pulse untuk monitoring performa.

---

## 7. Arsitektur & Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia 3 + React 19 + TypeScript |
| UI | shadcn/ui (new-york) + Tailwind CSS v4 + lucide icons ("UI Pro" skill) |
| Auth | Laravel Fortify (staff + penyewa), 2FA |
| Routing typed | Laravel Wayfinder |
| RBAC | spatie/laravel-permission |
| Media | spatie/laravel-medialibrary |
| Audit | spatie/laravel-activitylog |
| Pembayaran | Midtrans (VA, QRIS) + manual |
| Monitoring | Laravel Pulse |
| Testing | Pest |
| DB | SQLite (dev) / MySQL (prod) |

Pola: business logic di **Action classes**, controller tipis, request validation, policy untuk otorisasi, event/listener untuk efek samping (cashflow otomatis, notifikasi).

---

## 8. Roadmap Berfase

| Fase | Fokus | Output |
|---|---|---|
| **Fase 0** | Fondasi | Scaffold, auth staff+penyewa, RBAC, multi-tenant core + branch switcher, layout admin/portal. |
| **Fase 1** | Master data | RoomType, Room, Facility, Penyewa (+ media KTP). |
| **Fase 2** | Booking & Front Desk | Availability engine, booking date-range, grid front desk, check-in/out, perpanjang sewa + log. |
| **Fase 3** | Billing & Kas | Invoice (recurring + denda), Payment (Midtrans + manual), Cashflow + kategori, auto-income. |
| **Fase 4** | Deposit & Laundry | Deposit + ledger, LaundryOrder (potong deposit / bayar sendiri). |
| **Fase 5** | Notifikasi | Scheduler reminder H-7/3/1, in-app + email, notification center, preferensi. |
| **Fase 6** | Portal Penyewa | Dashboard, tagihan + bayar, riwayat bayar/kamar, deposit, laundry, notifikasi. |
| **Fase 7** | Analitik & Polish | Dashboard/laporan, ekspor CSV, hardening, optimisasi. |

Prioritas: Fase 0–3 = MVP yang bisa dipakai operasional. Fase 4–7 = pembeda utama dari versi lama.

---

## 9. Metrik Keberhasilan

- **Adopsi:** jumlah cabang & penyewa aktif dikelola.
- **Efisiensi:** waktu proses check-in/out & penerbitan tagihan turun.
- **Keuangan:** rasio tunggakan turun setelah reminder aktif; akurasi cashflow (selisih rekonsiliasi ≈ 0).
- **Engagement portal:** % penyewa yang login & bayar online.
- **Keandalan:** 0 double-booking; 0 selisih saldo deposit.

---

## 10. Risiko & Asumsi

- **Asumsi:** satu penyewa = satu kamar aktif pada satu waktu (sharing kamar bisa ditangani lewat multi-booking per kamar bila kapasitas > 1 — dipertimbangkan di Fase 2).
- **Risiko:** kompleksitas availability engine (overlap tanggal) → mitigasi: uji unit ketat + constraint DB.
- **Risiko:** ketergantungan gateway Midtrans → mitigasi: dukung pembayaran manual sebagai fallback.
- **Risiko:** akurasi keuangan (deposit & cashflow) → mitigasi: semua mutasi via ledger + DB transaction + activity log.

---

## 11. Referensi

- Dokumen desain: [`DESIGN.md`](./DESIGN.md)
- Riwayat perubahan: [`CHANGELOG.md`](./CHANGELOG.md)
- Versi pendahulu (referensi domain): `kontrakan-beneran` (Laravel + Livewire).
