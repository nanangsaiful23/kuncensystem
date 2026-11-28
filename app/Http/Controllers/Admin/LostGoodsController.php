<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BarangSeringHilang;
use App\Services\StockOpnameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Laporan Barang Sering Hilang (dari hasil semua stock opname). */
class LostGoodsController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        abort_unless(canMenu('laporan.sering_hilang'), 403);

        $filter = $this->filter($request);
        $data   = BarangSeringHilang::data(...array_values($filter));

        if ($request->get('export') === 'csv') {
            return $this->csv($data['rows'], $filter);
        }

        return view('admin.lost-goods', $data + $filter + [
            'default'      => ['page_name' => 'Barang Sering Hilang'],
            'categories'   => DB::table('categories')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'distributors' => DB::table('distributors')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function filter(Request $request): array
    {
        $tanggal = fn ($v, $bawaan) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $bawaan;

        $dari   = $tanggal($request->get('dari'), Carbon::today()->subMonths(3)->toDateString());
        $sampai = $tanggal($request->get('sampai'), Carbon::today()->toDateString());
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $urut = $request->get('urut');

        return [
            'dari'        => $dari,
            'sampai'      => $sampai,
            'kategori'    => $request->integer('kategori') ?: null,
            'distributor' => $request->integer('distributor') ?: null,
            'minKurang'   => max(0, min(50, $request->integer('min_kurang', 1))),
            'urut'        => isset(BarangSeringHilang::URUTAN[$urut]) ? $urut : 'rugi',
        ];
    }

    private function csv($rows, array $filter)
    {
        $nama = 'barang-sering-hilang-' . $filter['dari'] . '-sd-' . $filter['sampai'] . '.csv';

        return response()->streamDownload(function () use ($rows, $filter) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // supaya Excel membaca UTF-8
            fputcsv($out, ['Barang Sering Hilang', $filter['dari'] . ' s/d ' . $filter['sampai']]);
            fputcsv($out, []);
            fputcsv($out, ['Kode', 'Nama Barang', 'Kategori', 'Distributor', 'Satuan', 'Kali di-SO', 'Kali kurang',
                           '% kurang', 'Kali lebih', 'Qty kurang', 'Rugi (Rp)', 'Qty lebih', 'Lebih (Rp)', 'Bersih (Rp)',
                           'Terakhir kurang', 'Stok sekarang']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->code, $row->nama, $row->kategori, $row->distributor, $row->satuan,
                    $row->kali_dicek, $row->kali_kurang, $row->persen, $row->kali_lebih,
                    $row->qty_kurang, $row->rp_kurang, $row->qty_lebih, $row->rp_lebih, $row->rp_bersih,
                    $row->terakhir_kurang ? Carbon::parse($row->terakhir_kurang)->format('d-m-Y') : '-',
                    StockOpnameService::angka((float) $row->last_stock),
                ]);
            }

            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
