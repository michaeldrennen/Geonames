<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MichaelDrennen\Geonames\BaseTrait;
use Curl\Curl;
use MichaelDrennen\Geonames\Log;

class FeatureCodeSeeder extends Seeder {
    use BaseTrait;
    protected $featureCodeRemoteFileName = '';
    protected $featureCodeRemoteFilePath = '';
    protected $featureCodeLocalFilePath = '';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Curl $curl) {
        $this->setFeatureCodeRemoteFileName();
        $this->setFeatureCodeRemoteFilePath();
        $this->setStorage();
        $this->downloadFeatureCodeFile($curl);

        $this->insert($this->featureCodeLocalFilePath);



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

    protected function downloadFeatureCodeFile(Curl $curl) {
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
            DB::table('geo_feature_codes')->insert(['id'          => 'A',
                                                    'description' => 'country, state, region,...',]);
        }



    }
}