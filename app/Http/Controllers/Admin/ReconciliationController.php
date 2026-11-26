<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationCheck;
use App\Services\CekKecocokan;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Tombol di kotak Cek Kecocokan (dashboard admin), lihat App\Services\CekKecocokan.
 * Hak akses: kas.cek_kecocokan di Hak Akses Menu.
 */
class ReconciliationController extends Controller
{
    /** Tandai sudah dibaca / sudah ditangani: hilang dari dashboard. */
    public function mark(Request $request, $id)
    {
        $request->validate([
            'resolution' => 'required|in:' . ReconciliationCheck::DIBACA . ',' . ReconciliationCheck::DITANGANI,
            'note'       => 'nullable|string|max:1000',
        ]);
        $check = ReconciliationCheck::findOrFail($id);
        if ($check->resolved_at !== null) {
            throw ValidationException::withMessages(['cek' => 'Hasil cek ' . displayDate($check->check_date) . ' sudah ditandai oleh ' . $check->resolverName() . '.']);
        }

        $check->update([
            'resolved_at'      => now(),
            'resolution'       => $request->resolution,
            'resolved_by_role' => 'admin',
            'resolved_by_id'   => \Auth::user()->id,
            'resolve_note'     => trim((string) $request->note) !== '' ? trim($request->note) : null,
        ]);

        return redirect('admin#cek-kecocokan')->with('cek_ok', 'Hasil cek ' . displayDate($check->check_date) . ' ditandai sudah '
            . ($request->resolution === ReconciliationCheck::DITANGANI ? 'ditangani.' : 'dibaca.'));
    }

    /** Jalankan ulang cek tanggal itu (setelah data diperbaiki). */
    public function recheck($id)
    {
        $check = CekKecocokan::cekUlang(ReconciliationCheck::findOrFail($id), 'admin', \Auth::user()->id);

        return redirect('admin#cek-kecocokan')->with('cek_ok', 'Cek ulang ' . displayDate($check->check_date) . ': '
            . ($check->problem_count === 0 ? 'semua sudah cocok.' : $check->problem_count . ' masih tidak cocok.'));
    }

    /**
     * Samakan stok tercatat barang yang beda dengan riwayat loading/transaksi
     * (sama dengan `php artisan stock:sync` untuk barang itu saja), lalu cek ulang.
     */
    public function syncStock($id)
    {
        $check = ReconciliationCheck::findOrFail($id);
        $stok = collect($check->results ?? [])->firstWhere('key', 'stok');
        $goodIds = array_map('intval', $stok['good_ids'] ?? []);
        if (! $goodIds) {
            throw ValidationException::withMessages(['cek' => 'Tidak ada barang yang stoknya beda dengan riwayat.']);
        }

        foreach ($goodIds as $goodId) {
            StockService::sync($goodId);
        }
        $check = CekKecocokan::cekUlang($check, 'admin', \Auth::user()->id);

        return redirect('admin#cek-kecocokan')->with('cek_ok', 'Stok ' . count($goodIds) . ' barang disamakan dengan riwayat loading/transaksi. '
            . 'Cek ulang ' . displayDate($check->check_date) . ': ' . ($check->problem_count === 0 ? 'semua sudah cocok.' : $check->problem_count . ' masih tidak cocok.'));
    }
}
