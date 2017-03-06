<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

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
    protected $casts = ['countries' => 'array',];


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
     * In a perfect world, the geo_settings record was created when you ran the geonames:install command.
     * During development, I could not always count on the record to exist there. So I created this little
     * method to create the record if it did not exist. When users start to tinker with this library, and
     * accidentally delete the settings record (or change it's id or whatever), this will self-heal the system.
     * @param array $countries
     * @return bool
     */
    public static function init($countries = ['*']): bool {
        if (self::find(self::ID)) {
            return true;
        }

        // Create settings record.
        $setting = GeoSetting::create(['id'        => 1,
                                       'countries' => $countries]);

        if ($setting) {
            return true;
        }
        throw new \Exception("Unable to create the settings record in the init() function.");
    }

    /**
     * @param string $status The status of our geonames system.
     * @return bool
     * @throws \Exception
     */
    public static function setStatus(string $status): bool {
        self::init();
        return self::where('id', self::ID)->update(['status' => $status]);
    }
}