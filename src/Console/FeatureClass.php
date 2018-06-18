<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;


class FeatureClass extends Command {

    use GeonamesConsoleTrait;

    const TABLE = 'geonames_feature_classes';
    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:feature-class
        {--connection= : If you want to specify the name of the database connection you want used.}';
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

        $this->connectionName = $this->option( 'connection' );

        try {
            $this->setDatabaseConnectionName();
            $this->info( "The database connection name was set to: " . $this->connectionName );
            $this->comment( "Testing database connection..." );
            $this->checkDatabase();
            $this->info( "Confirmed database connection set up correctly." );
        } catch ( \Exception $exception ) {
            $this->error( $exception->getMessage() );
            $this->stopTimer();
            return FALSE;
        }

        try {
            GeoSetting::init(
                GeoSetting::DEFAULT_COUNTRIES_TO_BE_ADDED,
                GeoSetting::DEFAULT_LANGUAGES,
                GeoSetting::DEFAULT_STORAGE_SUBDIR,
                $this->connectionName );
        } catch ( \Exception $exception ) {
            Log::error( NULL, "Unable to initialize the GeoSetting record." );
            $this->stopTimer();
            return FALSE;
        }

        $progressBar = $this->output->createProgressBar( 9 );
        $progressBar->setFormat( "Inserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );
        $progressBar->setMessage( 'feature classes' );
        $progressBar->advance();

        DB::table( self::TABLE )->truncate();


        DB::table( self::TABLE )->insert( ['id'          => 'A',
                                           'description' => 'country, state, region,...',] );
        $progressBar->advance();

        DB::table( self::TABLE )->insert( ['id'          => 'H',
                                           'description' => 'stream, lake, ...',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'L',
                                           'description' => 'parks,area, ...',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'P',
                                           'description' => 'city, village,...',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'R',
                                           'description' => 'road, railroad',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'S',
                                           'description' => 'spot, building, farm',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'T',
                                           'description' => 'mountain,hill,rock,...',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'U',
                                           'description' => 'undersea',] );
        $progressBar->advance();
        DB::table( self::TABLE )->insert( ['id'          => 'V',
                                           'description' => 'forest,heath,...',] );
        $progressBar->advance();

        $this->info( self::TABLE . " table was truncated and refilled in " . $this->getRunTime() . " seconds." );
    }
}
