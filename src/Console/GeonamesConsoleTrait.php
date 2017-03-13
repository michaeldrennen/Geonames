<?php
namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\RemoteFile\RemoteFile;
use MichaelDrennen\Geonames\Log;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use ZipArchive;

trait GeonamesConsoleTrait {

    /**
     * @var string  The base download URL for the geonames.org site.
     *              I use a static var instead of a const because traits can't have constants.
     */
    protected static $url = 'http://download.geonames.org/export/dump/';

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
     * Start the timer. Record the start time in startTime()
     */
    protected function startTimer () {
        $this->startTime = microtime( true );
    }

    /**
     * Stop the timer. Record the end time in endTime, and the time elapsed in runTime.
     */
    protected function stopTimer () {
        $this->endTime = microtime( true );
        $this->runTime = $this->endTime - $this->startTime;
    }

    /**
     * This will return the time between startTimer() and stopTimer(), OR between
     * startTimer() and now
     * @return float    The time elapsed in seconds.
     */
    protected function getRunTime (): float {
        if ( $this->runTime > 0 ) {
            return (float)$this->runTime;
        }

        return (float)microtime( true ) - $this->startTime;
    }

    /**
     * @return array An array of all the anchor tag href attributes on the given url parameter.
     */
    public static function getAllLinksOnDownloadPage(): array {
        $curl = new Curl();

        $curl->get(self::$url);
        $html = $curl->response;

        $crawler = new Crawler($html);

        return $crawler->filter('a')->each(function (Crawler $node) {
            return $node->attr('href');
        });
    }


    /**
     * @param Command $command
     * @param array $downloadLinks
     * @return array
     */
    public static function downloadFiles(Command $command, array $downloadLinks): array {
        $localFilePaths = [];
        foreach ($downloadLinks as $link) {
            $localFilePaths[] = self::downloadFile($command, $link);
        }

        return $localFilePaths;
    }

    /**
     * @param Command $command The command instance from the console script.
     * @param string $link The absolute path to the remote file we want to download.
     * @return string           The absolute local path to the file we just downloaded.
     * @throws \Exception
     */
    public static function downloadFile(Command $command, string $link): string {
        $curl = new Curl();

        $basename = basename($link);
        //$localFilePath = GeoSetting::getStorage() . DIRECTORY_SEPARATOR . $basename;
        $localFilePath = GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . $basename;


        // Display a progress bar if we can get the remote file size.
        $fileSize = RemoteFile::getFileSize($link);
        if ($fileSize > 0) {
            $geonamesBar = $command->output->createProgressBar($fileSize);
            $curl->verbose();
            $curl->setopt(CURLOPT_NOPROGRESS, false);
            $curl->setopt(CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0) use ($geonamesBar) {
                $geonamesBar->setProgress($downloaded);
            });
        } else {
            $command->line("\nWe were unable to get the file size of $link, so we will not display a progress bar. This could take a while, FYI.\n");
        }


        $curl->get($link);

        if ($curl->error) {
            $command->error("\n" . $curl->error_code . ':' . $curl->error_message);
            Log::error($link, $curl->error_message, $curl->error_code);
            throw new \Exception("Unable to download the file at '" . $link . "', " . $curl->error_message);
        }

        $command->info("\n" . "Downloaded " . $link . "\n");
        $data = $curl->response;
        $bytesWritten = file_put_contents($localFilePath, $data);
        if ($bytesWritten === false) {
            Log::error($link, "Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?", 'local');
            throw new \Exception("Unable to create the local file at '" . $localFilePath . "', file_put_contents() returned false. Disk full? Permission problem?");
        }

        return $localFilePath;
    }

    /**
     * Given a csv file on disk, this function converts it to a php array.
     * @param string $localFilePath The absolute path to a csv file in storage.
     * @param string $delimiter In a csv file, the character between fields.
     * @return array    A multi-dimensional made of the data in the csv file.
     */
    public static function csvFileToArray(string $localFilePath, $delimiter = "\t"): array {
        $rows = [];
        if (($handle = fopen($localFilePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Unzips the zip file into our geonames storage dir that is set in GeoSettings.
     * @param string $localFilePath Absolute local path to the zip archive.
     * @throws \Exception
     */
    public static function unzip ( $localFilePath ) {
        $storage = GeoSetting::getAbsoluteLocalStoragePath();
        $zip = new ZipArchive;
        $zipOpenResult = $zip->open( $localFilePath );
        if ( $zipOpenResult !== true ) {
            throw new \Exception( "Error [" . $zipOpenResult . "] Unable to unzip the archive at " . $localFilePath );
        }
        $extractResult = $zip->extractTo( $storage );
        if ( $extractResult === false ) {
            throw new \Exception( "Unable to unzip the file at " . $localFilePath );
        }
        $closeResult = $zip->close();
        if ( $closeResult === false ) {
            throw new \Exception( "After unzipping unable to close the file at " . $localFilePath );
        }

        return;
    }

}