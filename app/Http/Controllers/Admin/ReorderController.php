<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Models\ReorderRepository;

class ReorderController extends Controller
{
    protected $repo;

    public function __construct(ReorderRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Halaman rekomendasi order ke distributor.
     * GET /admin/reports/reorder
     */
    public function index(Request $request)
    {
        $startDate     = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate       = $request->get('end_date',   Carbon::now()->toDateString());
        $distributorId = $request->get('distributor', null);
        $kategori      = $request->get('kategori',    null);
        $onlyNeeded    = $request->get('only_needed', '1') === '1';

        $data = $this->repo->getReorderRecommendations(
            $startDate,
            $endDate,
            $distributorId ? (int) $distributorId : null,
            $kategori ? (int) $kategori : null,
            $onlyNeeded
        );

        $distributors = $this->repo->getDistributors();
        $categories   = $this->repo->getCategories();

        return view('admin.reorder', [
            'groups'        => $data['groups'],
            'summary'       => $data['summary'],
            'distributors'  => $distributors,
            'categories'    => $categories,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'distributorId' => $distributorId,
            'kategori'      => $kategori,
            'onlyNeeded'    => $onlyNeeded,
        ]);
    }

    /**
     * Export daftar barang yang perlu di-restock ke file CSV.
     * Bisa untuk 1 distributor saja, atau semua distributor sekaligus
     * (jika parameter `distributor` tidak diisi / kosong).
     *
     * GET /admin/reports/reorder/export?distributor=&start_date=&end_date=&kategori=&only_needed=
     */
    public function export(Request $request)
    {
        $startDate        = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate          = $request->get('end_date',   Carbon::now()->toDateString());
        $distributorId    = $request->get('distributor', null);
        $distributorNama  = $request->get('distributor_nama', null); // fallback utk grup "Belum Ditentukan"
        $kategori         = $request->get('kategori',    null);
        $onlyNeeded       = $request->get('only_needed', '1') === '1';
        $goodIds          = $request->get('good_ids', null); // array good_id yang dicentang di halaman

        $data = $this->repo->getReorderRecommendations(
            $startDate,
            $endDate,
            $distributorId ? (int) $distributorId : null,
            $kategori ? (int) $kategori : null,
            $onlyNeeded
        );

        $groups = $data['groups'];

        // Jika export per-grup tapi grup itu tidak punya distributor_id
        // (mis. "Belum Ditentukan"), filter berdasarkan nama grup sebagai
        // fallback karena query tidak bisa filter by null secara eksplisit.
        if (!$distributorId && $distributorNama) {
            $groups = $groups->filter(function ($g) use ($distributorNama) {
                return $g->distributor_nama === $distributorNama;
            })->values();
        }

        // Filter ke barang yang dicentang saja (dari checkbox di halaman).
        // Jika tidak ada good_ids dikirim (mis. akses langsung via URL tanpa
        // lewat halaman), tampilkan semua seperti biasa — supaya export tetap
        // bisa dipakai lewat link langsung.
        if (is_array($goodIds) && count($goodIds) > 0) {
            $goodIdsInt = array_map('intval', $goodIds);
            $groups = $groups
                ->map(function ($g) use ($goodIdsInt) {
                    $g->items = $g->items
                        ->filter(function ($it) use ($goodIdsInt) {
                            return in_array((int) $it->good_id, $goodIdsInt, true);
                        })
                        ->values();
                    $g->jumlah_item  = $g->items->count();
                    $g->total_biaya  = $g->items->sum('estimasi_biaya');
                    $g->urgent_count = $g->items->where('urgensi', 1)->count();
                    return $g;
                })
                ->filter(function ($g) {
                    return $g->items->count() > 0; // buang grup yang jadi kosong setelah difilter
                })
                ->values();
        }

        if ($groups->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ada barang untuk di-export pada filter saat ini.');
        }

        // Nama file: kalau filter ke 1 distributor, pakai nama distributor itu;
        // kalau semua, pakai label umum.
        $namaFileBagian = $distributorId && $groups->count() === 1
            ? Str::slug($groups->first()->distributor_nama)
            : 'semua-distributor';

        $filename = sprintf('restock-%s-%s.csv', $namaFileBagian, Carbon::now()->format('Ymd-His'));

        return $this->streamCsv($groups, $filename, $startDate, $endDate);
    }

    /**
     * Bangun & alirkan file CSV dari data rekomendasi (dikelompokkan per
     * distributor). Pakai StreamedResponse supaya tidak perlu menulis file
     * sementara ke disk — cocok untuk daftar barang yang bisa cukup panjang.
     *
     * Format: tiap distributor dipisah oleh baris judul, supaya saat dibuka
     * di Excel/Sheets, pemilik toko bisa langsung lihat barang mana milik
     * distributor mana tanpa perlu filter ulang.
     */
    private function streamCsv($groups, string $filename, string $startDate, string $endDate): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($groups, $startDate, $endDate) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 agar Excel (terutama versi Windows) membaca karakter
            // non-ASCII (mis. "Rp", nama barang berbahasa Indonesia) dengan benar
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Daftar Restock / Pemesanan ke Distributor']);
            fputcsv($out, ['Periode analisis', Carbon::parse($startDate)->format('d-m-Y') . ' s/d ' . Carbon::parse($endDate)->format('d-m-Y')]);
            fputcsv($out, ['Dicetak pada', Carbon::now()->format('d-m-Y H:i')]);
            fputcsv($out, []);

            foreach ($groups as $group) {
                fputcsv($out, ['DISTRIBUTOR', $group->distributor_nama]);
                fputcsv($out, [
                    'Kode',
                    'Nama Barang',
                    'Kategori',
                    'Satuan',
                    'Stok Saat Ini',
                    'Rata-rata Jual/Hari',
                    'Min. Stok',
                    'Target Stok',
                    'Qty yang Disarankan Order',
                    'Estimasi Harga Beli/Satuan',
                    'Estimasi Total Biaya',
                    'Urgensi',
                ]);

                foreach ($group->items as $it) {
                    fputcsv($out, [
                        $it->kode,
                        $it->nama,
                        $it->kategori,
                        $it->satuan,
                        $it->stok_sekarang,
                        $it->avg_qty_per_day,
                        $it->min_stock,
                        $it->target_stock,
                        $it->reorder_paket,
                        $it->harga_beli,
                        $it->estimasi_biaya,
                        $it->urgensi_label,
                    ]);
                }

                fputcsv($out, ['', '', '', '', '', '', '', '', 'TOTAL ESTIMASI BIAYA', '', $group->total_biaya, '']);
                fputcsv($out, []); // baris kosong pemisah antar distributor
            }

            fclose($out);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}