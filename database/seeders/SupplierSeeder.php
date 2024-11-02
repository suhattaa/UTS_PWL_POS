<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'supplier_id' => 1,
                'supplier_kode' => 'S01', 
                'supplier_nama' => 'Kurohige',
                'supplier_alamat' => 'Surabaya',
            ],
            [
                'supplier_id' => 2,
                'supplier_kode' => 'S02', 
                'supplier_nama' => 'Clown',
                'supplier_alamat' => 'Jakarta',
            ],
            [
                'supplier_id' => 3,
                'supplier_kode' => 'S03', 
                'supplier_nama' => 'Beast',
                'supplier_alamat' => 'Bandung',
            ],
        ];
        DB::table('m_supplier')->insert($data);
    }
}
