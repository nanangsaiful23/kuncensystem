<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Good;
use App\Models\Season;
use App\Models\SeasonPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Master musim untuk menu Perputaran Barang (/admin/reports/movement/musim).
 * Hak aksesnya ikut menu Perputaran Barang (path reports/movement/*).
 */
class SeasonController extends Controller
{
    /** Saran nama musim di formulir tambah. */
    public const SARAN = [
        'Ramadan & Lebaran', 'Idul Adha', 'Tahun Ajaran Baru', 'Natal & Tahun Baru',
        'Musim Hujan', 'Musim Kemarau', '17 Agustus', 'Imlek', 'Valentine',
    ];

    /** GET /admin/reports/movement/musim */
    public function index()
    {
        $tabelAda = Season::pastikanTabel();
        $now      = Carbon::now();

        $seasons     = $tabelAda ? Season::with('periods')->orderBy('name')->get() : collect();
        $jumlahJenis = $tabelAda
            ? DB::table('types')->whereNull('deleted_at')->whereNotNull('season_id')->where('season_id', '>', 0)
                ->groupBy('season_id')->selectRaw('season_id, COUNT(*) n')->pluck('n', 'season_id')
            : collect();
        $jumlahBarang = $tabelAda ? $this->jumlahBarangPerMusim() : collect();

        // Jenis yang ditandai "musiman" di Penataan Kategori tapi belum dihubungkan ke musim.
        $jenisTanpaMusim = $tabelAda
            ? DB::table('types')->leftJoin('categories', 'categories.id', '=', 'types.category_id')
                ->whereNull('types.deleted_at')->where('types.tipe_kontrol', 'musiman')
                ->where(fn ($q) => $q->whereNull('types.season_id')->orWhere('types.season_id', 0))
                ->orderBy('types.name')->get(['types.id', 'types.name', 'categories.name AS kategori'])
            : collect();

        return view('admin.season.index', [
            'tabelAda'        => $tabelAda,
            'seasons'         => $seasons,
            'status'          => $seasons->mapWithKeys(fn ($s) => [$s->id => $s->statusPada($now)]),
            'jumlahJenis'     => $jumlahJenis,
            'jumlahBarang'    => $jumlahBarang,
            'jenisTanpaMusim' => $jenisTanpaMusim,
            'saran'           => self::SARAN,
        ]);
    }

    /** POST /admin/reports/movement/musim */
    public function store(Request $request)
    {
        $data = $this->validasiMusim($request, true);
        if (!Season::tabelAda()) {
            return redirect()->back()->with('error', 'Tabel musim belum dibuat. Buka ulang halaman ini lalu coba lagi.');
        }

        $season = DB::transaction(function () use ($data, $request) {
            $season = Season::create($data);
            $season->periods()->create([
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
            ]);

            return $season;
        });

        return redirect()->route('admin.seasons.edit', $season->id)
            ->with('success', 'Musim "' . $season->name . '" dibuat. Sekarang pilih jenis barang yang termasuk musim ini.');
    }

    /** GET /admin/reports/movement/musim/{id} */
    public function edit(int $id)
    {
        if (!Season::pastikanTabel()) {
            return redirect()->route('admin.seasons.index')->with('error', 'Tabel musim belum bisa dibuat.');
        }
        $season = Season::with('periods')->findOrFail($id);

        $types = DB::table('types')
            ->leftJoin('categories', 'categories.id', '=', 'types.category_id')
            ->leftJoin('seasons', 'seasons.id', '=', 'types.season_id')
            ->whereNull('types.deleted_at')
            ->orderByRaw("types.season_id = ? DESC", [$id])
            ->orderByRaw("types.tipe_kontrol = 'musiman' DESC")
            ->orderBy('types.name')
            ->get(['types.id', 'types.name', 'types.tipe_kontrol', 'types.season_id', 'categories.name AS kategori', 'seasons.name AS musim_lain']);

        $jumlahBarangJenis = DB::table('goods')->whereNull('deleted_at')->where('is_discontinued', 0)
            ->whereNotNull('type_id')->groupBy('type_id')->selectRaw('type_id, COUNT(*) n')->pluck('n', 'type_id');

        // Barang yang musimnya diatur sendiri (bukan lewat jenis).
        $barangLangsung = $this->barangPengecualian()
            ->where('goods.season_id', $id)->get();
        // Barang di jenis musim ini yang dikecualikan (bukan musiman).
        $barangDikecualikan = $this->barangPengecualian()
            ->where('goods.season_id', 0)->where('types.season_id', $id)->get();

        return view('admin.season.edit', [
            'season'             => $season,
            'status'             => $season->statusPada(Carbon::now()),
            'types'              => $types,
            'jumlahBarangJenis'  => $jumlahBarangJenis,
            'barangLangsung'     => $barangLangsung,
            'barangDikecualikan' => $barangDikecualikan,
        ]);
    }

    /** POST /admin/reports/movement/musim/{id} — data musim + daftar jenisnya */
    public function update(Request $request, int $id)
    {
        $season = Season::findOrFail($id);
        $data   = $this->validasiMusim($request, false);
        $request->validate(['type_ids' => 'nullable|array', 'type_ids.*' => 'integer']);

        $typeIds = array_values(array_unique(array_map('intval', $request->input('type_ids', []))));

        DB::transaction(function () use ($season, $data, $typeIds) {
            $season->update($data);

            // Jenis yang tidak dicentang lagi dilepas dari musim ini.
            $lepas = DB::table('types')->where('season_id', $season->id);
            foreach (array_chunk($typeIds, 500) as $chunk) {
                $lepas->whereNotIn('id', $chunk);
            }
            $lepas->update(['season_id' => null, 'updated_at' => now()]);

            foreach (array_chunk($typeIds, 500) as $chunk) {
                DB::table('types')->whereIn('id', $chunk)->update(['season_id' => $season->id, 'updated_at' => now()]);
            }
        });

        return redirect()->route('admin.seasons.edit', $season->id)->with('success', 'Musim disimpan.');
    }

    /** POST /admin/reports/movement/musim/{id}/hapus */
    public function destroy(int $id)
    {
        $season = Season::findOrFail($id);

        DB::transaction(function () use ($season) {
            DB::table('types')->where('season_id', $season->id)->update(['season_id' => null, 'updated_at' => now()]);
            DB::table('goods')->where('season_id', $season->id)->update(['season_id' => null]);
            $season->periods()->delete();
            $season->delete();
        });

        return redirect()->route('admin.seasons.index')->with('success', 'Musim "' . $season->name . '" dihapus. Jenis & barangnya tidak lagi dianggap musiman.');
    }

    /** POST /admin/reports/movement/musim/{id}/periode */
    public function addPeriod(Request $request, int $id)
    {
        $season = Season::findOrFail($id);
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $season->periods()->create($request->only('start_date', 'end_date'));

        return redirect()->route('admin.seasons.edit', $season->id)->with('success', 'Tanggal musim ditambahkan.');
    }

    /** POST /admin/reports/movement/musim/periode/{pid}/hapus */
    public function deletePeriod(int $pid)
    {
        $period = SeasonPeriod::findOrFail($pid);
        $period->delete();

        return redirect()->route('admin.seasons.edit', $period->season_id)->with('success', 'Tanggal musim dihapus.');
    }

    /** POST /admin/reports/movement/musim/barang/{goodId}/ikut-jenis — hapus pengaturan musim per barang */
    public function resetGood(int $goodId)
    {
        DB::table('goods')->where('id', $goodId)->update(['season_id' => null]);

        return redirect()->back()->with('success', 'Musim barang dikembalikan: ikut jenisnya.');
    }

    private function validasiMusim(Request $request, bool $baru): array
    {
        $rules = [
            'name'      => 'required|string|max:100',
            'prep_days' => 'required|integer|min:0|max:180',
            'note'      => 'nullable|string|max:500',
        ];
        if ($baru) {
            $rules['start_date'] = 'required|date';
            $rules['end_date']   = 'required|date|after_or_equal:start_date';
        }
        $request->validate($rules, [
            'name.required'           => 'Nama musim wajib diisi.',
            'start_date.required'     => 'Tanggal mulai musim wajib diisi.',
            'end_date.required'       => 'Tanggal selesai musim wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        return [
            'name'           => trim($request->input('name')),
            'repeats_yearly' => $request->boolean('repeats_yearly'),
            'prep_days'      => (int) $request->input('prep_days'),
            'note'           => $request->input('note'),
        ];
    }

    /** Jumlah barang aktif per musim (lewat jenis atau diatur per barang). */
    private function jumlahBarangPerMusim()
    {
        return DB::table('goods')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->whereNull('goods.deleted_at')->where('goods.is_discontinued', 0)
            ->selectRaw('CASE WHEN goods.season_id IS NULL THEN types.season_id WHEN goods.season_id = 0 THEN NULL ELSE goods.season_id END AS sid, COUNT(*) n')
            ->groupBy('sid')
            ->havingRaw('sid IS NOT NULL')
            ->pluck('n', 'sid');
    }

    private function barangPengecualian()
    {
        return Good::leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->whereNull('goods.deleted_at')
            ->select('goods.id', 'goods.code', 'goods.name', 'types.name AS tipe', 'goods.last_stock')
            ->orderBy('goods.name');
    }
}
