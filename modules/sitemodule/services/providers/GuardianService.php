<?php

namespace modules\sitemodule\services\providers;

use Craft;
use Throwable;
use craft\helpers\App;
use craft\helpers\Json;
use yii\base\Component;
use craft\helpers\ArrayHelper;
use modules\sitemodule\SiteModule;
use modules\sitemodule\models\Article;
use GuzzleHttp\Exception\RequestException;

 /**
* @property-read GuardianService $guardianService
*/
class GuardianService extends Component
{

    public function fetchData(array $params = []): mixed
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
                            'provider' => 'The Guardian',
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
