<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class FeatureClass extends Command {

    use GeonamesConsoleTrait;

    const TABLE = 'geonames_feature_classes';
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
    public function __construct () {
        parent::__construct();
    }


    /**
     * Execute the console command.
     * I don't worry about creating a temp/working table here, because it runs so fast. We're
     * only inserting a couple rows.
     */
    public function handle () {
        $this->startTimer();

        DB::table( self::TABLE )->truncate();


        DB::table( self::TABLE )->insert( ['id'          => 'A',
                                           'description' => 'country, state, region,...',] );

        DB::table( self::TABLE )->insert( ['id'          => 'H',
                                           'description' => 'stream, lake, ...',] );

        DB::table( self::TABLE )->insert( ['id'          => 'L',
                                           'description' => 'parks,area, ...',] );

        DB::table( self::TABLE )->insert( ['id'          => 'P',
                                           'description' => 'city, village,...',] );

        DB::table( self::TABLE )->insert( ['id'          => 'R',
                                           'description' => 'road, railroad',] );

        DB::table( self::TABLE )->insert( ['id'          => 'S',
                                           'description' => 'spot, building, farm',] );

        DB::table( self::TABLE )->insert( ['id'          => 'T',
                                           'description' => 'mountain,hill,rock,...',] );

        DB::table( self::TABLE )->insert( ['id'          => 'U',
                                           'description' => 'undersea',] );

        DB::table( self::TABLE )->insert( ['id'          => 'V',
                                           'description' => 'forest,heath,...',] );


        $this->info( self::TABLE . " table was truncated and refilled in " . $this->getRunTime() . " seconds." );
    }
}
