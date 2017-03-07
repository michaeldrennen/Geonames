<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MichaelDrennen\Geonames\BaseTrait;
use MichaelDrennen\Geonames\GeoSetting;

class Install extends Command {

    use BaseTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:install {--country=* : Add the 2 digit code for each country. One per option.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Run this after the migrations to populate the tables.";

    /**
     * @var float When this command starts.
     */
    protected $startTime;

    /**
     * @var float When this command ends.
     */
    protected $endTime;

    /**
     * @var float The number of seconds that this command took to run.
     */
    protected $runTime;


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();


    }


    /**
     * Execute the console command.
     */
    public function handle() {
        $this->createSettings($this->option('country'));
        GeoSetting::setStatus(GeoSetting::STATUS_INSTALLING);
        $this->startTime = microtime(true);
        $this->line("Starting " . $this->signature);

        try {
            $this->call('geonames:feature-class');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error($e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            GeoSetting::setStatus(GeoSetting::STATUS_ERROR);
            return false;
        }

        try {
            $this->call('geonames:feature-code');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            GeoSetting::setStatus(GeoSetting::STATUS_ERROR);
            return false;
        }

        try {
            $this->call('geonames:download');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            GeoSetting::setStatus(GeoSetting::STATUS_ERROR);
            return false;
        }

        try {
            $this->call('geonames:initialize');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            GeoSetting::setStatus(GeoSetting::STATUS_ERROR);
            return false;
        }


        $this->endTime = microtime(true);
        $this->runTime = $this->endTime - $this->startTime;
        GeoSetting::setStatus(GeoSetting::STATUS_LIVE);
        $this->line("Finished " . $this->signature);
    }


    protected function createSettings(array $countries = ['*']) {
        // Truncate settings table.
        DB::table('geo_settings')->truncate();

        // Create settings record.
        GeoSetting::create(['id'        => 1,
                            'countries' => $countries]);

    }


}
