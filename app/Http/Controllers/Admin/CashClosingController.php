<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Base\CashClosingControllerBase;
use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Services\TutupKasir;
use Illuminate\Http\Request;

class CashClosingController extends Controller
{
    use CashClosingControllerBase;

    public function index()
    {
        return $this->indexBase('admin');
    }

    public function store(Request $request)
    {
        return $this->storeBase($request, 'admin');
    }

    public function cancel($id)
    {
        abort_unless(canMenu('aksi.tutup_kasir.batal'), 403, 'Membatalkan tutup kasir ditutup untuk akun Anda.');

        $closing = CashClosing::findOrFail($id);
        TutupKasir::cancel($closing);

        return redirect(url('admin/cash-closing'))->with('tutup_kasir_ok',
            'Tutup kasir ' . displayDate($closing->closing_date) . ' dibatalkan dan jurnalnya dihapus. Kasir bisa ditutup ulang.');
    }
}
