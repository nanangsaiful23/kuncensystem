<?php

namespace App\Models;

use App\Admin;
use App\Cashier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa yang mengedit/menghapus loading dan mengedit/membatalkan transaksi.
 * Pembuat dokumen sudah tersimpan di role/role_id dokumen itu sendiri; tabel
 * ini mencatat perubahan sesudahnya, dengan ringkasan apa yang berubah.
 * Tabelnya dibuat migrasi 2026_11_26_000002 (bersama stock_minus_logs).
 */
class ActionLog extends Model
{
    public const LABEL = [
        'edit_transaksi'  => 'Edit transaksi',
        'batal_transaksi' => 'Batal transaksi',
        'hapus_transaksi' => 'Hapus transaksi',
        'edit_loading'    => 'Edit loading',
        'hapus_loading'   => 'Hapus loading',
    ];

    protected $fillable = ['action', 'transaction_id', 'good_loading_id', 'role', 'role_id', 'actor_name', 'note'];

    protected $casts = [
        'transaction_id'  => 'integer',
        'good_loading_id' => 'integer',
        'role_id'         => 'integer',
    ];

    private static ?bool $tabelAda = null;

    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('action_logs');
    }

    public static function lupakanTabel(): void
    {
        self::$tabelAda = null;
    }

    /**
     * Akun yang melakukan aksi: role/role_id yang diberikan, kalau tidak ada
     * akun yang sedang login (admin/kasir). Hasil [role, role_id, nama];
     * semuanya null kalau tidak ada akunnya (mis. perintah artisan).
     */
    public static function pelaku(?string $role = null, $roleId = null): array
    {
        if ($role && $roleId) {
            $akun = $role === 'admin' ? Admin::find($roleId) : Cashier::find($roleId);

            return [$role, (int) $roleId, $akun->name ?? null];
        }

        $user = Auth::user();
        if ($user instanceof Admin || $user instanceof Cashier) {
            return [$user instanceof Admin ? 'admin' : 'cashier', (int) $user->id, $user->name];
        }

        return [null, null, null];
    }

    /** Simpan satu catatan. Tidak pernah menggagalkan aksinya sendiri. */
    public static function catat(string $action, array $ref, ?string $note, ?string $role = null, $roleId = null): void
    {
        if (! self::tabelAda()) {
            return;
        }

        try {
            [$role, $roleId, $nama] = self::pelaku($role, $roleId);
            self::create($ref + ['action' => $action, 'role' => $role, 'role_id' => $roleId, 'actor_name' => $nama, 'note' => $note]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Jumlah tiap baris sebelum diubah, untuk catatEdit(): [id baris => jumlah].
     *
     * @param  class-string<Model>  $detailModel  TransactionDetail atau GoodLoadingDetail
     */
    public static function jumlahBaris(string $detailModel, string $kolomInduk, int $indukId): array
    {
        return $detailModel::where($kolomInduk, $indukId)->pluck('quantity', 'id')->map(fn ($q) => (float) $q)->all();
    }

    /**
     * Catat edit transaksi/loading: baris yang jumlahnya berubah dan totalnya.
     *
     * @param  array  $jumlahSebelum  hasil jumlahBaris() sebelum diedit
     */
    public static function catatEdit(string $action, Model $dokumen, string $detailModel, array $jumlahSebelum, $totalSebelum, $totalSesudah, ?string $role, $roleId): void
    {
        if (! self::tabelAda()) {
            return;
        }

        $kolomInduk = $action === 'edit_loading' ? 'good_loading_id' : 'transaction_id';
        $berubah = [];
        foreach ($detailModel::where($kolomInduk, $dokumen->id)->get() as $baris) {
            $lama = $jumlahSebelum[$baris->id] ?? null;
            if ($lama !== null && round($lama, 3) != round((float) $baris->quantity, 3)) {
                $berubah[] = self::namaBarang($baris->good_unit_id) . ': ' . self::angka($lama) . ' → ' . self::angka($baris->quantity);
            }
        }
        if (round((float) $totalSebelum, 2) != round((float) $totalSesudah, 2)) {
            $berubah[] = 'Total ' . showRupiah($totalSebelum) . ' → ' . showRupiah($totalSesudah);
        }

        self::catat($action, [$kolomInduk => $dokumen->id], $berubah ? implode('; ', $berubah) : 'Jumlah barang dan total tidak berubah.', $role, $roleId);
    }

    /** Nama barang + satuan untuk catatan, juga kalau barangnya sudah dihapus. */
    public static function namaBarang($goodUnitId): string
    {
        $unit = GoodUnit::withTrashed()->find($goodUnitId);
        $good = $unit ? Good::withTrashed()->find($unit->good_id) : null;

        return $good ? trim($good->getFullName() . ' ' . ($unit->unit->name ?? '')) : 'Barang #' . $goodUnitId;
    }

    public static function angka($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    }

    /** Catatan untuk satu transaksi/loading, urut waktu. */
    public static function untuk(string $kolom, $id)
    {
        return self::tabelAda() && $id ? self::where($kolom, $id)->orderBy('id')->get() : collect();
    }

    public function teks(): string
    {
        return (self::LABEL[$this->action] ?? $this->action) . ' oleh ' . ($this->actor_name ?: 'sistem')
            . ($this->created_at ? ', ' . $this->created_at->format('d/m/Y H:i') : '');
    }
}
