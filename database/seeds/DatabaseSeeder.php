<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Admin::create([
            'name'          => 'Admin NTN',
            'email'         => 'admin',
            'password'      => bcrypt('bismillaah' . '<4d[M!n}'),
            'is_active'     => 1
        ]);

        \App\Admin::create([
            'name'          => 'Coba Admin',
            'email'         => 'coba_admin',
            'password'      => bcrypt('ntn' . '<4d[M!n}'),
            'is_active'     => 1
        ]);

        \App\Cashier::create([
            'name'          => 'Cashier NTN',
            'email'         => 'cashier',
            'password'      => bcrypt('bismillaah' . 'k&4z~1e1R*'),
            'is_active'     => 1
        ]);

        \App\Cashier::create([
            'name'          => 'Coba Cashier',
            'email'         => 'coba_cashier',
            'password'      => bcrypt('ntn' . 'k&4z~1e1R*'),
            'is_active'     => 1
        ]);

        DB::table('members')->insert(array(
            array('name' => 'Non Member', 'address' => 'Getasan'),
        ));

        DB::table('accounts')->insert(array(
            array('code' => '1111', 'name' => 'Kas di Tangan', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1112', 'name' => 'Kas di Bank', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1131', 'name' => 'Piutang Dagang', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1132', 'name' => 'Cadangan Kerugian Piutang', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1133', 'name' => 'Piutang Karyawan', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1134', 'name' => 'Piutang Lain-lain', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1141', 'name' => 'Persediaan Barang', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1151', 'name' => 'Persekot Biaya Perjalanan', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1201', 'name' => 'Investasi Jangka Panjang', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1311', 'name' => 'Tanah', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1312', 'name' => 'Peralatan Toko', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1313', 'name' => 'Kendaraan', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1322', 'name' => 'Akumulasi Penyusutan Peralatan Toko', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1323', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '2101', 'name' => 'Utang Dagang', 'type' => 'Kredit', 'group' => 'Neraca', 'activa' => 'pasiva'),
            array('code' => '2102', 'name' => 'Utang Gaji', 'type' => 'Kredit', 'group' => 'Neraca', 'activa' => 'pasiva'),
            array('code' => '2104', 'name' => 'Utang Jangka Pendek Lain-lain', 'type' => 'Kredit', 'group' => 'Neraca', 'activa' => 'pasiva'),
            array('code' => '3001', 'name' => 'Modal Pemilik', 'type' => 'Kredit', 'group' => 'Neraca', 'activa' => 'pasiva'),
            array('code' => '3002', 'name' => 'Laba Periode Berjalan', 'type' => 'Kredit', 'group' => 'Neraca', 'activa' => 'pasiva'),
            array('code' => '4101', 'name' => 'Penjualan', 'type' => 'Kredit', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5101', 'name' => 'Harga Pokok Penjualan', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5211', 'name' => 'Biaya Gaji', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5212', 'name' => 'Biaya Pemasaran', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5213', 'name' => 'Biaya Penyusutan Peralatan Toko', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5214', 'name' => 'Biaya Pengiriman', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5215', 'name' => 'Biaya Penyusutan Barang', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5216', 'name' => 'Biaya Sewa', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5217', 'name' => 'Biaya Penyusutan Kendaraan', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5218', 'name' => 'Biaya Perjalanan', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5219', 'name' => 'Biaya Uang Hilang', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5220', 'name' => 'Biaya Operasional Toko', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5221', 'name' => 'Biaya Charity', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '5222', 'name' => 'Biaya Zakat', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '6101', 'name' => 'Pendapatan Lain-lain', 'type' => 'Kredit', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '6102', 'name' => 'Biaya Lain-lain', 'type' => 'Debet', 'group' => 'Laba Rugi', 'activa' =>''),
            array('code' => '1113', 'name' => 'Kas di Nanang', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
            array('code' => '1114', 'name' => 'Kas Uang Tukar', 'type' => 'Debet', 'group' => 'Neraca', 'activa' => 'aktiva'),
        ));

        DB::table('colors')->insert(array(
            array('code' => 'NONE', 'name' => 'Tidak ada warna', 'eng_name' => 'None', 'hex' => '#FFFFFF'),
            array('code' => 'BLK', 'name' => 'Hitam', 'eng_name' => 'Black', 'hex' => '#000000'),
            array('code' => 'RED', 'name' => 'Merah', 'eng_name' => 'Red', 'hex' => '#FF0000'),
            array('code' => 'PIN', 'name' => 'Pink', 'eng_name' => 'Pink', 'hex' => '#FFC0CB'),
            array('code' => 'WHT', 'name' => 'Putih', 'eng_name' => 'White', 'hex' => '#FFFFFF'),
            array('code' => 'BLU', 'name' => 'Biru', 'eng_name' => 'Blue', 'hex' => '#0000FF'),
            array('code' => 'LBL', 'name' => 'Biru muda', 'eng_name' => 'Light Blue', 'hex' => '#44e0f6'),
            array('code' => 'DBL', 'name' => 'Biru tua', 'eng_name' => 'Dark Blue', 'hex' => '#000dc7'),
            array('code' => 'GRE', 'name' => 'Hijau', 'eng_name' => 'Green', 'hex' => '#06870e'),
            array('code' => 'LGR', 'name' => 'Hijau muda', 'eng_name' => 'Light Green', 'hex' => '#49f254'),
            array('code' => 'DGR', 'name' => 'Hijau tua', 'eng_name' => 'Dark Green', 'hex' => '#014905'),
            array('code' => 'ORE', 'name' => 'Jingga', 'eng_name' => 'Orange', 'hex' => '#eeb13e'),
            array('code' => 'BRW', 'name' => 'Coklat', 'eng_name' => 'Brown', 'hex' => '#563904'),
            array('code' => 'LBR', 'name' => 'Coklat muda', 'eng_name' => 'Light Brown', 'hex' => '#c39948'),
            array('code' => 'DBR', 'name' => 'Coklat tua', 'eng_name' => 'Dark Brown', 'hex' => '#362300'),
            array('code' => 'GRY', 'name' => 'Abu-abu', 'eng_name' => 'Gray', 'hex' => '#94928e'),
            array('code' => 'PUR', 'name' => 'Ungu', 'eng_name' => 'Purple', 'hex' => '#bd00ff'),
            array('code' => 'MRO', 'name' => 'Merah marun', 'eng_name' => 'Maroon', 'hex' => '#800000'),
            array('code' => 'TOS', 'name' => 'Tosca', 'eng_name' => 'Tosca', 'hex' => '#1edf8c'),
            array('code' => 'VIO', 'name' => 'Violet', 'eng_name' => 'Violet', 'hex' => '#1edf8c'),
            array('code' => 'YEL', 'name' => 'Kuning', 'eng_name' => 'Yellow', 'hex' => '#1edf8c'),
            array('code' => 'GLD', 'name' => 'Emas', 'eng_name' => 'Gold', 'hex' => '#1edf8c'),
            array('code' => 'RBW', 'name' => 'Warna-warni', 'eng_name' => 'Rainbow', 'hex' => '#e59736'),
            array('code' => 'MRN', 'name' => 'Merah marun', 'eng_name' => 'Maroon', 'hex' => '#e59736'),
        ));

        DB::table('units')->insert(array(
            array('code' => 'PCS', 'name' => '1 pcs', 'eng_name' => 'Pieces', 'quantity' => 1, 'base' => 'pcs'),
            array('code' => 'PCS2', 'name' => '2 pcs', 'eng_name' => 'Two', 'quantity' => 2, 'base' => 'pcs'),
            array('code' => 'PCS3', 'name' => '3 pcs', 'eng_name' => 'Three', 'quantity' => 3, 'base' => 'pcs'),
            array('code' => 'PCS4', 'name' => '4 pcs', 'eng_name' => 'Four', 'quantity' => 4, 'base' => 'pcs'),
            array('code' => 'PCS5', 'name' => '5 pcs', 'eng_name' => 'Five', 'quantity' => 5, 'base' => 'pcs'),
            array('code' => 'PCS6', 'name' => '6 pcs', 'eng_name' => 'Six', 'quantity' => 6, 'base' => 'pcs'),
            array('code' => 'PCS10', 'name' => '10 pcs', 'eng_name' => 'Ten', 'quantity' => 10, 'base' => 'pcs'),
            array('code' => 'PCS24', 'name' => '24 pcs', 'eng_name' => '24 pcs', 'quantity' => 24, 'base' => 'pcs'),
            array('code' => 'PCS25', 'name' => '25 pcs', 'eng_name' => '25 pcs', 'quantity' => 25, 'base' => 'pcs'),
            array('code' => 'DOZ', 'name' => 'Lusin', 'eng_name' => 'Dozen', 'quantity' => 12, 'base' => 'pcs'),
            array('code' => 'CODE', 'name' => 'Kodi', 'eng_name' => 'Code', 'quantity' => 20, 'base' => 'pcs'),
            array('code' => 'HDR1', 'name' => 'Seratus', 'eng_name' => '1 Hundred', 'quantity' => 100, 'base' => 'pcs'),
            array('code' => 'HDR2', 'name' => 'Dua ratus', 'eng_name' => '2 Hundred', 'quantity' => 200, 'base' => 'pcs'),
            array('code' => 'GROSS', 'name' => 'Gross', 'eng_name' => 'Gross', 'quantity' => 144, 'base' => 'pcs'),
            array('code' => 'RIM', 'name' => 'Rim', 'eng_name' => 'Rim', 'quantity' => 500, 'base' => 'pcs'),
            array('code' => 'KG1', 'name' => '1 Kg', 'eng_name' => '1 Kg', 'quantity' => 1, 'base' => 'kg'),
            array('code' => 'KG1.4', 'name' => '1.4 Kg', 'eng_name' => '1.4 Kg', 'quantity' => 1.4, 'base' => 'kg'),
            array('code' => 'KG1.5', 'name' => '1.5 Kg', 'eng_name' => '1.5 Kg', 'quantity' => 1.5, 'base' => 'kg'),
            array('code' => 'KG1.7', 'name' => '1.7 Kg', 'eng_name' => '1.7 Kg', 'quantity' => 1.7, 'base' => 'kg'),
            array('code' => 'KG1.8', 'name' => '1.8 Kg', 'eng_name' => '1.8 Kg', 'quantity' => 1.8, 'base' => 'kg'),
            array('code' => 'KG1.9', 'name' => '1.9 Kg', 'eng_name' => '1.9 Kg', 'quantity' => 1.9, 'base' => 'kg'),
            array('code' => 'KG2', 'name' => '2 Kg', 'eng_name' => '2 Kg', 'quantity' => 2, 'base' => 'kg'),
            array('code' => 'KG2.2', 'name' => '2.2 Kg', 'eng_name' => '2.2 Kg', 'quantity' => 2.2, 'base' => 'kg'),
            array('code' => 'KG2.4', 'name' => '2.4 Kg', 'eng_name' => '2.4 Kg', 'quantity' => 2.4, 'base' => 'kg'),
            array('code' => 'KG2.5', 'name' => '2.5 Kg', 'eng_name' => '2.5 Kg', 'quantity' => 2.5, 'base' => 'kg'),
            array('code' => 'KG3', 'name' => '3 Kg', 'eng_name' => '3 Kg', 'quantity' => 3, 'base' => 'kg'),
            array('code' => 'KG4', 'name' => '4 Kg', 'eng_name' => '4 Kg', 'quantity' => 4, 'base' => 'kg'),
            array('code' => 'KG4.5', 'name' => '4.5 Kg', 'eng_name' => '4.5 Kg', 'quantity' => 4.5, 'base' => 'kg'),
            array('code' => 'KG4.8', 'name' => '4.8 Kg', 'eng_name' => '4.8 Kg', 'quantity' => 4.8, 'base' => 'kg'),
            array('code' => 'KG5', 'name' => '5 Kg', 'eng_name' => '5 Kg', 'quantity' => 5, 'base' => 'kg'),
            array('code' => 'KG6', 'name' => '6 Kg', 'eng_name' => '6 Kg', 'quantity' => 6, 'base' => 'kg'),
            array('code' => 'KG10', 'name' => '10 Kg', 'eng_name' => '10 Kg', 'quantity' => 10, 'base' => 'kg'),
            array('code' => 'KG25', 'name' => '25 Kg', 'eng_name' => '25 Kg', 'quantity' => 25, 'base' => 'kg'),
            array('code' => 'GR1', 'name' => '1 Gram', 'eng_name' => '1 Gram', 'quantity' => 0.001, 'base' => 'kg'),
            array('code' => 'GR10', 'name' => '10 Gram', 'eng_name' => '10 Gram', 'quantity' => 0.01, 'base' => 'kg'),
            array('code' => 'GR100', 'name' => '100 Gram', 'eng_name' => '100 Gram', 'quantity' => 0.1, 'base' => 'kg'),
            array('code' => 'GR150', 'name' => '150 Gram', 'eng_name' => '150 Gram', 'quantity' => 0.15, 'base' => 'kg'),
            array('code' => 'GR170', 'name' => '170 Gram', 'eng_name' => '170 Gram', 'quantity' => 0.17, 'base' => 'kg'),
            array('code' => 'GR189', 'name' => '189 Gram', 'eng_name' => '189 Gram', 'quantity' => 0.189, 'base' => 'kg'),
            array('code' => 'GR200', 'name' => '200 Gram', 'eng_name' => '200 Gram', 'quantity' => 0.2, 'base' => 'kg'),
            array('code' => 'GR250', 'name' => '250 Gram', 'eng_name' => '250 Gram', 'quantity' => 0.25, 'base' => 'kg'),
            array('code' => 'GR350', 'name' => '350 Gram', 'eng_name' => '350 Gram', 'quantity' => 0.35, 'base' => 'kg'),
            array('code' => 'GR400', 'name' => '400 Gram', 'eng_name' => '400 Gram', 'quantity' => 0.4, 'base' => 'kg'),
            array('code' => 'GR500', 'name' => '500 Gram', 'eng_name' => '500 Gram', 'quantity' => 0.5, 'base' => 'kg'),
            array('code' => 'GR750', 'name' => '750 Gram', 'eng_name' => '750 Gram', 'quantity' => 0.75, 'base' => 'kg'),
            array('code' => 'M0.5', 'name' => '0.5 Meter', 'eng_name' => '0.5 Meter', 'quantity' => 0.5, 'base' => 'meter'),
            array('code' => 'M1', 'name' => '1 Meter', 'eng_name' => '1 Meter', 'quantity' => 1, 'base' => 'meter'),
            array('code' => 'M2', 'name' => '2 Meter', 'eng_name' => '2 Meter', 'quantity' => 2, 'base' => 'meter'),
            array('code' => 'M3', 'name' => '3 Meter', 'eng_name' => '3 Meter', 'quantity' => 3, 'base' => 'meter'),
            array('code' => 'M6', 'name' => '6 Meter', 'eng_name' => '6 Meter', 'quantity' => 6, 'base' => 'meter'),
            array('code' => 'M15', 'name' => '15 Meter', 'eng_name' => '15 Meter', 'quantity' => 15, 'base' => 'meter'),
            array('code' => 'M20', 'name' => '20 Meter', 'eng_name' => '20 Meter', 'quantity' => 20, 'base' => 'meter'),
            array('code' => 'PCS48', 'name' => '48 pcs', 'eng_name' => '48 pcs', 'quantity' => 48, 'base' => 'pcs'),
            array('code' => 'PCS50', 'name' => '50 pcs', 'eng_name' => '50 pcs', 'quantity' => 50, 'base' => 'pcs'),
            array('code' => 'PCS40', 'name' => '40 pcs', 'eng_name' => '40 pcs', 'quantity' => 40, 'base' => 'pcs'),
            array('code' => 'PCS15', 'name' => '15 pcs', 'eng_name' => '15 pcs', 'quantity' => 15, 'base' => 'pcs'),
            array('code' => 'GR60', 'name' => '60 Gram', 'eng_name' => '60 Gram', 'quantity' => 0.06, 'base' => 'kg'),
            array('code' => 'GR600', 'name' => '600 Gram', 'eng_name' => '600 Gram', 'quantity' => 0.6, 'base' => 'kg'),
            array('code' => 'THSD1', 'name' => '1000 pcs', 'eng_name' => '1000 pcs', 'quantity' => 1000, 'base' => 'pcs'),
            array('code' => 'PCS20', 'name' => '20 pcs', 'eng_name' => '20 pcs', 'quantity' => 20, 'base' => 'pcs'),
            array('code' => 'PCS17', 'name' => '17 pcs', 'eng_name' => '17 pcs', 'quantity' => 17, 'base' => 'pcs'),
            array('code' => 'PCS60', 'name' => '60 pcs', 'eng_name' => '60 pcs', 'quantity' => 60, 'base' => 'pcs'),
            array('code' => 'PCS36', 'name' => '36 pcs', 'eng_name' => '36 pcs', 'quantity' => 36, 'base' => 'pcs'),
            array('code' => 'PCS30', 'name' => '30 pcs', 'eng_name' => '30 pcs', 'quantity' => 30, 'base' => 'pcs'),
            array('code' => 'PCS28', 'name' => '28 pcs', 'eng_name' => '28 pcs', 'quantity' => 28, 'base' => 'pcs'),
            array('code' => 'PCS7', 'name' => '7 pcs', 'eng_name' => '7 pcs', 'quantity' => 7, 'base' => 'pcs'),
            array('code' => 'PCS42', 'name' => '42 pcs', 'eng_name' => '42 pcs', 'quantity' => 42, 'base' => 'pcs'),
            array('code' => 'PCS55', 'name' => '55 pcs', 'eng_name' => '55 pcs', 'quantity' => 55, 'base' => 'pcs'),
            array('code' => 'HDR2.5', 'name' => '250 pcs', 'eng_name' => '250 pcs', 'quantity' => 250, 'base' => 'pcs'),
            array('code' => 'KG3.5', 'name' => '3.5 Kg', 'eng_name' => '3.5 Kg', 'quantity' => 3.5, 'base' => 'kg'),
            array('code' => 'KG50', 'name' => '50 Kg', 'eng_name' => '50 Kg', 'quantity' => 50, 'base' => 'kg'),
            array('code' => 'KG8.5', 'name' => '8.5 Kg', 'eng_name' => '8.5 Kg', 'quantity' => 8.5, 'base' => 'kg'),
            array('code' => 'M0.3', 'name' => '0.3 Meter', 'eng_name' => '0.3 Meter', 'quantity' => 0.3, 'base' => 'meter'),
        ));

        DB::table('categories')->insert(array(
            array('code' => 'HOUSEWARE', 'name' => 'Peralatan Rumah Tangga', 'eng_name' => 'Houseware Products', 'unit_id' => 1),
            array('code' => 'FOOD', 'name' => 'Makanan dan Minuman', 'eng_name' => 'Foods and Beverages', 'unit_id' => 1),
            array('code' => 'KiFOOD', 'name' => 'Makanan dan Minuman Kiloan', 'eng_name' => 'Snacks Kilo', 'unit_id' => 16),
            array('code' => 'KiRICE', 'name' => 'Beras Kiloan', 'eng_name' => 'Rice Kilo', 'unit_id' => 16),
            array('code' => 'MEDICINE', 'name' => 'Kesehatan', 'eng_name' => 'Health and Medical', 'unit_id' => 1),
            array('code' => 'BEDDING', 'name' => 'Bedding', 'eng_name' => 'Bedding', 'unit_id' => 1),
            array('code' => 'STATIONERY', 'name' => 'Buku dan Alat Tulis', 'eng_name' => 'Stationery Products', 'unit_id' => 1),
            array('code' => 'BABY', 'name' => 'Perlengkapan Bayi', 'eng_name' => 'Baby Products', 'unit_id' => 1),
            array('code' => 'BATH', 'name' => 'Perlengkapan Mandi', 'eng_name' => 'Bath Products', 'unit_id' => 1),
            array('code' => 'BEAUTY', 'name' => 'Tisu, Perawatan dan Kecantikan', 'eng_name' => 'Beauty Care', 'unit_id' => 1),
            array('code' => 'CLEANING', 'name' => 'Peralatan Pembersih', 'eng_name' => 'Cleaning Products', 'unit_id' => 1),
            array('code' => 'CLOTHING', 'name' => 'Pakaian', 'eng_name' => 'Clothing', 'unit_id' => 1),
            array('code' => 'FOOTWEAR', 'name' => 'Sepatu & Sandal', 'eng_name' => 'Footwear', 'unit_id' => 1),
            array('code' => 'FASHION', 'name' => 'Tas, Dompet, Dkk', 'eng_name' => 'Fashion', 'unit_id' => 1),
            array('code' => 'PRAY', 'name' => 'Peralatan Sholat', 'eng_name' => 'Pray', 'unit_id' => 1),
            array('code' => 'SCHOOL', 'name' => 'Peralatan Sekolah', 'eng_name' => 'School', 'unit_id' => 1),
            array('code' => 'STORAGE', 'name' => 'Lemari', 'eng_name' => 'Storage', 'unit_id' => 1),
            array('code' => 'GARDENING', 'name' => 'Peralatan Berkebun', 'eng_name' => 'Gardening Tools', 'unit_id' => 1),
            array('code' => 'KiGARDENING', 'name' => 'Peralatan Berkebun Kiloan', 'eng_name' => 'Gardening Tools Kilo', 'unit_id' => 16),
            array('code' => 'ELECTRONIC', 'name' => 'Barang Elektronik', 'eng_name' => 'Electronic Products', 'unit_id' => 1),
            array('code' => 'GLASSWARE', 'name' => 'Barang Pecah Belah', 'eng_name' => 'Glassware', 'unit_id' => 1),
            array('code' => 'STOVE', 'name' => 'Kompor', 'eng_name' => 'Stoves', 'unit_id' => 1),
            array('code' => 'PLASTIC', 'name' => 'Plastik', 'eng_name' => 'Plastic', 'unit_id' => 1),
            array('code' => 'FLOORING', 'name' => 'Karpet/Keset', 'eng_name' => 'Flooring Products', 'unit_id' => 1),
            array('code' => 'MeFLOORING', 'name' => 'Karpet/Keset Meteran', 'eng_name' => 'Flooring Products Meter', 'unit_id' => 38),
        ));
    }
}
