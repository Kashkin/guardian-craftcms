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
* @property-read NewsApiService $newsApiService
*/
class NewsApiService extends Component
{

    public function fetchData(array $params = []): mixed
    {
        try {

            $guzzleClient = Craft::createGuzzleClient();
            $key = App::env('NEWS_API_KEY', false);

            if (!$key) {
                $message = Craft::t('site-module', 'MISSING KEY: News API key is missing!');
                SiteModule::error($message);
                return $this->asJson(['error' => $message]);
            }

            // Construct query
            $baseQuery = [
                'apiKey' => $key,
                'pageSize' => 10,
            ];

            // Map project params to API params
            $options = [
                'q' => $params['q'] ?? '',
                'sortBy' => $params['orderBy'] ?? '',
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
            $response = $guzzleClient->get('https://newsapi.org/v2/everything', [
                'query' => $query,
            ]);

            if ($response && $response->getStatusCode() === 200) {
                $result = Json::decode((string)$response->getBody(), true);

                // SiteModule::log($result);
                $rawArticles = $result['articles'];

                $articles = [];
                foreach ($rawArticles as $article) {
                    try {
                        $articleModel = new Article([
                            'id' => $article['url'], // TODO: Change this to URI
                            'type' => null,
                            'section' => null,
                            'dateCreated' => $article['publishedAt'],
                            'dateModified' => null,
                            'headline' => $article['title'],
                            'alternativeHeadline' => $article['description'],
                            'url' => $article['url'],
                            'author' => $article['author'],
                            'thumbnailUrl' => $article['urlToImage'],
                            'provider' => $article['source']['name'],
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
                    'total' => $result['totalResults'],
                    'currentPage' => $params['page'] ?? null, // TODO: Investigate!
                    'pages' => null,
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
