<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Laporan Barang Sering Hilang: hasil semua stock opname dalam satu periode,
 * dikumpulkan per barang. Jurnal SO hanya berisi total; pola kehilangan
 * ("barang ini kurang di 4 dari 5 kali SO") dibaca dari stock_opname_details.
 *
 * Selisih per baris = stok baru - stok lama (dalam satuan yang dihitung),
 * nilainya kolom total (selalu positif, harga beli saat SO). Jumlah barang
 * diubah ke satuan dasar barang (satuan last_stock).
 */
class BarangSeringHilang
{
    const URUTAN = [
        'rugi'   => 'Rugi terbesar',
        'kali'   => 'Paling sering kurang',
        'persen' => '% kurang tertinggi',
    ];

    /**
     * @return array{rows: \Illuminate\Support\Collection, ringkasan: array}
     */
    public static function data(string $dari, string $sampai, ?int $kategori = null, ?int $distributor = null,
                                int $minKurang = 1, string $urut = 'rugi'): array
    {
        $baru  = 'CAST(d.new_stock AS DECIMAL(16,4))';
        $lama  = 'CAST(d.old_stock AS DECIMAL(16,4))';
        $nilai = 'CAST(d.total AS DECIMAL(16,4))';
        $isi   = 'COALESCE(NULLIF(CAST(u.quantity AS DECIMAL(16,4)), 0), 1)';
        $dasar = 'COALESCE(NULLIF(CAST(bu.quantity AS DECIMAL(16,4)), 0), 1)';
        // berapa kali stock opname (bukan baris) barang ini kurang
        $kaliKurang = "COUNT(DISTINCT CASE WHEN $baru < $lama THEN so.id END)";

        $query = DB::table('stock_opname_details as d')
            ->join('stock_opnames as so', 'so.id', '=', 'd.stock_opname_id')
            ->join('good_units as gu', 'gu.id', '=', 'd.good_unit_id')
            ->join('units as u', 'u.id', '=', 'gu.unit_id')
            ->join('goods as g', 'g.id', '=', 'gu.good_id')
            ->leftJoin('good_units as bgu', 'bgu.id', '=', 'g.base_unit_id')
            ->leftJoin('units as bu', 'bu.id', '=', 'bgu.unit_id')
            ->leftJoin('categories as c', 'c.id', '=', 'g.category_id')
            ->leftJoin('distributors as dist', 'dist.id', '=', 'g.last_distributor_id')
            ->whereNull('d.deleted_at')
            ->whereNull('so.deleted_at')
            ->whereBetween('so.created_at', [$dari . ' 00:00:00', $sampai . ' 23:59:59']);

        if ($kategori) {
            $query->where('g.category_id', $kategori);
        }
        if ($distributor) {
            $query->where('g.last_distributor_id', $distributor);
        }

        // Total periode (semua barang sesuai filter kategori/distributor).
        $total = (clone $query)->first([
            DB::raw('COUNT(DISTINCT so.id) AS jumlah_so'),
            DB::raw("COUNT(DISTINCT CASE WHEN $baru < $lama THEN g.id END) AS barang_kurang"),
            DB::raw("COALESCE(SUM(CASE WHEN $baru < $lama THEN $nilai ELSE 0 END), 0) AS rp_kurang"),
            DB::raw("COALESCE(SUM(CASE WHEN $baru > $lama THEN $nilai ELSE 0 END), 0) AS rp_lebih"),
        ]);

        $rows = (clone $query)
            ->groupBy('g.id', 'g.code', 'g.name', 'g.type_id', 'g.deleted_at', 'g.last_stock', 'bu.name', 'c.name', 'dist.name')
            ->havingRaw("$kaliKurang >= ?", [max($minKurang, 0)])
            ->get([
                'g.id', 'g.code', 'g.name', 'g.type_id', 'g.deleted_at', 'g.last_stock',
                DB::raw('bu.name AS satuan'),
                DB::raw('c.name AS kategori'),
                DB::raw('dist.name AS distributor'),
                DB::raw('COUNT(DISTINCT so.id) AS kali_dicek'),
                DB::raw("$kaliKurang AS kali_kurang"),
                DB::raw("COUNT(DISTINCT CASE WHEN $baru > $lama THEN so.id END) AS kali_lebih"),
                DB::raw("SUM(CASE WHEN $baru < $lama THEN ($lama - $baru) * $isi / $dasar ELSE 0 END) AS qty_kurang"),
                DB::raw("SUM(CASE WHEN $baru > $lama THEN ($baru - $lama) * $isi / $dasar ELSE 0 END) AS qty_lebih"),
                DB::raw("SUM(CASE WHEN $baru < $lama THEN $nilai ELSE 0 END) AS rp_kurang"),
                DB::raw("SUM(CASE WHEN $baru > $lama THEN $nilai ELSE 0 END) AS rp_lebih"),
                DB::raw("MAX(CASE WHEN $baru < $lama THEN so.created_at END) AS terakhir_kurang"),
                DB::raw("MAX(CASE WHEN $baru < $lama THEN so.id END) AS so_terakhir_kurang"),
            ]);

        // nama lengkap (jenis + nama) seperti di tempat lain
        $jenis = DB::table('types')->whereIn('id', $rows->pluck('type_id')->filter()->unique()->all())->pluck('name', 'id');

        $rows = $rows->map(function ($row) use ($jenis) {
            $row->nama        = \App\Models\Good::formatName($row->type_id ? ($jenis[$row->type_id] ?? null) : null, $row->name);
            $row->kali_dicek  = (int) $row->kali_dicek;
            $row->kali_kurang = (int) $row->kali_kurang;
            $row->kali_lebih  = (int) $row->kali_lebih;
            $row->qty_kurang  = round((float) $row->qty_kurang, 3);
            $row->qty_lebih   = round((float) $row->qty_lebih, 3);
            $row->rp_kurang   = round((float) $row->rp_kurang, 2);
            $row->rp_lebih    = round((float) $row->rp_lebih, 2);
            $row->rp_bersih   = round($row->rp_lebih - $row->rp_kurang, 2);
            $row->persen      = $row->kali_dicek > 0 ? (int) round($row->kali_kurang * 100 / $row->kali_dicek) : 0;

            return $row;
        });

        $rows = $rows->sort(function ($a, $b) use ($urut) {
            return match ($urut) {
                'kali'   => [$b->kali_kurang, $b->rp_kurang] <=> [$a->kali_kurang, $a->rp_kurang],
                'persen' => [$b->persen, $b->kali_kurang, $b->rp_kurang] <=> [$a->persen, $a->kali_kurang, $a->rp_kurang],
                default  => [$b->rp_kurang, $b->kali_kurang] <=> [$a->rp_kurang, $a->kali_kurang],
            };
        })->values();

        return [
            'rows'      => $rows,
            'ringkasan' => [
                'jumlah_so'     => (int) ($total->jumlah_so ?? 0),
                'barang_kurang' => (int) ($total->barang_kurang ?? 0),
                'rp_kurang'     => round((float) ($total->rp_kurang ?? 0), 2),
                'rp_lebih'      => round((float) ($total->rp_lebih ?? 0), 2),
                'berulang'      => DB::query()->fromSub((clone $query)->groupBy('g.id')->havingRaw("$kaliKurang >= 2")->select('g.id'), 'x')->count(),
            ],
        ];
    }
}
