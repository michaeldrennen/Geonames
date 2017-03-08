<?php

namespace MichaelDrennen\Geonames\Console;

use Carbon\Carbon;
use Curl\Curl;
use MichaelDrennen\Geonames\Log;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\BaseTrait;
use Illuminate\Support\Facades\DB;

class FeatureCode extends Command {

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
        $this->line("Starting " . $this->signature . "\n");

        $this->setFeatureCodeRemoteFileName();
        $this->setFeatureCodeRemoteFilePath();

        $this->setFeatureCodeLocalFilePath();
        $this->downloadFeatureCodeFile();
        $this->insert($this->featureCodeLocalFilePath);

        $this->line("\nFinished " . $this->signature);
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


    /**
     * After the featureCodes file is saved locally, we truncate our database table, and insert the data.
     * @param string $localFilePath
     */
    protected function insert(string $localFilePath) {

        DB::table('geo_feature_codes')->truncate();

        $rows = $this->fileToArray($localFilePath);

        //
        $numRowsInserted = 0;

        //
        $numRowsNotInserted = 0;

        //
        $numRowsInTheFile = count($rows);


        // Progress bar for console display.
        $bar = $this->output->createProgressBar($numRowsInTheFile);

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 1;

            if (!$this->isValidRow($row)) {
                $this->line("\nRow " . $rowNumber . " of " . $numRowsInTheFile . " is not valid, and won't be inserted.\n");
                continue;
            }

            list($id, $name, $description) = $row;
            list($feature_class, $feature_code) = explode('.', $id);


            $insertResult = DB::table('geo_feature_codes')->insert(['id'            => $id,
                                                                    'feature_class' => $feature_class,
                                                                    'feature_code'  => $feature_code,
                                                                    'name'          => $name,
                                                                    'description'   => $description,
                                                                    'created_at'    => Carbon::now(),
                                                                    'updated_at'    => Carbon::now(),]);

            if ($insertResult === true) {
                $numRowsInserted++;
                //$this->info("Row " .  $rowNumber. " of " . $numRowsInTheFile .  " was inserted.");
            } else {
                $numRowsNotInserted++;
                $this->error("\nRow " . $rowNumber . " of " . $numRowsInTheFile . " was NOT inserted.");
            }
            $bar->advance();
        }

        $bar->finish();

        if ($numRowsNotInserted > 0) {
            Log::error($localFilePath, "There was at least one row from the featureCodes file that was not inserted.", 'database');
        }


    }

    /**
     * The geonames.org featureCodes file has an ending row:
     * "null	not available"
     * I'm not sure if it will always be there. (If it is, then I could just pop it off the end.)
     * Since I can't necessarily count on that, then let's do a more robust check to make sure the row is valid.
     * Basically make sure that whatever data is in that row can be inserted into the database.
     */
    protected function isValidRow(array $row) {
        $classAndCode = explode('.', $row[0]);
        if (count($classAndCode) != 2) {
            return false;
        }
        if (empty($classAndCode[0]) || empty($classAndCode[1])) {
            return false;
        }

        return true;
    }


}
