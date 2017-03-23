<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class FeatureClassSeeder
 *
 * feature classes from the geonames.org website:
 * A: country, state, region,...
 * H: stream, lake, ...
 * L: parks,area, ...
 * P: city, village,...
 * R: road, railroad
 * S: spot, building, farm
 * T: mountain,hill,rock,...
 * U: undersea
 * V: forest,heath,...
 */
class FeatureClassSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('geo_feature_classes')->insert(['id'          => 'A',
                                                  'description' => 'country, state, region,...',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'H',
                                                  'description' => 'stream, lake, ...',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'L',
                                                  'description' => 'parks,area, ...',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'P',
                                                  'description' => 'city, village,...',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'R',
                                                  'description' => 'road, railroad',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'S',
                                                  'description' => 'spot, building, farm',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'T',
                                                  'description' => 'mountain,hill,rock,...',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'U',
                                                  'description' => 'undersea',]);

        DB::table('geo_feature_classes')->insert(['id'          => 'V',
                                                  'description' => 'forest,heath,...',]);
    }
}