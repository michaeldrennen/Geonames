<?php
namespace MichaelDrennen\Geonames;

use Curl\Curl;

trait BaseTrait {
    /**
     * @var string Absolute local path to where we store the downloaded geonames files.
     */
    protected $storageDir;


    /**
     * @return string The absolute path to where the downloaded geonames files will be stored.
     * @throws \Exception
     */
    protected function setStorage() {
        $geonamesStorageDirectory = config('geonames.storage');

        $path = storage_path() . DIRECTORY_SEPARATOR . $geonamesStorageDirectory;
        if (file_exists($path) && is_writable($path)) {
            $this->storageDir = $path;

            return $path;
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new \Exception("The storage path at '" . $path . "' exists but we can't write to it.");
        }

        if (mkdir($path, 0700)) {
            $this->storageDir = $path;

            return $path;
        }

        throw new \Exception("We were unable to create the storage path at '" . $path . "' so check to make sure you have the proper permissions.");
    }

    /**
     * @return string The absolute path to the local storage directory.
     * @throws \Exception
     */
    protected function getStorage() {
        if (!empty($this->storageDir) && file_exists($this->storageDir)) {
            return $this->storageDir;
        }
        throw new \Exception("The local storage directory has not been set yet. You need to do that first.");
    }

    public static function getHumanFileSize($bytes, $decimals = 2) {
        $size = ['B',
                 'kB',
                 'MB',
                 'GB',
                 'TB',
                 'PB',
                 'EB',
                 'ZB',
                 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[ $factor ];
    }


    /**
     * @param $url string The remote file we want to get the file size of.
     * @param Curl $curl
     * @return int The size of the remote file in bytes.
     * @throws \Exception
     */
    public static function getRemoteFileSize($url, Curl $curl) {
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_HEADER, true);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->get($url);

        if ($curl->error) {
            throw new \Exception("Unable to get the remote file size of " . $url . " The error is: " . $curl->error_message);
        }

        $data = $curl->response;

        if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
            $contentLength = (int)$matches[1];

            return $contentLength;
        }
        throw new \Exception("Unable to find the 'Content-Length' header of the remote file at " . $url);
    }

}