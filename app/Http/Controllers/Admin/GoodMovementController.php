<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\GoodMovementRepository;
use App\Models\Season;
use App\Services\SkemaCekObral;
use Illuminate\Validation\ValidationException;

class GoodMovementController extends Controller
{
    protected $repo;

    public function __construct(GoodMovementRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Halaman utama analisis pergerakan barang.
     * GET /admin/reports/movement
     */
    public function index(Request $request)
    {
        // Kolom obral/musim, harga obral & daftar cek stok dibuat otomatis (produksi tidak punya terminal).
        $adaKolomBaru = Season::pastikanTabel();
        SkemaCekObral::pastikan();

        // Barang obral yang stoknya sudah habis → discontinued.
        $obralSelesai = $this->repo->finishSoldOutClearance();

        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $kategori  = $request->get('kategori',   null);
        $status    = $request->get('status',     'all');
        $sortBy    = $request->get('sort_by',    'total_omzet');
        $sortDir   = $request->get('sort_dir',   'desc') === 'asc' ? 'asc' : 'desc';

        $allowedStatus = array_merge(['all', 'discontinued', 'kandidat_musim'], GoodMovementRepository::STATUSES);
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'all';
        }

        $allowedSort = [
            'total_omzet', 'total_transaksi', 'total_qty',
            'days_of_stock', 'days_since_trx', 'stok_sekarang',
            'nilai_stok', 'total_laba', 'urgency',
        ];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'total_omzet';
        }

        $data              = $this->repo->getMovementData($startDate, $endDate, $kategori, $status, $sortBy, $sortDir);
        $categories        = $this->repo->getCategories();
        $discontinuedCount = $this->repo->countDiscontinued();
        $negativeStockCount = $this->repo->countNegativeStock();
        $negativeStockGoods = $negativeStockCount > 0 ? $this->repo->getNegativeStockGoods(20) : collect();
        $seasons           = $adaKolomBaru ? Season::orderBy('name')->get(['id', 'name']) : collect();

        return view('admin.good-movement', [
            'goods'              => $data['goods'],
            'summary'            => $data['summary'],
            'days'               => $data['days'],
            'categories'         => $categories,
            'discontinuedCount'  => $discontinuedCount,
            'negativeStockCount' => $negativeStockCount,
            'negativeStockGoods' => $negativeStockGoods,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'kategori'           => $kategori,
            'status'             => $status,
            'sortBy'             => $sortBy,
            'sortDir'            => $sortDir,
            'adaKolomBaru'       => $adaKolomBaru,
            'seasons'            => $seasons,
            'obralSelesai'       => $obralSelesai,
            // Tugas cek stok (Sidak berisi daftar) & harga obral
            'bolehSidak'         => canMenu('sidak') && SkemaCekObral::sidakAda(),
            'hargaObralAda'      => $adaKolomBaru && SkemaCekObral::hargaObralAda(),
            'lihatModal'         => canMenu('info.harga_beli'),
        ]);
    }

    /**
     * Tandai barang sebagai discontinued.
     * POST /admin/reports/movement/{id}/discontinue
     */
    public function discontinue(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'Alasan discontinue wajib diisi.',
            'reason.min'      => 'Alasan minimal 5 karakter.',
        ]);

        $userId = auth()->id() ?? 1;

        $ok = $this->repo->markDiscontinued($id, $request->input('reason'), $userId);

        if (!$ok) {
            return redirect()->back()
                ->with('error', 'Barang tidak ditemukan atau sudah dihapus.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', 'Barang berhasil di-discontinue dan tidak akan muncul dalam laporan.');
    }

    /**
     * Tandai BANYAK barang sekaligus sebagai discontinued (checklist).
     * POST /admin/reports/movement/bulk-discontinue
     */
    public function bulkDiscontinue(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'reason' => 'required|string|min:5|max:500',
        ], [
            'ids.required'    => 'Pilih minimal satu barang terlebih dahulu.',
            'reason.required' => 'Alasan discontinue wajib diisi.',
            'reason.min'      => 'Alasan minimal 5 karakter.',
        ]);

        $userId = auth()->id() ?? 1;

        $count = $this->repo->markDiscontinuedBulk(
            $request->input('ids'),
            $request->input('reason'),
            $userId
        );

        if ($count === 0) {
            return redirect()->back()
                ->with('error', 'Tidak ada barang yang berhasil di-discontinue. Coba muat ulang halaman.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', $count . ' barang berhasil di-discontinue sekaligus.');
    }

    /**
     * Aktifkan kembali barang yang sudah discontinued.
     * POST /admin/reports/movement/{id}/restore
     */
    public function restore(int $id)
    {
        $ok = $this->repo->restoreDiscontinued($id);

        if (!$ok) {
            return redirect()->back()
                ->with('error', 'Barang tidak ditemukan atau tidak dalam status discontinued.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', 'Barang berhasil diaktifkan kembali.');
    }

    /**
     * Tandai satu atau banyak barang sebagai obral (checklist), sekaligus
     * pasang harga obralnya.
     * POST /admin/reports/movement/obral   (ids[], reason, prices[good_unit_id] = harga obral)
     *
     * Harga obral paling rendah sama dengan harga beli (modal). Satuan yang
     * harganya dikosongkan / tidak diubah tetap memakai harga sekarang.
     */
    public function clearance(Request $request)
    {
        $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer',
            'reason'   => 'required|string|min:5|max:500',
            'prices'   => 'nullable|array',
            'prices.*' => 'nullable|numeric|min:0|max:999999999',
        ], [
            'ids.required'    => 'Pilih minimal satu barang terlebih dahulu.',
            'reason.required' => 'Keterangan obral wajib diisi.',
            'reason.min'      => 'Keterangan minimal 5 karakter.',
            'prices.*.numeric' => 'Harga obral harus berupa angka.',
        ]);

        if (!Season::tabelAda()) {
            return redirect()->back()->with('error', 'Kolom obral belum dibuat. Buka ulang menu Perputaran Barang lalu coba lagi.');
        }

        $ids    = $request->input('ids');
        $prices = SkemaCekObral::hargaObralAda() ? (array) $request->input('prices', []) : [];

        $errors = $this->repo->clearancePriceErrors($ids, $prices, canMenu('info.harga_beli'));
        if ($errors) {
            throw ValidationException::withMessages(['harga' => $errors]);
        }

        $userId = auth()->id() ?? 1;
        $count  = $this->repo->markClearance($ids, $request->input('reason'), $userId);

        if ($count === 0) {
            return redirect()->back()
                ->with('error', 'Tidak ada barang yang ditandai obral. Hanya barang aktif yang stoknya masih ada yang bisa diobral.');
        }

        $diubah = $this->repo->applyClearancePrices($ids, $prices, $request->input('reason'), $userId);

        return redirect()
            ->route('admin.reports.movement', ['status' => 'obral', 'sort_by' => 'nilai_stok'])
            ->with('success', $count . ' barang ditandai OBRAL' . ($diubah ? ' dan ' . $diubah . ' harga satuan diganti harga obral' : '')
                . ': tidak akan disarankan order lagi, dan otomatis di-discontinue saat stoknya habis (harga kembali normal). '
                . 'Centang barangnya di bawah untuk cetak label obral & copy teks WA.');
    }

    /**
     * Data barang (satuan, harga, harga normal) untuk jendela obral dan teks WA.
     * GET /admin/reports/movement/obral/barang?ids[]=
     * Harga beli hanya dikirim ke akun yang boleh melihat harga beli.
     */
    public function clearanceGoods(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1|max:500', 'ids.*' => 'integer']);

        $lihatModal = canMenu('info.harga_beli');
        $goods = array_map(function ($g) use ($lihatModal) {
            if (! $lihatModal) {
                $g['units'] = array_map(fn ($u) => array_merge($u, ['buy_price' => null]), $g['units']);
            }

            return $g;
        }, $this->repo->clearanceGoods($request->input('ids')));

        return response()->json(['goods' => $goods, 'lihat_modal' => $lihatModal]);
    }

    /**
     * Cetak label rak obral (harga normal dicoret → harga obral).
     * POST /admin/reports/movement/obral/label   (ids[], layout, quantities[good_id])
     */
    public function clearanceLabel(Request $request)
    {
        $request->validate([
            'ids'          => 'required|array|min:1|max:500',
            'ids.*'        => 'integer',
            'quantities'   => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:0|max:50',
        ], ['ids.required' => 'Pilih minimal satu barang obral.']);

        $qty   = (array) $request->input('quantities', []);
        $goods = [];
        foreach ($this->repo->clearanceGoods($request->input('ids')) as $g) {
            $prices = array_map(fn ($u) => [
                'unit'   => $u['unit'],
                'price'  => $u['selling_price'],
                'normal' => $u['normal_price'] !== null && $u['normal_price'] > $u['selling_price'] ? $u['normal_price'] : null,
            ], $g['units']);
            // satuan yang benar-benar diobral ditaruh dulu; paling banyak 3 harga per label
            usort($prices, fn ($a, $b) => ($b['normal'] !== null) <=> ($a['normal'] !== null));
            $prices = array_slice($prices, 0, 3);

            for ($i = 0, $n = (int) ($qty[$g['good_id']] ?? 1); $i < $n; $i++) {
                $goods[] = ['name' => $g['nama'], 'price' => $prices];
            }
        }

        return view('layout.good.print-display-obral', ['role' => 'admin', 'goods' => $goods]);
    }

    /**
     * Batalkan obral: harga jual kembali normal.
     * POST /admin/reports/movement/{id}/batal-obral
     */
    public function cancelClearance(int $id)
    {
        if (!$this->repo->cancelClearance($id, auth()->id())) {
            return redirect()->back()->with('error', 'Barang tidak ditemukan atau tidak sedang diobral.');
        }

        return redirect()->back()->with('success', 'Obral dibatalkan: harga jual kembali normal, barang dinilai seperti biasa lagi.');
    }

    /**
     * Atur musim barang atau jenisnya.
     * POST /admin/reports/movement/{id}/musim   (scope=jenis|barang, season_id='' ikut jenis | 0 bukan musiman | id)
     */
    public function setSeason(Request $request, int $id)
    {
        $request->validate([
            'scope'     => 'required|in:jenis,barang',
            'season_id' => 'nullable|integer|min:0',
        ]);

        if (!Season::tabelAda()) {
            return redirect()->back()->with('error', 'Tabel musim belum dibuat. Buka ulang menu Perputaran Barang lalu coba lagi.');
        }

        $raw      = $request->input('season_id');
        $seasonId = ($raw === null || $raw === '') ? null : (int) $raw;
        if ($seasonId && !Season::whereKey($seasonId)->exists()) {
            return redirect()->back()->with('error', 'Musim tidak ditemukan.');
        }
        if ($request->input('scope') === 'jenis' && $seasonId === null) {
            $seasonId = 0; // untuk jenis, "ikut jenis" tidak berlaku: artinya jenisnya bukan musiman
        }

        if (!$this->repo->setSeason($id, $request->input('scope'), $seasonId)) {
            return redirect()->back()->with('error', 'Barang tidak ditemukan, atau barang ini belum punya jenis.');
        }

        return redirect()->back()->with('success', 'Musim barang disimpan.');
    }
}
