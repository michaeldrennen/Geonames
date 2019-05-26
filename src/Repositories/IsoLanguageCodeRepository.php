<?php

namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Support\Collection;
use MichaelDrennen\Geonames\Models\IsoLanguageCode;

class IsoLanguageCodeRepository {

    /**
     * @return Collection
     */
    public function all() {
        return IsoLanguageCode::all();
    }
}