<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;
use MichaelDrennen\Geonames\GeoSetting;

class Status extends Command {

    use BaseTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:status';

    /**
     * @var string The console command description.
     */
    protected $description = "Outputs the status of this geonames installation.";


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
        $this->line("Status of this Geonames Installation");
        $settings = GeoSetting::first();

        $headers = [];
        $settings = $settings->toArray();
        $this->table($headers, $settings);

        $this->line("Finished " . $this->signature);
    }

}
