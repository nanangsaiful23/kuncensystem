<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Rangkuman struktur Area > Kategori > Jenis Barang beserta jumlah barang,
 * stok, dan hal-hal yang perlu dirapikan (data dobel / tidak konsisten).
 * Hanya membaca data, tidak mengubah apa pun.
 */
class RangkumanKategori
{
    public function hitung(): array
    {
        $areas = DB::table('main_categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $categories = DB::table('categories')
            ->leftJoin('units', 'units.id', '=', 'categories.unit_id')
            ->whereNull('categories.deleted_at')
            ->orderBy('categories.name')
            ->get(['categories.id', 'categories.code', 'categories.name', 'categories.main_category_id',
                   'categories.habis_pakai', 'units.code as unit_code']);

        $types = DB::table('types')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category_id', 'tipe_kontrol', 'min_model', 'max_model']);

        // Statistik barang aktif (belum dihapus & tidak discontinue)
        $statSelect = 'COUNT(*) AS barang,
                       SUM(COALESCE(goods.last_stock, 0) > 0) AS berstok,
                       SUM(COALESCE(goods.last_stock, 0) <= 0) AS habis,
                       SUM(goods.type_id IS NULL) AS tanpa_jenis,
                       SUM(GREATEST(COALESCE(goods.last_stock, 0), 0) * COALESCE(gu.buy_price, 0)) AS nilai_stok';

        $goodsBase = fn () => DB::table('goods')
            ->leftJoin('good_units AS gu', 'gu.id', '=', 'goods.base_unit_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0);

        $perCategory = $goodsBase()->selectRaw('goods.category_id AS id, ' . $statSelect)
            ->groupBy('goods.category_id')->get()->keyBy('id');

        $perType = $goodsBase()->selectRaw('goods.type_id AS id, ' . $statSelect)
            ->whereNotNull('goods.type_id')
            ->groupBy('goods.type_id')->get()->keyBy('id');

        $kosong = (object) ['barang' => 0, 'berstok' => 0, 'habis' => 0, 'tanpa_jenis' => 0, 'nilai_stok' => 0];

        $typesByCategory = $types->groupBy('category_id');

        // Susun pohon Area > Kategori > Jenis
        $categoryIds = $categories->pluck('id')->all();
        $areaIds     = $areas->pluck('id')->all();
        $tree        = [];

        $buatKategori = function ($c) use ($perCategory, $perType, $typesByCategory, $kosong) {
            $stat  = $perCategory->get($c->id, $kosong);
            $jenis = ($typesByCategory->get($c->id) ?? collect())->map(function ($t) use ($perType, $kosong) {
                $t->stat = $perType->get($t->id, $kosong);
                return $t;
            })->values();

            $c->stat         = $stat;
            $c->jenis        = $jenis;
            $c->jenis_kosong = $jenis->filter(fn ($t) => (int) $t->stat->barang === 0)->count();

            return $c;
        };

        foreach ($areas as $a) {
            $cats = $categories->where('main_category_id', $a->id)->map($buatKategori)->values();
            $tree[] = (object) ['area' => $a, 'kategori' => $cats, 'total' => $this->jumlahkan($cats)];
        }

        $tanpaArea = $categories->filter(fn ($c) => !in_array($c->main_category_id, $areaIds))->map($buatKategori)->values();
        if ($tanpaArea->count()) {
            $tree[] = (object) ['area' => (object) ['id' => null, 'code' => '-', 'name' => 'Belum ada Area'],
                                'kategori' => $tanpaArea, 'total' => $this->jumlahkan($tanpaArea)];
        }

        // Barang yang kategorinya kosong / sudah dihapus
        $barangTanpaKategori = $goodsBase()
            ->where(function ($q) use ($categoryIds) {
                $q->whereNull('goods.category_id')->orWhereNotIn('goods.category_id', $categoryIds ?: [0]);
            })->count();

        $total = $this->jumlahkan(collect($tree)->flatMap(fn ($a) => $a->kategori));

        return [
            'tree'      => $tree,
            'total'     => $total,
            'jumlah'    => [
                'area'     => $areas->count(),
                'kategori' => $categories->count(),
                'jenis'    => $types->count(),
            ],
            'masalah'   => $this->masalah($categories, $types, $categoryIds, $barangTanpaKategori, $tanpaArea->count()),
            'discontinue' => DB::table('goods')->whereNull('deleted_at')->where('is_discontinued', 1)->count(),
        ];
    }

    private function jumlahkan($cats): object
    {
        $t = ['barang' => 0, 'berstok' => 0, 'habis' => 0, 'tanpa_jenis' => 0, 'nilai_stok' => 0, 'jenis' => 0];
        foreach ($cats as $c) {
            foreach (['barang', 'berstok', 'habis', 'tanpa_jenis', 'nilai_stok'] as $k) {
                $t[$k] += (float) $c->stat->$k;
            }
            $t['jenis'] += $c->jenis->count();
        }

        return (object) $t;
    }

    /** Daftar hal yang perlu dirapikan, termasuk nama dobel. */
    private function masalah($categories, $types, array $categoryIds, int $barangTanpaKategori, int $kategoriTanpaArea): array
    {
        $norm = fn ($s) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));

        $kategoriDobel = $categories->groupBy(fn ($c) => $norm($c->name))
            ->filter(fn ($g) => $g->count() > 1)
            ->map(fn ($g) => $g->values())->values();

        $catNames = $categories->pluck('name', 'id');

        // Jenis dengan nama sama di dalam kategori yang sama
        $jenisDobel = $types->groupBy(fn ($t) => $t->category_id . '|' . $norm($t->name))
            ->filter(fn ($g) => $g->count() > 1)
            ->map(fn ($g) => $g->values())->values();

        $jenisTanpaKategori = $types->filter(fn ($t) => !in_array($t->category_id, $categoryIds))->values();

        // Barang yang kategorinya berbeda dengan kategori induk jenisnya
        $tidakCocok = DB::table('goods')
            ->join('types', 'types.id', '=', 'goods.type_id')
            ->leftJoin('categories AS kb', 'kb.id', '=', 'goods.category_id')
            ->leftJoin('categories AS kj', 'kj.id', '=', 'types.category_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0)
            ->whereNotNull('types.category_id')
            ->whereRaw('COALESCE(goods.category_id, 0) <> types.category_id')
            ->orderBy('goods.name')
            ->limit(200)
            ->get(['goods.id', 'goods.code', 'goods.name', 'kb.name AS kategori_barang',
                   'types.name AS jenis', 'kj.name AS kategori_jenis']);

        return [
            'kategori_dobel'        => $kategoriDobel,
            'jenis_dobel'           => $jenisDobel,
            'jenis_tanpa_kategori'  => $jenisTanpaKategori,
            'kategori_tanpa_area'   => $kategoriTanpaArea,
            'barang_tanpa_kategori' => $barangTanpaKategori,
            'barang_tidak_cocok'    => $tidakCocok,
            'nama_kategori'         => $catNames,
        ];
    }
}
