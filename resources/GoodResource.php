<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dipakai jika ingin melewatkan resource transformation
 * ketimbang format manual di service.
 *
 * Contoh pemakaian:
 *   return GoodResource::collection($goods);
 */
class GoodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'kode'        => $this->code,
            'nama'        => $this->name,
            'kategori'    => optional($this->category)->name,
            'merek'       => optional($this->brand)->name,
            'tipe'        => optional($this->type)->name,
            'stok'        => (float) $this->last_stock,
            'status_stok' => $this->stock_label,   // accessor di Good model

            'satuan' => $this->units->map(fn ($u) => [
                'nama'    => optional($u->unit)->name,
                'kode'    => optional($u->unit)->code,
                'isi'     => optional($u->unit)->quantity,
                'harga'   => (float) $u->selling_price,
                'display' => 'Rp ' . number_format((float) $u->selling_price, 0, ',', '.'),
            ]),

            'harga_mulai' => $this->min_price,      // accessor di Good model

            'foto_utama'  => $this->when(
                $this->profilePhoto,
                optional($this->profilePhoto)->location
            ),

            'foto_semua'  => $this->when(
                $this->relationLoaded('photos'),
                $this->photos->pluck('location')
            ),

            // Hanya tampil jika relasi di-load (mode detailed)
            'terakhir_restock' => $this->when(
                $this->last_loading,
                optional($this->last_loading)?->format('d M Y')
            ),
            'terakhir_terjual' => $this->when(
                $this->last_transaction,
                optional($this->last_transaction)?->format('d M Y')
            ),
        ];
    }
}