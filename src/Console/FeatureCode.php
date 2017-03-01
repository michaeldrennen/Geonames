<?php

namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use MichaelDrennen\Geonames\Log;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;

class FeatureCode extends Command {
    use BaseTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:feature-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download and insert the feature code list from geonames.";

    /**
     * @var Curl Instance of a Curl object that we use to download the files.
     */
    protected $curl;

    protected $featureCodeRemoteFileName = '';
    protected $featureCodeRemoteFilePath = '';
    protected $featureCodeLocalFilePath = '';


    /**
     * Create a new command instance.
     * @param Curl $curl
     */
    public function __construct() {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //
        $this->line("Starting " . $this->signature);

        $this->setFeatureCodeRemoteFileName();
        $this->setFeatureCodeRemoteFilePath();
        $this->setStorage();
        $this->downloadFeatureCodeFile();
        $this->insert($this->featureCodeLocalFilePath);

        $this->line("Finished " . $this->signature);
    }


    protected function setFeatureCodeRemoteFileName() {
        $this->featureCodeRemoteFileName = config('geonames.feature_code_file_name_prefix') . config('geonames.language') . '.txt';
    }

    protected function setFeatureCodeRemoteFilePath() {
        $this->featureCodeRemoteFilePath = config('geonames.download_base_url') . $this->featureCodeRemoteFileName;
    }

    protected function setFeatureCodeLocalFilePath() {
        $this->featureCodeLocalFilePath = $this->getStorage() . $this->featureCodeRemoteFileName;
    }

    protected function downloadFeatureCodeFile() {
        $curl = new Curl;
        $curl->get($this->featureCodeRemoteFilePath);

        if ($curl->error) {

            Log::error($this->featureCodeRemoteFilePath, $curl->error_message, $curl->error_code);
            throw new \Exception("Unable to download the file at '" . $this->featureCodeRemoteFilePath . "', " . $curl->error_message);
        }


        $data = $curl->response;
        $bytesWritten = file_put_contents($this->featureCodeLocalFilePath, $data);
        if ($bytesWritten === false) {
            Log::error($this->featureCodeRemoteFilePath, "Unable to create the local file at '" . $this->featureCodeLocalFilePath . "', file_put_contents() returned false. Disk full? Permission problem?", 'local');
            throw new \Exception("Unable to create the local file at '" . $this->featureCodeLocalFilePath . "', file_put_contents() returned false. Disk full? Permission problem?");
        }
    }

    /**
     * @param $localFilePath
     * @return array
     */
    protected function fileToArray($localFilePath) {
        $rows = [];
        if (($handle = fopen($localFilePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 0, "\t")) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }


    protected function insert($localFilePath) {

        $rows = $this->fileToArray($localFilePath);

        foreach ($rows as $row) {
            list($id, $name, $description) = $row;
            list($feature_class, $feature_code) = $id;

            DB::table('geo_feature_codes')->insert(['id'            => $id,
                                                    'feature_class' => $feature_class,
                                                    'feature_code'  => $feature_code,
                                                    'name'          => $name,
                                                    'description'   => $description,]);
        }


    }



}
