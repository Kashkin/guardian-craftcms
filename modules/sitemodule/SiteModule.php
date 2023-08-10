<?php

namespace modules\sitemodule;

use Craft;
use yii\log\Logger;
use Psr\Log\LogLevel;
use verbb\base\base\Module;
use craft\log\MonologTarget;
use Monolog\Formatter\LineFormatter;
use modules\sitemodule\models\Settings;
use modules\sitemodule\services\NewsService;

class SiteModule extends Module
{
    public array $providers = ['guardian'];

    public function init(): void
    {
        Craft::setAlias('@modules/sitemodule', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'modules\\sitemodule\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\sitemodule\\controllers';
        }

        parent::init();

        // Register a custom log target, keeping the format as simple as possible.
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'site-module',
            'categories' => ['site-module'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "%datetime% %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);

        // Register components
        $this->setComponents([
            'news' => NewsService::class,
        ]);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    public static function log($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('site-module', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'site-module');
    }

    public static function error($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('site-module', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'site-module');
    }

    public function getNews(): NewsService
    {
        return $this->get('news');
    }

    public function getSettings(): Settings
    {
        return new Settings();
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
