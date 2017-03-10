<?php
namespace MichaelDrennen\Geonames\Console;

use Curl\Curl;
use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\RemoteFile\RemoteFile;
use MichaelDrennen\Geonames\Log;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

trait GeonamesConsoleTrait {

    /**
     * @var string
     */
    protected static $url = 'http://download.geonames.org/export/dump/';

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


    public static function downloadFiles(Command $command, array $downloadLinks): array {
        $localFilePaths = [];
        foreach ($downloadLinks as $link) {
            $localFilePaths[] = self::downloadFile($command, $link);
        }

        return $localFilePaths;
    }

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

}