<?php

namespace modules\sitemodule\models;

use craft\base\Model;

/**
 * Settings model
 * $enableCache: bool
 * $cacheDuration: string, ISO 8601 DateInterval
 */
class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool $enableCache = true;
    public mixed $cacheDuration = 'PT1H';

}
