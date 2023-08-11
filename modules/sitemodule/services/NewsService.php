<?php

namespace modules\sitemodule\services;

use Craft;
use Throwable;
use craft\helpers\App;
use craft\helpers\Json;
use yii\base\Component;
use craft\helpers\ArrayHelper;
use yii\caching\TagDependency;
use craft\helpers\ConfigHelper;
use modules\sitemodule\SiteModule;
use GuzzleHttp\Exception\RequestException;
use modules\sitemodule\models\Article;

/**
 * @property-read NewsService $newsService
 */
class NewsService extends Component
{

    public function fetchData(string $provider = '', array $params = []): mixed
    {

        if (!ArrayHelper::isIn($provider, SiteModule::getInstance()->providers)) {
            $message = Craft::t('site-module', 'This provider isn\'t supported yet!');
            SiteModule::error($message);
        }

        $settings = SiteModule::getInstance()->getSettings();

        // Cache if enabled
        if ($settings->enableCache) {
            $seconds = ConfigHelper::durationInSeconds($settings->cacheDuration);
            $cacheTags = ['news', $provider];

            // Generate a cache key based on all the provided data and duration (in case we change it)
            $cacheKey = md5(Json::encode([$provider, $params, $seconds]));

            $cacheData = Craft::$app->getCache()->getOrSet($cacheKey, function () use ($provider, $params) {
                // Only set cache data if we have a result
                if ($cacheData = $this->fetchRawData($provider, $params)) {
                    return $cacheData;
                }

                // Returning `false` ensures that the result is not cached, and evaluated next time
                return false;
            }, $seconds, new TagDependency([
                // Allow us to tag the cache to make invalidating it easier
                'tags' => $cacheTags,
            ]));

            // Only return if we have a result
            if ($cacheData) {
                return $cacheData;
            }
        }

        return $this->fetchRawData($provider, $params);
    }

    public function fetchRawData(string $provider = '', array $params = []): mixed
    {

        return SiteModule::getInstance()->$provider->fetchData($params);
    }

}
