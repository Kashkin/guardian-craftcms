<?php

namespace modules\sitemodule\controllers;

use yii\web\Response;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use modules\sitemodule\SiteModule;

/**
 * News controller
 */
class NewsController extends Controller
{
    protected array|int|bool $allowAnonymous = ['index'];

    /**
     * site-module/news action
     */
    public function actionIndex(): Response
    {
        $request = $this->request;

        // If passed through, limit to selection, otherwise use all
        $providers = $request->getQueryParam('providers') ?? SiteModule::getInstance()->providers;

        $options = $request->getQueryParams();

        $data = [];

        foreach ($providers as $provider) {
            $data[] = SiteModule::getInstance()->getNews()->fetchData($provider, $options);
        }

        return $this->asJson(...$data);

    }
}
