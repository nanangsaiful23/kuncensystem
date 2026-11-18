<?php

/*
 * Daftar menu admin yang aksesnya bisa dibuka/tutup per role lewat halaman
 * Pengaturan > Hak Akses Menu (khusus superadmin).
 *
 * - Superadmin SELALU boleh membuka semua menu (tidak bisa ditutup).
 * - Role yang bisa diatur: lihat 'roles'. Akun admin yang role-nya kosong /
 *   selain supervisor & superadmin dihitung sebagai 'admin'.
 * - 'bawaan'  : nilai awal kalau superadmin belum pernah mengubahnya
 *               (sama dengan aturan lama sebelum halaman ini ada).
 * - 'paths'   : URL di bawah /admin/ yang ikut DITOLAK (403) kalau menu ditutup,
 *               pola memakai * (Str::is). Kosong = menu hanya disembunyikan.
 * - 'kasir'   : apakah menu tampil di sidebar kasir (tidak diatur di halaman).
 *
 * Menambah menu baru: tambahkan satu baris di sini, lalu pakai
 * @if(canMenu('kunci')) di sidebar.
 */
return [

    'roles' => [
        'supervisor' => 'Supervisor',
        'admin'      => 'Admin',
    ],

    /* URL yang selalu boleh (dipakai AJAX oleh halaman lain, mis. kasir & loading). */
    'selalu_boleh' => [
        'distributor/search/*',
        'type/search/*',
        'delivery-fee/search/*',
        'voucher/searchByCode/*',
        'member/search/*',
        'member/searchByName/*',
    ],

    'menus' => [

        // ---------------- Menu utama ----------------
        'kas.riwayat' => [
            'grup' => 'Menu Utama', 'label' => 'Riwayat Kas di Tangan',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['cashFlow/*'],
        ],

        // ---------------- Barang ----------------
        'barang.expired' => [
            'grup' => 'Barang', 'label' => 'Daftar Barang Expired',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['good/exp'],
        ],
        'barang.print_display' => [
            'grup' => 'Barang', 'label' => 'Print Harga Barang Display',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['good/printDisplay'],
        ],
        'barang.stok_habis' => [
            'grup' => 'Barang', 'label' => 'Stock Habis & Stock Minus',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['good/zeroStock/*'],
        ],
        'barang.grosir' => [
            'grup' => 'Barang', 'label' => 'Harga Grosir Tidak Sesuai',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['good/wholesalePrice'],
        ],
        'barang.rangkuman' => [
            'grup' => 'Barang', 'label' => 'Rangkuman Barang',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['good/resume/*'],
        ],
        'barang.ganti_nama' => [
            'grup' => 'Barang', 'label' => 'Ganti Nama Barang',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['good/changeNameImport'],
        ],
        'sidak' => [
            'grup' => 'Barang', 'label' => 'Sidak Stok Toko',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['sidak-stok', 'sidak-stok/*'],
        ],
        'stock_opname' => [
            'grup' => 'Barang', 'label' => 'Stock Opname Barang',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['stock-opname/*'],
        ],
        'loading' => [
            'grup' => 'Barang', 'label' => 'Loading Barang',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['good-loading/*'],
        ],

        // ---------------- Transaksi ----------------
        'transaksi.resume_total' => [
            'grup' => 'Transaksi', 'label' => 'Resume Transaksi Total',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['transaction/resumeTotal/*'],
        ],
        'transaksi.resume_barang' => [
            'grup' => 'Transaksi', 'label' => 'Resume Transaksi Per Barang',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['transaction/resume/*'],
        ],
        'transaksi_internal' => [
            'grup' => 'Transaksi', 'label' => 'Transaksi Internal',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['internal-transaction/*'],
        ],
        'retur' => [
            'grup' => 'Transaksi', 'label' => 'Barang Retur',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['retur/*'],
        ],
        'transaksi_lain' => [
            'grup' => 'Transaksi', 'label' => 'Transaksi Lain (pemasukan)',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['other-transaction/*'],
        ],
        'biaya_lain' => [
            'grup' => 'Transaksi', 'label' => 'Biaya Lain (pengeluaran)',
            'bawaan' => ['supervisor' => true, 'admin' => true], 'kasir' => true,
            'paths' => ['other-payment/*'],
        ],

        // ---------------- Data master ----------------
        'member.tambah' => [
            'grup' => 'Data Master', 'label' => 'Tambah Member',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['member/create', 'member/store'],
        ],
        'delivery_fee' => [
            'grup' => 'Data Master', 'label' => 'Delivery Fee',
            'bawaan' => ['supervisor' => true, 'admin' => true],
            'paths' => ['delivery-fee/*'],
        ],
        'brand' => [
            'grup' => 'Data Master', 'label' => 'Brand',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['brand/*'],
        ],
        'distributor' => [
            'grup' => 'Data Master', 'label' => 'Distributor',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['distributor/*'],
        ],
        'kategori' => [
            'grup' => 'Data Master', 'label' => 'Kategori & Jenis Barang',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['category/*', 'type/*'],
        ],
        'kategori.penataan' => [
            'grup' => 'Data Master', 'label' => 'Penataan Kategori',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['penataan-kategori', 'penataan-kategori/*'],
        ],
        'satuan' => [
            'grup' => 'Data Master', 'label' => 'Satuan',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['unit/*'],
        ],
        'voucher' => [
            'grup' => 'Data Master', 'label' => 'Voucher',
            'bawaan' => ['supervisor' => true, 'admin' => false],
            'paths' => ['voucher/*'],
        ],

        // ---------------- Laporan ----------------
        'laporan.keuangan' => [
            'grup' => 'Laporan', 'label' => 'Laporan Financial (Jurnal, Laba Rugi, Neraca)',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['journal/*', 'reports/financial', 'reports/financial/*', 'scale/*', 'scaleLedger/*', 'getScaleLedger/*'],
        ],
        'akun' => [
            'grup' => 'Laporan', 'label' => 'Akun',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['account/*'],
        ],
        'grafik' => [
            'grup' => 'Laporan', 'label' => 'Grafik Penjualan',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['salesGraph/*', 'getSalesGraph/*'],
        ],
        'laporan.pemilik' => [
            'grup' => 'Laporan', 'label' => 'Ringkasan Pemilik',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['reports/owner', 'reports/owner/*'],
        ],
        'laporan.penjualan' => [
            'grup' => 'Laporan', 'label' => 'Laporan Penjualan',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['reports/sales', 'reports/sales/*'],
        ],
        'laporan.perputaran' => [
            'grup' => 'Laporan', 'label' => 'Perputaran Barang',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['reports/movement', 'reports/movement/*'],
        ],
        'laporan.reorder' => [
            'grup' => 'Laporan', 'label' => 'Rekomendasi Reorder',
            'bawaan' => ['supervisor' => false, 'admin' => false],
            'paths' => ['reports/reorder', 'reports/reorder/*'],
        ],
    ],
];
