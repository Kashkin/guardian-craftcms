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
        switch ($provider) {
            case 'guardian':
                return $this->_fetchGuardianData($params);
                break;
        }

        return null;
    }

    private function _fetchGuardianData(array $params = []): mixed
    {
        try {

            $guzzleClient = Craft::createGuzzleClient();
            $key = App::env('GUARDIAN_API_KEY', false);

            if (!$key) {
                $message = Craft::t('site-module', 'MISSING KEY: Guardian API key is missing!');
                SiteModule::error($message);
                return $this->asJson(['error' => $message]);
            }

            // Construct query
            $baseQuery = [
                'api-key' => $key,
                'format' => 'json',
                'page-size' => 10,
                'show-fields' => 'thumbnail,wordcount,byline,trailText,liveBloggingNow,isLive,lastModified',
            ];

            // Map project params to API params
            $options = [
                'q' => $params['q'] ?? '',
                'order-by' => $params['orderBy'] ?? '',
                'page' => $params['page'] ?? '',
            ];

            $filteredOptions = ArrayHelper::filterEmptyStringsFromArray($options);

            $query = ArrayHelper::merge($baseQuery, $filteredOptions);

            // dd([
            //     'params' => $params,
            //     'options' => $options,
            //     'filteredOptions' => $filteredOptions,
            //     'query' => $query,
            // ]);

            // Make request
            $response = $guzzleClient->get('https://content.guardianapis.com/search', [
                'query' => $query,
            ]);

            if ($response && $response->getStatusCode() === 200) {
                $result = Json::decode((string)$response->getBody(), true);
                // SiteModule::log($result);
                $rawArticles = $result['response']['results'];

                $articles = [];
                foreach ($rawArticles as $article) {
                    try {
                        $articleModel = new Article([
                            'id' => $article['id'],
                            'type' => $article['type'],
                            'section' => $article['pillarName'],
                            'dateCreated' => $article['webPublicationDate'],
                            'dateModified' => $article['fields']['lastModified'],
                            'headline' => $article['webTitle'],
                            'alternativeHeadline' => $article['fields']['trailText'],
                            'url' => $article['webUrl'],
                            'author' => $article['fields']['byline'] ?? '',
                            'thumbnailUrl' => $article['fields']['thumbnail'],
                            'provider' => 'guardian',
                        ]);
                        $articles[] = $articleModel;
                    } catch (Throwable $e) {
                        $message = Craft::t('site-module', 'Error: “{message}” {file}:{line}', [
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);

                        SiteModule::error($message);
                    }
                }

                return [
                    'total' => $result['response']['total'],
                    'currentPage' => $result['response']['currentPage'],
                    'pages' => $result['response']['pages'],
                    'articles' => $articles,
                ];
            }
        } catch (Throwable $e) {
            $messageText = $e->getMessage();

            // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
            if ($e instanceof RequestException && $e->getResponse()) {
                $messageText = (string)$e->getResponse()->getBody()->getContents();
            }

            $message = Craft::t('site-module', 'Error: “{message}” {file}:{line}', [
                'message' => $messageText,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            SiteModule::error($message);

            return null;
        }
    }
}
