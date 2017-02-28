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

        $data = file_get_contents($this->featureCodeLocalFilePath);


        DB::table('geo_feature_classes')->insert(['id'          => 'A',
                                                  'description' => 'country, state, region,...',]);
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


    protected function insert($localFilePath) {

        Schema::dropIfExists('geonames_working');


        DB::statement('CREATE TABLE geonames_working LIKE geonames; ');

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE geonames_working
        (geonameid, 
             name, 
             asciiname, 
             alternatenames, 
             latitude, 
             longitude, 
             feature_class, 
             feature_code, 
             country_code, 
             cc2, 
             admin1_code, 
             admin2_code, 
             admin3_code, 
             admin4_code, 
             population, 
             elevation, 
             dem, 
             timezone, 
             modification_date, 
             @created_at, 
             @updated_at)
SET created_at=NOW(),updated_at=null";

        $this->comment($query);

        $rowsInserted = DB::connection()->getpdo()->exec($query);
        if ($rowsInserted === false) {
            throw new \Exception("Unable to execute the load data infile query.");
        }

        $this->info("Inserted text file into geonames_working.");

        Schema::dropIfExists('geonames');
        $this->line("Dropped the geonames table.");

        Schema::rename('geonames_working', 'geonames');
        $this->info("Renamed geonames_working to geonames.");

    }
}