import subprocess, sys, os, re, collections
sys.path.insert(0, os.path.dirname(__file__))
from tahap2_data import AREAS, CATEGORIES, RETIRED, M, DELETED_TYPES
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment
from openpyxl.utils import get_column_letter

MYSQL = r'C:\xampp7\mysql\bin\mysql.exe'
def q(sql):
    out = subprocess.run([MYSQL, '-uroot', 'kuncen', '-N', '-B', '-e', sql], capture_output=True, text=True, encoding='utf-8', check=True).stdout
    return [line.split('\t') for line in out.splitlines() if line]

cats = {int(r[0]): r[1] for r in q('SELECT id, name FROM bak_tahap2_categories')}
mains = {int(r[0]): r[1] for r in q('SELECT id, name FROM bak_tahap2_main_categories')}
types = {}
for r in q('SELECT t.id, t.name, t.deleted_at IS NOT NULL, COUNT(g.id), COALESCE(SUM(g.deleted_at IS NULL),0), COALESCE(SUM(g.deleted_at IS NULL AND g.last_stock>0),0), '
           'COALESCE(GROUP_CONCAT(DISTINCT g.category_id ORDER BY g.category_id),"") FROM bak_tahap2_types t LEFT JOIN (SELECT g.id, b.type_id, g.category_id, g.deleted_at, g.last_stock FROM goods g JOIN bak_tahap2_goods b ON b.id=g.id) g ON g.type_id=t.id GROUP BY t.id'):
    types[int(r[0])] = dict(name=r[1], deleted=r[2] == '1', total=int(r[3]), aktif=int(r[4]), stok=int(r[5]),
                            cats=[int(x) for x in r[6].split(',') if x])
samples = collections.defaultdict(list)
for r in q('SELECT b.type_id, b.name FROM bak_tahap2_goods b JOIN goods g ON g.id=b.id WHERE b.type_id IS NOT NULL AND g.deleted_at IS NULL ORDER BY last_stock DESC'):
    if len(samples[int(r[0])]) < 2:
        samples[int(r[0])].append(r[1])

# jenis baru -> kategori
newtype_cat, newtype_src = {}, {}
for code, name, area, hp, oid, oldname, eng, tlist in CATEGORIES:
    for t in tlist:
        tn = t.lstrip('*')
        assert tn not in newtype_cat, tn
        newtype_cat[tn] = code
        newtype_src[tn] = 'Usulan Claude' if t.startswith('*') else 'Daftar pemilik'
catname = {c[0]: c[1] for c in CATEGORIES}

# validasi
problems = []
for tid, t in types.items():
    if t['deleted']:
        continue
    if tid not in M:
        problems.append(f'jenis {tid} {t["name"]} belum dipetakan')
for tid, (nt, aw, note) in M.items():
    if tid not in types: problems.append(f'id {tid} tidak ada di DB')
    elif nt != '?' and nt not in newtype_cat: problems.append(f'{tid}: jenis baru "{nt}" tidak ada di daftar')
if problems:
    print('\n'.join(problems)); sys.exit(1)

EQ = {'alumunium': 'aluminium', 'cutton': 'cotton'}
def toks(s):
    return [w for w in re.split(r'[\s/]+', s.lower()) if w and w not in ('+', '&', ',')]
def awalan(old, new, manual):
    if manual is not None:
        return manual
    newt = {EQ.get(w, w) for w in toks(new)}
    return ' '.join(w for w in toks(old) if EQ.get(w, w) not in newt)

def disp(typ, name):
    s = typ.lower() + ' ' + name
    return s[:1].upper() + s[1:]

# basis: jenis lama dengan barang terbanyak dipakai ulang (ganti nama), lainnya digabung
by_new = collections.defaultdict(list)
for tid, (nt, aw, note) in M.items():
    if nt != '?':
        by_new[nt].append(tid)
base = {nt: max(ids, key=lambda i: (types[i]['total'], -i)) for nt, ids in by_new.items()}

wb = Workbook()
H = Font(bold=True, color='FFFFFF'); HF = PatternFill('solid', fgColor='305496')
YEL = PatternFill('solid', fgColor='FFF2CC'); RED = PatternFill('solid', fgColor='F8CBAD')
def sheet(ws, header, rows, widths, hl=None):
    ws.append(header)
    for c in ws[1]: c.font = H; c.fill = HF; c.alignment = Alignment(wrap_text=True, vertical='top')
    for r in rows:
        ws.append(r)
        if hl:
            fill = hl(r)
            if fill:
                for c in ws[ws.max_row]: c.fill = fill
    for i, w in enumerate(widths, 1): ws.column_dimensions[get_column_letter(i)].width = w
    ws.freeze_panes = 'A2'; ws.auto_filter.ref = ws.dimensions

# 1. Area & kategori
cnt = {int(r[0]): (int(r[1]), int(r[2])) for r in q('SELECT category_id, SUM(deleted_at IS NULL), SUM(deleted_at IS NULL AND last_stock>0) FROM goods GROUP BY category_id')}
ws = wb.active; ws.title = '1 Area & Kategori'
rows = []
areaname = {a[0]: a[1] for a in AREAS}
for a in AREAS:
    aksi = f'pakai ulang id {a[3]} (dulu "{mains[a[3]]}"), ganti nama' if a[3] else 'area baru'
    rows.append([a[0], a[1], '', '', '', aksi, '', ''])
    for code, name, area, hp, oid, oldname, eng, tl in CATEGORIES:
        if area != a[0]: continue
        if oid:
            aksi = 'tetap (id sama)' if cats[oid] == name else f'ganti nama, id {oid} tetap'
            n, s = cnt.get(oid, (0, 0))
        else:
            aksi, n, s = 'kategori baru', '', ''
        rows.append(['', '', name, code, 'Ya' if hp else 'Tidak', aksi, oldname, n, s])
    for oid, code, oldname, area, note in RETIRED:
        if area != a[0]: continue
        n, s = cnt.get(oid, (0, 0))
        rows.append(['', '', f'{oldname} (lama)', code, '', 'ditinggalkan: ' + note, oldname, n, s])
sheet(ws, ['Area', 'Nama area', 'Kategori', 'Kode', 'Habis pakai', 'Aksi', 'Nama lama', 'Barang aktif sekarang', 'Berstok'],
      rows, [6, 26, 36, 12, 11, 60, 32, 12, 10],
      lambda r: YEL if r[0] else (RED if str(r[5]).startswith('ditinggalkan') else None))

# 2. Jenis baru
ws = wb.create_sheet('2 Jenis Baru')
rows = []
for code, name, area, hp, oid, oldname, eng, tl in CATEGORIES:
    for t in tl:
        tn = t.lstrip('*')
        ids = by_new.get(tn, [])
        n = sum(types[i]['aktif'] for i in ids); s = sum(types[i]['stok'] for i in ids)
        b = base.get(tn)
        if b is None: aksi = 'jenis baru (kosong, diisi di Tahap 3)'
        elif types[b]['name'] == tn: aksi = f'pakai id {b}' + (f', gabung {len(ids)-1} jenis lain' if len(ids) > 1 else '')
        else: aksi = f'pakai id {b} ("{types[b]["name"]}") ganti nama' + (f', gabung {len(ids)-1} jenis lain' if len(ids) > 1 else '')
        rows.append([areaname[area], name, tn, newtype_src[tn], ', '.join(f'{types[i]["name"]} ({i})' for i in ids), n, s, aksi])
sheet(ws, ['Area', 'Kategori', 'Jenis baru', 'Sumber', 'Jenis lama yang masuk (id)', 'Barang aktif', 'Berstok', 'Aksi'],
      rows, [22, 32, 26, 15, 70, 10, 9, 55], lambda r: YEL if r[3] == 'Usulan Claude' else None)

# 3. Pemetaan jenis lama
ws = wb.create_sheet('3 Pemetaan Jenis Lama')
rows = []; rename_goods = 0
for tid in sorted(types, key=lambda i: (types[i]['cats'][:1] or [99], types[i]['name'])):
    t = types[tid]
    if t['deleted']: continue
    nt, aw, note = M[tid]
    oldcats = ', '.join(cats.get(c, str(c)) for c in t['cats'])
    if nt == '?':
        rows.append([tid, t['name'], t['aktif'], t['stok'], oldcats, '?', '', 'PERLU DIPUTUSKAN', '', samples[tid][0] if samples[tid] else '', '', note]); continue
    a = awalan(t['name'], nt, aw)
    if a and t['total']: rename_goods += t['total']
    if base[nt] == tid: aksi = 'tetap' if t['name'] == nt else 'ganti nama'
    else: aksi = f'gabung ke id {base[nt]}'
    ex = samples[tid][0] if samples[tid] else ''
    before = disp(t['name'], ex) if ex else ''
    after = disp(nt, (a + ' ' + ex) if a else ex) if ex else ''
    rows.append([tid, t['name'], t['aktif'], t['stok'], oldcats, nt, catname[newtype_cat[nt]], aksi, a, before, after, note])
sheet(ws, ['id', 'Jenis lama', 'Barang aktif', 'Berstok', 'Kategori barangnya sekarang', 'Jenis baru', 'Kategori baru', 'Aksi',
           'Awalan ditambahkan ke nama barang', 'Contoh tampil SEBELUM', 'Contoh tampil SESUDAH', 'Catatan'],
      rows, [6, 28, 9, 8, 34, 24, 30, 16, 22, 42, 46, 50],
      lambda r: RED if r[5] == '?' else (YEL if r[11] else None))

# 4. Perlu diputuskan
ws = wb.create_sheet('4 Perlu Diputuskan')
dec = [
 ['Tikar', 'Keputusan pemilik: ikut Karpet & Keset (sesuai rak).', 'Sudah diterapkan'],
 ['Pasta Gigi Anak (id 143, 144)', 'Keputusan pemilik: masuk Pasta Gigi.', 'Sudah diterapkan'],
 ['Gula kiloan', 'Keputusan pemilik: kategori baru Sembako (Area Makanan dan Minuman) berisi Minyak Goreng, Gula, Tepung, Garam, Bahan Kue. Gula kiloan dan gula kemasan masuk jenis Gula.', 'Kategori & jenis sudah dibuat; barangnya dipindah di Tahap 3'],
 ['Tas Bayi, Tempat Bedak Bayi', 'Keputusan pemilik: jenis baru Tas Bayi; tempat bedak masuk Bak Mandi Bayi.', 'Sudah diterapkan'],
 ['Kategori lama', 'Keputusan pemilik: diberi akhiran (lama), dinonaktifkan setelah semua barangnya pindah.', 'Akhiran sudah diberi; penonaktifan setelah Tahap 3'],
 ['Barang lain-lain di Tas, Dompet, Dkk', 'Helm, jam digital, kacamata, vas bunga, bunga hias, hiasan dinding, koyo rambut, spon bedak, mainan anak.', 'Diputuskan per barang di CSV Tahap 3'],
]
sheet(ws, ['Hal', 'Masalah', 'Usulan'], dec, [32, 90, 70])
for row in ws.iter_rows(min_row=2):
    for c in row: c.alignment = Alignment(wrap_text=True, vertical='top')

out = sys.argv[1]
wb.save(out)
newcount = sum(1 for tn in newtype_cat if tn not in base)
print(f'OK {out}\njenis lama aktif: {sum(1 for t in types.values() if not t["deleted"])}, jenis baru: {len(newtype_cat)} '
      f'({len(base)} memakai id lama, {newcount} baru), perlu diputuskan: {sum(1 for v in M.values() if v[0]=="?")}, '
      f'barang yang namanya diberi awalan: {rename_goods}')
