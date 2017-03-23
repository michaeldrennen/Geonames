<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Models\BaseTrait;
use MichaelDrennen\Geonames\Models\GeoSetting;

class Test extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:test';

    /**
     * @var string The console command description.
     */
    protected $description = "A testing ground for new functions.";


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
        $this->line("Starting " . $this->signature);

        $dir = GeoSetting::getStorage();
        $this->line(print_r($dir, true));


        $this->line("Finished " . $this->signature);
    }


}
