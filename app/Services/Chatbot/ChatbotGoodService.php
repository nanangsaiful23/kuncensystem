<?php

namespace App\Services\Chatbot;

use App\Models\Good;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Type;
use App\Models\GoodUnit;
use App\Models\Unit;
use Carbon\Carbon;

/**
 * ChatbotGoodService
 *
 * Kumpulan query Eloquent yang menjawab pertanyaan
 * umum pembeli melalui chatbot pelayanan toko.
 *
 * Cara pakai (dari ChatbotController):
 *
 *   $svc = new ChatbotGoodService();
 *   $result = $svc->searchByName('susu');
 *
 * Atau pakai service container:
 *   app(ChatbotGoodService::class)->searchByName('susu');
 */
class ChatbotGoodService
{
    // ══════════════════════════════════════════════════════════
    //  BASE QUERY — fondasi semua query barang
    // ══════════════════════════════════════════════════════════

    private function baseQuery()
    {
        return Good::whereNull('goods.deleted_at')
            ->with([
                'category'     => function ($q) { $q->select('id', 'name', 'code', 'color'); },
                'brand'        => function ($q) { $q->select('id', 'name'); },
                'type'         => function ($q) { $q->select('id', 'name'); },
                'good_units'   => function ($q) {
                    $q->with(['unit' => function ($q2) {
                        $q2->select('id', 'name', 'code', 'quantity');
                    }]);
                },
                
            ]);
    }

    // ══════════════════════════════════════════════════════════
    //  1. CARI BARANG (nama / kode)
    //     Contoh: "cari indomie" / "ada abc?"
    // ══════════════════════════════════════════════════════════

    public function searchByName($keyword, $limit = 10)
    {
        $goods = $this->baseQuery()
            ->where(function ($q) use ($keyword) {
                $q->where('goods.name', 'like', '%' . $keyword . '%')
                  ->orWhere('goods.code', 'like', '%' . $keyword . '%');
            })
            ->where('goods.last_stock', '>', 0)
            ->orderByRaw('last_stock DESC')
            ->limit($limit)
            ->get();

        return $goods->map(function ($g) {
            return $this->formatForChat($g);
        });
    }

    // ══════════════════════════════════════════════════════════
    //  2. DETAIL SATU BARANG (by id atau kode)
    //     Contoh: "info produk X"
    // ══════════════════════════════════════════════════════════

    public function getDetail($idOrCode)
    {
        $query = $this->baseQuery()
           ;

        if (is_numeric($idOrCode)) {
            $good = $query->where('goods.id', $idOrCode)->first();
        } else {
            $good = $query->where(function ($q) use ($idOrCode) {
                $q->where('goods.code', $idOrCode)
                  ->orWhere('goods.name', $idOrCode);
            })->first();
        }

        return $good ? $this->formatForChat($good, true) : null;
    }

    // ══════════════════════════════════════════════════════════
    //  3. TANYA STOK
    //     Contoh: "masih ada gula nggak?"
    // ══════════════════════════════════════════════════════════

    public function checkStock($keyword)
    {
        $goods = Good::whereNull('deleted_at')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('code', 'like', '%' . $keyword . '%');
            })
            ->select('id', 'name', 'code', 'last_stock', 'last_transaction')
            ->with(['good_units' => function ($q) {
                $q->select('id', 'good_id', 'unit_id', 'selling_price')
                  ->with(['unit' => function ($q2) {
                      $q2->select('id', 'name', 'code');
                  }]);
            }])
            ->get();

        return $goods->map(function ($g) {
            return [
                'nama'         => $g->name,
                'kode'         => $g->code,
                'stok'         => (float) $g->last_stock,
                'status_stok'  => $g->stock_label,
                'harga_mulai'  => $g->good_units->min(function ($u) {
                    return (float) $u->selling_price;
                }),
                
            ];
        });
    }

    // ══════════════════════════════════════════════════════════
    //  4. TANYA HARGA
    //     Contoh: "harga beras 5kg?" / "berapa harga susu?"
    // ══════════════════════════════════════════════════════════

    public function getPrices($keyword)
    {
        $goods = Good::whereNull('deleted_at')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('code', 'like', '%' . $keyword . '%');
            })
            ->where('last_stock', '>', 0)
            ->with(['good_units' => function ($q) {
                $q->select('id', 'good_id', 'unit_id', 'selling_price')
                  ->with(['unit' => function ($q2) {
                      $q2->select('id', 'name', 'code', 'quantity');
                  }]);
            }])
            ->select('id', 'name', 'code', 'last_stock')
            ->limit(5)
            ->get();

        return $goods->map(function ($g) {
            return [
                'nama'   => $g->name,
                'kode'   => $g->code,
                'satuan' => $g->good_units->map(function ($u) {
                    return [
                        'satuan'  => optional($u->unit)->name,
                        'isi'     => optional($u->unit)->quantity,
                        'harga'   => (float) $u->selling_price,
                        'display' => 'Rp ' . number_format((float) $u->selling_price, 0, ',', '.'),
                    ];
                }),
            ];
        });
    }

    // ══════════════════════════════════════════════════════════
    //  5. BROWSING PER KATEGORI
    //     Contoh: "lihat produk minuman"
    // ══════════════════════════════════════════════════════════

    public function browseByCategory($categoryName, $limit = 15)
    {
        $category = Category::whereNull('deleted_at')
            ->where('name', 'like', '%' . $categoryName . '%')
            ->first(['id', 'name', 'code', 'color']);

        if (! $category) {
            return ['kategori' => null, 'barang' => []];
        }

        $goods = $this->baseQuery()
            ->where('goods.category_id', $category->id)
            ->where('goods.last_stock', '>', 0)
            ->orderBy('goods.name')
            ->limit($limit)
            ->get()
            ->map(function ($g) {
                return $this->formatForChat($g);
            });

        return [
            'kategori' => [
                'id'    => $category->id,
                'nama'  => $category->name,
                'kode'  => $category->code,
                'warna' => $category->color,
            ],
            'barang'         => $goods,
            'total_tersedia' => $goods->count(),
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  6. BARANG TERLARIS
    //     Contoh: "rekomendasikan produk terlaris"
    // ══════════════════════════════════════════════════════════

    public function getBestSellers($days = 30, $limit = 8)
    {
        $since = Carbon::now()->subDays($days);

        $goods = Good::whereNull('goods.deleted_at')
            ->join('good_units as gu', 'gu.good_id', '=', 'goods.id')
            ->whereNull('gu.deleted_at')
            ->join('transaction_details as td', 'td.good_unit_id', '=', 'gu.id')
            ->whereNull('td.deleted_at')
            ->join('transactions as t', 't.id', '=', 'td.transaction_id')
            ->whereNull('t.deleted_at')
            ->where('t.created_at', '>=', $since)
            ->select(
                'goods.id',
                'goods.name',
                'goods.code',
                'goods.last_stock'
            )
            ->selectRaw('SUM(td.quantity) as total_terjual')
            ->selectRaw('COUNT(DISTINCT td.transaction_id) as jumlah_transaksi')
            ->with([
                'good_units' => function ($q) {
                    $q->select('id', 'good_id', 'unit_id', 'selling_price')
                      ->with(['unit' => function ($q2) {
                          $q2->select('id', 'name', 'code');
                      }]);
                },
                'category' => function ($q) { $q->select('id', 'name'); },
            ])
            ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.last_stock')
            ->orderBy('total_terjual', 'desc')
            ->limit($limit)
            ->get();

        return $goods->map(function ($g) {
            return [
                'id'               => $g->id,
                'nama'             => $g->name,
                'kode'             => $g->code,
                'kategori'         => optional($g->category)->name,
                'harga_mulai'      => $g->good_units->min(function ($u) {
                    return (float) $u->selling_price;
                }),
                'status_stok'      => $g->stock_label,
                'total_terjual'    => (int) $g->total_terjual,
                'jumlah_transaksi' => (int) $g->jumlah_transaksi,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════
    //  7. FILTER HARGA (range)
    //     Contoh: "produk di bawah 10 ribu"
    // ══════════════════════════════════════════════════════════

    public function filterByPrice($keyword = null, $unitName = null, $min = null, $max = null, $limit = 400)
    {
        $query = Good::whereNull('goods.deleted_at')
            ->join('good_units as gu', 'gu.good_id', '=', 'goods.id')
            ->join('units as u', 'u.id', '=', 'gu.unit_id')
            ->whereNull('gu.deleted_at')
            // ->where('goods.last_stock', '>', 0)
            ->select(
                'goods.id', 'goods.name', 'goods.code',
                'goods.last_stock', 'goods.category_id',
                'u.name as unit_name'
            )
            ->selectRaw('MIN(CAST(gu.selling_price AS DECIMAL)) as harga_min')
            ->selectRaw('MAX(CAST(gu.selling_price AS DECIMAL)) as harga_max')
            ->with([
                'category'     => function ($q) { $q->select('id', 'name'); },
            ])
            ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.last_stock', 'goods.category_id', 'u.name')
            ->orderBy('harga_min')
            ;

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('goods.name', 'like', '%' . $keyword . '%')
                  ->orWhere('goods.code', 'like', '%' . $keyword . '%');
            });
        }

        if ($unitName) {
            $query->where('u.name', 'like', '%' . $unitName . '%');
        }

        if ($min !== null) {
            $query->having('harga_max', '>=', $min);
        }
        if ($max !== null) {
            $query->having('harga_max', '<=', $max);
        }

        $goods = $query->get();

        return $goods->map(function ($g) {
            return [
                'id'          => $g->id,
                'nama'        => $g->name,
                'satuan'      => $g->unit_name,
                'kategori'    => optional($g->category)->name,
                'harga_min'   => $g->harga_min,
                'harga_max'   => $g->harga_max,
                'display'     => 'Rp ' . number_format($g->harga_min, 0, ',', '.'),
                'status_stok' => $g->stock_label,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════
    //  8. DAFTAR SEMUA KATEGORI
    //     Contoh: "ada kategori apa aja?"
    // ══════════════════════════════════════════════════════════

    public function listCategories()
    {
        $categories = Category::whereNull('deleted_at')
            ->withCount(['goods as jumlah_barang' => function ($q) {
                $q->whereNull('deleted_at')->where('last_stock', '>', 0);
            }])
            ->having('jumlah_barang', '>', 0)
            ->orderBy('jumlah_barang', 'desc')
            ->get(['id', 'name', 'code', 'color']);

        return $categories->map(function ($c) {
            return [
                'id'            => $c->id,
                'nama'          => $c->name,
                'kode'          => $c->code,
                'jumlah_barang' => $c->jumlah_barang,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE — Format output untuk chatbot
    // ══════════════════════════════════════════════════════════

    private function formatForChat($g, $detailed = false)
    {
        $data = [
            'id'          => $g->id,
            'kode'        => $g->code,
            'nama'        => $g->name,
            'kategori'    => optional($g->category)->name,
            'merek'       => optional($g->brand)->name,
            'tipe'        => optional($g->type)->name,
            'stok'        => (float) $g->last_stock,
            'status_stok' => $g->stock_label,
            'satuan'      => $g->good_units->map(function ($u) {
                return [
                    'nama'    => optional($u->unit)->name,
                    'kode'    => optional($u->unit)->code,
                    'isi'     => optional($u->unit)->quantity,
                    'harga'   => (float) $u->selling_price,
                    'display' => 'Rp ' . number_format((float) $u->selling_price, 0, ',', '.'),
                ];
            }),
            'harga_mulai' => $g->min_price,
           
        ];

        if ($detailed) {
            $data['terakhir_restock'] = $g->last_loading
                ? $g->last_loading->format('d M Y') : null;
            $data['terakhir_terjual'] = $g->last_transaction
                ? $g->last_transaction->format('d M Y') : null;
        }

        return $data;
    }
}