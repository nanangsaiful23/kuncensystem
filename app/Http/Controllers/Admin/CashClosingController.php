<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Base\CashClosingControllerBase;
use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Services\TutupKasir;
use Illuminate\Http\Request;

/** Simpan & batal Tutup Kasir; formnya ada di Resume Transaksi Total. */
class CashClosingController extends Controller
{
    use CashClosingControllerBase;

    public function store(Request $request)
    {
        return $this->storeBase($request, 'admin');
    }

    public function cancel($id)
    {
        abort_unless(canMenu('aksi.tutup_kasir.batal'), 403, 'Membatalkan tutup kasir ditutup untuk akun Anda.');

        $closing = CashClosing::findOrFail($id);
        $date = $closing->closing_date->format('Y-m-d');
        TutupKasir::cancel($closing);

        return redirect(self::resumeTotalUrl('admin', $date))->with('tutup_kasir_ok',
            'Tutup kasir ' . displayDate($date) . ' dibatalkan dan jurnalnya dihapus. Tanggal itu bisa ditutup ulang.');
    }
}
