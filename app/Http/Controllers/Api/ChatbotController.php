<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\ChatbotGoodService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected $goodService;

    public function __construct(ChatbotGoodService $goodService)
    {
        $this->goodService = $goodService;
    }

    // ── GET /api/chatbot/search?q=indomie ────────────────────
    public function search(Request $request)
    {
        $keyword = trim($request->get('q', ''));

        if (strlen($keyword) < 2) {
            return response()->json(['error' => 'Kata kunci terlalu pendek'], 422);
        }

        return response()->json([
            'type'   => 'search',
            'query'  => $keyword,
            'barang' => $this->goodService->searchByName($keyword),
        ]);
    }

    // ── GET /api/chatbot/detail/{idOrCode} ───────────────────
    public function detail($idOrCode)
    {
        $data = $this->goodService->getDetail($idOrCode);

        if (! $data) {
            return response()->json(['error' => 'Barang tidak ditemukan'], 404);
        }

        return response()->json(['type' => 'detail', 'barang' => $data]);
    }

    // ── GET /api/chatbot/stock?q=gula ────────────────────────
    public function stock(Request $request)
    {
        return response()->json([
            'type'   => 'stock',
            'barang' => $this->goodService->checkStock($request->get('q', '')),
        ]);
    }

    // ── GET /api/chatbot/price?q=beras ───────────────────────
    public function price(Request $request)
    {
        return response()->json([
            'type'   => 'price',
            'barang' => $this->goodService->getPrices($request->get('q', '')),
        ]);
    }

    // ── GET /api/chatbot/category?name=minuman ───────────────
    public function category(Request $request)
    {
        return response()->json(
            $this->goodService->browseByCategory($request->get('name', ''))
        );
    }

    // ── GET /api/chatbot/bestsellers?days=30 ─────────────────
    public function bestsellers(Request $request)
    {
        return response()->json([
            'type'   => 'bestsellers',
            'barang' => $this->goodService->getBestSellers(
                (int) $request->get('days', 30),
                (int) $request->get('limit', 8)
            ),
        ]);
    }

    // ── GET /api/chatbot/filter?min=5000&max=20000 ───────────
    public function filterPrice(Request $request)
    {
        return response()->json([
            'type'   => 'filter_price',
            'barang' => $this->goodService->filterByPrice(
                $request->get('q'),
                $request->get('unit'),
                $request->has('min') ? (float) $request->get('min') : null,
                $request->has('max') ? (float) $request->get('max') : null,
                (int) $request->get('limit', 12)
            ),
        ]);
    }

    // ── GET /api/chatbot/categories ──────────────────────────
    public function categories()
    {
        return response()->json([
            'type'     => 'categories',
            'kategori' => $this->goodService->listCategories(),
        ]);
    }
}