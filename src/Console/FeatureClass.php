<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class FeatureClass extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:feature-class';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the FeatureClasses table.";


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command.
     * I don't worry about creating a temp/working table here, because it runs so fast. We're
     * only inserting a couple rows.
     */
    public function handle() {
        $this->startTimer();

        DB::table('geo_feature_classes')->truncate();


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


        $this->info( "The feature_classes table was truncated and refilled in " . $this->getRunTime() . " seconds." );
    }
}
