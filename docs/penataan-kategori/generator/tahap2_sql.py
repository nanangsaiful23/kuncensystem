"""Membuat file SQL Tahap 2 dari tahap2_data.py (+ data jenis di DB lokal untuk menentukan jenis basis)."""
import subprocess, sys, os, re, collections, unicodedata
sys.path.insert(0, os.path.dirname(__file__))
from tahap2_data import AREAS, CATEGORIES, RETIRED, M

MYSQL = r'C:\xampp7\mysql\bin\mysql.exe'
def q(sql):
    out = subprocess.run([MYSQL, '-uroot', 'kuncen', '-N', '-B', '-e', sql], capture_output=True, text=True, encoding='utf-8', check=True).stdout
    return [l.split('\t') for l in out.splitlines() if l]

def lit(s):
    return "'" + str(s).replace('\\', '\\\\').replace("'", "''") + "'"

# Status DB lokal sebelum Tahap 2 (hanya dibaca)
types = {int(r[0]): dict(name=r[1], total=int(r[2])) for r in
         q('SELECT t.id, t.name, COUNT(g.id) FROM types t LEFT JOIN goods g ON g.type_id=t.id WHERE t.deleted_at IS NULL GROUP BY t.id')}
cats = {int(r[0]): r[1] for r in q('SELECT id, code FROM categories')}

newtype_cat = {}
for c in CATEGORIES:
    for t in c[7]:
        newtype_cat[t.lstrip('*')] = c[0]
by_new = collections.defaultdict(list)
for tid, (nt, aw, note) in M.items():
    assert nt != '?', f'jenis {tid} belum diputuskan'
    by_new[nt].append(tid)
base = {nt: max(ids, key=lambda i: (types[i]['total'], -i)) for nt, ids in by_new.items()}

EQ = {'alumunium': 'aluminium', 'cutton': 'cotton'}
def toks(s): return [w for w in re.split(r'[\s/]+', s.lower()) if w and w not in ('+', '&', ',')]
def awalan(old, new, manual):
    if manual is not None: return manual
    newt = {EQ.get(w, w) for w in toks(new)}
    return ' '.join(w for w in toks(old) if EQ.get(w, w) not in newt)
def slug(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode()
    return re.sub(r'[^A-Z0-9]+', '-', s.upper()).strip('-')

COLORS = {'BABYCARE': '#9BD18B', 'BABYGEAR': '#5E9C57', 'CLEANTOOL': '#6FA8B5', 'BUCKET': '#F4A261', 'STORAGEBOX': '#C08552',
          'HOMEGOODS': '#E76F51', 'COOKWARE': '#B5838D', 'TABLEWARE': '#FFB4A2', 'SEMBAKO': '#E9C46A'}

PREFIX = '2026-11-18-tahap2-isi-kategori-jenis'
up, cek, down = [], [], []
W = up.append

W(f"""-- Pengganti:
--   php artisan db:seed --class=PenataanKategoriTahap2Seeder
--
-- TAHAP 2 penataan kategori: isi Area, Kategori, dan Jenis Barang baru,
-- serta gabungkan jenis barang yang dobel/terpecah.
--
-- SYARAT: docs/sql/2026-11-18-tahap1-struktur-kategori.sql sudah dijalankan.
-- SEBELUM MENJALANKAN: export seluruh database lewat phpMyAdmin dan simpan.
--
-- Jalankan di phpMyAdmin (tab SQL): tempel seluruh isi file lalu klik Go.
-- Aman bila tidak sengaja dijalankan dua kali (perubahan tidak dobel).
-- Setelah itu jalankan {PREFIX}-cek.sql untuk memeriksa hasil.
--
-- Yang diubah:
--   - main_categories (Area): ganti nama + tambah area baru.
--   - categories: ganti nama (id & kode tetap), isi Area & habis pakai,
--     tambah kategori baru; kategori yang dipecah diberi akhiran "(lama)".
--   - types: ganti nama, isi kategori induk, gabungkan yang dobel, tambah jenis baru.
--   - goods: HANYA kolom type_id dan name (diberi awalan) untuk barang yang
--     jenisnya digabung, supaya nama tampil di struk tetap sama.
--     Kategori barang BELUM dipindah (itu Tahap 3).
-- Tidak mengubah harga, stok, transaksi, loading, maupun jurnal.
--
-- Untuk membatalkan: {PREFIX}-BATAL.sql (sebelum Tahap 3 dijalankan).

-- 0. Backup tabel yang diubah (dibuat sekali; tidak ditimpa bila dijalankan ulang)
CREATE TABLE IF NOT EXISTS `bak_tahap2_goods` AS SELECT `id`, `name`, `type_id`, `category_id` FROM `goods`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_types` AS SELECT * FROM `types`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_categories` AS SELECT * FROM `categories`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_main_categories` AS SELECT * FROM `main_categories`;

START TRANSACTION;

-- 1. Area""")
for code, name, eng, oid in AREAS:
    if oid:
        W(f"UPDATE `main_categories` SET `code` = {lit(code)}, `name` = {lit(name)}, `eng_name` = {lit(eng)} WHERE `id` = {oid};")
for code, name, eng, oid in AREAS:
    if not oid:
        W(f"INSERT INTO `main_categories` (`code`, `name`, `eng_name`, `created_at`) SELECT {lit(code)}, {lit(name)}, {lit(eng)}, NOW() "
          f"FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `main_categories` WHERE `code` = {lit(code)});")

AREA = lambda a: f"(SELECT `id` FROM `main_categories` WHERE `code` = {lit(a)})"
CAT = lambda c: f"(SELECT `id` FROM `categories` WHERE `code` = {lit(c)})"

W("\n-- 2. Kategori: ganti nama (id & kode tetap), isi Area dan habis pakai")
for code, name, area, hp, oid, oldname, eng, tl in CATEGORIES:
    if oid:
        assert cats[oid] == code, (oid, code)
        W(f"UPDATE `categories` SET `name` = {lit(name)}, `eng_name` = {lit(eng)}, `habis_pakai` = {hp}, "
          f"`main_category_id` = {AREA(area)} WHERE `id` = {oid}; -- dulu: {oldname}")
W("\n-- 3. Kategori baru")
for code, name, area, hp, oid, oldname, eng, tl in CATEGORIES:
    if not oid:
        W(f"INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) "
          f"SELECT {AREA(area)}, {lit(code)}, {lit(name)}, {lit(eng)}, (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), "
          f"{lit(COLORS[code])}, {hp}, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = {lit(code)});")
W("\n-- 4. Kategori lama yang dipecah/digabung: diberi akhiran (lama); barangnya dipindah di Tahap 3")
for oid, code, oldname, area, note in RETIRED:
    assert cats[oid] == code
    W(f"UPDATE `categories` SET `name` = {lit(oldname + ' (lama)')}, `main_category_id` = {AREA(area)} WHERE `id` = {oid}; -- {note}")

W("\n-- 5. Barang pada jenis yang digabung/diganti nama: pindah jenis dan beri awalan nama.\n"
  "--    Syarat nama jenis lama di setiap baris membuat perintah ini tidak dobel bila diulang.")
renamed_goods = 0
# Jenis basis lebih dulu (barangnya belum bercampur dengan barang pindahan),
# baru jenis yang digabung ke basis.
for tid in sorted(M, key=lambda i: (base[M[i][0]] != i, i)):
    nt, aw, note = M[tid]
    old = types[tid]['name']
    a = awalan(old, nt, aw)
    b = base[nt]
    sets = []
    if a: sets.append(f"g.`name` = CONCAT({lit(a + ' ')}, g.`name`)")
    if b != tid: sets.append(f"g.`type_id` = {b}")
    if not sets or types[tid]['total'] == 0: continue
    renamed_goods += types[tid]['total'] if a else 0
    W(f"UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET {', '.join(sets)} "
      f"WHERE t.`id` = {tid} AND t.`name` = {lit(old)}; -- {old} -> {nt}")

W("\n-- 6. Jenis basis: ganti nama dan isi kategori induk")
for nt, b in sorted(base.items(), key=lambda x: x[1]):
    W(f"UPDATE `types` SET `name` = {lit(nt)}, `category_id` = {CAT(newtype_cat[nt])} WHERE `id` = {b}; -- dulu: {types[b]['name']}")

merged = sorted(t for t in M if base[M[t][0]] != t)
W("\n-- 7. Jenis lama yang sudah digabung (sudah tidak punya barang): dinonaktifkan")
W(f"UPDATE `types` SET `deleted_at` = NOW() WHERE `deleted_at` IS NULL AND `id` IN ({', '.join(map(str, merged))}) "
  f"AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`type_id` = `types`.`id`);")

W("\n-- 8. Jenis baru (masih kosong; barangnya diisi di Tahap 3)")
newcount = 0
for c in CATEGORIES:
    for t in c[7]:
        tn = t.lstrip('*')
        if tn in base: continue
        newcount += 1
        code = 'K2-' + slug(tn)
        W(f"INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT {CAT(c[0])}, {lit(code)}, {lit(tn)}, NOW() "
          f"FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = {lit(code)});")
W("\nCOMMIT;\n")

# ---------- cek ----------
cek.append(f"""-- Pemeriksaan setelah {PREFIX}.sql. Semua query harus menghasilkan 0 baris,
-- kecuali yang disebut lain. Hanya membaca, tidak mengubah apa pun.

-- 1. Jenis aktif yang belum punya kategori induk (jenis yang dibuat setelah salinan data lokal)
SELECT `id`, `name` FROM `types` WHERE `deleted_at` IS NULL AND `category_id` IS NULL;

-- 2. Barang (termasuk yang terhapus) yang menunjuk jenis nonaktif
SELECT g.`id`, g.`name`, g.`type_id` FROM `goods` g JOIN `types` t ON t.`id` = g.`type_id` WHERE t.`deleted_at` IS NOT NULL;

-- 3. Kategori aktif tanpa Area
SELECT `id`, `name` FROM `categories` WHERE `deleted_at` IS NULL AND `main_category_id` IS NULL;

-- 4. Nama jenis aktif yang dobel
SELECT LOWER(`name`) AS jenis, COUNT(*) FROM `types` WHERE `deleted_at` IS NULL GROUP BY LOWER(`name`) HAVING COUNT(*) > 1;

-- 5. (Informasi, bukan 0) Ringkasan per Area
SELECT m.`code`, m.`name` AS area, COUNT(DISTINCT c.`id`) AS kategori, COUNT(DISTINCT t.`id`) AS jenis
FROM `main_categories` m LEFT JOIN `categories` c ON c.`main_category_id` = m.`id` AND c.`deleted_at` IS NULL
LEFT JOIN `types` t ON t.`category_id` = c.`id` AND t.`deleted_at` IS NULL
WHERE m.`deleted_at` IS NULL GROUP BY m.`id` ORDER BY m.`code`;

-- 6. (Informasi) Jumlah barang yang namanya berubah = harus sekitar {renamed_goods}
SELECT COUNT(*) FROM `goods` g JOIN `bak_tahap2_goods` b ON b.`id` = g.`id` WHERE NOT (g.`name` <=> b.`name`);
""")

# ---------- batal ----------
down.append(f"""-- MEMBATALKAN Tahap 2 ({PREFIX}.sql), memakai tabel backup bak_tahap2_*.
-- Hanya dijalankan SEBELUM Tahap 3 (pemindahan kategori barang) dijalankan.
-- Mengembalikan nama & jenis barang, tabel types, categories, dan main_categories
-- persis seperti sebelum Tahap 2.

START TRANSACTION;

UPDATE `goods` g JOIN `bak_tahap2_goods` b ON b.`id` = g.`id` SET g.`name` = b.`name`, g.`type_id` = b.`type_id`;

DELETE FROM `types` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_types`)
  AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`type_id` = `types`.`id`);
UPDATE `types` t JOIN `bak_tahap2_types` b ON b.`id` = t.`id`
SET t.`category_id` = b.`category_id`, t.`code` = b.`code`, t.`name` = b.`name`, t.`eng_name` = b.`eng_name`,
    t.`tipe_kontrol` = b.`tipe_kontrol`, t.`min_model` = b.`min_model`, t.`max_model` = b.`max_model`,
    t.`updated_at` = b.`updated_at`, t.`deleted_at` = b.`deleted_at`;

DELETE FROM `categories` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_categories`)
  AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`category_id` = `categories`.`id`);
UPDATE `categories` c JOIN `bak_tahap2_categories` b ON b.`id` = c.`id`
SET c.`main_category_id` = b.`main_category_id`, c.`code` = b.`code`, c.`name` = b.`name`, c.`eng_name` = b.`eng_name`,
    c.`unit_id` = b.`unit_id`, c.`color` = b.`color`, c.`habis_pakai` = b.`habis_pakai`,
    c.`updated_at` = b.`updated_at`, c.`deleted_at` = b.`deleted_at`;

DELETE FROM `main_categories` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_main_categories`);
UPDATE `main_categories` m JOIN `bak_tahap2_main_categories` b ON b.`id` = m.`id`
SET m.`code` = b.`code`, m.`name` = b.`name`, m.`eng_name` = b.`eng_name`, m.`description` = b.`description`,
    m.`updated_at` = b.`updated_at`, m.`deleted_at` = b.`deleted_at`;

COMMIT;

-- Setelah yakin, tabel backup boleh dihapus:
--   DROP TABLE `bak_tahap2_goods`, `bak_tahap2_types`, `bak_tahap2_categories`, `bak_tahap2_main_categories`;
""")

outdir = sys.argv[1]
for suffix, body in (('', up), ('-cek', cek), ('-BATAL', down)):
    with open(os.path.join(outdir, PREFIX + suffix + '.sql'), 'w', encoding='utf-8', newline='\r\n') as f:
        f.write('\n'.join(body))
print(f'jenis basis {len(base)}, digabung {len(merged)}, jenis baru {newcount}, barang diberi awalan {renamed_goods}')
