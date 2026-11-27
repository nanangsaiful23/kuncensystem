<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Musim penjualan (Ramadan–Lebaran, Tahun Ajaran Baru, Natal–Tahun Baru,
 * Musim Hujan, ...). Dipakai menu Perputaran Barang dan Rekomendasi Reorder:
 * barang musiman di luar musimnya tidak dicap Slow/Dead dan tidak disarankan
 * order; menjelang musim (prep_days hari sebelumnya) disarankan menyiapkan
 * stok sebanyak penjualan musim lalu.
 *
 * Tanggal musim ada di season_periods. Musim berulang (repeats_yearly) cukup
 * satu periode: tanggal & bulannya dipakai setiap tahun. Musim yang
 * tanggalnya bergeser (Ramadan) diisi satu periode per tahun.
 *
 * Barang terhubung ke musim lewat jenisnya (types.season_id) atau langsung
 * (goods.season_id: NULL = ikut jenis, 0 = bukan musiman, > 0 = musim itu).
 */
class Season extends Model
{
    public const LUAR      = 'luar';
    public const PERSIAPAN = 'persiapan';
    public const MUSIM     = 'musim';
    public const TANPA     = 'tanpa_tanggal';

    protected $fillable = ['name', 'repeats_yearly', 'prep_days', 'note'];

    protected $casts = [
        'repeats_yearly' => 'boolean',
        'prep_days'      => 'integer',
    ];

    private static ?bool $tabelAda = null;

    public function periods()
    {
        return $this->hasMany(SeasonPeriod::class)->orderBy('start_date');
    }

    /** Kolom obral/musim di goods & types serta tabel musim sudah ada. */
    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('seasons') && Schema::hasTable('season_periods')
            && Schema::hasColumn('goods', 'is_clearance') && Schema::hasColumn('goods', 'season_id')
            && Schema::hasColumn('types', 'season_id');
    }

    public static function lupakanTabel(): void
    {
        self::$tabelAda = null;
    }

    /**
     * Buat kolom & tabel kalau belum ada. Hanya dari request GET: CREATE/ALTER
     * TABLE di MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikanTabel(): bool
    {
        if (self::tabelAda()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_27_000001_add_clearance_and_seasons.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        self::lupakanTabel();

        return self::tabelAda();
    }

    /**
     * Musim yang berlaku untuk satu barang.
     * goods.season_id NULL = ikut jenis, 0 = bukan musiman, > 0 = musim itu.
     */
    public static function efektif($musimBarang, $musimJenis): ?int
    {
        if ($musimBarang === null || $musimBarang === '') {
            return (int) $musimJenis > 0 ? (int) $musimJenis : null;
        }

        return (int) $musimBarang > 0 ? (int) $musimBarang : null;
    }

    /**
     * Rentang tanggal musim ini, diurutkan dari yang paling awal.
     * Musim berulang diproyeksikan ke 2 tahun sebelum s/d 1 tahun sesudah $sekitar.
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function rentang(Carbon $sekitar): array
    {
        $hasil = [];
        foreach ($this->periods as $p) {
            $mulai   = Carbon::parse($p->start_date)->startOfDay();
            $selesai = Carbon::parse($p->end_date)->endOfDay();
            if ($selesai->lt($mulai)) {
                continue;
            }
            if ($this->repeats_yearly) {
                $panjang = (int) round($mulai->diffInDays($selesai->copy()->startOfDay()));
                for ($y = $sekitar->year - 2; $y <= $sekitar->year + 1; $y++) {
                    $m = $mulai->copy()->setDate($y, $mulai->month, min($mulai->day, Carbon::create($y, $mulai->month, 1)->daysInMonth));
                    $hasil[] = [$m, $m->copy()->addDays($panjang)->endOfDay()];
                }
            } else {
                $hasil[] = [$mulai, $selesai];
            }
        }
        usort($hasil, fn ($a, $b) => $a[0] <=> $b[0]);

        return $hasil;
    }

    /**
     * Posisi $tgl terhadap musim ini.
     *
     * @return array{status: string, sekarang: ?array, berikut: ?array, lalu: ?array, hari_lagi: ?int, label: string}
     *   lalu = musim terakhir yang sudah selesai (acuan stok musim berikutnya)
     */
    public function statusPada(Carbon $tgl): array
    {
        $rentang = $this->rentang($tgl);
        $sekarang = $berikut = $lalu = null;

        foreach ($rentang as $r) {
            if ($r[1]->lt($tgl)) {
                $lalu = $r;
            } elseif ($r[0]->lte($tgl)) {
                $sekarang = $sekarang ?? $r;
            } elseif ($berikut === null) {
                $berikut = $r;
            }
        }

        $hariLagi = $berikut ? (int) ceil($tgl->copy()->startOfDay()->diffInDays($berikut[0])) : null;

        if (! $rentang) {
            $status = self::TANPA;
            $label  = 'Belum ada tanggal musim';
        } elseif ($sekarang) {
            $status = self::MUSIM;
            $label  = 'Sedang musim (s/d ' . $sekarang[1]->isoFormat('D MMM Y') . ')';
        } elseif ($berikut && $hariLagi <= $this->prep_days) {
            $status = self::PERSIAPAN;
            $label  = 'Musim mulai ' . $hariLagi . ' hari lagi (' . $berikut[0]->isoFormat('D MMM Y') . ')';
        } else {
            $status = self::LUAR;
            $label  = 'Di luar musim' . ($berikut ? ' · berikutnya ' . $berikut[0]->isoFormat('D MMM Y') : '');
        }

        return [
            'status'    => $status,
            'sekarang'  => $sekarang,
            'berikut'   => $berikut,
            'lalu'      => $lalu,
            'hari_lagi' => $hariLagi,
            'label'     => $label,
        ];
    }

    /**
     * Status semua musim pada $tgl, key = season id.
     *
     * @return Collection<int, array>  tiap item: statusPada() + ['season' => Season]
     */
    public static function statusSemua(Carbon $tgl): Collection
    {
        if (! self::tabelAda()) {
            return collect();
        }

        return self::with('periods')->get()
            ->mapWithKeys(fn (Season $s) => [$s->id => ['season' => $s] + $s->statusPada($tgl)]);
    }
}
