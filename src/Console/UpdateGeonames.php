<?php

namespace MichaelDrennen\Geonames\Console;

use MichaelDrennen\Geonames\Geoname;
use MichaelDrennen\Geonames\Log;
use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\Geonames\Console\GeonamesConsoleTrait;

use Symfony\Component\DomCrawler\Crawler;
use Curl\Curl;
use Goutte\Client;
use Illuminate\Console\Command;

use StdClass;


class UpdateGeonames extends Command {
    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:update-geonames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download the modifications txt file from geonames.org, then update our database.";

    /**
     * The actual file name looks like 'modifications-2017-02-22.txt' which we will set in the constructor.
     *
     * @var string
     */
    protected $modificationsTxtFileNamePrefix = 'modifications-';

    /**
     * Set in the constructor.
     * @var string
     */
    protected $modificationsTxtFileName;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $urlForDownloadList = 'http://download.geonames.org/export/dump/';

    /**
     * @var array
     */
    protected $linksOnDownloadPage = [];

    /**
     * @var float When the update started. Microseconds included.
     */
    protected $startTime;

    /**
     * @var float When the update ended. Microseconds included.
     */
    protected $endTime;

    /**
     * @var float How long the update command took to run. Microseconds included.
     */
    protected $runTime;


    /**
     * Update constructor.
     * @param Curl $curl
     * @param Client $client
     */
    public function __construct(Curl $curl, Client $client) {
        parent::__construct();
        $this->curl = $curl;
        $this->client = $client;
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        GeoSetting::setStatus(GeoSetting::STATUS_UPDATING);
        $this->startTime = (float)microtime(true);
        $this->line("Starting " . $this->signature);


        // Download the file from geonames.org and save it on local storage.
        $localFilePath = $this->saveRemoteModificationsFile();

        //
        $modificationRows = $this->prepareRowsForUpdate($localFilePath);

        $bar = $this->output->createProgressBar(count($modificationRows));

        foreach ($modificationRows as $obj):

            try {
                $geoname = Geoname::firstOrNew(['geonameid' => $obj->geonameid]);

                $geoname->name = $obj->name;
                $geoname->asciiname = $obj->asciiname;
                $geoname->alternatenames = $obj->alternatenames;
                $geoname->latitude = $obj->latitude;
                $geoname->longitude = $obj->longitude;
                $geoname->feature_class = $obj->feature_class;
                $geoname->feature_code = $obj->feature_code;
                $geoname->country_code = $obj->country_code;
                $geoname->cc2 = $obj->cc2;
                $geoname->admin1_code = $obj->admin1_code;
                $geoname->admin2_code = $obj->admin2_code;
                $geoname->admin3_code = $obj->admin3_code;
                $geoname->admin4_code = $obj->admin4_code;
                $geoname->population = $obj->population;
                $geoname->elevation = $obj->elevation;
                $geoname->dem = $obj->dem;
                $geoname->timezone = $obj->timezone;
                $geoname->modification_date = $obj->modification_date;

                if (!$geoname->isDirty()) {
                    //$this->info("Geoname record " . $obj->geonameid . " does not need to be updated.");
                    $bar->advance();
                    continue;
                }

                $saveResult = $geoname->save();

                if ($saveResult) {

                    if ($geoname->wasRecentlyCreated) {
                        Log::insert('', "Geoname record " . $obj->geonameid . " was inserted.", "create");
                    } else {
                        Log::modification('', "Geoname record " . $obj->geonameid . " was updated.", "update");
                    }
                    $bar->advance();

                } else {
                    Log::error('', "Unable to updateOrCreate geoname record: [" . $obj->geonameid . "]");
                    $bar->advance();
                    continue;
                }

            } catch (\Exception $e) {
                Log::error('', $e->getMessage() . " Unable to save the geoname record with id: [" . $obj->geonameid . "]", 'database');
                $bar->advance();
            }
        endforeach;

        $this->endTime = (float)microtime(true);
        $this->runTime = $this->endTime - $this->startTime;
        Log::info('', "Finished updates in " . $localFilePath . " in " . $this->runTime . " seconds.", 'update');
        $this->line("Finished " . $this->signature);
        GeoSetting::setStatus(GeoSetting::STATUS_LIVE);

        return true;
    }

    /**
     * Given the local path to the modifications file, pull it into an array, and mung the rows so they are ready
     * to be sent to the Laravel model for updates.
     * @param string $absoluteLocalFilePath
     * @return array An array of StdClass objects to be passed to the Laravel model.
     */
    protected function prepareRowsForUpdate(string $absoluteLocalFilePath): array {
        $modificationRows = file($absoluteLocalFilePath);

        // An array of StdClass objects to be passed to the Laravel model.
        $geonamesData = [];
        foreach ($modificationRows as $row):

            $array = explode("\t", $row);
            $array = array_map('trim', $array);

            $object = new StdClass;
            $object->geonameid = $array[0];
            $object->name = $array[1];
            $object->asciiname = $array[2];
            $object->alternatenames = $array[3];

            // The lat and long fields are decimal (nullable), so if the value in the modifications file is blank, we
            // want the value to be null instead of 0 (zero).
            $object->latitude = empty($array[4]) ? null : number_format((float)$array[4], 8);
            $object->longitude = empty($array[5]) ? null : number_format((float)$array[5], 8);

            $object->feature_class = $array[6];
            $object->feature_code = $array[7];
            $object->country_code = $array[8];
            $object->cc2 = $array[9];
            $object->admin1_code = $array[10];
            $object->admin2_code = $array[11];
            $object->admin3_code = $array[12];
            $object->admin4_code = $array[13];
            $object->population = $array[14];

            // Null is different than zero, which was getting entered when the field was blank.
            $object->elevation = empty($array[15]) ? null : $array[15];
            $object->dem = empty($array[16]) ? null : $array[16];

            $object->timezone = $array[17];
            $object->modification_date = $array[18];
            $geonamesData[] = $object;
        endforeach;

        return $geonamesData;
    }


    /**
     * Go to the downloads page on the geonames.org site, download the modifications file, and
     * save it locally.
     * @return string The absolute file path of the local copy of the modifications file.
     * @throws \Exception
     */
    protected function saveRemoteModificationsFile() {
        $this->line("Downloading the modifications file from geonames.org");

        // Grab the remote file.
        $this->linksOnDownloadPage = $this->getAllLinksOnDownloadPage();
        $this->modificationsTxtFileName = $this->filterModificationsLink($this->linksOnDownloadPage);
        $absoluteUrlToModificationsFile = $this->urlForDownloadList . '/' . $this->modificationsTxtFileName;
        $this->curl->get($absoluteUrlToModificationsFile);


        if ($this->curl->error) {
            $this->error($this->curl->error_code . ':' . $this->curl->error_message);
            Log::error($absoluteUrlToModificationsFile, $this->curl->error_message, $this->curl->error_code);
            throw new \Exception("Unable to download the file at '" . $absoluteUrlToModificationsFile . "', " . $this->curl->error_message);
        }

        $data = $this->curl->response;
        $this->info("Downloaded " . $absoluteUrlToModificationsFile);


        // Save it locally
        $localFilePath = $this->storageDir . DIRECTORY_SEPARATOR . $this->modificationsTxtFileName;
        $bytesWritten = file_put_contents($localFilePath, $data);
        if ($bytesWritten === false) {
            Log::error($absoluteUrlToModificationsFile, "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?", 'local');
            throw new \Exception("Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?");
        }
        $this->info("Saved modification file to: " . $localFilePath);

        return $localFilePath;
    }


    /**
     * Returns a list of every link (href) on the geonames.org page for downloads.
     * @return array
     */
    protected function getAllLinksOnDownloadPage(): array {
        $crawler = $this->client->request('GET', $this->urlForDownloadList);

        return $crawler->filter('a')->each(function (Crawler $node) {
            return $node->attr('href');
        });
    }


    /**
     * The geonames.org link to the modifications file has a different file name every day.
     * This function accepts an array of ALL of the links from the downloads page, and returns
     * the file name of the current modifications file.
     * @param array $links The list of links on the geonames export page.
     * @return string The file name of the current modifications file on the geonames website.
     * @throws \Exception If we can't find the modifications file name in the list of links.
     */
    protected function filterModificationsLink(array $links): string {
        foreach ($links as $link) {
            if (preg_match('/^modifications-/', $link) === 1) {
                return $link;
            }
        }
        throw new \Exception("We were unable to find the modifications file on the geonames site. This is very unusual.");
    }
}
