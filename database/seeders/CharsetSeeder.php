<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Charsets;

class CharsetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $data_insert[] = ['charset' => 'utf-8'];
        $data_insert[] = ['charset' => 'iso-8859-1'];
        $data_insert[] = ['charset' => 'iso-8859-2'];
        $data_insert[] = ['charset' => 'iso-8859-3'];
        $data_insert[] = ['charset' => 'iso-8859-4'];
        $data_insert[] = ['charset' => 'iso-8859-5'];
        $data_insert[] = ['charset' => 'iso-8859-6'];
        $data_insert[] = ['charset' => 'iso-8859-7'];
        $data_insert[] = ['charset' => 'iso-8859-8'];
        $data_insert[] = ['charset' => 'iso-8859-9'];
        $data_insert[] = ['charset' => 'iso-8859-10'];
        $data_insert[] = ['charset' => 'iso-8859-10'];
        $data_insert[] = ['charset' => 'iso-8859-13'];
        $data_insert[] = ['charset' => 'iso-8859-14'];
        $data_insert[] = ['charset' => 'iso-8859-15'];
        $data_insert[] = ['charset' => 'iso-8859-16'];
        $data_insert[] = ['charset' => 'windows-1250'];
        $data_insert[] = ['charset' => 'windows-1251'];
        $data_insert[] = ['charset' => 'windows-1252'];
        $data_insert[] = ['charset' => 'windows-1253'];
        $data_insert[] = ['charset' => 'windows-1254'];
        $data_insert[] = ['charset' => 'windows-1255'];
        $data_insert[] = ['charset' => 'windows-1256'];
        $data_insert[] = ['charset' => 'windows-1257'];
        $data_insert[] = ['charset' => 'windows-1258'];
        $data_insert[] = ['charset' => 'gb2312'];
        $data_insert[] = ['charset' => 'big5'];
        $data_insert[] = ['charset' => 'iso-2022-jp'];
        $data_insert[] = ['charset' => 'ks_c_5601-1987'];
        $data_insert[] = ['charset' => 'euc-kr'];
        $data_insert[] = ['charset' => 'windows-874'];
        $data_insert[] = ['charset' => 'koi8-r'];
        $data_insert[] = ['charset' => 'koi8-u'];

        foreach ($data_insert as $row) {
            Charsets::create(['charset' => $row['charset']]);
        }
    }
}
