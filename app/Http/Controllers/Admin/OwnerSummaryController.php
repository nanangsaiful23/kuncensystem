<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OwnerSummaryRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Ringkasan Pemilik (menu Laporan). */
class OwnerSummaryController extends Controller
{
    public function __construct(private OwnerSummaryRepository $repo)
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        abort_unless(isSuperadmin(), 403);

        $date = Carbon::parse($request->get('date', date('Y-m-d')));
        if ($date->isFuture()) {
            $date = Carbon::today();
        }
        $data = $this->repo->summary($date);
        $default = ['page_name' => 'Ringkasan Pemilik'];

        return view('admin.owner-summary', $data + compact('default'));
    }
}
