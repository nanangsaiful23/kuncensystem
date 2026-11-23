<?php

namespace Tests\Feature\Stock;

use App\Cashier;
use App\Models\PosDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * API Kasir HP (docs/rancangan-kasir-hp-tahap1.md). Memakai database test
 * MySQL seperti tes stok lain; setiap tes di-rollback.
 *
 * Tabel Kasir HP dibuat sekali dari docs/sql/2026-11-23-kasir-hp-tahap1.sql
 * lewat koneksi PDO terpisah (DDL di koneksi tes akan meng-commit transaksi tes).
 */
class KasirHpApiTest extends StockTestCase
{
    private static bool $posTablesReady = false;

    private string $token;
    private PosDevice $device;

    protected function setUp(): void
    {
        // Sebelum parent::setUp(): DDL harus jalan sebelum transaksi tes dibuka,
        // kalau tidak ALTER TABLE menunggu metadata lock selamanya.
        if (! self::$posTablesReady) {
            self::$posTablesReady = true;
            try {
                $this->createPosTables();
            } catch (\PDOException $e) {
                // Database tes tidak ada: parent::setUp() akan melewati tes.
            }
        }

        parent::setUp();

        DB::table('members')->where('id', 1)->update(['name' => 'Non Member']);

        $this->device = PosDevice::create(['name' => 'HP Test', 'code' => 'HP1', 'is_active' => true]);
        $this->token  = $this->device->issueToken();
    }

    private function createPosTables(): void
    {
        $pdo = new \PDO(
            'mysql:host=' . (getenv('TEST_DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('TEST_DB_PORT') ?: '3306') . ';dbname=' . (getenv('TEST_DB_DATABASE') ?: 'kuncen_test'),
            getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: ''
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if (! in_array('transactions', $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN))) {
            $pdo->exec(file_get_contents(dirname(__DIR__, 3) . '/database/schema/kuncen-schema.sql'));
        }
        $hasColumn = $pdo->query("show columns from transactions like 'client_uuid'")->fetch() !== false;

        $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__, 3) . '/docs/sql/2026-11-23-kasir-hp-tahap1.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if (str_contains($statement, '`migrations`')) {
                continue;
            }
            if ($hasColumn && str_starts_with($statement, 'ALTER TABLE `transactions`')) {
                continue;
            }
            $pdo->exec($statement);
        }
    }

    private function api(): self
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    /** Satu transaksi HP. $items: [good_unit_id, qty, price, discount]. */
    private function sale(array $items, array $extra = []): array
    {
        $cashierId = $extra['cashier_id'] ?? ($this->cashierId ??= $this->cashier()->id);
        $total = 0;
        $rows  = [];
        foreach ($items as $it) {
            [$gu, $qty, $price] = $it;
            $disc   = $it[3] ?? 0;
            $rows[] = ['good_unit_id' => $gu, 'quantity' => $qty, 'price' => $price, 'discount' => $disc];
            $total += $qty * $price - $disc;
        }
        $total -= $extra['total_discount'] ?? 0;

        return array_merge([
            'client_uuid'    => (string) Str::uuid(),
            'receipt_no'     => 'HP1-' . date('Ymd') . '-0001',
            'sold_at'        => now()->subMinutes(5)->toIso8601String(),
            'cashier_id'     => $cashierId,
            'member_id'      => null,
            'payment'        => 'cash',
            'items'          => $rows,
            'total_discount' => 0,
            'total'          => $total,
            'money_paid'     => $total,
            'money_returned' => 0,
            'note'           => null,
        ], $extra);
    }

    private ?int $cashierId = null;

    private function send(array ...$sales)
    {
        return $this->api()->postJson('/api/pos/v1/transactions', ['transactions' => $sales]);
    }

    public function test_tanpa_token_atau_hp_nonaktif_ditolak(): void
    {
        $this->getJson('/api/pos/v1/ping')->assertStatus(401);
        $this->withHeader('Authorization', 'Bearer salah')->getJson('/api/pos/v1/ping')->assertStatus(401);

        $this->api()->getJson('/api/pos/v1/ping')->assertOk()->assertJsonPath('device.code', 'HP1');

        $this->device->update(['is_active' => false]);
        $this->api()->getJson('/api/pos/v1/ping')->assertStatus(403);
    }

    public function test_pasang_hp_dengan_kode_sekali_pakai(): void
    {
        $device = PosDevice::create(['name' => 'HP Baru', 'code' => 'HP2', 'is_active' => true]);
        $code   = $device->newPairCode();

        $token = $this->postJson('/api/pos/v1/pair', ['pair_code' => $code])
                      ->assertOk()->assertJsonPath('device.code', 'HP2')->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/pos/v1/ping')->assertOk();
        $this->postJson('/api/pos/v1/pair', ['pair_code' => $code])->assertStatus(422);

        // Kode kedaluwarsa
        $code = $device->newPairCode();
        $device->forceFill(['pair_expires_at' => now()->subMinute()])->save();
        $this->postJson('/api/pos/v1/pair', ['pair_code' => $code])->assertStatus(422);
    }

    public function test_login_kasir(): void
    {
        // Disimpan persis seperti akun kasir asli (password + PASSWORD_SUFFIX), bukan Hash::make polos.
        $id = DB::table('cashiers')->insertGetId([
            'name' => 'Rina', 'email' => 'rina@test', 'password' => Hash::make('rahasia' . Cashier::PASSWORD_SUFFIX), 'is_active' => 1,
        ]);

        // Login web kasir dan login Kasir HP/PC menerima password yang sama.
        $this->post('/cashier/login', ['email' => 'rina@test', 'password' => 'rahasia'])->assertRedirect('/cashier');
        $this->assertAuthenticated('cashier');

        $this->api()->postJson('/api/pos/v1/login', ['email' => 'rina@test', 'password' => 'rahasia'])
             ->assertOk()->assertJsonPath('cashier.id', $id)->assertJsonPath('cashier.name', 'Rina');
        $this->api()->postJson('/api/pos/v1/login', ['email' => 'rina@test', 'password' => 'salah'])->assertStatus(422);
        $this->api()->postJson('/api/pos/v1/login', ['email' => 'rina@test', 'password' => 'rahasia' . Cashier::PASSWORD_SUFFIX])->assertStatus(422);

        DB::table('cashiers')->where('id', $id)->update(['is_active' => 0]);
        $this->api()->postJson('/api/pos/v1/login', ['email' => 'rina@test', 'password' => 'rahasia'])->assertStatus(403);
    }

    public function test_katalog_penuh_dan_perubahan_tanpa_harga_beli(): void
    {
        [$good, $pcs, $box] = $this->makeGood('Indomie', 3000, 3500, 40);
        [$old]              = $this->makeGood('Lama', 1000, 1500);
        DB::table('goods')->update(['updated_at' => now()->subHour()]);
        DB::table('good_units')->update(['updated_at' => now()->subHour()]);

        $res = $this->api()->getJson('/api/pos/v1/catalog')->assertOk()->assertJsonPath('full', true);
        $this->assertCount(2, $res->json('goods'));
        $indomie = collect($res->json('goods'))->firstWhere('id', $good);
        $this->assertSame([$pcs, $box], array_column($indomie['units'], 'good_unit_id'));
        $this->assertEquals(3500, $indomie['units'][0]['selling_price']);
        $this->assertStringNotContainsString('buy_price', $res->getContent());

        // Harga berubah & barang dihapus setelah tarikan pertama
        $since = now()->subMinutes(10)->toIso8601String();
        DB::table('good_units')->where('id', $pcs)->update(['selling_price' => 3700, 'updated_at' => now()]);
        DB::table('goods')->where('id', $old)->update(['deleted_at' => now()]);

        $delta = collect($this->api()->getJson('/api/pos/v1/catalog?since=' . urlencode($since))
                              ->assertOk()->assertJsonPath('full', false)->json('goods'))->keyBy('id');
        $this->assertCount(2, $delta);
        $this->assertEquals(3700, $delta[$good]['units'][0]['selling_price']);
        $this->assertFalse($delta[$old]['is_active']);
        $this->assertSame([], $delta[$old]['units']);
    }

    public function test_member_hanya_daftar_yang_ada(): void
    {
        DB::table('members')->insert(['id' => 57, 'name' => 'Bu Sri']);

        $names = array_column($this->api()->getJson('/api/pos/v1/members')->assertOk()->json('members'), 'name');
        $this->assertContains('Bu Sri', $names);
    }

    public function test_transaksi_tersimpan_seperti_kasir_desktop(): void
    {
        [$good, $pcs] = $this->makeGood('Teh', 1000, 1500);
        $this->load($pcs, 10, 1000);

        $sale = $this->sale([[$pcs, 3, 1500, 500]], ['payment' => 'cash', 'money_paid' => 5000]);

        $res = $this->send($sale)->assertOk()->assertJsonPath('results.0.status', 'saved');
        $id  = $res->json('results.0.transaction_id');

        $t = DB::table('transactions')->find($id);
        $this->assertSame($sale['client_uuid'], $t->client_uuid);
        $this->assertSame($this->device->id, (int) $t->pos_device_id);
        $this->assertSame('HP1-' . date('Ymd') . '-0001', $t->receipt_no);
        $this->assertSame('cashier', $t->role);
        $this->assertEquals(1, $t->member_id); // tanpa member = Non Member, sama dengan desktop
        $this->assertEquals(4000, $t->total_sum_price);
        $this->assertEquals(1000, $t->money_returned);

        $this->assertEquals(7, $this->stock($good));
        $this->assertEquals(4000, $this->balance('4101') * -1);
        $this->assertEquals(3000, $this->balance('5101'));     // HPP harga beli server
        $this->assertEquals(0, DB::table('journals')->where('type', 'piutang')->count());
    }

    public function test_kirim_ulang_tidak_dobel(): void
    {
        [$good, $pcs] = $this->makeGood('Gula', 1000, 1500);
        $this->load($pcs, 10, 1000);
        $sale = $this->sale([[$pcs, 2, 1500]]);

        $first = $this->send($sale)->assertJsonPath('results.0.status', 'saved')->json('results.0.transaction_id');
        $this->send($sale)->assertJsonPath('results.0.status', 'duplicate')->assertJsonPath('results.0.transaction_id', $first);

        $this->assertSame(1, DB::table('transactions')->where('client_uuid', $sale['client_uuid'])->count());
        $this->assertEquals(8, $this->stock($good));
        $this->assertEquals(3000, $this->balance('4101') * -1);
    }

    public function test_transaksi_offline_kemarin_masuk_tanggal_kemarin(): void
    {
        [, $pcs] = $this->makeGood('Kopi', 1000, 1500);
        $this->load($pcs, 10, 1000);
        $yesterday = now()->subDay()->setTime(19, 30);

        $id = $this->send($this->sale([[$pcs, 1, 1500]], ['sold_at' => $yesterday->toIso8601String(), 'payment' => 'transfer']))
                   ->assertJsonPath('results.0.status', 'saved')->json('results.0.transaction_id');

        $t = DB::table('transactions')->find($id);
        $this->assertSame($yesterday->format('Y-m-d H:i:s'), $t->created_at);
        $this->assertSame($yesterday->format('Y-m-d H:i:s'), $t->sold_at);
        $this->assertSame($yesterday->format('Y-m-d'), substr(DB::table('transaction_details')->where('transaction_id', $id)->value('created_at'), 0, 10));

        $journalDates = DB::table('journals')->whereIn('type', ['transaction', 'hpp'])->pluck('journal_date')->unique()->values()->all();
        $this->assertSame([$yesterday->format('Y-m-d')], $journalDates);
        $this->assertEquals(1500, $this->balance('1112')); // transfer -> Kas di Bank
    }

    public function test_bayar_kurang_ditolak_dan_dicatat_sekali(): void
    {
        [$good, $pcs] = $this->makeGood('Beras', 10000, 12000);
        $this->load($pcs, 10, 10000);
        $sale = $this->sale([[$pcs, 1, 12000]], ['money_paid' => 10000]);

        $this->send($sale)->assertJsonPath('results.0.status', 'rejected');
        $this->send($sale)->assertJsonPath('results.0.status', 'rejected');

        $this->assertSame(0, DB::table('transactions')->count());
        $this->assertSame(1, DB::table('pos_sync_rejects')->where('client_uuid', $sale['client_uuid'])->count());
        $this->assertStringContainsString('piutang', DB::table('pos_sync_rejects')->value('reason'));
        $this->assertEquals(10, $this->stock($good));
    }

    public function test_data_salah_ditolak(): void
    {
        [, $pcs] = $this->makeGood('Sabun', 1000, 1500);
        $this->load($pcs, 10, 1000);

        $cases = [
            'total'      => $this->sale([[$pcs, 1, 1500]], ['total' => 1000, 'money_paid' => 1500]),
            'lama'       => $this->sale([[$pcs, 1, 1500]], ['sold_at' => now()->subDays(8)->toIso8601String()]),
            'masa depan' => $this->sale([[$pcs, 1, 1500]], ['sold_at' => now()->addHour()->toIso8601String()]),
            'member'     => $this->sale([[$pcs, 1, 1500]], ['member_id' => 99999]),
            'barang'     => $this->sale([[999999, 1, 1500]]),
            'bayar'      => $this->sale([[$pcs, 1, 1500]], ['payment' => 'qris']),
            'kasir'      => $this->sale([[$pcs, 1, 1500]], ['cashier_id' => 99999]),
            'uuid'       => $this->sale([[$pcs, 1, 1500]], ['client_uuid' => 'bukan-uuid']),
        ];

        $results = $this->send(...array_values($cases))->assertOk()->json('results');

        foreach (array_keys($cases) as $i => $name) {
            $this->assertSame('rejected', $results[$i]['status'], $name);
        }
        $this->assertSame(0, DB::table('transactions')->count());
        $this->assertSame(count($cases), DB::table('pos_sync_rejects')->count());
    }

    public function test_kiriman_campuran_urutan_jawaban_tetap(): void
    {
        [$good, $pcs] = $this->makeGood('Roti', 1000, 2000);
        $this->load($pcs, 10, 1000);

        $later  = $this->sale([[$pcs, 1, 2000]], ['sold_at' => now()->subMinutes(1)->toIso8601String()]);
        $bad    = $this->sale([[$pcs, 1, 2000]], ['money_paid' => 0]);
        $earlier = $this->sale([[$pcs, 2, 2000]], ['sold_at' => now()->subMinutes(30)->toIso8601String()]);

        $results = $this->send($later, $bad, $earlier)->json('results');

        $this->assertSame([$later['client_uuid'], $bad['client_uuid'], $earlier['client_uuid']], array_column($results, 'client_uuid'));
        $this->assertSame(['saved', 'rejected', 'saved'], array_column($results, 'status'));
        // Yang lebih dulu dijual tersimpan lebih dulu
        $this->assertLessThan($results[0]['transaction_id'], $results[2]['transaction_id']);
        $this->assertEquals(7, $this->stock($good));
    }

    public function test_halaman_admin_kasir_hp_superadmin(): void
    {
        $this->actingAs($this->admin('admin'), 'admin')->get('/admin/kasir-hp')->assertForbidden();

        $super = $this->admin('superadmin');
        $this->actingAs($super, 'admin')->get('/admin/kasir-hp')->assertOk()->assertSee('HP Test');

        $this->actingAs($super, 'admin')->post('/admin/kasir-hp', ['name' => 'HP Kasir 2'])->assertRedirect('/admin/kasir-hp');
        $device = PosDevice::where('name', 'HP Kasir 2')->first();
        $this->assertSame('HP2', $device->code);
        $this->assertNotNull($device->pair_code);

        $this->actingAs($super, 'admin')->get('/admin/kasir-hp')->assertOk()->assertSee($device->pair_code);

        // Kasir PC (aplikasi Windows) mendapat kode sendiri: PC1, PC2, ...
        $this->actingAs($super, 'admin')->post('/admin/kasir-hp', ['name' => 'PC Kasir Depan', 'jenis' => 'pc'])->assertRedirect('/admin/kasir-hp');
        $this->assertSame('PC1', PosDevice::where('name', 'PC Kasir Depan')->value('code'));
        $this->actingAs($super, 'admin')->post('/admin/kasir-hp', ['name' => 'HP Kasir 3', 'jenis' => 'hp'])->assertRedirect('/admin/kasir-hp');
        $this->assertSame('HP3', PosDevice::where('name', 'HP Kasir 3')->value('code'));

        $this->actingAs($super, 'admin')->post('/admin/kasir-hp/' . $this->device->id . '/aktif')->assertRedirect();
        $this->assertFalse($this->device->fresh()->is_active);
    }
}
