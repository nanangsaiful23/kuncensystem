<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodLoadingControllerBase;

use App\Models\GoodLoading;
use App\Models\Account;

class GoodLoadingController extends Controller
{
    use GoodLoadingControllerBase;

    /**
     * Sesuaikan di sini kalau nama tabel Admin/Cashier di project kamu
     * berbeda dari default Laravel (nama_kelas + 's').
     */
    protected $adminTable   = 'admins';
    protected $cashierTable = 'cashiers';

    /**
     * Kode akun Kas (dipakai juga di MainController::index() untuk
     * $cash_account = Account::where('code','1111')). Dipusatkan di sini
     * supaya gampang disesuaikan kalau kode akun kas kamu berbeda.
     */
    protected $cashAccountCode = 'cash';

    /**
     * Nilai kolom good_loadings.checker yang menandakan loading dibuat
     * otomatis oleh sistem (bukan input manual staf).
     */
    protected $systemCheckerValues = ['Created by system', 'Load by sistem'];

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Daftar Loading — SEMUA filter (tanggal, distributor, jenis, payment,
     * keyword, exclude cash, exclude sistem) diproses di sini, di backend,
     * SEBELUM pagination & agregasi dijalankan. Jadi apa yang tampil di
     * tabel maupun total di KPI = persis hasil query yang difilter.
     *
     * GET /admin/good-loading/{start_date}/{end_date}/{distributor_id}/{pagination}
     *      ?type=internal|external&payment=<kode_akun>&keyword=<kata kunci>
     *      &exclude_cash=1&exclude_system=1
     */
    public function index(Request $request, $start_date, $end_date, $distributor_id, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'all';

        $type          = trim((string) $request->get('type', ''));       // '', 'internal', 'external'
        $payment       = trim((string) $request->get('payment', ''));    // kode akun, mis. '1111'
        $keyword       = trim((string) $request->get('keyword', ''));
        $excludeCash   = $request->boolean('exclude_cash');
        $excludeSystem = $request->boolean('exclude_system');

        // ── Query utama, semua filter digabung di sini ──────────────────────
        $query = GoodLoading::query()
            ->whereNull('good_loadings.deleted_at')
            ->whereDate('good_loadings.loading_date', '>=', $start_date)
            ->whereDate('good_loadings.loading_date', '<=', $end_date)
            ->leftJoin('distributors', 'distributors.id', '=', 'good_loadings.distributor_id')
            ->leftJoin('accounts', 'accounts.code', '=', 'good_loadings.payment')
            ->select('good_loadings.*');

        if ($distributor_id && $distributor_id !== 'all') {
            $query->where('good_loadings.distributor_id', $distributor_id);
        }

        if ($type === 'internal') {
            $query->where('good_loadings.type', 'internal');
        } elseif ($type === 'external') {
            $query->where('good_loadings.type', '!=', 'internal');
        }

        if ($payment !== '') {
            $query->where('good_loadings.payment', $payment);
        }

        if ($keyword !== '') {
            $adminTable   = $this->adminTable;
            $cashierTable = $this->cashierTable;

            $query->where(function ($q) use ($keyword, $adminTable, $cashierTable) {
                $q->where('good_loadings.id', 'like', "%{$keyword}%")
                  ->orWhere('good_loadings.note', 'like', "%{$keyword}%")
                  ->orWhere('distributors.name', 'like', "%{$keyword}%")
                  ->orWhere('accounts.name', 'like', "%{$keyword}%")
                  ->orWhereExists(function ($sub) use ($keyword, $adminTable) {
                      $sub->select(DB::raw(1))
                          ->from($adminTable)
                          ->whereColumn("{$adminTable}.id", 'good_loadings.role_id')
                          ->where('good_loadings.role', 'admin')
                          ->where("{$adminTable}.name", 'like', "%{$keyword}%");
                  })
                  ->orWhereExists(function ($sub) use ($keyword, $cashierTable) {
                      $sub->select(DB::raw(1))
                          ->from($cashierTable)
                          ->whereColumn("{$cashierTable}.id", 'good_loadings.role_id')
                          ->where('good_loadings.role', 'cashier')
                          ->where("{$cashierTable}.name", 'like', "%{$keyword}%");
                  });
            });
        }

        // ── Exclude Payment Cash ────────────────────────────────────────────
        if ($excludeCash) {
            $query->where('good_loadings.payment', '!=', $this->cashAccountCode);
        }

        // ── Exclude Loading "By Sistem" (dibuat otomatis, bukan input staf) ─
        if ($excludeSystem) {
            $query->where('good_loadings.payment', '!=', "by system");
        }

        // ── Agregat dihitung dari SELURUH hasil filter (bukan hanya
        //    halaman yang sedang tampil), supaya KPI selalu akurat. ────────
        $totalLoadingSum = (clone $query)->sum('good_loadings.total_item_price');
        $totalCashSum    = (clone $query)
            ->where('good_loadings.payment', $this->cashAccountCode)
            ->sum('good_loadings.total_item_price');

        $query->orderBy('good_loadings.id', 'desc');

        if ($pagination == 'all') {
            $good_loadings = $query->get();
            $totalFiltered = $good_loadings->count();
            $shownCount    = $good_loadings->count();
        } else {
            $good_loadings = $query->paginate($pagination)->appends($request->query());
            $totalFiltered = $good_loadings->total();
            $shownCount    = $good_loadings->count();
        }

        // ── Opsi dropdown Payment: hanya kode yang benar-benar dipakai
        //    dalam rentang tanggal + distributor terpilih, supaya pilihan
        //    selalu relevan (tidak tergantung filter type/payment/keyword
        //    itu sendiri, biar dropdown tidak "menghilangkan diri sendiri").
        $paymentCodes = GoodLoading::whereNull('deleted_at')
            ->whereDate('loading_date', '>=', $start_date)
            ->whereDate('loading_date', '<=', $end_date)
            ->when($distributor_id && $distributor_id !== 'all', function ($q) use ($distributor_id) {
                $q->where('distributor_id', $distributor_id);
            })
            ->whereNotNull('payment')
            ->distinct()
            ->pluck('payment');

        $paymentOptions = Account::whereIn('code', $paymentCodes)
            ->orderBy('name')
            ->get(['code', 'name']);

        return view('admin.layout.page', compact(
            'default', 'good_loadings',
            'start_date', 'end_date', 'distributor_id', 'pagination',
            'type', 'payment', 'keyword', 'excludeCash', 'excludeSystem',
            'paymentOptions', 'totalFiltered', 'shownCount',
            'totalLoadingSum', 'totalCashSum'
        ));
    }

    public function create($type)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        if($type == 'internal')
            $default['page_name'] = 'Tambah Loading Internal';
        elseif($type == 'transaction-internal')
            $default['page_name'] = 'Tambah Loading & Transaksi Internal';
        else
            $default['page_name'] = 'Tambah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default', 'type'));
    }
public function createnew($type)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        if($type == 'internal')
            $default['page_name'] = 'Tambah Loading Internal';
        elseif($type == 'transaction-internal')
            $default['page_name'] = 'Tambah Loading & Transaksi Internal';
        else
            $default['page_name'] = 'Tambah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'createnew';

        return view('admin.layout.page', compact('default', 'type'));
    }

    public function store($type, Request $request)
    {
        $good_loading = $this->storeGoodLoadingBase('admin', \Auth::user()->id, $type, $request);

        session(['alert' => 'add', 'data' => 'loading barang']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function detail($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'detail';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('admin.layout.page', compact('default', 'good_loading'));
    }

    public function excel()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Import Excel Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'excel';

        return view('admin.layout.page', compact('default'));
    }

    public function storeExcel(Request $request)
    {
        $good_loading = $this->storeExcelGoodLoadingBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'loading barang']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function print($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Print Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'print';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('layout.good-loading.print', compact('default', 'good_loading'));
    }

    public function edit($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'edit';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('admin.layout.page', compact('default', 'good_loading'));
    }

    public function update($good_loading_id, Request $request)
    {
        $good_loading = $this->updateGoodLoadingBase('admin', \Auth::user()->id, $good_loading_id, $request);

        session(['alert' => 'edit', 'data' => 'Data loading']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function delete($good_loading_id)
    {
        $this->deleteGoodLoadingBase($good_loading_id);

        session(['alert' => 'delete', 'data' => 'Loading barang']);

        return redirect('/admin/good-loading/' . date('Y-m-d') . '/' . date('Y-m-d') . '/all/20');
    }
}