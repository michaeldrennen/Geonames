<?php

namespace MichaelDrennen\Geonames\Console;

use League\Flysystem\Exception;
use MichaelDrennen\Geonames\Geoname;
use MichaelDrennen\Geonames\Log;
use Symfony\Component\DomCrawler\Crawler;
use Curl\Curl;
use Goutte\Client;


class Update extends Base {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:update';

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

    protected $curl;
    protected $client;

    protected $urlForDownloadList = 'http://download.geonames.org/export/dump/';

    protected $linksOnDownloadPage = [];


    /**
     * Initialize constructor.
     */
    public function __construct(Curl $curl, Client $client) {
        parent::__construct();
        $this->setModificationFileNameForToday();
        $this->curl = $curl;
        $this->client = $client;
    }

    /**
     * @todo Check when the file on geonames.org gets updated. Take their timezone into account.
     */
    protected function setModificationFileNameForToday() {
        $this->modificationsTxtFileName = $this->modificationsTxtFileNamePrefix . date('Y-m-d') . '.txt';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->line("Starting " . $this->signature);

        $localFilePath = $this->saveRemoteModificationsFile();

        $modificationRows = file($localFilePath);

        foreach ($modificationRows as $row) {
            $array = explode("\t", $row);

            $geoname = Geoname::find($array[0]);
            $geoname->name = $array[1];
            $geoname->asciiname = $array[2];
            $geoname->alternatenames = $array[3];
            $geoname->latitude = $array[4];
            $geoname->longitude = $array[5];
            $geoname->feature_class = $array[6];
            $geoname->feature_code = $array[7];
            $geoname->country_code = $array[8];
            $geoname->cc2 = $array[9];
            $geoname->admin1_code = $array[10];
            $geoname->admin2_code = $array[11];
            $geoname->admin3_code = $array[12];
            $geoname->admin4_code = $array[13];
            $geoname->population = $array[14];
            $geoname->elevation = $array[15];
            $geoname->dem = $array[16];
            $geoname->timezone = $array[17];
            $geoname->modification_date = $array[18];

            try {

            } catch (\Exception $e) {
                Log::error('', $e->getMessage() . " [Unable to save the geoname record with id: " . $array[0] . "]", 'database');
            }
            $saveResult = $geoname->save();
            if ($saveResult === false) {
                Log::error('', "Unable to update the geoname record with id: " . $array[0], 'database');
                $this->error("Unable to update the geoname record with id: " . $array[0]);
            } else {
                $this->info("Geoname record " . $array[0] . " was updated.");
            }

        }

        $this->line("Finished " . $this->signature);
    }


    protected function saveRemoteModificationsFile() {

        // Grab the remote file.
        $this->linksOnDownloadPage = $this->getAllLinksOnDownloadPage();
        $modificationFileName = $this->filterModificationsLink($this->linksOnDownloadPage);
        $absoluteUrlToModificationsFile = $this->urlForDownloadList . '/' . $modificationFileName;
        $this->curl->get($absoluteUrlToModificationsFile);


        if ($this->curl->error) {
            $this->error($this->curl->error_code . ':' . $this->curl->error_message);
            Log::error($absoluteUrlToModificationsFile, $this->curl->error_message, $this->curl->error_code);
            throw new \Exception("Unable to download the file at '" . $absoluteUrlToModificationsFile . "', " . $this->curl->error_message);
        }

        $this->info("Downloaded " . $absoluteUrlToModificationsFile);


        $data = $this->curl->response;

        // Save it locally
        $localFilePath = $this->storageDir . DIRECTORY_SEPARATOR . $modificationFileName;


        $bytesWritten = file_put_contents($localFilePath, $data);
        if ($bytesWritten === false) {
            Log::error($absoluteUrlToModificationsFile, "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?", 'local');
            throw new \Exception("Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?");
        }

        return $localFilePath;
    }


    /**
     * @return array
     */
    protected function getAllLinksOnDownloadPage() {
        $crawler = $this->client->request('GET', $this->urlForDownloadList);
        return $crawler->filter('a')->each(function (Crawler $node, $i) {
            return $node->attr('href');
        });
    }


    /**
     * @param array $links The list of links on the geonames export page.
     * @return string The file name of the current modifications file on the geonames website.
     * @throws \Exception If we can't find the modifications file name in the list of links.
     */
    protected function filterModificationsLink(array $links) {
        foreach ($links as $link) {
            if (preg_match('/^modifications-/', $link) === 1) {
                return $link;
            }
        }
        throw new \Exception("We were unable to find the modifications file on the geonames site. This is very unusual.");
    }
}
