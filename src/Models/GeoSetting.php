<?php
namespace MichaelDrennen\Geonames\Models;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\File;

/**
 * Class GeoSetting
 * @package MichaelDrennen\Geonames
 */
class GeoSetting extends Model {

    protected $table = 'geonames_settings';

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
                        'languages'             => 'array',
                        'installed_at'          => 'date',
                        'last_modified_at'      => 'date'];


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
     * If you wanted multiple specific countries: "'CA','US','MX";
     */
    const DEFAULT_COUNTRIES_TO_BE_ADDED = "*";

    /**
     * If you wanted multiple specific languages: "'en','ru'";
     */
    const DEFAULT_LANGUAGES = "en";

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
     *
     */
    const DB_COLUMN_COUNTRIES_TO_BE_ADDED = 'countries_to_be_added';

    /**
     * The name of the status column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_STATUS = 'status';

    /**
     *
     */
    const DB_COLUMN_INSTALLED_AT = 'installed_at';

    /**
     *
     */
    const DB_COLUMN_LAST_MODIFIED_AT = 'last_modified_at';

    /**
     * The name of the languages column in the database. If for whatever reason, you needed to change it,
     * it'd be nice to only have to do it in one place.
     */
    const DB_COLUMN_LANGUAGES = 'languages';

    /**
     * The root url of all of the download files.
     */
    const URL = 'http://download.geonames.org/export/dump/';

    /**
     * Create our GeoSetting record in the database. This is where we pull all of our
     * configuration data from to process the install and later queries.
     * @param array $countriesToBeAdded
     * @param array $languages
     * @param string $storageSubDir
     * @return bool
     * @throws Exception
     */
    public static function install ( array $countriesToBeAdded = [self::DEFAULT_COUNTRIES_TO_BE_ADDED], array $languages = [self::DEFAULT_LANGUAGES], string $storageSubDir = self::DEFAULT_STORAGE_SUBDIR ): bool {

        // Establish defaults. If the user of the Install script did not call any options when running the script, these
        // parameters (above) will come in as empty arrays. Hence the default values up there won't get called.
        // So we take care of that right here.
        $countriesToBeAdded = empty( $countriesToBeAdded ) ? [self::DEFAULT_COUNTRIES_TO_BE_ADDED] : $countriesToBeAdded;
        $languages = empty( $languages ) ? [self::DEFAULT_LANGUAGES] : $languages;
        $storageSubDir = empty( $storageSubDir ) ? [self::DEFAULT_STORAGE_SUBDIR] : $storageSubDir;

        if ( $settings = self::find( self::ID ) ) {
            $settings->{self::DB_COLUMN_COUNTRIES_TO_BE_ADDED} = $countriesToBeAdded;
            $settings->{self::DB_COLUMN_COUNTRIES} = [];
            $settings->{self::DB_COLUMN_LANGUAGES} = $languages;
            $settings->{self::DB_COLUMN_STORAGE_SUBDIR} = $storageSubDir;
            $settings->{self::DB_COLUMN_INSTALLED_AT} = null;
            $settings->{self::DB_COLUMN_LAST_MODIFIED_AT} = null;
            $settings->save();

            return true;
        }

        // Create settings record.
        try {
            GeoSetting::create( [self::DB_COLUMN_ID                    => self::ID,
                                 self::DB_COLUMN_COUNTRIES_TO_BE_ADDED => $countriesToBeAdded,
                                 self::DB_COLUMN_LANGUAGES             => $languages,
                                 self::DB_COLUMN_STORAGE_SUBDIR        => $storageSubDir] );
        } catch ( Exception $e ) {
            Log::error( '', "Unable to create the settings record in the install() function.", 'local' );
            throw new Exception( "Unable to create the settings record in the install() function." );
        }

        try {
            self::setStorage( $storageSubDir );
        } catch ( Exception $e ) {
            Log::error( '', "Unable to create the storage sub directory in the install() function.", 'filesystem' );
            throw $e;
        }


        return true;
    }

    /**
     * In a perfect world, the geonames_settings record was created when you ran the geonames:install command.
     * During development, I could not always count on the record to exist there. So I created this little
     * method to create the record if it did not exist. When users start to tinker with this library, and
     * accidentally delete the settings record (or change it's id or whatever), this will self-heal the system.
     * @param array $countries
     * @param array $languages
     * @param string $storageSubDir
     * @return bool Really only returns true. All other errors throw an Exception.
     * @throws Exception
     */
    public static function init ( array $countries = [self::DEFAULT_COUNTRIES_TO_BE_ADDED], array $languages = [self::DEFAULT_LANGUAGES], string $storageSubDir = self::DEFAULT_STORAGE_SUBDIR ): bool {

        if ( self::find( self::ID ) ) {
            return true;
        }

        // Create settings record.
        $setting = GeoSetting::create( [self::DB_COLUMN_ID             => self::ID,
                                        self::DB_COLUMN_COUNTRIES      => $countries,
                                        self::DB_COLUMN_LANGUAGES      => $languages,
                                        self::DB_COLUMN_STORAGE_SUBDIR => self::setStorage( $storageSubDir )] );

        if ( $setting ) {
            return true;
        }
        Log::error( '', "Unable to create the settings record in the init() function.", 'local' );
        throw new Exception( "Unable to create the settings record in the init() function." );
    }

    /**
     * Saves a new language code to the settings if it isn't already in there.
     * @param string $languageCode
     * @return bool
     * @throws Exception
     * @todo Verify that the language code is valid.
     */
    public static function addLanguage ( string $languageCode = 'en' ): bool {
        $existingLanguages = self::getLanguages();
        if ( array_search( $languageCode, $existingLanguages ) !== false ) {
            return true;
        }

        $existingLanguages[] = $languageCode;
        if ( self::where( self::DB_COLUMN_ID, self::ID )->update( [self::DB_COLUMN_COUNTRIES => $existingLanguages] ) ) {
            return true;
        }

        throw new Exception( "Unable to add this language to our settings " . $languageCode );
    }

    /**
     * Removes a language code from the settings if it was in there.
     * @param string $languageCode
     * @return bool Returns true. Any error gets thrown as an exception.
     * @throws Exception
     */
    public static function removeLanguage ( string $languageCode ): bool {
        $existingLanguages = self::getLanguages();
        $existingLanguageIndex = array_search( $languageCode, $existingLanguages );
        if ( $existingLanguageIndex !== false ) {
            return true;
        }
        unset( $existingLanguages[ $existingLanguageIndex ] );
        if ( self::where( self::DB_COLUMN_ID, self::ID )->update( [self::DB_COLUMN_COUNTRIES => $existingLanguages] ) ) {
            return true;
        }
        throw new Exception( "Unable to remove this language to our settings " . $languageCode );
    }

    /**
     * Returns an array of the language codes stored in the settings.
     * @return array
     */
    public static function getLanguages (): array {
        $columnName = self::DB_COLUMN_LANGUAGES;
        $languages = (string)self::first()->$columnName;

        return $languages;
    }

    /**
     * @param string $status The status of our geonames system.
     * @return bool
     * @throws Exception
     */
    public static function setStatus ( string $status ): bool {
        self::init();

        return self::where( self::DB_COLUMN_ID, self::ID )->update( [self::DB_COLUMN_STATUS => $status] );
    }


    /**
     * @param string $storageSubdir
     * @return string Either the string that was passed in, or the default string defined in DB_COLUMN_STORAGE_SUBDIR
     * @throws Exception
     */
    public static function setStorage ( string $storageSubdir ): string {
        $storageSubdir = $storageSubdir ?? self::DEFAULT_STORAGE_SUBDIR;

        $updateResult = self::where( self::DB_COLUMN_ID, self::ID )->update( [self::DB_COLUMN_STORAGE_SUBDIR => $storageSubdir] );

        if ( $updateResult === false ) {
            throw new Exception( "Unable to update the storage dir column to: " . $storageSubdir );
        }

        try {
            self::createStorageDirInFilesystem( $storageSubdir );

            return $storageSubdir;
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    /**
     * @param string $storageSubdir
     * @return string
     * @throws Exception
     */
    public static function createStorageDirInFilesystem ( string $storageSubdir ): string {
        $path = storage_path() . DIRECTORY_SEPARATOR . $storageSubdir;
        if ( file_exists( $path ) && is_writable( $path ) ) {
            return $path;
        }

        if ( file_exists( $path ) && !is_writable( $path ) ) {
            throw new Exception( "The storage path at '" . $path . "' exists but we can't write to it." );
        }

        if ( mkdir( $path, 0700, true ) ) {
            return $path;
        }

        throw new Exception( "We were unable to create the storage path at '" . $path . "' so check to make sure you have the proper permissions." );
    }

    /**
     * Return the string representing the storage subdir for Geonames, or set it to default, and return that.
     * It's possible for this function to trigger an Exception from the setStorage() call.
     * @return string
     * @throws Exception
     */
    public static function getStorage (): string {
        $settingRecord = self::first();
        if ( is_null( $settingRecord ) ) {
            throw new Exception( "The setting record does not exist in the database yet. You need to run geonames:install first." );
        }

        $columnName = self::DB_COLUMN_STORAGE_SUBDIR;
        $storageSubdir = (string)$settingRecord->$columnName;

        if ( empty( $storageSubdir ) ) {
            $storageSubdir = self::setStorage();
        }

        return $storageSubdir;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getAbsoluteLocalStoragePath (): string {
        return storage_path() . DIRECTORY_SEPARATOR . self::getStorage();
    }


    /**
     * @param string $fileName
     *
     * @return string
     * @throws \Exception
     */
    public static function getAbsoluteLocalStoragePathToFile ( string $fileName ): string {
        return self::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . $fileName;
    }


    /**
     * @param array $fileNames
     * @return array
     */
    public static function getAbsoluteLocalStoragePathToFiles ( array $fileNames ): array {
        $absolutePaths = [];
        foreach ( $fileNames as $fileName ) {
            $absolutePaths[] = self::getAbsoluteLocalStoragePathToFile( $fileName );
        }

        return $absolutePaths;
    }

    /**
     * Given a file name, this function returns the remote url to that file on the geonames.org website.
     * @param string $path
     * @return string
     */
    public static function getDownloadUrlForFile ( string $path ): string {
        return self::URL . $path;
    }


    /**
     * @return array
     * @throws Exception
     */
    public static function getCountriesToBeAdded (): array {
        $settingRecord = self::first();
        if ( is_null( $settingRecord ) ) {
            throw new Exception( "The setting record does not exist in the database yet. You need to run geonames:install first." );
        }
        $columnName = self::DB_COLUMN_COUNTRIES_TO_BE_ADDED;

        return $settingRecord->$columnName;
    }

    /**
     * After the Install command inserts all of the required geonames records, we move the value from
     * countries_to_be_added to the countries field.
     * @return bool
     * @throws Exception
     */
    public static function setCountriesFromCountriesToBeAdded (): bool {
        $settingRecord = self::first();
        if ( is_null( $settingRecord ) ) {
            throw new Exception( "The setting record does not exist in the database yet. You need to run geonames:install first." );
        }

        $settingRecord->{self::DB_COLUMN_COUNTRIES} = $settingRecord->{self::DB_COLUMN_COUNTRIES_TO_BE_ADDED};
        $settingRecord->{self::DB_COLUMN_COUNTRIES_TO_BE_ADDED} = '';


        return $settingRecord->save();
    }

    /**
     * After the last operation of the install command is complete, we set the installed_at
     * date for right now. We also set the modified_at column to null. Since this is a fresh
     * install, no modifications have been made to it yet.
     * @return bool
     * @throws Exception
     */
    public static function setInstalledAt () {
        $settingRecord = self::first();
        if ( is_null( $settingRecord ) ) {
            throw new Exception( "The setting record does not exist in the database yet. You need to run geonames:install first." );
        }

        $settingRecord->{self::DB_COLUMN_INSTALLED_AT} = Carbon::now();
        $settingRecord->{self::DB_COLUMN_LAST_MODIFIED_AT} = null;

        return (bool)$settingRecord->save();
    }

    public static function setModifiedAt () {
        $settingRecord = self::first();
        if ( is_null( $settingRecord ) ) {
            throw new Exception( "The setting record does not exist in the database yet. You need to run geonames:install first." );
        }

        $settingRecord->{self::DB_COLUMN_LAST_MODIFIED_AT} = Carbon::now();

        return (bool)$settingRecord->save();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public static function emptyTheStorageDirectory () {
        File::cleanDirectory( self::getAbsoluteLocalStoragePath() );
        $allFiles = File::files( self::getAbsoluteLocalStoragePath() );
        $numFiles = count( $allFiles );
        if ( $numFiles != 0 ) {
            throw new Exception( "We were unable to delete all of the files in " . self::getAbsoluteLocalStoragePath() . " Check the permissions." );
        }

        return true;
    }
}