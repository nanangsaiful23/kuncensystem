<?php

/*
 * Daftar menu admin yang aksesnya bisa dibuka/tutup per role lewat halaman
 * Pengaturan > Hak Akses Menu (khusus superadmin).
 *
 * - Superadmin SELALU boleh membuka semua menu (tidak bisa ditutup).
 * - Role yang bisa diatur: lihat 'roles'. Akun admin yang role-nya kosong /
 *   selain supervisor & superadmin dihitung sebagai 'admin'.
 * - 'grup'    : judul bagian di halaman pengaturan.
 * - 'induk'   : nama menu induk di sidebar kalau ini sub menu (mis. Loading Barang).
 * - 'bawaan'  : nilai awal kalau superadmin belum pernah mengubahnya
 *               (sama dengan aturan lama sebelum halaman ini ada).
 * - 'paths'   : URL di bawah /admin/ yang ikut DITOLAK (403) kalau menu ditutup,
 *               pola memakai * (Str::is, * juga melewati "/").
 *               Kosong = menu hanya disembunyikan.
 * - 'kecuali' : URL yang cocok dengan 'paths' tapi milik sub menu lain.
 * - 'kasir'   : apakah menu tampil di sidebar kasir (tidak diatur di halaman).
 * - 'catatan' : keterangan kecil di halaman pengaturan.
 *
 * Kalau satu URL cocok dengan beberapa menu, SEMUANYA harus boleh.
 *
 * Menambah menu baru: tambahkan satu baris di sini, lalu pakai
 * @if(canMenu('kunci')) di sidebar.
 */

$semua      = ['supervisor' => true, 'admin' => true];
$supervisor = ['supervisor' => true, 'admin' => false];
$superadmin = ['supervisor' => false, 'admin' => false];

/* Sub menu "Tambah X" (create + store) dan "Daftar X" (sisanya: daftar, detail, edit). */
$tambahDaftar = function (string $key, string $prefix, string $grup, string $induk, string $nama, array $bawaan, array $ekstra = []) {
    return [
        "$key.tambah" => [
            'grup' => $grup, 'induk' => $induk, 'label' => "Tambah $nama",
            'bawaan' => $bawaan, 'paths' => ["$prefix/create", "$prefix/store"],
        ] + $ekstra,
        "$key.daftar" => [
            'grup' => $grup, 'induk' => $induk, 'label' => "Daftar $nama",
            'bawaan' => $bawaan, 'paths' => ["$prefix/*"], 'kecuali' => ["$prefix/create", "$prefix/store"],
        ] + $ekstra,
    ];
};

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

    'menus' => array_merge(
        [
            // ---------------- Menu utama ----------------
            // Menu inti tanpa 'paths': hanya disembunyikan, URL-nya tetap jalan
            // karena dipakai halaman kasir (pencarian barang/member lewat AJAX).
            'cari_barang' => [
                'grup' => 'Menu Utama', 'label' => 'CARI BARANG',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'kas.riwayat' => [
                'grup' => 'Menu Utama', 'label' => 'Riwayat Kas di Tangan',
                'bawaan' => $semua, 'paths' => ['cashFlow/*'],
            ],
            // Tidak ada di sidebar: kotaknya ada di halaman Resume Transaksi Total.
            'kas.tutup' => [
                'grup' => 'Menu Utama', 'label' => 'Tutup Kasir (di Resume Transaksi Total)',
                'catatan' => 'hitung uang di laci, catat selisih, serahkan uang ke owner (penarikan uang)',
                'bawaan' => $semua, 'kasir' => true, 'paths' => ['cash-closing', 'cash-closing/*'],
            ],
            // Tidak ada di sidebar: kotaknya ada di dashboard admin.
            'kas.cek_kecocokan' => [
                'grup' => 'Menu Utama', 'label' => 'Cek Kecocokan (di dashboard admin)',
                'catatan' => 'hasil cek otomatis setiap tutup kasir: tandai sudah dibaca/ditangani, cek ulang, samakan stok',
                'bawaan' => $semua, 'paths' => ['reconciliation/*'],
            ],

            // ---------------- Barang ----------------
            'barang.daftar' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Daftar Barang',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'barang.expired' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Daftar Barang Expired',
                'bawaan' => $semua, 'kasir' => true, 'paths' => ['good/exp'],
            ],
            'barang.print_display' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Print Harga Barang Display',
                'bawaan' => $semua, 'kasir' => true, 'paths' => ['good/printDisplay'],
            ],
            'barang.stok_habis' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Stock Habis',
                'bawaan' => $semua, 'paths' => ['good/zeroStock/*'],
                'kecuali' => ['good/zeroStock/*/-1', 'good/zeroStock/export'],
            ],
            'barang.stok_minus' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Stock Minus',
                'bawaan' => $semua, 'paths' => ['good/zeroStock/*/-1'],
            ],
            'barang.grosir' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Harga Grosir Tidak Sesuai',
                'bawaan' => $semua, 'paths' => ['good/wholesalePrice'],
            ],
            'barang.rangkuman' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Rangkuman Barang',
                'bawaan' => $supervisor, 'paths' => ['good/resume/*'],
            ],
            'barang.ganti_nama' => [
                'grup' => 'Barang', 'induk' => 'Barang', 'label' => 'Ganti Nama Barang',
                'bawaan' => $supervisor, 'paths' => ['good/changeNameImport'],
            ],
            'sidak' => [
                'grup' => 'Barang', 'label' => 'Sidak Stok Toko',
                'bawaan' => $supervisor, 'paths' => ['sidak-stok', 'sidak-stok/*'],
            ],
            'stock_opname.tambah' => [
                'grup' => 'Barang', 'induk' => 'Stock Opname Barang', 'label' => 'Tambah Stock Opname Barang',
                'bawaan' => $semua, 'paths' => ['stock-opname/create', 'stock-opname/store', 'stock-opname/minus-goods'],
            ],
            'stock_opname.daftar' => [
                'grup' => 'Barang', 'induk' => 'Stock Opname Barang', 'label' => 'Daftar Stock Opname Barang',
                'bawaan' => $semua, 'paths' => ['stock-opname/*'],
                'kecuali' => ['stock-opname/create', 'stock-opname/store', 'stock-opname/minus-goods'],
            ],
            'loading.tambah' => [
                'grup' => 'Barang', 'induk' => 'Loading Barang', 'label' => 'Tambah Loading Barang',
                'bawaan' => $semua, 'paths' => ['good-loading/normal/create', 'good-loading/normal/createnew', 'good-loading/normal/store'],
            ],
            'loading.tambah_internal' => [
                'grup' => 'Barang', 'induk' => 'Loading Barang', 'label' => 'Tambah Loading Barang Internal',
                'bawaan' => $semua,
                'paths' => ['good-loading/internal/create', 'good-loading/internal/createnew', 'good-loading/internal/store', 'good-loading/transaction-internal/*'],
            ],
            'loading.daftar' => [
                'grup' => 'Barang', 'induk' => 'Loading Barang', 'label' => 'Daftar Loading Barang',
                'bawaan' => $semua, 'paths' => ['good-loading/*'],
                'kecuali' => ['good-loading/*/create', 'good-loading/*/createnew', 'good-loading/*/store', 'good-loading/transaction-internal/*', 'good-loading/excel', 'good-loading/storeExcel'],
            ],
            'loading.import' => [
                'grup' => 'Barang', 'induk' => 'Loading Barang', 'label' => 'Import Data Excel',
                'bawaan' => $semua, 'paths' => ['good-loading/excel', 'good-loading/storeExcel'],
            ],

            // ---------------- Transaksi ----------------
            'transaksi.tambah' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Tambah Transaksi',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'transaksi.tambah_gpt' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Tambah Transaksi (new page)',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'transaksi.tambah_hp' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Tambah Transaksi (HP)',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'transaksi.daftar' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Daftar Transaksi',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
            'transaksi.resume_total' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Resume Transaksi Total',
                'bawaan' => $semua, 'kasir' => true, 'paths' => ['transaction/resumeTotal/*'],
            ],
            'transaksi.resume_barang' => [
                'grup' => 'Transaksi', 'induk' => 'Transaksi Barang', 'label' => 'Resume Transaksi Per Barang',
                'bawaan' => $superadmin, 'paths' => ['transaction/resume/*'],
            ],
        ],
        $tambahDaftar('transaksi_internal', 'internal-transaction', 'Transaksi', 'Transaksi Internal', 'Transaksi Internal', $semua, ['kasir' => true]),
        [
            'retur' => [
                'grup' => 'Transaksi', 'label' => 'Barang Retur',
                'bawaan' => $semua, 'paths' => ['retur/*'],
            ],
        ],
        $tambahDaftar('transaksi_lain', 'other-transaction', 'Pemasukan & Pengeluaran Lain', 'Transaksi Lain (pemasukan)', 'Transaksi Lain', $semua, ['kasir' => true]),
        $tambahDaftar('biaya_lain', 'other-payment', 'Pemasukan & Pengeluaran Lain', 'Biaya Lain (pengeluaran)', 'Biaya Lain', $semua, ['kasir' => true]),

        // ---------------- Data master ----------------
        [
            'member.tambah' => [
                'grup' => 'Data Master', 'induk' => 'Member', 'label' => 'Tambah Member',
                'bawaan' => $superadmin, 'paths' => ['member/create', 'member/store'],
            ],
            'member.daftar' => [
                'grup' => 'Data Master', 'induk' => 'Member', 'label' => 'Daftar Member',
                'bawaan' => $semua, 'kasir' => true, 'paths' => [],
            ],
        ],
        $tambahDaftar('delivery_fee', 'delivery-fee', 'Data Master', 'Delivery Fee', 'Delivery Fee', $semua),
        $tambahDaftar('brand', 'brand', 'Data Master', 'Brand', 'Brand', $supervisor),
        $tambahDaftar('distributor', 'distributor', 'Data Master', 'Distributor', 'Distributor', $supervisor),
        [
            'kategori.rangkuman' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Rangkuman Kategori',
                'bawaan' => $supervisor, 'paths' => ['category/rangkuman'],
            ],
            'kategori.daftar' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Daftar Kategori',
                'bawaan' => $supervisor, 'paths' => ['category/*'],
                'kecuali' => ['category/create', 'category/store', 'category/rangkuman'],
            ],
            'kategori.tambah' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Tambah Kategori',
                'bawaan' => $supervisor, 'paths' => ['category/create', 'category/store'],
            ],
            'jenis.daftar' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Daftar Jenis Barang',
                'bawaan' => $supervisor, 'paths' => ['type/*'], 'kecuali' => ['type/create', 'type/store'],
            ],
            'jenis.tambah' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Tambah Jenis Barang',
                'bawaan' => $supervisor, 'paths' => ['type/create', 'type/store'],
            ],
            'kategori.penataan' => [
                'grup' => 'Data Master', 'induk' => 'Kategori & Jenis', 'label' => 'Penataan Kategori',
                'bawaan' => $supervisor, 'paths' => ['penataan-kategori', 'penataan-kategori/*'],
            ],
        ],
        $tambahDaftar('satuan', 'unit', 'Data Master', 'Satuan', 'Satuan', $supervisor),
        $tambahDaftar('voucher', 'voucher', 'Data Master', 'Voucher', 'Voucher', $supervisor),

        // ---------------- Laporan ----------------
        [
            'laporan.jurnal' => [
                'grup' => 'Laporan', 'induk' => 'Laporan Financial', 'label' => 'Jurnal',
                'bawaan' => $superadmin, 'paths' => ['journal/*'],
            ],
            'laporan.keuangan' => [
                'grup' => 'Laporan', 'induk' => 'Laporan Financial', 'label' => 'Laba Rugi, Neraca Bulanan & Kas',
                'bawaan' => $superadmin, 'paths' => ['reports/financial', 'reports/financial/*'],
            ],
            'laporan.neraca' => [
                'grup' => 'Laporan', 'induk' => 'Laporan Financial', 'label' => 'Detail Neraca',
                'bawaan' => $superadmin, 'paths' => ['scale/*'],
            ],
            'laporan.ledger' => [
                'grup' => 'Laporan', 'induk' => 'Laporan Financial', 'label' => 'Grafik Ledger Neraca',
                'bawaan' => $superadmin, 'paths' => ['scaleLedger/*', 'getScaleLedger/*'],
            ],
        ],
        $tambahDaftar('akun', 'account', 'Laporan', 'Akun', 'Akun', $superadmin),
        [
            'grafik.bulan' => [
                'grup' => 'Laporan', 'induk' => 'Grafik Penjualan', 'label' => 'Per Bulan',
                'bawaan' => $superadmin, 'paths' => ['salesGraph/month/*', 'getSalesGraph/month/*'],
            ],
            'grafik.kategori' => [
                'grup' => 'Laporan', 'induk' => 'Grafik Penjualan', 'label' => 'Per Kategori',
                'bawaan' => $superadmin, 'paths' => ['salesGraph/category/*', 'getSalesGraph/category/*'],
            ],
            'laporan.pemilik' => [
                'grup' => 'Laporan', 'label' => 'Ringkasan Pemilik',
                'bawaan' => $superadmin, 'paths' => ['reports/owner', 'reports/owner/*'],
            ],
            'laporan.penjualan' => [
                'grup' => 'Laporan', 'label' => 'Laporan Penjualan',
                'bawaan' => $superadmin, 'paths' => ['reports/sales', 'reports/sales/*'],
            ],
            'laporan.perputaran' => [
                'grup' => 'Laporan', 'label' => 'Perputaran Barang',
                'bawaan' => $superadmin, 'paths' => ['reports/movement', 'reports/movement/*'],
            ],
            'laporan.reorder' => [
                'grup' => 'Laporan', 'label' => 'Rekomendasi Reorder',
                'bawaan' => $superadmin, 'paths' => ['reports/reorder', 'reports/reorder/*'],
            ],
            'laporan.sering_hilang' => [
                'grup' => 'Laporan', 'label' => 'Barang Sering Hilang',
                'catatan' => 'barang yang kurang di stock opname: berapa kali, berapa rupiah',
                'bawaan' => $superadmin, 'paths' => ['reports/lost-goods', 'reports/lost-goods/*'],
            ],

            // ---------------- Informasi di dalam halaman ----------------
            // Bukan menu: menentukan isi halaman yang tampil (menu tetap bisa dibuka).
            'info.harga_beli' => [
                'grup' => 'Informasi di dalam halaman', 'label' => 'Lihat & ubah harga beli',
                'catatan' => 'kolom harga beli / modal di daftar & detail barang, loading, transaksi internal, edit transaksi, rangkuman kategori',
                'bawaan' => $supervisor, 'paths' => [],
            ],
            'info.laba' => [
                'grup' => 'Informasi di dalam halaman', 'label' => 'Lihat laba, HPP & untung',
                'catatan' => 'untung per barang, laba loading, HPP & untung transaksi, harga beli di detail/riwayat transaksi, total aset/untung/rugi per distributor',
                'bawaan' => $superadmin, 'paths' => [],
            ],
            'info.total_aset' => [
                'grup' => 'Informasi di dalam halaman', 'label' => 'Lihat total seluruh aset',
                'catatan' => 'tulisan "Total seluruh aset: Rp..." di atas daftar distributor',
                'bawaan' => $supervisor, 'paths' => [],
            ],
        ],

        // ---------------- Edit & hapus per fungsi ----------------
        // Melihat daftar/detail diatur oleh sub menu "Daftar ..." di atas;
        // di sini hanya tombol edit/hapus. URL-nya ikut ditolak kalau ditutup.
        // Induk sengaja beda dengan nama menu sidebar, supaya canInduk() menu
        // tidak ikut terbuka hanya karena hak edit.
        [
            'aksi.transaksi.edit' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Transaksi Barang (aksi)', 'label' => 'Edit transaksi',
                'catatan' => 'tombol Edit di daftar transaksi dan tombol Ubah di detail transaksi',
                'bawaan' => $superadmin, 'paths' => ['transaction/*/edit'],
            ],
            'aksi.transaksi.hapus' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Transaksi Barang (aksi)', 'label' => 'Hapus transaksi',
                'bawaan' => $superadmin, 'paths' => ['transaction/*/delete'],
            ],
            'aksi.transaksi_internal.edit' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Transaksi Internal (aksi)', 'label' => 'Edit transaksi internal',
                'bawaan' => $superadmin, 'paths' => ['internal-transaction/*/edit'],
            ],
            'aksi.transaksi_internal.hapus' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Transaksi Internal (aksi)', 'label' => 'Hapus transaksi internal',
                'bawaan' => $superadmin, 'paths' => ['internal-transaction/*/delete'],
            ],
            'aksi.loading.edit' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Loading Barang (aksi)', 'label' => 'Edit loading barang',
                'bawaan' => $superadmin, 'paths' => ['good-loading/*/edit'],
            ],
            'aksi.loading.hapus' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Loading Barang (aksi)', 'label' => 'Hapus loading barang',
                'bawaan' => $superadmin, 'paths' => ['good-loading/*/delete'],
            ],
            'aksi.member.edit' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Member (aksi)', 'label' => 'Tombol Ubah di daftar member',
                'catatan' => 'hanya tombol di daftar; ubah data dari halaman detail member tetap boleh untuk semua admin',
                'bawaan' => $superadmin, 'paths' => [],
            ],
            'aksi.member.hapus' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Member (aksi)', 'label' => 'Hapus member',
                'bawaan' => $superadmin, 'paths' => ['member/*/delete'],
            ],
            'aksi.tutup_kasir.batal' => [
                'grup' => 'Edit & Hapus', 'induk' => 'Tutup Kasir (aksi)', 'label' => 'Batalkan tutup kasir terakhir',
                'catatan' => 'jurnal selisih dan penarikannya ikut dihapus, lalu kasir bisa ditutup ulang',
                'bawaan' => $superadmin, 'paths' => ['cash-closing/*/cancel'],
            ],
        ]
    ),
];
