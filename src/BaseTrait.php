<?php
namespace MichaelDrennen\Geonames;
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

}