<?php

namespace Tests\Feature;

use App\Admin;
use App\Services\MenuAkses;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Hak akses menu per role (config/menu_akses.php + tabel menu_permissions).
 * Memakai database test MySQL `kuncen_test` (sama dengan tes lain); tabel
 * menu_permissions dibuat lalu dihapus lagi oleh tes ini.
 */
class MenuAksesTest extends TestCase
{
    private array $previousEnv = [];

    protected function setUp(): void
    {
        $this->useEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_URL'        => '',
            'DB_HOST'       => getenv('TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT'       => getenv('TEST_DB_PORT') ?: '3306',
            'DB_DATABASE'   => getenv('TEST_DB_DATABASE') ?: 'kuncen_test',
            'DB_USERNAME'   => getenv('TEST_DB_USERNAME') ?: 'root',
            'DB_PASSWORD'   => getenv('TEST_DB_PASSWORD') ?: '',
        ]);
        try {
            new \PDO('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        } catch (\Throwable $e) {
            $this->restoreEnv();
            $this->markTestSkipped('Database test MySQL tidak tersedia: ' . $e->getMessage());
        }

        parent::setUp();
        Schema::dropIfExists('menu_permissions');
        (require base_path('database/migrations/2026_11_18_000002_create_menu_permissions_table.php'))->up();
        MenuAkses::lupakan();
    }

    protected function tearDown(): void
    {
        if ($this->app) {
            Schema::dropIfExists('menu_permissions');
            MenuAkses::lupakan();
        }
        parent::tearDown();
        $this->restoreEnv();
    }

    private function useEnv(array $values): void
    {
        foreach ($values as $k => $v) {
            $this->previousEnv[$k] = [$_ENV[$k] ?? null, $_SERVER[$k] ?? null, getenv($k)];
            $_ENV[$k] = $_SERVER[$k] = $v;
            putenv("$k=$v");
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->previousEnv as $k => [$env, $server, $put]) {
            if ($env === null) unset($_ENV[$k]); else $_ENV[$k] = $env;
            if ($server === null) unset($_SERVER[$k]); else $_SERVER[$k] = $server;
            putenv($put === false ? $k : "$k=$put");
        }
        $this->previousEnv = [];
    }

    private function login(?string $role): void
    {
        $admin = new Admin(['name' => 'tes', 'email' => 'tes@example.com', 'role' => $role]);
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    public function test_bawaan_sama_dengan_aturan_lama(): void
    {
        $this->assertTrue(MenuAkses::boleh('brand.daftar', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('brand.daftar', 'admin'));
        $this->assertFalse(MenuAkses::boleh('laporan.penjualan', 'supervisor'));
        $this->assertTrue(MenuAkses::boleh('stock_opname.daftar', 'admin'));
        $this->assertTrue(MenuAkses::boleh('laporan.penjualan', 'superadmin'));
        $this->assertFalse(MenuAkses::boleh('menu.tidak.ada', 'superadmin'));
    }

    public function test_simpan_mengubah_akses_dan_superadmin_tetap_boleh(): void
    {
        MenuAkses::simpan(['supervisor' => ['laporan.penjualan' => '1'], 'admin' => ['brand.daftar' => '1']]);

        $this->assertTrue(MenuAkses::boleh('laporan.penjualan', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('brand.daftar', 'supervisor'), 'tidak dicentang = ditutup');
        $this->assertTrue(MenuAkses::boleh('brand.daftar', 'admin'));
        $this->assertTrue(MenuAkses::boleh('brand.daftar', 'superadmin'));

        MenuAkses::kembalikanBawaan();
        $this->assertTrue(MenuAkses::boleh('brand.daftar', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('laporan.penjualan', 'supervisor'));
    }

    public function test_tanpa_tabel_memakai_bawaan(): void
    {
        Schema::drop('menu_permissions');
        MenuAkses::lupakan();

        $this->assertFalse(MenuAkses::tabelAda());
        $this->assertTrue(MenuAkses::boleh('brand.daftar', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('brand.daftar', 'admin'));
    }

    public function test_role_kosong_dihitung_admin(): void
    {
        $this->login(null);
        $this->assertSame('admin', MenuAkses::roleSekarang());
        $this->assertFalse(canMenu('brand.daftar'));
        $this->assertTrue(canMenu('stock_opname.daftar'));
    }

    public function test_url_dijaga_kecuali_pencarian_ajax(): void
    {
        $this->assertSame(['brand.daftar'], MenuAkses::menuUntukPath('brand/15'));
        $this->assertSame(['brand.tambah'], MenuAkses::menuUntukPath('brand/create'));
        $this->assertSame(['laporan.penjualan'], MenuAkses::menuUntukPath('reports/sales'));
        $this->assertSame(['transaksi.resume_barang'], MenuAkses::menuUntukPath('transaction/resume/normal/all/all/2026-01-01/2026-01-31/100'));
        $this->assertSame(['transaksi.resume_total'], MenuAkses::menuUntukPath('transaction/resumeTotal/2026-01-01/2026-01-31'));
        $this->assertSame([], MenuAkses::menuUntukPath('distributor/search/abc'));
        $this->assertSame([], MenuAkses::menuUntukPath('good/searchByBarcode/123'));
        $this->assertSame([], MenuAkses::menuUntukPath('transaction/store'));
    }

    public function test_url_menu_tertutup_ditolak(): void
    {
        $this->login('supervisor');
        $this->get('/admin/reports/sales')->assertForbidden();

        $this->login(null);
        $this->get('/admin/brand/15')->assertForbidden();
        $this->get('/admin/hak-akses')->assertForbidden();
    }

    public function test_superadmin_membuka_dan_menyimpan_halaman_hak_akses(): void
    {
        $this->login('superadmin');

        $this->get('/admin/hak-akses')
            ->assertOk()
            ->assertSee('Hak Akses Menu')
            ->assertSee('name="akses[supervisor][brand.daftar]"', false);

        $this->post('/admin/hak-akses', ['akses' => ['supervisor' => ['laporan.penjualan' => '1']]])
            ->assertRedirect(route('admin.menu-akses.index'));

        $this->assertTrue(MenuAkses::boleh('laporan.penjualan', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('brand.daftar', 'supervisor'));
    }

    public function test_halaman_membuat_tabel_kalau_belum_ada(): void
    {
        Schema::drop('menu_permissions');
        MenuAkses::lupakan();
        $this->login('superadmin');

        $this->get('/admin/hak-akses')->assertOk()->assertDontSee('gagal dibuat otomatis');
        $this->assertTrue(Schema::hasTable('menu_permissions'));
    }

    public function test_superadmin_lihat_sebagai_role_lain(): void
    {
        MenuAkses::simpan(['supervisor' => ['stock_opname.daftar' => '1']]);
        $this->login('superadmin');
        $this->assertTrue(canMenu('brand.daftar'));

        $this->get('/admin/hak-akses/lihat-sebagai/supervisor')->assertRedirect('/admin');
        $this->assertSame('supervisor', MenuAkses::roleSekarang());
        $this->assertFalse(canMenu('brand.daftar'));
        $this->assertTrue(isSuperadmin(), 'role asli tetap superadmin');
        $this->get('/admin/brand/15')->assertForbidden();
        $this->get('/admin/hak-akses')->assertOk()->assertSee('Selesai mencoba');

        $this->get('/admin/hak-akses/lihat-sebagai/selesai')->assertRedirect(route('admin.menu-akses.index'));
        $this->assertSame('superadmin', MenuAkses::roleSekarang());
        $this->assertTrue(canMenu('brand.daftar'));
    }

    public function test_lihat_sebagai_tidak_berlaku_untuk_bukan_superadmin(): void
    {
        $this->login('supervisor');
        session([MenuAkses::SESSION_LIHAT_SEBAGAI => 'admin']);

        $this->assertSame('supervisor', MenuAkses::roleSekarang());
        $this->get('/admin/hak-akses/lihat-sebagai/admin')->assertForbidden();
    }

    public function test_harga_beli_bisa_diatur(): void
    {
        $this->assertTrue(MenuAkses::boleh('info.harga_beli', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('info.harga_beli', 'admin'));

        MenuAkses::simpan(['supervisor' => ['brand.daftar' => '1']]);
        $this->assertFalse(MenuAkses::boleh('info.harga_beli', 'supervisor'));
    }

    public function test_edit_hapus_butuh_menu_dan_hak_edit(): void
    {
        $this->assertSame(['loading.daftar', 'aksi.loading.edit'], MenuAkses::menuUntukPath('good-loading/5/edit'));
        $this->assertSame(['loading.daftar', 'aksi.loading.hapus'], MenuAkses::menuUntukPath('good-loading/5/delete'));
        $this->assertSame(['aksi.transaksi.edit'], MenuAkses::menuUntukPath('transaction/5/edit'));
        $this->assertSame(['aksi.transaksi.hapus'], MenuAkses::menuUntukPath('transaction/5/delete'));
        $this->assertSame(['aksi.member.hapus'], MenuAkses::menuUntukPath('member/5/delete'));
        $this->assertSame([], MenuAkses::menuUntukPath('transaction/5/detail'));
        $this->assertSame([], MenuAkses::menuUntukPath('member/5/edit'));

        $this->login('supervisor');
        $this->get('/admin/good-loading/5/edit')->assertForbidden();
        $this->delete('/admin/transaction/5/delete')->assertForbidden();

        MenuAkses::simpan(['supervisor' => ['aksi.loading.edit' => '1']]);
        $this->get('/admin/good-loading/5/edit')->assertForbidden(); // menu Daftar Loading ditutup
    }

    /** Contoh dari pemakai: transaksi hanya lihat, loading lihat + edit (tanpa hapus). */
    public function test_edit_hapus_dicentang_per_fungsi(): void
    {
        MenuAkses::simpan(['supervisor' => [
            'transaksi.daftar' => '1',
            'loading.daftar'   => '1', 'aksi.loading.edit' => '1',
        ]]);
        $this->login('supervisor');

        $this->assertTrue(canMenu('aksi.loading.edit'));
        $this->assertFalse(canMenu('aksi.loading.hapus'));
        $this->assertFalse(canMenu('aksi.transaksi.edit'));
        $this->assertFalse(canMenu('aksi.transaksi.hapus'));

        $this->assertNotSame(403, $this->get('/admin/good-loading/5/edit')->getStatusCode());
        $this->delete('/admin/good-loading/5/delete')->assertForbidden();
        $this->get('/admin/transaction/5/edit')->assertForbidden();
        $this->delete('/admin/transaction/5/delete')->assertForbidden();

        // menu induk sidebar tidak ikut terbuka karena hak edit
        $this->assertFalse(canInduk('Transaksi Internal'));
    }

    public function test_sub_menu_dijaga_sendiri_sendiri(): void
    {
        $cek = [
            'good-loading/normal/create'                    => ['loading.tambah'],
            'good-loading/normal/store'                     => ['loading.tambah'],
            'good-loading/internal/create'                  => ['loading.tambah_internal'],
            'good-loading/2026-01-01/2026-01-31/all/50'     => ['loading.daftar'],
            'good-loading/5/detail'                         => ['loading.daftar'],
            'good-loading/excel'                            => ['loading.import'],
            'good-loading/storeExcel'                       => ['loading.import'],
            'stock-opname/create'                           => ['stock_opname.tambah'],
            'stock-opname/5/detail'                         => ['stock_opname.daftar'],
            'category/rangkuman'                            => ['kategori.rangkuman'],
            'category/15'                                   => ['kategori.daftar'],
            'category/create'                               => ['kategori.tambah'],
            'type/name/asc/30'                              => ['jenis.daftar'],
            'type/store'                                    => ['jenis.tambah'],
            'good/zeroStock/all/all/all/1/0'                => ['barang.stok_habis'],
            'good/zeroStock/all/all/all/all/-1'             => ['barang.stok_minus'],
            'journal/all/all/2026-01-01/2026-01-31/id/asc/15' => ['laporan.jurnal'],
            'scale/2020-01-01/2026-01-31'                   => ['laporan.neraca'],
            'scaleLedger/2020-01-01/2026-01-31'             => ['laporan.ledger'],
            'salesGraph/month/2020-01-01/2026-01-31'        => ['grafik.bulan'],
            'getSalesGraph/category/2020-01-01/2026-01-31'  => ['grafik.kategori'],
            'internal-transaction/create'                   => ['transaksi_internal.tambah'],
            'internal-transaction/5/edit'                   => ['transaksi_internal.daftar', 'aksi.transaksi_internal.edit'],
        ];
        foreach ($cek as $path => $keys) {
            $this->assertSame($keys, MenuAkses::menuUntukPath($path), $path);
        }

        // Tambah dibuka, Daftar ditutup: hanya halaman tambah yang bisa dibuka.
        MenuAkses::simpan(['admin' => ['brand.tambah' => '1']]);
        $this->login(null);
        $this->get('/admin/brand/15')->assertForbidden();
        $this->assertNotSame(403, $this->get('/admin/brand/create')->getStatusCode());
        $this->assertTrue(canInduk('Brand'));
        $this->assertFalse(canInduk('Distributor'));
    }

    public function test_total_aset_bisa_disembunyikan(): void
    {
        $this->assertTrue(MenuAkses::boleh('info.total_aset', 'supervisor'));
        MenuAkses::simpan(['supervisor' => ['distributor.daftar' => '1']]);
        $this->assertFalse(MenuAkses::boleh('info.total_aset', 'supervisor'));
    }

    public function test_simpan_membuang_kunci_lama(): void
    {
        \Illuminate\Support\Facades\DB::table('menu_permissions')->insert(['role' => 'supervisor', 'menu_key' => 'menu_lama', 'allowed' => 1]);
        MenuAkses::simpan([]);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('menu_permissions')->where('menu_key', 'menu_lama')->count());
    }

    public function test_laba_bawaan_hanya_superadmin(): void
    {
        $this->assertFalse(MenuAkses::boleh('info.laba', 'supervisor'));
        $this->assertFalse(MenuAkses::boleh('info.laba', 'admin'));
        $this->assertTrue(MenuAkses::boleh('info.laba', 'superadmin'));
    }

    public function test_menu_yang_dibuka_lolos_middleware(): void
    {
        MenuAkses::simpan(['supervisor' => ['laporan.penjualan' => '1']]);
        $this->login('supervisor');

        // Lolos penjaga menu; selanjutnya controller boleh gagal karena DB tes kosong.
        $status = $this->get('/admin/reports/sales')->getStatusCode();
        $this->assertNotSame(403, $status);
    }
}
