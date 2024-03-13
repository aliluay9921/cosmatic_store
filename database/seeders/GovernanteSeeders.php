<?php

namespace Database\Seeders;

use App\Models\Governante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GovernanteSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        Governante::create([
            "name" => "بغداد",
            "price" => "5000",
        ]);
        Governante::create([
            "name" => "بصرة",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "كربلاء",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "بابل",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "نجف",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "اربيل",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "موصل",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "سليمانية",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "دهوك",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "عمارة",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "ناصرية",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "كوت",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "انبار",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "ديالى",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "كركوك",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "صلاح الدين",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "الديوانية ",
            "price" => "6000",
        ]);
        Governante::create([
            "name" => "السماوة ",
            "price" => "6000",
        ]);
    }
}
