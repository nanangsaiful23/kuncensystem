<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MenuAkses;
use Illuminate\Http\Request;

/** Pengaturan > Hak Akses Menu: superadmin membuka/menutup menu per role. */
class MenuAksesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(isSuperadmin(), 403, 'Hak Akses Menu hanya untuk superadmin.');

            return $next($request);
        });
    }

    public function index()
    {
        $roles = MenuAkses::roles();
        $grup  = [];
        foreach (MenuAkses::menus() as $key => $menu) {
            $menu['boleh'] = [];
            foreach (array_keys($roles) as $role) {
                $menu['boleh'][$role] = MenuAkses::boleh($key, $role);
            }
            $grup[$menu['grup'] ?? 'Lain'][$key] = $menu;
        }

        return view('admin.menu-akses', [
            'roles'    => $roles,
            'grup'     => $grup,
            'tabelAda' => MenuAkses::tabelAda(),
        ]);
    }

    public function update(Request $request)
    {
        if (! MenuAkses::tabelAda()) {
            return back()->with('menu_akses_error', 'Tabel menu_permissions belum ada. Jalankan dulu docs/sql/2026-11-18-tambah-hak-akses-menu.sql di phpMyAdmin.');
        }

        if ($request->input('aksi') === 'bawaan') {
            MenuAkses::kembalikanBawaan();

            return redirect()->route('admin.menu-akses.index')->with('menu_akses_ok', 'Semua hak akses dikembalikan ke bawaan.');
        }

        MenuAkses::simpan((array) $request->input('akses', []));

        return redirect()->route('admin.menu-akses.index')->with('menu_akses_ok', 'Hak akses menu disimpan.');
    }
}
