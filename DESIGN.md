# DESIGN — KostPro

**Design & Frontend Architecture Document**
Versi dokumen: 1.0 · Status: Draft · Terakhir diperbarui: 2026-07-29

Dokumen ini melengkapi [`PRD.md`](./PRD.md). PRD menjawab *apa & kenapa*; dokumen ini menjawab *bagaimana rupanya & bagaimana frontend disusun*.

---

## 1. Prinsip Desain

1. **Operasional dulu, cantik kemudian.** Layar padat-data (front desk, tagihan) harus cepat dipindai & minim klik.
2. **Konsisten satu sistem.** Semua layar memakai token & komponen yang sama — admin dan portal penyewa terasa satu produk.
3. **Transparan untuk penyewa.** Portal menonjolkan yang penting: tagihan berjalan, jatuh tempo, saldo deposit.
4. **Aman secara default.** Aksi destruktif dan keuangan butuh konfirmasi eksplisit.
5. **Aksesibel & responsif.** Keyboard-friendly, kontras WCAG AA, mobile-first untuk portal penyewa.

**Tone:** profesional, tenang, terpercaya (aplikasi keuangan) — bukan playful. Bahasa Indonesia sebagai default.

---

## 2. Design System

Design system mengikuti **skill "UI/UX Pro Max"** (`uipro init --ai claude`, terpasang di `.claude/skills/`) di atas **shadcn/ui** + **Tailwind CSS v4**.

- **Basis komponen:** shadcn/ui gaya **new-york**, base color **neutral**, CSS variables aktif (`components.json`).
- **Ikon:** `lucide-react`.
- **Styling:** Tailwind v4 (config-less, token via CSS custom properties di `resources/css/app.css`).
- **Skill referensi** (dipakai saat membangun UI): `ui-styling`, `design-system`, `ui-ux-pro-max` (berisi panduan warna, tipografi, motion, komponen shadcn, aksesibilitas, responsiveness).

> Saat menambah komponen baru: gunakan CLI shadcn (`npx shadcn@latest add <name>`) agar konsisten dengan token & alias (`@/components/ui`).

### 2.1 Warna (token semantik)
Memakai token shadcn (`--background`, `--foreground`, `--primary`, `--muted`, `--card`, `--border`, `--destructive`, dst.) yang otomatis mendukung **light & dark mode**. Tambahan warna semantik status domain:

| Status | Warna (maksud) | Contoh pemakaian |
|---|---|---|
| `available` | hijau | kamar kosong |
| `booked` | kuning/amber | kamar dibooking |
| `occupied` | biru | kamar terisi |
| `maintenance` | abu/merah muda | kamar perbaikan |
| `paid` | hijau | invoice lunas |
| `unpaid` / `overdue` | amber / merah | tagihan belum bayar / telat |
| `deposit held` | ungu | saldo deposit ditahan |

Status divisualkan lewat komponen **Badge** dengan varian warna, bukan warna teks mentah.

### 2.2 Tipografi
- Font UI sans-serif (default stack Inter/system via Tailwind). Angka keuangan pakai *tabular-nums* agar rata.
- Skala: heading halaman `text-2xl/semibold`, judul kartu `text-lg`, body `text-sm`, label tabel `text-xs uppercase muted`.

### 2.3 Spacing, radius, elevasi
- Grid 4px; padding kartu `p-4`/`p-6`; gap konten `gap-4`/`gap-6`.
- Radius mengikuti token shadcn (`rounded-lg` untuk kartu, `rounded-md` kontrol).
- Elevasi tipis (border + shadow-sm); hindari bayangan berat.

### 2.4 Dark mode
Didukung penuh via token + toggle appearance (sudah ada di starter: `settings/appearance`). Semua warna status harus punya pasangan yang kontras di dark.

---

## 3. Layout / Shell

Dua shell utama, keduanya berbagi komponen dasar.

### 3.1 Shell Admin (staff/owner)
Berbasis `resources/js/layouts/app/app-sidebar-layout.tsx` (sudah ada di starter).

```
┌───────────────────────────────────────────────────────────┐
│ Sidebar        │  Topbar: [Branch Switcher ▾] [🔔] [avatar ▾] │
│ (nav per role) │────────────────────────────────────────────│
│                │  Breadcrumb                                 │
│  Dashboard     │  ┌──────────────────────────────────────┐  │
│  Front Desk    │  │  Konten halaman (Inertia page)       │  │
│  Kamar    ▾    │  │                                      │  │
│  Penyewa       │  │                                      │  │
│  Booking       │  └──────────────────────────────────────┘  │
│  Tagihan       │                                            │
│  Pembayaran    │                                            │
│  Deposit       │                                            │
│  Laundry       │                                            │
│  Cashflow ▾    │                                            │
│  Laporan       │                                            │
│  Pengaturan ▾  │                                            │
└───────────────────────────────────────────────────────────┘
```

- **Branch Switcher** di topbar: memilih cabang aktif (memengaruhi scope semua data). Owner/super-admin bisa "Semua cabang".
- **Notification bell**: dropdown notification center (badge jumlah belum dibaca).
- **Nav per role**: item sidebar difilter berdasarkan permission (bukan hanya visibilitas — route juga dijaga policy).

### 3.2 Shell Portal Penyewa
Lebih ringan, mobile-first — bisa berbasis `app-header-layout.tsx` (top nav) daripada sidebar.

```
┌───────────────────────────────────────────────┐
│ KostPro   [Beranda][Tagihan][Riwayat][🔔][👤]  │
├───────────────────────────────────────────────┤
│  Kartu Kamar Saya      │  Tagihan Berjalan      │
│  Saldo Deposit         │  Notifikasi Terbaru    │
└───────────────────────────────────────────────┘
```

### 3.3 Auth
Memakai layout auth starter (`auth-split-layout` / `auth-card-layout`). Satu alur login yang mengarahkan user ke shell sesuai role (staff → admin, penyewa → portal).

---

## 4. Inventaris Komponen

Sudah tersedia dari starter (`resources/js/components/ui`, 26 komponen shadcn): `button, card, badge, dialog, dropdown-menu, alert, avatar, breadcrumb, checkbox, collapsible, input, label, select, separator, sheet, sidebar, skeleton, table, tabs, tooltip, ...`

Komponen komposit yang perlu dibangun untuk domain:

| Komponen | Basis | Untuk |
|---|---|---|
| `DataTable` | table + tanstack (opsional) | daftar penyewa/kamar/tagihan (sort, filter, paginate). |
| `StatCard` | card | kartu dashboard (occupancy, income, expense, tunggakan, deposit). |
| `StatusBadge` | badge | status kamar/invoice/deposit dengan varian warna §2.1. |
| `RoomGrid` | grid + card | front desk grid kamar berwarna. |
| `AvailabilityCalendar` | custom + popover | pilih rentang tanggal booking, tampil slot terisi. |
| `MoneyInput` / `MoneyText` | input | format Rupiah + tabular-nums. |
| `FormDialog` / `FormSheet` | dialog/sheet + form | CRUD create/update konsisten. |
| `ConfirmDialog` | dialog | konfirmasi aksi destruktif/keuangan. |
| `InvoiceView` | card + table | rincian tagihan + item + tombol bayar. |
| `NotificationCenter` | dropdown + list | daftar notifikasi in-app, tandai baca. |
| `DepositLedger` | table | riwayat transaksi deposit. |
| `EmptyState` / `LoadingState` / `ErrorState` | card/skeleton/alert | state kosong/muat/error konsisten. |

---

## 5. Layar Kunci

### 5.1 Admin
- **Dashboard:** baris `StatCard` (occupancy rate, pemasukan, pengeluaran, tunggakan, total deposit) + grafik ringkas + daftar jatuh tempo terdekat. Filter cabang & periode.
- **Front Desk:** `RoomGrid` berwarna per status; klik kamar → aksi cepat (booking, check-in/out, bayar, perpanjang) via `FormSheet`.
- **Booking / Kalender:** `AvailabilityCalendar` per kamar; form booking dengan validasi overlap.
- **Kamar & Tipe:** `DataTable` + `FormDialog`; tab Tipe Kamar / Kamar / Fasilitas.
- **Penyewa:** `DataTable`; detail penyewa dgn tab (Profil, Kamar/Stay History, Tagihan, Deposit, Laundry, Dokumen KTP).
- **Tagihan:** `DataTable` invoice (filter status/periode) → `InvoiceView`; tombol terima pembayaran manual + upload bukti.
- **Cashflow:** `DataTable` transaksi + form pemasukan/pengeluaran; ringkasan saldo; kelola kategori.
- **Deposit:** daftar deposit per penyewa + `DepositLedger` + aksi potong/refund/topup (`ConfirmDialog`).
- **Laundry:** `DataTable` order + form; pilih sumber bayar (deposit/self) dgn indikator saldo.
- **Laporan:** ringkasan + tombol ekspor CSV.

### 5.2 Portal Penyewa
- **Beranda:** kartu Kamar Saya, Tagihan Berjalan (dengan CTA Bayar), Saldo Deposit, Notifikasi terbaru.
- **Tagihan:** daftar invoice + `InvoiceView` + bayar online (Midtrans redirect/snap) + unduh.
- **Riwayat Pembayaran:** tabel payment + status.
- **Riwayat Kamar:** daftar periode menginap (stay history).
- **Deposit:** saldo + `DepositLedger`.
- **Laundry:** daftar order + (opsional) form request.

---

## 6. State, Interaksi, Motion

- **Empty / Loading / Error** wajib untuk setiap list (skeleton saat muat, ilustrasi/CTA saat kosong, alert saat gagal).
- **Feedback:** toast untuk sukses/gagal aksi; inline error untuk validasi form (Inertia `errors`).
- **Konfirmasi:** aksi keuangan & destruktif lewat `ConfirmDialog` dengan ringkasan dampak (mis. "Potong deposit Rp50.000, sisa saldo Rp150.000").
- **Motion:** transisi halus & singkat (150–200ms), hormati `prefers-reduced-motion`.
- **Optimistic UI** hanya untuk aksi ringan (tandai notifikasi dibaca); operasi keuangan menunggu konfirmasi server.

---

## 7. Aksesibilitas

- Komponen berbasis Radix (shadcn) → fokus, ARIA, keyboard nav bawaan.
- Kontras teks/latar minimal WCAG AA; status tidak hanya dibedakan warna (sertakan label/ikon).
- Semua form field punya `label`; error terhubung via `aria-describedby`.
- Target sentuh ≥ 44px di portal (mobile).

---

## 8. Arsitektur Frontend

Stack: **Inertia 3 + React 19 + TypeScript**, dibundel **Vite** (+ Laravel Wayfinder untuk route typed).

### 8.1 Struktur folder (`resources/js`)
```
resources/js/
├── app.tsx                 # entry Inertia
├── pages/                  # halaman Inertia (1 file = 1 route)
│   ├── dashboard.tsx
│   ├── auth/ ...           # (starter)
│   ├── settings/ ...       # (starter)
│   ├── cms/                # (baru) layar admin: rooms, penyewa, booking, invoices, cashflow, deposit, laundry
│   └── portal/             # (baru) layar penyewa
├── layouts/                # app-sidebar-layout, app-header-layout, auth-*
├── components/
│   ├── ui/                 # shadcn primitives (26)
│   └── (komposit domain)   # DataTable, StatCard, RoomGrid, dst.
├── hooks/                  # useBranch, useNotifications, dst.
├── lib/                    # utils, formatters (rupiah, tanggal)
├── routes/ · wayfinder/    # typed routes (Wayfinder)
└── types/                  # tipe shared (Penyewa, Invoice, dst.)
```

### 8.2 Pola data
- **Server-driven** via Inertia props: controller mengirim data terstruktur (resource-like) ke page.
- **Otorisasi** ditentukan di server (policy/permission); frontend hanya menyembunyikan aksi yang tak diizinkan (`can` props).
- **Branch context**: cabang aktif dikirim via shared props (`usePage`) + hook `useBranch`; ganti cabang = Inertia visit dengan query cabang.
- **Formatter** terpusat di `lib` (Rupiah, tanggal Indonesia).
- **Form**: Inertia `useForm` + validasi server; komponen `FormDialog`/`FormSheet` seragam.

### 8.3 Konvensi
- Nama file page kebab-case mengikuti route; komponen PascalCase.
- Tipe domain di `types/` dan dipakai lintas page.
- Tambah komponen shadcn via CLI (jangan tulis manual) agar token konsisten.

---

## 9. Referensi
- Kebutuhan produk: [`PRD.md`](./PRD.md)
- Riwayat perubahan: [`CHANGELOG.md`](./CHANGELOG.md)
- Skill design terpasang: `.claude/skills/ui-ux-pro-max`, `.claude/skills/ui-styling`, `.claude/skills/design-system`.
