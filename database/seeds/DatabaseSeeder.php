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
        ]);

        \App\Cashier::create([
            'name'          => 'Cashier NTN',
            'email'         => 'cashier',
            'password'      => bcrypt('bismillaah' . 'k&4z~1e1R*'),
        ]);

        \App\Member::create([
            'name'          => 'Member NTN',
            'email'         => 'member'
        ]);

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
            array('code' => 'PCS', 'name' => '1 pcs', 'eng_name' => 'Pieces', 'quantity' => 1),
            array('code' => 'TWO', 'name' => '2 pcs', 'eng_name' => 'Two', 'quantity' => 2),
            array('code' => 'THREE', 'name' => '3 pcs', 'eng_name' => 'Three', 'quantity' => 3),
            array('code' => 'FOUR', 'name' => '4 pcs', 'eng_name' => 'Four', 'quantity' => 4),
            array('code' => 'FIVE', 'name' => '5 pcs', 'eng_name' => 'Five', 'quantity' => 5),
            array('code' => 'SIX', 'name' => '6 pcs', 'eng_name' => 'Six', 'quantity' => 6),
            array('code' => 'TEN', 'name' => '10 pcs', 'eng_name' => 'Ten', 'quantity' => 10),
            array('code' => 'DOZ', 'name' => 'Lusin', 'eng_name' => 'Dozen', 'quantity' => 12),
            array('code' => 'CODE', 'name' => 'Kodi', 'eng_name' => 'Code', 'quantity' => 20),
            array('code' => 'HDR1', 'name' => 'Seratus', 'eng_name' => '1 Hundred', 'quantity' => 100),
            array('code' => 'HDR2', 'name' => 'Dua ratus', 'eng_name' => '2 Hundred', 'quantity' => 200),
            array('code' => 'GROSS', 'name' => 'Gross', 'eng_name' => 'Gross', 'quantity' => 144),
            array('code' => 'RIM', 'name' => 'Rim', 'eng_name' => 'Rim', 'quantity' => 500),
            array('code' => 'KG1', 'name' => '1 Kg', 'eng_name' => '1 Kg', 'quantity' => 1),
            array('code' => 'KG2', 'name' => '2 Kg', 'eng_name' => '2 Kg', 'quantity' => 2),
            array('code' => 'KG2.2', 'name' => '2.2 Kg', 'eng_name' => '2.2 Kg', 'quantity' => 2.2),
            array('code' => 'KG2.5', 'name' => '2.5 Kg', 'eng_name' => '2.5 Kg', 'quantity' => 2.5),
            array('code' => 'KG3', 'name' => '3 Kg', 'eng_name' => '3 Kg', 'quantity' => 3),
            array('code' => 'KG4', 'name' => '4 Kg', 'eng_name' => '4 Kg', 'quantity' => 4),
            array('code' => 'KG4.5', 'name' => '4.5 Kg', 'eng_name' => '4.5 Kg', 'quantity' => 4.5),
            array('code' => 'KG5', 'name' => '5 Kg', 'eng_name' => '5 Kg', 'quantity' => 5),
            array('code' => 'KG6', 'name' => '6 Kg', 'eng_name' => '6 Kg', 'quantity' => 6),
            array('code' => 'KG10', 'name' => '10 Kg', 'eng_name' => '10 Kg', 'quantity' => 10),
            array('code' => 'KG25', 'name' => '25 Kg', 'eng_name' => '25 Kg', 'quantity' => 25),
            array('code' => 'GR1', 'name' => '1 Gram', 'eng_name' => '1 Gram', 'quantity' => 0.001),
            array('code' => 'GR10', 'name' => '10 Gram', 'eng_name' => '10 Gram', 'quantity' => 0.01),
            array('code' => 'GR100', 'name' => '100 Gram', 'eng_name' => '100 Gram', 'quantity' => 0.1),
            array('code' => 'GR150', 'name' => '150 Gram', 'eng_name' => '150 Gram', 'quantity' => 0.15),
            array('code' => 'GR170', 'name' => '170 Gram', 'eng_name' => '170 Gram', 'quantity' => 0.17),
            array('code' => 'GR189', 'name' => '189 Gram', 'eng_name' => '189 Gram', 'quantity' => 0.189),
            array('code' => 'GR200', 'name' => '200 Gram', 'eng_name' => '200 Gram', 'quantity' => 0.2),
            array('code' => 'GR250', 'name' => '250 Gram', 'eng_name' => '250 Gram', 'quantity' => 0.25),
            array('code' => 'GR350', 'name' => '350 Gram', 'eng_name' => '350 Gram', 'quantity' => 0.35),
            array('code' => 'GR500', 'name' => '500 Gram', 'eng_name' => '500 Gram', 'quantity' => 0.5),
            array('code' => 'M0.5', 'name' => '0.5 Meter', 'eng_name' => '0.5 Meter', 'quantity' => 0.5),
            array('code' => 'M1', 'name' => '1 Meter', 'eng_name' => '1 Meter', 'quantity' => 1),
            array('code' => 'M2', 'name' => '2 Meter', 'eng_name' => '2 Meter', 'quantity' => 2),
            array('code' => 'M3', 'name' => '3 Meter', 'eng_name' => '3 Meter', 'quantity' => 3),
            array('code' => 'M6', 'name' => '6 Meter', 'eng_name' => '6 Meter', 'quantity' => 6),
            array('code' => 'M15', 'name' => '15 Meter', 'eng_name' => '15 Meter', 'quantity' => 15),
            array('code' => 'M20', 'name' => '20 Meter', 'eng_name' => '20 Meter', 'quantity' => 20),
            array('code' => 'BOX4', 'name' => '1 box (4 pcs)', 'eng_name' => '1 box (4 pcs)', 'quantity' => 4),
            array('code' => 'BOX5', 'name' => '1 box (5 pcs)', 'eng_name' => '1 box (5 pcs)', 'quantity' => 5),
            array('code' => 'BOX6', 'name' => '1 box (6 pcs)', 'eng_name' => '1 box (6 pcs)', 'quantity' => 6),
            array('code' => 'BOX7', 'name' => '1 box (7 pcs)', 'eng_name' => '1 box (7 pcs)', 'quantity' => 7),
            array('code' => 'BOX10', 'name' => '1 box (10 pcs)', 'eng_name' => '1 box (10 pcs)', 'quantity' => 10),
            array('code' => 'BOX12', 'name' => '1 box (12 pcs)', 'eng_name' => '1 box (12 pcs)', 'quantity' => 12),
            array('code' => 'BOX20', 'name' => '1 box (20 pcs)', 'eng_name' => '1 box (20 pcs)', 'quantity' => 20),
            array('code' => 'BOX24', 'name' => '1 box (24 pcs)', 'eng_name' => '1 box (24 pcs)', 'quantity' => 24),
            array('code' => 'BOX28', 'name' => '1 box (28 pcs)', 'eng_name' => '1 box (28 pcs)', 'quantity' => 28),
            array('code' => 'BOX30', 'name' => '1 box (30 pcs)', 'eng_name' => '1 box (30 pcs)', 'quantity' => 30),
            array('code' => 'BOX36', 'name' => '1 box (36 pcs)', 'eng_name' => '1 box (36 pcs)', 'quantity' => 36),
            array('code' => 'BOX40', 'name' => '1 box (40 pcs)', 'eng_name' => '1 box (40 pcs)', 'quantity' => 40),
            array('code' => 'BOX42', 'name' => '1 box (42 pcs)', 'eng_name' => '1 box (42 pcs)', 'quantity' => 42),
            array('code' => 'BOX50', 'name' => '1 box (50 pcs)', 'eng_name' => '1 box (50 pcs)', 'quantity' => 50),
            array('code' => 'BOX72', 'name' => '1 box (72 pcs)', 'eng_name' => '1 box (72 pcs)', 'quantity' => 72),
            array('code' => 'BOX120', 'name' => '1 box (120 pcs)', 'eng_name' => '1 box (120 pcs)', 'quantity' => 120),
        ));

        DB::table('categories')->insert(array(
            array('code' => 'BEAUTY', 'name' => 'Perawatan dan Kecantikan', 'eng_name' => 'Beauty Care', 'unit_id' => 1),
            array('code' => 'BABY', 'name' => 'Perlengkapan Bayi', 'eng_name' => 'Baby Products', 'unit_id' => 1),
            array('code' => 'BATH', 'name' => 'Perlengkapan Mandi', 'eng_name' => 'Bath Products', 'unit_id' => 1),
            array('code' => 'CLOTHING', 'name' => 'Pakaian', 'eng_name' => 'Clothing', 'unit_id' => 1),
            array('code' => 'ELECTRONIC', 'name' => 'Barang Elektronik', 'eng_name' => 'Electronic Products', 'unit_id' => 1),
            array('code' => 'GARDENING', 'name' => 'Peralatan Berkebun', 'eng_name' => 'Gardening Tools', 'unit_id' => 1),
            array('code' => 'KiGARDENING', 'name' => 'Peralatan Berkebun Kiloan', 'eng_name' => 'Gardening Tools Kilo', 'unit_id' => 14),
            array('code' => 'MEDICINE', 'name' => 'Kesehatan', 'eng_name' => 'Health and Medical', 'unit_id' => 1),
            array('code' => 'HOUSEWARE', 'name' => 'Peralatan Rumah Tangga', 'eng_name' => 'Houseware Products', 'unit_id' => 1),
            array('code' => 'STATIONERY', 'name' => 'Buku dan Alat Tulis', 'eng_name' => 'Stationery Products', 'unit_id' => 1),
            array('code' => 'KiFOOD', 'name' => 'Makanan dan Minuman Kiloan', 'eng_name' => 'Snacks Kilo', 'unit_id' => 14),
            array('code' => 'FOOD', 'name' => 'Makanan dan Minuman', 'eng_name' => 'Foods and Beverages', 'unit_id' => 1),
            array('code' => 'BEDDING', 'name' => 'Bedding', 'eng_name' => 'Bedding', 'unit_id' => 1),
            array('code' => 'CLEANING', 'name' => 'Peralatan Pembersih', 'eng_name' => 'Cleaning Products', 'unit_id' => 1),
        ));
    }
}
