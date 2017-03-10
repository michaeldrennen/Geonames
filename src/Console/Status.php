<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Console\GeonamesConsoleTrait;
use MichaelDrennen\Geonames\GeoSetting;

class Status extends Command {

    use GeonamesConsoleTrait;

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

        print_r($settings);


        $headers = ['Status',
                    'Countries',
                    'Countries to be Added',
                    'Storage Subdir',
                    'Installed',
                    'Last Modified',
                    'Created',
                    'Updated'];

        $settings = [$settings->status,
                     implode("\n", $settings->countries),
                     implode("\n", $settings->countries_to_be_added),
                     $settings->storage_subdir,
                     $settings->installed_at,
                     $settings->last_modified_at,
                     $settings->created_at,
                     $settings->updated_at];

        $this->table($headers, $settings);
    }

}
