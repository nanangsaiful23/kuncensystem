<?php

namespace App\Http\Controllers\Base;

use App\Models\CashClosing;
use App\Services\TutupKasir;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Tutup Kasir untuk admin dan kasir (lihat App\Services\TutupKasir). */
trait CashClosingControllerBase
{
    public function indexBase(string $role)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tutup Kasir';
        $default['page'] = 'cash-closing';
        $default['section'] = 'index';

        // Dibuat di GET (bukan di POST): CREATE TABLE di MySQL menutup
        // transaksi yang dibuka AtomicRequest untuk POST.
        $tabelAda = CashClosing::pastikanTabel();
        $data = compact('default', 'tabelAda');

        if ($tabelAda) {
            $data['today']      = TutupKasir::today();
            $data['saldo']      = TutupKasir::saldoKasDiTangan();
            $data['devices']    = TutupKasir::devices();
            $data['closings']   = CashClosing::orderByDesc('closing_date')->orderByDesc('id')->paginate(30);
            $data['bolehBatal'] = $role === 'admin' && canMenu('aksi.tutup_kasir.batal');
        }

        return view($role . '.layout.page', $data);
    }

    public function storeBase(Request $request, string $role)
    {
        $request->validate([
            'physical_cash' => 'required|string|max:30',
            'draw_amount'   => 'nullable|string|max:30',
            'note'          => 'nullable|string|max:1000',
        ], [
            'physical_cash.required' => 'Isi jumlah uang fisik di laci.',
        ]);

        if (! CashClosing::tabelAda()) {
            throw ValidationException::withMessages(['physical_cash' => 'Tabel tutup kasir belum ada. Buka ulang halaman Tutup Kasir.']);
        }

        $fisik = parseNumber($request->physical_cash);
        if ($fisik === null) {
            throw ValidationException::withMessages(['physical_cash' => 'Jumlah uang fisik tidak terbaca.']);
        }
        $diserahkan = parseNumber($request->draw_amount) ?? 0.0;
        $saldoDilihat = is_numeric($request->system_balance_seen) ? (float) $request->system_balance_seen : null;

        $closing = TutupKasir::close($fisik, $diserahkan, $request->note, $role, \Auth::user()->id, $saldoDilihat);

        if ($closing->difference == 0) {
            $selisih = 'uang pas';
        } elseif ($closing->difference < 0) {
            $selisih = 'uang kurang ' . showRupiah(abs($closing->difference)) . ' (dicatat ke Biaya Uang Hilang)';
        } else {
            $selisih = 'uang lebih ' . showRupiah($closing->difference) . ' (dicatat ke Pendapatan Lain-lain)';
        }

        return redirect(url($role . '/cash-closing'))->with('tutup_kasir_ok',
            'Tutup kasir tersimpan: ' . $selisih . '. Diserahkan ke owner ' . showRupiah($closing->draw_amount)
            . ', sisa di laci ' . showRupiah($closing->remaining()) . '.');
    }
}
