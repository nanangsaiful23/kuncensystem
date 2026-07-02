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
     * Mendukung 2 format:
     * - 'lengkap' (default): semua kolom analisis, untuk arsip/keperluan internal.
     * - 'ringkas': hanya nama barang & jumlah order, siap dikirim ke distributor.
     *
     * GET /admin/reports/reorder/export?distributor=&start_date=&end_date=&kategori=&only_needed=&format=
     */
    public function export(Request $request)
    {
        $startDate        = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate          = $request->get('end_date',   Carbon::now()->toDateString());
        $distributorId    = $request->get('distributor', null);
        $distributorNama  = $request->get('distributor_nama', null); // fallback utk grup "Belum Ditentukan"
        $kategori         = $request->get('kategori',    null);
        $onlyNeeded       = $request->get('only_needed', '1') === '1';
        $goodIds          = $request->get('good_ids', null);     // array good_id yang dicentang di halaman
        $qtyOverrides     = $request->get('qty_overrides', null); // [good_id => qty hasil edit user]
        $format           = $request->get('format', 'lengkap');  // 'lengkap' atau 'ringkas'
        $format           = in_array($format, ['lengkap', 'ringkas'], true) ? $format : 'lengkap';

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

        // Filter ke barang yang dicentang saja (dari checkbox di halaman),
        // dan terapkan qty hasil edit user (dari textfield Qty Order) jika ada
        // — supaya angka yang ter-export PERSIS sama dengan yang terlihat di
        // layar saat user export, bukan hasil hitung otomatis awal.
        if (is_array($goodIds) && count($goodIds) > 0) {
            $goodIdsInt = array_map('intval', $goodIds);
            $groups = $groups
                ->map(function ($g) use ($goodIdsInt, $qtyOverrides) {
                    $g->items = $g->items
                        ->filter(function ($it) use ($goodIdsInt) {
                            return in_array((int) $it->good_id, $goodIdsInt, true);
                        })
                        ->map(function ($it) use ($qtyOverrides) {
                            if (is_array($qtyOverrides) && isset($qtyOverrides[$it->good_id])) {
                                // Qty Order selalu bulat (satuan kemasan utuh), jadi hasil
                                // edit manual dari textfield juga dipaksa jadi integer di sini.
                                $newQty = (int) round((float) $qtyOverrides[$it->good_id]);
                                $newQty = max(0, $newQty);
                                $it->reorder_qty    = $newQty;
                                // harga_beli_per_unit sudah dalam harga per satuan kemasan terbesar
                                $it->estimasi_biaya = round($newQty * $it->harga_beli_per_unit, 0);
                            }
                            return $it;
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
        // kalau semua, pakai label umum. Diberi penanda "ringkas" supaya
        // pemilik toko mudah membedakan file mana yang siap dikirim ke
        // distributor vs file arsip lengkap.
        $namaFileBagian = $distributorId && $groups->count() === 1
            ? Str::slug($groups->first()->distributor_nama)
            : 'semua-distributor';
        $namaFileFormat = $format === 'ringkas' ? 'ringkas' : 'lengkap';

        $filename = sprintf(
            'restock-%s-%s-%s.csv',
            $namaFileBagian,
            $namaFileFormat,
            Carbon::now()->format('Ymd-His')
        );

        return $format === 'ringkas'
            ? $this->streamCsvRingkas($groups, $filename)
            : $this->streamCsv($groups, $filename, $startDate, $endDate);
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
                    'Tanggal Loading Terakhir',
                    'Qty Loading Terakhir',
                    'Satuan Loading Terakhir',
                    'Stok Saat Ini',
                    'Rata-rata Jual/Hari',
                    'Min. Stok',
                    'Target Stok',
                    'Qty Order',
                    'Satuan Order',
                    'Estimasi Harga Beli/Satuan',
                    'Estimasi Total Biaya',
                    'Urgensi',
                ]);

                foreach ($group->items as $it) {
                    fputcsv($out, [
                        $it->kode,
                        $it->nama,
                        $it->kategori,
                        $it->last_loading_date ?? '-',
                        $it->last_loading_qty  ?? '-',
                        $it->last_loading_unit ?? '-',
                        $it->stok_sekarang,
                        $it->avg_qty_per_day,
                        $it->min_stock,
                        $it->target_stock,
                        $it->reorder_qty,
                        $it->reorder_unit,
                        $it->harga_beli_per_unit,
                        $it->estimasi_biaya,
                        $it->urgensi_label,
                    ]);
                }

                fputcsv($out, ['', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL ESTIMASI BIAYA', $group->total_biaya, '']);
                fputcsv($out, []); // baris kosong pemisah antar distributor
            }

            fclose($out);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Bangun & alirkan file CSV RINGKAS — hanya nama barang dan jumlah yang
     * perlu di-order, tanpa kolom analisis internal (stok, min. stok, urgensi,
     * dll). Format ini didesain supaya siap langsung dikirim/dilampirkan ke
     * distributor sebagai daftar pesanan, tanpa perlu disunting lagi.
     *
     * Jika $groups berisi lebih dari 1 distributor, tetap dipisah per bagian
     * dengan baris judul distributor, supaya kalau memang ada kebutuhan
     * gabung banyak distributor dalam 1 file, tetap jelas mana milik siapa.
     */
    private function streamCsvRingkas($groups, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($groups) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 agar Excel baca karakter non-ASCII dengan benar

            fputcsv($out, ['Daftar Pesanan Barang']);
            fputcsv($out, ['Tanggal', Carbon::now()->format('d-m-Y')]);
            fputcsv($out, []);

            foreach ($groups as $group) {
                fputcsv($out, ['Kepada Yth. Distributor', $group->distributor_nama]);
                fputcsv($out, []);
                fputcsv($out, ['No', 'Nama Barang', 'Jumlah Pesan', 'Satuan']);

                $no = 1;
                foreach ($group->items as $it) {
                    fputcsv($out, [
                        $no++,
                        $it->nama,
                        $it->reorder_qty,
                        $it->reorder_unit,
                    ]);
                }

                fputcsv($out, []);
                fputcsv($out, ['Total ' . $group->jumlah_item . ' jenis barang']);
                fputcsv($out, []); // baris kosong pemisah antar distributor
            }

            fclose($out);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}