<?php

namespace App\Services\PenataanKategori;

use Illuminate\Support\Facades\DB;

/**
 * Tahap 3 penataan kategori: memetakan setiap barang ke kategori dan jenis
 * barang baru.
 *
 * - Barang yang sudah punya jenis: kategorinya mengikuti kategori induk jenis.
 * - Barang tanpa jenis: jenis ditebak dari kata di nama barang (aturan.php).
 *   Barang yang tidak cocok dengan aturan mana pun TIDAK ditebak; masuk daftar
 *   "tidak terpetakan".
 *
 * Penerapan ke database hanya dari CSV yang sudah diperiksa pemilik
 * (terapkanCsv), tidak menghitung ulang. Sebelum menulis, kolom
 * id/name/type_id/category_id semua barang disalin ke tabel bak_tahap3_goods_*,
 * yang bisa dipakai untuk memulihkan (pulihkan).
 *
 * Dipakai oleh command barang:petakan-kategori dan halaman admin
 * Penataan Kategori (server tanpa terminal).
 */
class PemetaanBarang
{
    public const BACKUP_PREFIX = 'bak_tahap3_goods_';

    public const KOLOM_PEMETAAN = [
        'id', 'nama', 'stok', 'kategori lama', 'jenis lama', 'kategori baru', 'jenis baru', 'tingkat keyakinan', 'alasan',
        'nama baru', 'status', 'kategori_lama_id', 'jenis_lama_id',
    ];

    public const KOLOM_TIDAK_TERPETAKAN = [
        'id', 'nama', 'stok', 'kategori lama', 'jenis lama', 'usulan jenis baru', 'usulan kategori', 'alasan', 'status',
        'jenis baru', 'nama baru', 'kategori_lama_id', 'jenis_lama_id',
    ];

    private array $categories = [];   // id => [code, name, deleted]
    private array $categoryByCode = [];
    private array $types = [];        // id => [name, category_id, deleted]
    private array $typeByName = [];   // nama huruf kecil => id (jenis aktif)
    private array $rules = [];
    private array $ruleErrors = [];

    public function __construct()
    {
        foreach (DB::table('categories')->get(['id', 'code', 'name', 'deleted_at']) as $c) {
            $this->categories[$c->id] = ['code' => $c->code, 'name' => $c->name, 'deleted' => $c->deleted_at !== null];
            $this->categoryByCode[$c->code] = $c->id;
        }

        foreach (DB::table('types')->get(['id', 'name', 'category_id', 'deleted_at']) as $t) {
            $this->types[$t->id] = ['name' => $t->name, 'category_id' => $t->category_id, 'deleted' => $t->deleted_at !== null];
            if ($t->deleted_at === null) {
                $this->typeByName[mb_strtolower(trim($t->name))] = $t->id;
            }
        }

        foreach (require __DIR__ . '/aturan.php' as $i => $rule) {
            if (isset($rule['jenis']) && ! isset($this->typeByName[mb_strtolower($rule['jenis'])])) {
                $this->ruleErrors[] = 'Aturan #' . ($i + 1) . ': jenis "' . $rule['jenis'] . '" tidak ada.';
                continue;
            }
            if (isset($rule['usulan']) && isset($this->typeByName[mb_strtolower($rule['usulan'][0])])) {
                // Jenis usulan sudah disetujui dan dibuat: perlakukan sebagai jenis biasa.
                $rule['jenis'] = $rule['usulan'][0];
                unset($rule['usulan']);
            }
            foreach ((array) ($rule['dari'] ?? []) as $code) {
                if (! isset($this->categoryByCode[$code])) {
                    $this->ruleErrors[] = 'Aturan #' . ($i + 1) . ': kategori "' . $code . '" tidak ada.';
                }
            }
            $rule['regex'] = '/' . $rule['pola'] . '/u';
            $this->rules[] = $rule;
        }
    }

    /** Masalah pada aturan.php (jenis/kategori yang tidak ada). */
    public function ruleErrors(): array
    {
        return $this->ruleErrors;
    }

    /**
     * Usulan untuk semua barang (termasuk yang terhapus), urut prioritas:
     * berstok dulu, Peralatan Rumah Tangga dulu, lalu kategori dan nama.
     *
     * @return array{pemetaan: array, tidak_terpetakan: array, ringkasan: array}
     */
    public function usulan(): array
    {
        $goods = DB::table('goods')->get(['id', 'name', 'category_id', 'type_id', 'last_stock', 'is_discontinued', 'deleted_at']);

        $mapped = $unmapped = [];
        $summary = [];

        foreach ($goods as $g) {
            $p = $this->petakan($g);
            $oldCat = $this->categories[$g->category_id] ?? ['code' => '?', 'name' => '(tanpa kategori)'];
            $key = $oldCat['name'];
            $summary[$key] ??= ['kategori' => $key, 'barang' => 0, 'berstok' => 0, 'terpetakan' => 0, 'tidak_terpetakan' => 0, 'tetap' => 0];
            $summary[$key]['barang']++;
            if ($g->deleted_at === null && $g->last_stock > 0) {
                $summary[$key]['berstok']++;
            }

            $base = [
                'id'            => $g->id,
                'nama'          => $g->name,
                'stok'          => $this->angka($g->last_stock),
                'kategori lama' => $oldCat['name'],
                'jenis lama'    => $g->type_id ? ($this->types[$g->type_id]['name'] ?? '') : '',
            ];
            $status = $g->deleted_at !== null ? 'terhapus' : ($g->is_discontinued ? 'nonaktif' : 'aktif');
            $sort = [($g->deleted_at === null && $g->last_stock > 0) ? 0 : 1, $oldCat['code'] === 'HOUSEWARE' ? 0 : 1, $oldCat['name'], mb_strtolower((string) $g->name)];

            if ($p['jenis_id'] === null) {
                $summary[$key]['tidak_terpetakan']++;
                $unmapped[] = ['_sort' => $sort] + $base + [
                    'usulan jenis baru' => $p['usulan'][0] ?? '',
                    'usulan kategori'   => isset($p['usulan']) ? $this->categories[$this->categoryByCode[$p['usulan'][1]]]['name'] : '',
                    'alasan'            => $p['alasan'],
                    'status'            => $status,
                    'kategori_lama_id'  => $g->category_id,
                    'jenis_lama_id'     => $g->type_id ?? '',
                ];
                continue;
            }

            $newCat = $this->types[$p['jenis_id']]['category_id'];
            if ($newCat == $g->category_id && $p['jenis_id'] == $g->type_id && $p['nama_baru'] === $g->name) {
                $summary[$key]['tetap']++;
                continue;
            }

            $summary[$key]['terpetakan']++;
            $mapped[] = ['_sort' => $sort] + $base + [
                'kategori baru'     => $this->categories[$newCat]['name'] ?? '?',
                'jenis baru'        => $this->types[$p['jenis_id']]['name'],
                'tingkat keyakinan' => $p['yakin'],
                'alasan'            => $p['alasan'],
                'nama baru'         => $p['nama_baru'] === $g->name ? '' : $p['nama_baru'],
                'status'            => $status,
                'kategori_lama_id'  => $g->category_id,
                'jenis_lama_id'     => $g->type_id ?? '',
            ];
        }

        $bySort = fn ($a, $b) => $a['_sort'] <=> $b['_sort'];
        usort($mapped, $bySort);
        usort($unmapped, $bySort);
        $strip = fn ($rows) => array_map(function ($r) { unset($r['_sort']); return $r; }, $rows);

        ksort($summary);

        return ['pemetaan' => $strip($mapped), 'tidak_terpetakan' => $strip($unmapped), 'ringkasan' => array_values($summary)];
    }

    /** Usulan untuk satu barang. */
    public function petakan(object $g): array
    {
        // 1. Sudah punya jenis aktif berkategori: ikut jenisnya.
        if ($g->type_id && isset($this->types[$g->type_id])) {
            $t = $this->types[$g->type_id];
            if (! $t['deleted'] && $t['category_id']) {
                $pindah = $t['category_id'] != $g->category_id;

                return ['jenis_id' => $g->type_id, 'nama_baru' => $g->name, 'yakin' => 'tinggi',
                        'alasan' => $pindah ? 'kategori mengikuti jenis yang sudah ada' : 'jenis sudah ada'];
            }

            return ['jenis_id' => null, 'alasan' => 'jenis lama "' . $t['name'] . '" belum punya kategori induk'];
        }

        // 2. Tanpa jenis: cari dari kata di nama barang.
        $name = mb_strtolower(trim((string) $g->name));
        $code = $this->categories[$g->category_id]['code'] ?? null;

        // Kata kunci yang posisinya paling awal di nama menang (posisi sama:
        // aturan yang ditulis lebih dulu). Aturan 'merek' hanya dipakai bila
        // tidak ada kata produk yang cocok.
        $best = null;
        foreach ($this->rules as $order => $rule) {
            if (isset($rule['dari']) && ! in_array($code, (array) $rule['dari'], true)) {
                continue;
            }
            if (! preg_match($rule['regex'], $name, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $score = [! empty($rule['merek']) ? 1 : 0, $m[0][1], $order];
            if ($best === null || $score < $best['score']) {
                $best = ['score' => $score, 'rule' => $rule, 'kata' => trim($m[0][0]), 'pos' => $m[0][1]];
            }
        }

        if ($best !== null) {
            $rule = $best['rule'];
            $kata = $best['kata'];
            $yakin = $best['pos'] === 0 && empty($rule['merek']) ? 'tinggi' : 'sedang';

            if (isset($rule['usulan'])) {
                return ['jenis_id' => null, 'usulan' => $rule['usulan'],
                        'alasan' => 'kata "' . $kata . '": jenis "' . $rule['usulan'][0] . '" belum ada, perlu persetujuan'];
            }

            $typeId = $this->typeByName[mb_strtolower($rule['jenis'])];

            return ['jenis_id' => $typeId, 'nama_baru' => $this->buangAwalan((string) $g->name, $this->types[$typeId]['name']),
                    'yakin' => $yakin, 'alasan' => 'kata "' . $kata . '" ' . ($yakin === 'tinggi' ? 'di awal nama' : 'di dalam nama')];
        }

        return ['jenis_id' => null, 'alasan' => 'tidak ada kata kunci yang cocok'];
    }

    /**
     * Nama tampil = jenis + nama. Supaya tidak menjadi "Ember ember ikawa",
     * kata di awal nama yang sudah ada di nama jenis dibuang, selama nama
     * tidak menjadi kosong.
     */
    public function buangAwalan(string $name, string $typeName): string
    {
        $norm = fn ($w) => strtr(mb_strtolower($w), ['alumunium' => 'aluminium', 'cutton' => 'cotton']);
        $typeWords = array_map($norm, preg_split('/[\s\/&+,]+/u', $typeName, -1, PREG_SPLIT_NO_EMPTY));

        $words = preg_split('/(\s+)/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        $i = 0;
        while ($i < count($words) - 1 && in_array($norm(trim($words[$i], '/&+,')), $typeWords, true)) {
            $i++;
        }

        return $i === 0 ? $name : implode(' ', array_slice($words, $i));
    }

    // ------------------------------------------------------------------
    // CSV
    // ------------------------------------------------------------------

    /** Tulis CSV yang terbaca rapi oleh Excel berbahasa Indonesia (pemisah ;, UTF-8 BOM). */
    public static function tulisCsv(string $path, array $header, array $rows): void
    {
        $f = fopen($path, 'w');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, $header, ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($f, array_map(fn ($h) => $row[$h] ?? '', $header), ';', '"', '');
        }
        fclose($f);
    }

    /** Baca CSV (pemisah ; atau , ; UTF-8 atau ANSI dari Excel). Kunci = nama kolom huruf kecil. */
    public static function bacaCsv(string $path): array
    {
        $raw = file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }
        $firstLine = strtok($raw, "\n");
        $delim = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $f = fopen('php://memory', 'r+');
        fwrite($f, $raw);
        rewind($f);
        $header = array_map(fn ($h) => mb_strtolower(trim($h)), fgetcsv($f, null, $delim, '"', '') ?: []);
        $rows = [];
        while (($line = fgetcsv($f, null, $delim, '"', '')) !== false) {
            if ($line === [null] || count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), ''));
        }
        fclose($f);

        return $rows;
    }

    // ------------------------------------------------------------------
    // Terapkan & pulihkan
    // ------------------------------------------------------------------

    /** Salin id/name/type_id/category_id semua barang ke tabel backup baru. */
    public function buatBackup(): string
    {
        // CREATE TABLE meng-commit transaksi yang sedang terbuka secara diam-diam;
        // tolak daripada perubahan berikutnya tidak lagi bisa dibatalkan.
        if (DB::transactionLevel() > 0) {
            throw new \LogicException('Backup tidak boleh dibuat di dalam transaksi database (route harus tanpa AtomicRequest).');
        }

        $table = self::BACKUP_PREFIX . date('Ymd_His');
        DB::statement("CREATE TABLE `$table` AS SELECT `id`, `name`, `type_id`, `category_id` FROM `goods`");

        return $table;
    }

    /** @return string[] nama tabel backup, terbaru dulu */
    public static function daftarBackup(): array
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'like', str_replace('_', '\_', self::BACKUP_PREFIX) . '%')
            ->orderBy('table_name', 'desc')
            ->pluck('table_name')
            ->all();
    }

    /**
     * Terapkan CSV hasil pemeriksaan. Hanya baris yang kategori dan jenis
     * barangnya masih sama dengan kolom kategori_lama_id / jenis_lama_id yang
     * diubah; nama hanya diubah bila nama barang masih sama dengan kolom "nama".
     *
     * @return array{backup: string, diubah: int, nama_diubah: int, dilewati: array}
     */
    public function terapkanCsv(string $path): array
    {
        $rows = self::bacaCsv($path);
        if ($rows && ! array_key_exists('jenis baru', $rows[0])) {
            throw new \InvalidArgumentException('Kolom "jenis baru" tidak ditemukan. Pakai CSV usulan pemetaan.');
        }

        $plan = [];
        $skipped = [];
        foreach ($rows as $n => $r) {
            $line = $n + 2;
            $id = (int) ($r['id'] ?? 0);
            $jenis = trim((string) ($r['jenis baru'] ?? ''));
            if ($jenis === '') {
                continue;   // belum diisi pemilik (mis. di CSV tidak terpetakan)
            }
            if (! $id) {
                $skipped[] = "baris $line: id kosong";
                continue;
            }
            $typeId = $this->typeByName[mb_strtolower($jenis)] ?? null;
            if (! $typeId || ! $this->types[$typeId]['category_id']) {
                $skipped[] = "baris $line (id $id): jenis \"$jenis\" tidak ada";
                continue;
            }
            $plan[$id] = [
                'line' => $line, 'type_id' => $typeId, 'category_id' => $this->types[$typeId]['category_id'],
                'old_cat' => (int) ($r['kategori_lama_id'] ?? 0), 'old_type' => ($r['jenis_lama_id'] ?? '') === '' ? null : (int) $r['jenis_lama_id'],
                'nama' => (string) ($r['nama'] ?? ''), 'nama_baru' => trim((string) ($r['nama baru'] ?? '')),
            ];
        }

        $backup = $this->buatBackup();
        $changed = $renamed = 0;

        DB::transaction(function () use ($plan, &$skipped, &$changed, &$renamed) {
            foreach (array_chunk(array_keys($plan), 500) as $ids) {
                $current = DB::table('goods')->whereIn('id', $ids)->get(['id', 'name', 'type_id', 'category_id'])->keyBy('id');
                foreach ($ids as $id) {
                    $p = $plan[$id];
                    $g = $current[$id] ?? null;
                    if (! $g) {
                        $skipped[] = "baris {$p['line']} (id $id): barang tidak ditemukan";
                        continue;
                    }
                    if ((int) $g->category_id !== $p['old_cat'] || ($g->type_id === null ? null : (int) $g->type_id) !== $p['old_type']) {
                        $skipped[] = "baris {$p['line']} (id $id): kategori/jenis barang sudah berubah sejak CSV dibuat";
                        continue;
                    }

                    $update = ['type_id' => $p['type_id'], 'category_id' => $p['category_id']];
                    if ($p['nama_baru'] !== '' && $p['nama_baru'] !== $g->name) {
                        if ($g->name === $p['nama']) {
                            $update['name'] = $p['nama_baru'];
                            $renamed++;
                        } else {
                            $skipped[] = "baris {$p['line']} (id $id): nama tidak diubah karena nama di CSV berbeda dengan nama sekarang";
                        }
                    }
                    DB::table('goods')->where('id', $id)->update($update);
                    $changed++;
                }
            }
        });

        return ['backup' => $backup, 'diubah' => $changed, 'nama_diubah' => $renamed, 'dilewati' => $skipped];
    }

    /** Kembalikan nama/jenis/kategori barang dari tabel backup. */
    public function pulihkan(string $table): int
    {
        if (! in_array($table, self::daftarBackup(), true)) {
            throw new \InvalidArgumentException("Tabel backup $table tidak ada.");
        }

        return DB::update("UPDATE `goods` g JOIN `$table` b ON b.`id` = g.`id`
                           SET g.`name` = b.`name`, g.`type_id` = b.`type_id`, g.`category_id` = b.`category_id`
                           WHERE NOT (g.`name` <=> b.`name`) OR NOT (g.`type_id` <=> b.`type_id`) OR NOT (g.`category_id` <=> b.`category_id`)");
    }

    private function angka($n): string
    {
        $n = round((float) $n, 2);

        return str_replace('.', ',', rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.'));
    }
}
