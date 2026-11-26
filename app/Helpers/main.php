<?php
    use App\Admin;
    use App\Cashier;
    use App\Models\Account;
    use App\Models\Brand;
    use App\Models\Category;
    use App\Models\Color;
    use App\Models\DeliveryFee;
    use App\Models\Distributor;
    use App\Models\Good;
    use App\Models\GoodChecking;
    use App\Models\GoodLoading;
    use App\Models\GoodUnit;
    use App\Models\MainCategory;
    use App\Models\Member;
    use App\Models\Type;
    use App\Models\Unit;

    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\DB;

    // Server produksi tidak punya ekstensi mbstring, dan polyfill Symfony tidak
    // menyediakan mb_strcut. Laravel memakainya saat mencatat query panjang
    // (> 2000 karakter) ketika APP_DEBUG aktif, sehingga request gagal 500.
    if (! function_exists('mb_strcut')) {
        function mb_strcut(string $string, int $start, ?int $length = null, ?string $encoding = null): string
        {
            $potong = substr($string, $start, $length ?? strlen($string));
            // Jangan memotong di tengah karakter UTF-8.
            for ($i = 0; $i < 3 && $potong !== '' && ! preg_match('//u', $potong); $i++) {
                $potong = substr($potong, 0, -1);
            }

            return $potong;
        }
    }
    use Illuminate\Support\Facades\Storage;
    use Intervention\Image\ImageManager;

    function alert()
    {
        $type  = null;
        $color = null;
        $data  = null;

        if(session('alert'))
        {
            $alert = session('alert');
            $data  = session('data');

            switch ($alert) {
                case 'add':
                    $type = 'tambah';
                    $color = 'success';
                    break;

                case 'edit':
                    $type = 'ubah';
                    $color = 'warning';
                    break;

                case 'delete':
                    $type = 'hapus';
                    $color = 'danger';
                    break;

                case 'error':
                    $type = 'error';
                    $color = 'danger';
                    break;

                case 'errorEdit':
                    $type = 'errorEdit';
                    $color = 'danger';
                    break;
            }
            session()->forget('alert');
            session()->forget('data');
        }

        return [$type, $color, $data];
    }

    function callGetGuzzle($url, $token = null)
    {
        if($token == null)
        {
            $header = [
                    'Secret' => config('app.secret')
                ];
        }
        else
        {
            $header = [
                    'Secret' => config('app.secret'),
                    'Authorization' => 'Bearer ' . $token                
                ];
        }

        $client = new \GuzzleHttp\Client([
                'headers' => $header
            ]);
        try {
            $response = $client->get($url);
            $response = json_decode($response->getBody()->getContents());
            
            return ["status" => "ok", "data" => $response->content];
        } 
        catch (\GuzzleHttp\Exception\RequestException $e) {
            return ["status" => "error", "data" => $e->getMessage(), "code" => $e->getCode()];
        }
    }

    function callPostGuzzle($url, $token = null, $data = null)
    {
        if($token == null)
        {
            $header = [
                    'Content-Type' => 'application/json',
                    'Secret' => config('app.secret')
                ];
        }
        else
        {
            $header = [
                    'Content-Type' => 'application/json',
                    'Secret' => config('app.secret'),
                    'Authorization' => 'Bearer ' . $token                
                ];
        }

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('POST', $url, [
                'headers' => $header,
                'json' => $data
            ]);

            $response = json_decode($response->getBody()->getContents());
            
            return ["status" => "ok", "data" => $response->content];
        } 
        catch (\GuzzleHttp\Exception\RequestException $e) {
            return ["status" => "error", "data" => $e->getMessage(), "code" => $e->getCode()];
        }
    }
    
    function api_response($status, $message, $code, $content = null)
    {
        return response([
            'status' => $status,
            'message' => $message,
            'code' => $code,
            'content' => $content
        ], $code);
    }

    function resizeImage($folder, $pathInput)
    {
        // Intervention Image v3: scale to max 512px wide, keep aspect ratio,
        // never upscale (same as v2 resize + aspectRatio + upsize).
        $jpeg = ImageManager::gd()
                    ->read($pathInput)
                    ->scaleDown(width: 512)
                    ->toJpeg(quality: 90)
                    ->toString();
        $hash = md5($jpeg);
        $path = $folder . "/{$hash}.jpg";
        Storage::put($path, $jpeg);

        return $path;
    }

    function changeInput($text)
    {
        // First, replace UTF-8 characters.
        $text = str_replace(
        array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
        array("'", "'", '"', '"', '-', '--', '...'),
        $text);

        // Next, either REPLACE their Windows-1252 equivalents.
        $text = str_replace(
        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
        array("'", "'", '"', '"', '-', '--', '...'),
        $text);

        // OR, STRIP their Windows-1252 equivalents.
        $text = str_replace(
        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
        array('', '', '', '', '', '', ''),
        $text);

        return $text;
    }

    function getActor($model, $model_id)
    {
        if($model == 'cashier')
            $model = 'Cashier';
        else
            $model = 'Admin';
        $model = "App\\" . $model;
        $data  = $model::find($model_id);

        return $data;
    }

    function getPaginations()
    {
        $paginations = ['all' => 'all', '5' => '5 per halaman', '10' => '10 per halaman', '15' => '15 per halaman', '20' => '20 per halaman', '200' => '200 per halaman'];

        return $paginations;
    }

    function convertDate($date)
    {
        if($date == null) return null;
        return date('Y-m-d', strtotime($date));
    }

    function convertDateExcel($date)
    {
        if($date == null) return null;
        return date('Y-m-d', strtotime(date('d/m/Y', $date)));
    }

    function displayDate($date)
    {
        return date('d F Y', strtotime($date));
    }

    function displayDateTime($date)
    {
        return date('d F Y H:i:s', strtotime($date));
    }

    function formatNumber($number)
    {
        return number_format(checkNull($number),0,'',',');
    }

    /*
     * Admin roles (admins.role), from high to low:
     *   superadmin  everything, incl. what used to be only for the account
     *               with email 'admin' (reports, delete, unit changes, ...)
     *   supervisor  sees and changes buy prices
     *   admin       daily work
     * A higher role always has the rights of the lower ones.
     */
    function isSuperadmin()
    {
        $user = \Auth::user();

        return $user !== null && ($user->role ?? null) === 'superadmin';
    }

    /** Supervisor or higher: may see and change buy prices. */
    function isSupervisor()
    {
        $user = \Auth::user();

        return $user !== null && in_array($user->role ?? null, ['supervisor', 'superadmin'], true);
    }

    /**
     * Boleh membuka menu ini? Daftar menu: config/menu_akses.php,
     * diatur superadmin di halaman Hak Akses Menu.
     */
    function canMenu(string $key)
    {
        return \App\Services\MenuAkses::boleh($key);
    }

    /** Ada sub menu dari menu induk ini (mis. 'Loading Barang') yang boleh dibuka? */
    function canInduk(string $induk)
    {
        return \App\Services\MenuAkses::bolehInduk($induk);
    }

    /**
     * Old name, kept so the ~80 call sites keep working. It used to only strip
     * commas, so "12.500" became 12.5 while parseNumber() read it as 12500.
     * Now there is ONE set of rules: this is parseNumber().
     * Returns a float, or null for an empty / non-numeric value (which is a
     * real NULL for a DECIMAL column and 0 for checkNull()).
     */
    function unformatNumber($number)
    {
        return parseNumber($number);
    }

    /**
     * Reads a number typed or shown in either style:
     *   Indonesian "12.500" / "1.234,5"   English "12,500" / "1,234.5"   raw "12500.00"
     * Rules (prices and quantities have at most 2 decimals):
     *   - both "." and ",": the last one is the decimal separator
     *   - one kind only, used more than once ("1.234.567"): thousands
     *   - one kind only, once, followed by exactly 3 digits ("12.500", "12,500"): thousands
     *   - otherwise it is the decimal separator ("12500.00", "2,5")
     * Returns null for an empty value. The same rules are used in the browser
     * (resources/views/layout/good-loading/number-js.blade.php).
     */
    function parseNumber($value)
    {
        if ($value === null || is_bool($value)) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = preg_replace('/[^0-9.,\-]/', '', (string) $value);
        if ($s === '' || $s === '-') {
            return null;
        }

        $negative = str_starts_with($s, '-');
        $s = str_replace('-', '', $s);
        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimal = $lastDot > $lastComma ? '.' : ',';
            $thousands = $decimal === '.' ? ',' : '.';
            $s = str_replace([$thousands, $decimal], ['', '.'], $s);
        } elseif ($lastDot !== false || $lastComma !== false) {
            $sep = $lastDot !== false ? '.' : ',';
            $parts = explode($sep, $s);
            $isThousands = count($parts) > 2 || (strlen(end($parts)) === 3 && $parts[0] !== '' && $parts[0] !== '0');
            $s = $isThousands ? str_replace($sep, '', $s) : str_replace($sep, '.', $s);
        }

        if (! is_numeric($s)) {
            return null;
        }

        return $negative ? -(float) $s : (float) $s;
    }

    function showRupiah($money)
    {
        is_numeric($money) ? : $money = unformatNumber($money);
        return 'Rp' . number_format(checkNull($money),2,'.',',');
    }

    function roundMoney($money)
    {
        if(is_numeric($money))
        return round($money / 100) * 100;
        else 0;
    }

    function printRupiah($money)
    {
        return number_format(checkNull($money),0,'.',',');
    }

    function checkNull($item)
    {
        return $item == NULL || $item == 'NULL' || $item == "" ? 0 : $item;
    }

    function checkNullString($item)
    {
        return $item == NULL || $item == 'NULL' || $item == "" ? "" : $item;
    }

    /** 12500 -> "Rp 12.500", 2.5 -> "Rp 2,5" (cara tampil di halaman loading) */
    function rupiahShort($money)
    {
        $s = number_format((float) $money, 2, ',', '.');

        return 'Rp ' . rtrim(rtrim($s, '0'), ',');
    }

    /**
     * Cek harga beli dan harga jual satu satuan barang (loading, ganti harga,
     * tambah harga, barang baru). Mengembalikan alasan kenapa harga tidak boleh
     * disimpan, atau null bila harganya benar:
     *   - harga beli dan harga jual wajib diisi dan lebih dari 0
     *   - harga jual tidak boleh lebih kecil dari harga beli (jual rugi)
     * Aturan yang sama dipakai di browser: layout/good/price-check-js.blade.php
     * (di sana harga jual = harga beli hanya diberi peringatan).
     */
    function buySellPriceProblem($buy_price, $sell_price)
    {
        $buy  = parseNumber($buy_price);
        $sell = parseNumber($sell_price);

        if ($buy === null || round($buy, 2) <= 0) {
            return 'harga beli masih kosong atau 0';
        }
        if ($sell === null || round($sell, 2) <= 0) {
            return 'harga jual masih kosong atau 0';
        }
        if (round($sell, 2) < round($buy, 2)) {
            return 'harga jual ' . rupiahShort($sell) . ' lebih kecil dari harga beli ' . rupiahShort($buy)
                 . ' (rugi ' . rupiahShort(round($buy, 2) - round($sell, 2)) . ')';
        }

        return null;
    }

    /**
     * Batalkan penyimpanan bila ada harga yang salah. $rows berisi
     * ['label' => 'Nama barang (SATUAN)', 'buy' => harga beli, 'sell' => harga jual].
     * Form biasa kembali ke halaman sebelumnya dengan pesan error,
     * request AJAX mendapat 422 berisi pesan yang sama.
     */
    function abortOnBadPrices(array $rows)
    {
        $messages = [];
        foreach ($rows as $row) {
            $problem = buySellPriceProblem($row['buy'] ?? null, $row['sell'] ?? null);
            if ($problem !== null) {
                $messages[] = $row['label'] . ': ' . $problem;
            }
        }

        if ($messages) {
            throw \Illuminate\Validation\ValidationException::withMessages(['harga' => $messages]);
        }
    }

    function calculateProfit($buy_price, $sell_price)
    {
        if($buy_price != null && $buy_price != 0)
            return round((floatval($sell_price) - floatval($buy_price)) / $buy_price * 100, 2);
        else return 0;
    }

    function changeGramToKg($weight)
    {
        $kg = $weight/1000;
        $gram = $weight%1000;
        $result = $kg > 0 ? $kg . ' kg ' : '';
        $result .= $gram > 0 ? $gram . ' gram' : '';

        return $result;
    }

    /** Nama barang tampilan "Jenis nama" dari kolom mentah; aturannya di Good::formatName. */
    function formatGoodName($type, $name)
    {
        return Good::formatName($type, $name);
    }

    function showShortName($string)
    {
        if(strlen($string) > 30){
            $textLength = strlen($string);
            $maxChars = 25;

            $string = substr_replace($string, '...', 5, $textLength-$maxChars);
        }
        return ucwords($string);

        // if(strlen($string) > 30)
        //     $string = substr($string, 0, 30);

        // return $string;
    }

    function getAccounts()
    {
        return Account::orderBy('code', 'asc')->get();
    }

    function getAccountJournalLists()
    {
        $accounts = ['' => 'Pilih akun'];
        foreach (Account::orderBy('code', 'asc')->get() as $data) {
            $accounts = Arr::add($accounts, $data->id, $data->code . ' - ' . $data->name);
        }
        return $accounts;
    }

    function getAccountLists()
    {
        $accounts = ['all' => 'Seluruh akun'];
        foreach (Account::orderBy('code', 'asc')->get() as $data) {
            $accounts = Arr::add($accounts, $data->code, $data->code . ' - ' . $data->name);
        }
        return $accounts;
    }

    function getBrands()
    {
        $brands = ['' => 'Pilih merek'];
        foreach (Brand::orderBy('name', 'asc')->get() as $data) {
            $brands = Arr::add($brands, $data->id, $data->name);
        }
        return $brands;
    }

    function getMainCategories()
    {
        $categories = ['' => 'Pilih kategori utama', 'all' => 'Seluruh kategori utama'];
        foreach (MainCategory::orderBy('name', 'asc')->get() as $data) {
            $categories = Arr::add($categories, $data->id, $data->name . ' (' . $data->eng_name . ')');
        }
        return $categories;
    }

    function getCategories()
    {
        $categories = ['' => 'Pilih kategori', 'all' => 'Seluruh kategori'];
        foreach (Category::orderBy('name', 'asc')->get() as $data) {
            $categories = Arr::add($categories, $data->id, $data->name . ' (' . $data->eng_name . ')');
        }
        return $categories;
    }

    function getColors()
    {
        $colors = ['' => 'Pilih warna'];
        foreach (Color::orderBy('name', 'asc')->get() as $data) {
            $colors = Arr::add($colors, $data->id, $data->name . ' (' . $data->code . ')');
        }
        return $colors;
    }

    function getColorAsObjects()
    {
        return Color::orderBy('name', 'asc')->get();
    }

    function getDistributors()
    {
        $distributors = Distributor::orderBy('name', 'asc')->get();

        return $distributors;
    }

    function getDistributorLists()
    {
        $distributors = ['' => 'Pilih distributor', 'all' => 'Seluruh distributor'];
        foreach (Distributor::orderBy('name', 'asc')->get() as $data) {
            $distributors = Arr::add($distributors, $data->id, $data->name);
        }
        return $distributors;
    }

    function getDistributorLoading($distributor_id, $start_date = null, $end_date = null)
    {
        $search = ['all' => 'Semua'];
        if($start_date != null)
        {
            foreach (DB::select("SELECT DISTINCT good_loadings.distributor_id as distributor_id, distributors.name FROM good_loadings join distributors on good_loadings.distributor_id = distributors.id WHERE good_loadings.created_at >= '" . $start_date . "' AND good_loadings.created_at <= '" . $end_date . "' ORDER BY distributors.name") as $data) {
                $search = Arr::add($search, $data->distributor_id, $data->name);
            }
        }
        return $search;
    }

    function getDistributorLocations()
    {
        $locations = ['all' => 'Semua'];
        foreach (DB::select("SELECT DISTINCT distributors.location FROM distributors ORDER BY distributors.location ASC") as $data) {
            $locations = Arr::add($locations, $data->location, $data->location);
        }

        return $locations;
    }

    function getGoods()
    {
        $goods = Good::all();

        return $goods;
    }

    function getGoodLists()
    {
        $goods = ['' => 'Pilih barang'];
        foreach (getGoods() as $data) {
            $goods = Arr::add($goods, $data->id, $data->name . ' ' . $data->getPcsSellingPrice()->unit->name);
        }
        return $goods;
    }

    function getGoodUnits()
    {
        $good_units = GoodUnit::join('goods', 'goods.id', 'good_units.good_id')
                              ->select('good_units.*', 'goods.*', 'good_units.id as good_unit_id', 'goods.name as good_name')
                              ->orderBy('goods.name', 'asc')->get();

        return $good_units;
    }

    function getGoodUnitLists()
    {
        $good_units = ['' => 'Pilih barang'];
        foreach (getGoodUnits() as $data) {
            $good_units = Arr::add($good_units, $data->good_unit_id, $data->good_name . ' ' . $data->unit->name);
        }
        return $good_units;
    }

    function getJournalTypes()
    {
        $types = ['all' => 'Seluruh tipe', 'transaction' => 'Transaksi', 'hpp' => 'HPP', 'good_loading' => 'Loading barang', 'other_payment' => 'Biaya lain', 'operasional' => 'Operasional', 'cash_draw' => 'Penarikan uang', 'cash_adjustment' => 'Selisih kas','modal pemilik' => 'Modal pemilik', 'penyusutan' => 'Penyusutan', 'retur' => 'Retur', 'internal_cash_flow' => 'Perpindahan uang', 'credit_payment' => 'Pembayaran hutang'];

        return $types;
    }

    function getLoadingPaymentType()
    {
        $types = ["0000" => '0000 - Sistem Error', "1111" => '1111 - Kas di Tangan', "1112" => '1112 - Kas di Bank', "1113" => '1113 - Kas di Nanang', "2101" =>'2101 - Utang Dagang', "3001" => '3001 - Modal Pemilik', "1131" => '1131 - Piutang Dagang'];

        return $types;
    }

    function getGoodSort()
    {
        $types = ["goods.id" => "ID", "goods.name" => "Nama", "last_loading" => "Tanggal Loading", "last_transaction" => "Tanggal Transaksi", "last_stock" => 'Stok Terakhir'];

        return $types;
    }

    function getGoodTypes()
    {
        $types = ['' => 'Pilih jenis barang', 'all' => 'Seluruh jenis barang'];
        foreach (Type::orderBy('name', 'asc')->get() as $data) {
            $types = Arr::add($types, $data->id, $data->name . ' (' . $data->eng_name . ')');
        }
        return $types;
    }

    function getMembers()
    {
        $members = Member::all();

        return $members;
    }

    function getMemberLists()
    {
        $members = ['' => 'Pilih member', 'all' => 'Seluruh member'];
        foreach (Member::orderBy('name', 'asc')->get() as $data) {
            $members = Arr::add($members, $data->id, $data->name);
        }
        return $members;
    }

    function getOngkir()
    {
        $fees = DeliveryFee::orderBy('location', 'asc')->get();

        return $fees;
    }

    function getOtherPayment()
    {
        $payments = ['' => 'Pilih biaya'];
        foreach (Account::where('name', 'like', 'Biaya %')->orderBy('name', 'asc')->get() as $data) {
            $payments = Arr::add($payments, $data->id, $data->name . ' (' . $data->code . ')');
        }
        return $payments;
    }

    function getRoles()
    {
        $roles = ['admin' => 'admin', 'cashier' => 'cashier', 'member' => 'member'];

        return $roles;
    }

    function getSearchLoading($type, $start_date = null, $end_date = null)
    {
        $search = ['all' => 'Semua'];
        if($start_date != null)
        {
            foreach (DB::select("SELECT DISTINCT " . $type . " as name FROM good_loadings WHERE loading_date >= '" . $start_date . "' AND loading_date <= '" . $end_date . "' ORDER BY " . $type) as $data) {
                $search = Arr::add($search, $data->name, $data->name);
            }
        }
        else
        {
            foreach (DB::select("SELECT DISTINCT " . $type . " as name FROM good_loadings ORDER BY " . $type) as $data) {
                $search = Arr::add($search, $data->name, $data->name);
            }
        }
        return $search;
    }

    function getTransactionDetailTypes($start_date, $end_date)
    {
        $types = [];
        foreach (DB::select("SELECT DISTINCT type FROM transaction_details WHERE DATE(transaction_details.created_at) >= '" . $start_date . "' AND DATE(transaction_details.created_at) <= '" . $end_date . "' ORDER BY type") as $data) {
            $types = Arr::add($types, $data->type, $data->type);
        }
        return $types;
    }

    function getUnits()
    {
        $units = ['' => 'Pilih satuan'];
        foreach (Unit::orderBy('name', 'asc')->get() as $data) {
            $units = Arr::add($units, $data->id, $data->name . ' (' . $data->code . ')');
        }
        return $units;
    }

    function getUnitAsObjects()
    {
        return Unit::orderBy('name', 'asc')->get();
    }

    function getUsers()
    {
        $users = ['all/all' => 'Semua user'];
        foreach (Admin::where('is_active', 1)->orderBy('name', 'asc')->get() as $data) {
            $users = Arr::add($users, 'admin/' . $data->id, $data->name . ' (admin)');
        }
        foreach (Cashier::where('is_active', 1)->orderBy('name', 'asc')->get() as $data) {
            $users = Arr::add($users, 'cashier/' . $data->id, $data->name . ' (kasir)');
        }

        return $users;
    }

    /**
     * QR code as inline SVG, built straight from the BaconQrCode matrix.
     * Unlike QrCode::generate() this needs no libxml/XMLWriter extension,
     * which the production server does not have.
     */
    function qrCodeSvg($text, int $size = 200, int $margin = 0): string
    {
        $matrix = \BaconQrCode\Encoder\Encoder::encode(
            (string) $text,
            \BaconQrCode\Common\ErrorCorrectionLevel::L(),
            \BaconQrCode\Encoder\Encoder::DEFAULT_BYTE_MODE_ECODING
        )->getMatrix();

        $n = $matrix->getWidth();
        $box = $n + 2 * $margin;
        $path = '';
        for ($y = 0; $y < $n; $y++) {
            $x = 0;
            while ($x < $n) {
                if ($matrix->get($x, $y) !== 1) { $x++; continue; }
                $start = $x;
                while ($x < $n && $matrix->get($x, $y) === 1) $x++;
                $path .= 'M' . ($start + $margin) . ' ' . ($y + $margin) . 'h' . ($x - $start) . 'v1h-' . ($x - $start) . 'z';
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $box . ' ' . $box . '" shape-rendering="crispEdges">'
            . '<rect width="100%" height="100%" fill="#ffffff"/>'
            . '<path fill="#000000" d="' . $path . '"/></svg>';
    }
