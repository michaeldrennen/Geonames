<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GeoSetting
 * @package MichaelDrennen\Geonames
 */
class GeoSetting extends Model {

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['countries'             => 'array',
                        'countries_to_be_added' => 'array',
                        'languages'             => 'array'];


    /**
     * The id value from the database for our settings row. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const ID = 1;

    /**
     * Set at the beginning of the geonames:install console command.
     */
    const STATUS_INSTALLING = 'installing';

    /**
     * Set at the begininng of the geonames:update console command.
     */
    const STATUS_UPDATING = 'updating';

    /**
     * Hopefully the normal state. Set at the end of install or update.
     */
    const STATUS_LIVE = 'live';

    /**
     * Set when there is an error that prevents an install or update completing.
     */
    const STATUS_ERROR = 'error';


    /**
     * This library makes use of the Laravel storage_dir() as the root. This const defines the name of the child
     * directory that stores all of our downloaded geonames files.
     */
    const DEFAULT_STORAGE_SUBDIR = 'geonames';

    /**
     * The name of the id column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_ID = 'id';

    /**
     * The name of the storage subdir's column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_STORAGE_SUBDIR = 'storage_subdir';

    /**
     * The name of the country's column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_COUNTRIES = 'countries';

    /**
     * The name of the status column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_STATUS = 'status';

    /**
     * The name of the languages column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_LANGUAGES = 'languages';

    /**
     * In a perfect world, the geo_settings record was created when you ran the geonames:install command.
     * During development, I could not always count on the record to exist there. So I created this little
     * method to create the record if it did not exist. When users start to tinker with this library, and
     * accidentally delete the settings record (or change it's id or whatever), this will self-heal the system.
     * @param array $countries
     * @param array $languages
     * @param string $storageSubDir
     * @return bool Really only returns true. All other errors throw an Exception.
     * @throws \Exception
     */
    public static function init($countries = ['*'], $languages = ['en'], $storageSubDir = 'geonames'): bool {
        if (self::find(self::ID)) {
            return true;
        }

        // Create settings record.
        $setting = GeoSetting::create([self::DB_COLUMN_ID             => self::ID,
                                       self::DB_COLUMN_COUNTRIES      => $countries,
                                       self::DB_COLUMN_LANGUAGES      => $languages,
                                       self::DB_COLUMN_STORAGE_SUBDIR => $storageSubDir]);

        if ($setting) {
            return true;
        }
        throw new \Exception("Unable to create the settings record in the init() function.");
    }

    /**
     * Saves a new language code to the settings if it isn't already in there.
     * @param string $languageCode
     * @return bool
     * @throws \Exception
     * @todo Verify that the language code is valid.
     */
    public static function addLanguage(string $languageCode = 'en'): bool {
        $existingLanguages = self::getLanguages();
        if (array_search($languageCode, $existingLanguages) !== false) {
            return true;
        }

        $existingLanguages[] = $languageCode;
        if (self::where(self::DB_COLUMN_ID, self::ID)->update([self::DB_COLUMN_COUNTRIES => $existingLanguages])) {
            return true;
        }

        throw new \Exception("Unable to add this language to our settings " . $languageCode);
    }

    /**
     * Removes a language code from the settings if it was in there.
     * @param string $languageCode
     * @return bool Returns true. Any error gets thrown as an exception.
     * @throws \Exception
     */
    public static function removeLanguage(string $languageCode): bool {
        $existingLanguages = self::getLanguages();
        $existingLanguageIndex = array_search($languageCode, $existingLanguages);
        if ($existingLanguageIndex !== false) {
            return true;
        }
        unset($existingLanguages[ $existingLanguageIndex ]);
        if (self::where(self::DB_COLUMN_ID, self::ID)->update([self::DB_COLUMN_COUNTRIES => $existingLanguages])) {
            return true;
        }
        throw new \Exception("Unable to remove this language to our settings " . $languageCode);
    }

    /**
     * Returns an array of the language codes stored in the settings.
     * @return array
     */
    public static function getLanguages(): array {
        $columnName = self::DB_COLUMN_LANGUAGES;
        $languages = (string)self::first()->$columnName;

        return $languages;
    }

    /**
     * @param string $status The status of our geonames system.
     * @return bool
     * @throws \Exception
     */
    public static function setStatus(string $status): bool {
        self::init();

        return self::where(self::DB_COLUMN_ID, self::ID)->update([self::DB_COLUMN_STATUS => $status]);
    }

    /**
     * Return the string representing the storage subdir for Geonames, or set it to default, and return that.
     * It's possible for this function to trigger an Exception from the setStorage() call.
     * @return string
     */
    public static function getStorage(): string {
        $columnName = self::DB_COLUMN_STORAGE_SUBDIR;

        $settingRecord = self::first();
        if (is_null($settingRecord)) {
            throw new \Exception("The setting record does not exist in the database yet. You need to run geonames:install first.");
        }

        $storageSubdir = (string)$settingRecord->$columnName;

        if (empty($storageSubdir)) {
            $storageSubdir = self::setStorage();
        }

        return $storageSubdir;
    }

    /**
     * @param string|null $storageSubdir
     * @return string Either the string that was passed in, or the default string defined in DB_COLUMN_STORAGE_SUBDIR
     * @throws \Exception
     */
    public static function setStorage(string $storageSubdir = null): string {
        $storageSubdir = $storageSubdir ?? self::DEFAULT_STORAGE_SUBDIR;
        if (self::where(self::DB_COLUMN_ID, self::ID)->update([self::DB_COLUMN_STORAGE_SUBDIR => $storageSubdir])) {
            self::createStorageDirInFilesystem($storageSubdir);

            return $storageSubdir;
        }
        throw new \Exception("Unable to setStorage to: " . $storageSubdir);
    }

    /**
     * @param string $storageSubdir
     * @return string
     * @throws \Exception
     */
    public static function createStorageDirInFilesystem(string $storageSubdir): string {
        $path = storage_path() . DIRECTORY_SEPARATOR . $storageSubdir;
        if (file_exists($path) && is_writable($path)) {
            return $path;
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new \Exception("The storage path at '" . $path . "' exists but we can't write to it.");
        }

        if (mkdir($path, 0700)) {
            return $path;
        }

        throw new \Exception("We were unable to create the storage path at '" . $path . "' so check to make sure you have the proper permissions.");
    }

    public static function getAbsoluteLocalStoragePath() {
        return storage_path() . DIRECTORY_SEPARATOR . self::getStorage();
    }
}