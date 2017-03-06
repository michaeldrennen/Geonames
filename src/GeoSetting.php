<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

class GeoSetting extends Model {

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
     * @param string $status The status of our geonames system.
     * @return bool
     * @throws \Exception
     */
    public static function setStatus(string $status): bool {
        if (!defined(self::$status)) {
            throw new \Exception("The status you passed in (" . $status . ") is not a defined status.");
        }

        return self::where('id', self::ID)->update(['status' => $status]);
    }
}