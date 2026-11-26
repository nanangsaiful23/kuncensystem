<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa yang membuat stok barang jadi minus. Diisi App\Services\StockService::sync():
 * saat stok hasil hitungan turun di bawah nol dan barang itu belum punya
 * catatan terbuka, dibuat satu baris dengan aksi, transaksi/loading dan akun
 * penyebabnya. Saat stok kembali >= 0, ended_at diisi. Jadi catatan terbuka
 * (ended_at kosong) = penyebab minus yang sekarang.
 *
 * Kasir HP/PC offline: yang dicatat adalah urutan transaksi masuk ke server,
 * bukan urutan barang dijual. Dengan beberapa perangkat, akun yang tercatat
 * bisa bukan yang pertama kali menjual barang yang tidak ada stoknya.
 */
class StockMinusLog extends Model
{
    public const LABEL = [
        'transaksi'          => 'Transaksi',
        'kasir_hp'           => 'Transaksi Kasir HP/PC',
        'edit_transaksi'     => 'Edit transaksi',
        'batal_transaksi'    => 'Batal transaksi',
        'hapus_transaksi'    => 'Hapus transaksi',
        'retur'              => 'Retur ke distributor',
        'stock_opname'       => 'Stock opname',
        'loading'            => 'Loading',
        'edit_loading'       => 'Edit loading',
        'hapus_loading'      => 'Hapus loading',
        'hitung_ulang'       => 'Hitung ulang stok',
        'lainnya'            => 'Perubahan data barang',
        'sebelum_pencatatan' => 'Sudah minus sebelum pencatatan dimulai',
    ];

    protected $fillable = [
        'good_id', 'stock', 'action', 'transaction_id', 'good_loading_id',
        'role', 'role_id', 'actor_name', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'good_id'         => 'integer',
        'stock'           => 'float',
        'transaction_id'  => 'integer',
        'good_loading_id' => 'integer',
        'role_id'         => 'integer',
        'started_at'      => 'datetime',
        'ended_at'        => 'datetime',
    ];

    private static ?bool $tabelAda = null;

    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('stock_minus_logs');
    }

    public static function lupakanTabel(): void
    {
        self::$tabelAda = null;
        ActionLog::lupakanTabel();
    }

    /**
     * Buat tabel kalau belum ada (juga action_logs). Hanya dari request GET:
     * CREATE TABLE di MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikanTabel(): bool
    {
        if (self::tabelAda() && ActionLog::tabelAda()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_26_000002_create_stock_minus_logs_table.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        self::lupakanTabel();

        return self::tabelAda();
    }

    /**
     * Dipanggil setiap stok satu barang dihitung ulang.
     *
     * @param  array  $sebab  ['aksi' => ..., 'transaction_id' => ?, 'good_loading_id' => ?, 'role' => ?, 'role_id' => ?]
     */
    public static function perbarui(int $goodId, float $stok, array $sebab): void
    {
        $terbuka = self::where('good_id', $goodId)->whereNull('ended_at');

        if ($stok >= 0) {
            $terbuka->update(['ended_at' => now()]);

            return;
        }
        if ($terbuka->exists()) {
            return; // sudah minus sebelumnya: penyebab pertamanya tetap
        }

        [$role, $roleId, $nama] = ActionLog::pelaku($sebab['role'] ?? null, $sebab['role_id'] ?? null);
        self::create([
            'good_id'         => $goodId,
            'stock'           => $stok,
            'action'          => $sebab['aksi'] ?? 'lainnya',
            'transaction_id'  => $sebab['transaction_id'] ?? null,
            'good_loading_id' => $sebab['good_loading_id'] ?? null,
            'role'            => $role,
            'role_id'         => $roleId,
            'actor_name'      => $nama,
            'started_at'      => now(),
        ]);
    }

    /**
     * Penyebab minus yang masih berlaku, per barang.
     *
     * @return Collection<int, self>  key = good_id
     */
    public static function terbukaUntuk(array $goodIds): Collection
    {
        $goodIds = array_values(array_unique(array_map('intval', $goodIds)));
        if (! $goodIds || ! self::tabelAda()) {
            return collect();
        }

        // Per 500 id: MariaDB mengubah IN (...) berisi 1000 nilai atau lebih menjadi subquery.
        $hasil = collect();
        foreach (array_chunk($goodIds, 500) as $ids) {
            $hasil = $hasil->merge(self::whereIn('good_id', $ids)->whereNull('ended_at')->orderBy('id')->get()->all());
        }

        return $hasil->unique('good_id')->keyBy('good_id');
    }

    /** Mis. "Transaksi oleh Budi (nota #123), 26/11/2026 10:15". */
    public function teks(): string
    {
        if ($this->action === 'sebelum_pencatatan') {
            return 'sudah minus sebelum pencatatan dimulai';
        }

        $ref = $this->transaction_id ? 'nota #' . $this->transaction_id
            : ($this->good_loading_id ? 'loading #' . $this->good_loading_id : null);

        return (self::LABEL[$this->action] ?? $this->action) . ' oleh ' . ($this->actor_name ?: 'sistem')
            . ($ref ? ' (' . $ref . ')' : '')
            . ($this->started_at ? ', ' . $this->started_at->format('d/m/Y H:i') : '');
    }

    /** Halaman transaksi/loading penyebabnya (loading yang dihapus tidak punya halaman). */
    public function url(): ?string
    {
        if ($this->transaction_id) {
            return 'admin/transaction/' . $this->transaction_id . '/detail';
        }
        if ($this->good_loading_id && $this->action !== 'hapus_loading') {
            return 'admin/good-loading/' . $this->good_loading_id . '/detail';
        }

        return null;
    }
}
