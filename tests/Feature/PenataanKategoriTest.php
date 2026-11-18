<?php

namespace Tests\Feature;

use App\Services\PenataanKategori\PemetaanBarang;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tahap 3 penataan kategori: usulan, CSV, terapkan, pulihkan.
 *
 * Memakai database test kosong (bawaan `kuncen_test`, sama dengan tes stok).
 * Tidak memakai transaksi rollback karena penerapan membuat tabel backup
 * (CREATE TABLE meng-commit transaksi); data tes dihapus sendiri di tearDown.
 */
class PenataanKategoriTest extends TestCase
{
    private static ?string $unavailable = null;
    private array $previousEnv = [];
    private array $files = [];

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

        if (self::$unavailable === null) {
            self::$unavailable = $this->prepareDatabase();
        }
        if (self::$unavailable !== '') {
            $this->restoreEnv();
            $this->markTestSkipped(self::$unavailable);
        }

        parent::setUp();
        $this->cleanUp();
        $this->seed_();
    }

    protected function tearDown(): void
    {
        if ($this->app) {
            $this->cleanUp();
        }
        foreach ($this->files as $f) {
            @unlink($f);
        }
        parent::tearDown();
        $this->restoreEnv();
    }

    private function cleanUp(): void
    {
        foreach (PemetaanBarang::daftarBackup() as $t) {
            DB::statement("DROP TABLE `$t`");
        }
        foreach (['goods', 'types', 'categories'] as $t) {
            DB::table($t)->delete();
        }
    }

    public function test_usulan_mengikuti_jenis_dan_kata_kunci(): void
    {
        $u = $this->usulan();

        // barang tanpa jenis -> jenis dari kata di awal nama, awalan nama dibuang
        $this->assertSame(['Alat Kebersihan', 'Sapu', 'tinggi', 'ijuk hitam'], $u[1]);
        $this->assertSame(['Ember, Bak & Alat Cuci', 'Ember', 'tinggi', 'ikawa 3 galon'], $u[2]);
        // sudah punya jenis -> kategori ikut jenis, nama tidak diubah
        $this->assertSame(['Ember, Bak & Alat Cuci', 'Gayung', 'tinggi', ''], $u[3]);
        // "kue toples" adalah makanan, bukan toples
        $this->assertSame(['Makanan & Minuman Kemasan', 'Kue Toples', 'tinggi', 'nastar'], $u[5]);
        // barang terhapus tetap dipetakan
        $this->assertSame('Sapu', $u[7][1]);
        // kata paling awal di nama yang menang
        $this->assertSame('Toples Plastik', $u[8][1]);

        $un = $this->tidakTerpetakan();
        $this->assertSame('', $un[4]['usulan jenis baru']);           // "Piring dinasty": bahan tidak disebut
        $this->assertSame('Rak Plastik', $un[6]['usulan jenis baru']); // jenis usulan belum ada
    }

    public function test_buang_awalan_nama(): void
    {
        $svc = new PemetaanBarang();
        $this->assertSame('injak 10 L', $svc->buangAwalan('Tempat sampah injak 10 L', 'Tempat Sampah'));
        $this->assertSame('plastik', $svc->buangAwalan('Baskom plastik', 'Bak & Baskom'));
        $this->assertSame('Gembok', $svc->buangAwalan('Gembok', 'Gembok'));
        $this->assertSame('willow food container', $svc->buangAwalan('willow food container', 'Tempat Makan'));
    }

    public function test_baca_csv_dari_excel_ansi_dan_koma(): void
    {
        $path = $this->tmp("id,nama,jenis baru\r\n1,caf\xe9 kecil,Sapu\r\n\r\n");
        $rows = PemetaanBarang::bacaCsv($path);

        $this->assertCount(1, $rows);
        $this->assertSame('café kecil', $rows[0]['nama']);
        $this->assertSame('Sapu', $rows[0]['jenis baru']);
    }

    public function test_terapkan_csv_lalu_pulihkan(): void
    {
        $svc = new PemetaanBarang();
        $rows = $svc->usulan()['pemetaan'];

        // Pemilik mengoreksi barang 2 menjadi Gayung dan menghapus baris barang 7.
        foreach ($rows as &$r) {
            if ($r['id'] == 2) {
                $r['jenis baru'] = 'Gayung';
            }
        }
        unset($r);
        $rows = array_values(array_filter($rows, fn ($r) => $r['id'] != 7));
        $path = $this->tmp('');
        PemetaanBarang::tulisCsv($path, PemetaanBarang::KOLOM_PEMETAAN, $rows);

        // Setelah CSV diunduh, barang 3 diubah orang lain -> harus dilewati.
        DB::table('goods')->where('id', 3)->update(['category_id' => $this->cat['FOOD']]);
        $before = $this->goods();

        $hasil = (new PemetaanBarang())->terapkanCsv($path);

        $after = $this->goods();
        $this->assertSame(['ijuk hitam', $this->type['Sapu'], $this->cat['CLEANTOOL']], $after[1]);
        $this->assertSame(['ikawa 3 galon', $this->type['Gayung'], $this->cat['BUCKET']], $after[2]);
        $this->assertSame($before[3], $after[3]);   // dilewati
        $this->assertSame($before[4], $after[4]);   // tidak terpetakan
        $this->assertSame($before[7], $after[7]);   // barisnya dihapus pemilik
        $this->assertCount(1, $hasil['dilewati']);
        $this->assertStringContainsString('id 3', $hasil['dilewati'][0]);
        $this->assertSame(['last_stock' => 5.0], ['last_stock' => (float) DB::table('goods')->where('id', 1)->value('last_stock')]);
        $this->assertContains($hasil['backup'], PemetaanBarang::daftarBackup());

        (new PemetaanBarang())->pulihkan($hasil['backup']);
        $this->assertSame($before, $this->goods());
    }

    public function test_csv_tidak_terpetakan_bisa_diisi_lalu_diterapkan(): void
    {
        $rows = (new PemetaanBarang())->usulan()['tidak_terpetakan'];
        foreach ($rows as &$r) {
            if ($r['id'] == 4) {
                $r['jenis baru'] = 'Toples Plastik';   // pemilik memutuskan barang 4
            }
        }
        unset($r);
        $path = $this->tmp('');
        PemetaanBarang::tulisCsv($path, PemetaanBarang::KOLOM_TIDAK_TERPETAKAN, $rows);
        $before = $this->goods();

        $hasil = (new PemetaanBarang())->terapkanCsv($path);

        $this->assertSame(1, $hasil['diubah']);
        $this->assertSame([], $hasil['dilewati']);           // baris kosong diabaikan, bukan error
        $this->assertSame(['Piring dinasty', $this->type['Toples Plastik'], $this->cat['TABLEWARE']], $this->goods()[4]);
        $this->assertSame($before[6], $this->goods()[6]);
    }

    public function test_halaman_hanya_untuk_supervisor(): void
    {
        $admin = \App\Admin::forceCreate(['name' => 'Tes', 'email' => 'tes_pk', 'password' => 'x', 'is_active' => 1, 'role' => 'admin']);
        $super = \App\Admin::forceCreate(['name' => 'Sup', 'email' => 'sup_pk', 'password' => 'x', 'is_active' => 1, 'role' => 'supervisor']);

        try {
            $this->actingAs($admin, 'admin')->get('/admin/penataan-kategori')->assertForbidden();

            $this->actingAs($super, 'admin')->get('/admin/penataan-kategori')
                ->assertOk()->assertSee('Penataan Kategori')->assertSee('Peralatan Rumah Tangga (lama)');

            $csv = $this->actingAs($super, 'admin')->get('/admin/penataan-kategori/pemetaan.csv');
            $csv->assertOk();
            $this->assertStringContainsString('Sapu ijuk hitam', file_get_contents($csv->baseResponse->getFile()->getPathname()));

            // tanpa centang backup -> ditolak, data tidak berubah
            $before = $this->goods();
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', "id;jenis baru\n1;Sapu\n");
            $this->actingAs($super, 'admin')->post('/admin/penataan-kategori/terapkan', ['file' => $file])
                ->assertSessionHasErrors('sudah_backup');
            $this->assertSame($before, $this->goods());

            // lewat HTTP (dengan semua middleware web) -> diterapkan tanpa error transaksi
            $path = $this->tmp('');
            PemetaanBarang::tulisCsv($path, PemetaanBarang::KOLOM_PEMETAAN, (new PemetaanBarang())->usulan()['pemetaan']);
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', file_get_contents($path));
            $this->actingAs($super, 'admin')
                ->post('/admin/penataan-kategori/terapkan', ['file' => $file, 'sudah_backup' => '1'])
                ->assertRedirect('/admin/penataan-kategori')
                ->assertSessionHas('hasil', fn ($h) => $h['diubah'] === 6 && $h['dilewati'] === []);
            $this->assertSame(['ijuk hitam', $this->type['Sapu'], $this->cat['CLEANTOOL']], $this->goods()[1]);
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            DB::table('admins')->whereIn('id', [$admin->id, $super->id])->delete();
        }
    }

    // ------------------------------------------------------------------

    private array $cat = [];
    private array $type = [];

    private function seed_(): void
    {
        foreach (['HOUSEWARE' => 'Peralatan Rumah Tangga (lama)', 'CLEANTOOL' => 'Alat Kebersihan', 'BUCKET' => 'Ember, Bak & Alat Cuci',
                  'TABLEWARE' => 'Alat Makan & Minum Tidak Pecah', 'FOOD' => 'Makanan & Minuman Kemasan', 'STORAGEBOX' => 'Box Penyimpanan'] as $code => $name) {
            $this->cat[$code] = DB::table('categories')->insertGetId(['code' => $code, 'name' => $name]);
        }
        foreach (['Sapu' => 'CLEANTOOL', 'Ember' => 'BUCKET', 'Gayung' => 'BUCKET', 'Toples Plastik' => 'TABLEWARE', 'Kue Toples' => 'FOOD'] as $name => $code) {
            $this->type[$name] = DB::table('types')->insertGetId(['name' => $name, 'category_id' => $this->cat[$code]]);
        }
        $h = $this->cat['HOUSEWARE'];
        foreach ([
            [1, 'Sapu ijuk hitam', null, 5, null],
            [2, 'Ember ikawa 3 galon', null, 0, null],
            [3, 'tutup biru', $this->type['Gayung'], 2, null],
            [4, 'Piring dinasty', null, 1, null],
            [5, 'Kue toples nastar', null, 0, null],
            [6, 'Rak sepatu susun 3', null, 1, null],
            [7, 'Sapu nilon', null, 0, '2025-01-01 00:00:00'],
            [8, 'Toples kecil tutup sapu', null, 0, null],
        ] as [$id, $name, $type, $stock, $deleted]) {
            DB::table('goods')->insert(['id' => $id, 'name' => $name, 'category_id' => $h, 'type_id' => $type,
                                        'last_stock' => $stock, 'deleted_at' => $deleted]);
        }
    }

    /** id => [kategori baru, jenis baru, keyakinan, nama baru] */
    private function usulan(): array
    {
        $out = [];
        foreach ((new PemetaanBarang())->usulan()['pemetaan'] as $r) {
            $out[$r['id']] = [$r['kategori baru'], $r['jenis baru'], $r['tingkat keyakinan'], $r['nama baru']];
        }

        return $out;
    }

    private function tidakTerpetakan(): array
    {
        return collect((new PemetaanBarang())->usulan()['tidak_terpetakan'])->keyBy('id')->all();
    }

    private function goods(): array
    {
        return DB::table('goods')->orderBy('id')->get(['id', 'name', 'type_id', 'category_id'])
            ->mapWithKeys(fn ($g) => [$g->id => [$g->name, $g->type_id, $g->category_id]])->all();
    }

    private function tmp(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pkt');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    /** '' bila siap; selain itu alasan dilewati. Memuat ulang struktur bila database test kosong dan strukturnya lama. */
    private function prepareDatabase(): string
    {
        try {
            $pdo = new \PDO('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        } catch (\Throwable $e) {
            return 'Database test MySQL tidak tersedia: ' . $e->getMessage();
        }
        $tables = $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN);
        if (in_array('goods', $tables) && (int) $pdo->query('select count(*) from goods')->fetchColumn() > 0) {
            return 'Database test ' . $_ENV['DB_DATABASE'] . ' tidak kosong; pakai database kosong khusus test.';
        }
        $hasNewColumns = in_array('types', $tables)
            && $pdo->query("show columns from types like 'category_id'")->fetch() !== false;
        if (! $hasNewColumns) {
            $pdo->exec(file_get_contents(dirname(__DIR__, 2) . '/database/schema/kuncen-schema.sql'));
        }

        return '';
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
}
