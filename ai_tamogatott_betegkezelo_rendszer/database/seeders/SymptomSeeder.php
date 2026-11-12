<?php

namespace Database\Seeders;

use App\Models\Symptom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SymptomSeeder extends Seeder
{

    public function run(): void
    {
        $symptoms = [
            ['name' => 'Köhögés', 'description' => 'Száraz vagy produktív köhögés.'],
            ['name' => 'Láz', 'description' => '38°C feletti testhőmérséklet.',
                'detail_type' => 'number', 'detail_label' => 'Legmagasabb mért hőmérséklet',
                'detail_unit' => '°C', 'detail_min' => 35, 'detail_max' => 43],
            ['name' => 'Fejfájás', 'description' => 'Enyhe vagy erős fejfájás.'],
            ['name' => 'Torokfájás', 'description' => 'Nyelési nehézséggel járó torokfájdalom.'],
            ['name' => 'Fáradtság', 'description' => 'Szokatlen mértékű kimerültség, energiahiány.'],
            ['name' => 'Izomfájdalom', 'description' => 'Izomláz jellegű vagy izületi fájdalom.'],
            ['name' => 'Hányinger', 'description' => 'Hányinger, esetenként hányással.'],
            ['name' => 'Orrfolyás', 'description' => 'Orrdugulás, orrfoláys.'],
            ['name' => 'Légszomj', 'description' => 'Nehézlégzés, levegő után kapkodás.'],
            ['name' => 'Hasmenés', 'description' => 'Gyakori, laza székletürítés.'],
            ['name' => 'Szédülés', 'description' => 'Szédülésérzet, egyensúlyzavar.'],
            ['name' => 'Bőrkiütés', 'description' => 'Bőrelváltozás, kiütés, viszketés.',
                'detail_type' => 'text', 'detail_label' => 'Hol jelentkezik?'],
            ['name' => 'Gyógyszerallergia', 'description' => 'Ismert gyógyszer-túlérzékenység.',
                'detail_type' => 'text', 'detail_label' => 'Melyik gyógyszer(ek)re?'],
            ['name' => 'Ételallergia', 'description' => 'Ismert élelmiszer-túlérzékenység.',
                'detail_type' => 'text', 'detail_label' => 'Melyik élelmiszer(ek)re?'],
        ];

        foreach ($symptoms as $symptom) {
            Symptom::updateOrCreate(['name' => $symptom['name']], $symptom);
        }
    }
}
