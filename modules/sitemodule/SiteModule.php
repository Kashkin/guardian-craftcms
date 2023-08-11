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
use modules\sitemodule\services\providers\GuardianService;
use modules\sitemodule\services\providers\NewsApiService;

class SiteModule extends Module
{
    // Properties
    // =========================================================================
    public array $providers = ['guardian', 'newsApi'];

    // Public Functions
    // =========================================================================
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

        $this->_registerLogTarget();

        // Register components
        // Component name must match string in $providers!
        $this->setComponents([
            'news' => NewsService::class,
            'guardian' => GuardianService::class,
            'newsApi' => NewsApiService::class,
        ]);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
            // ...
        });
    }

    /**
     * Logs an informational message to our custom log target.
     */
    public static function log($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('site-module', $message, $attributes);
        }
        Craft::info($message, 'site-module');
    }
    /**
     * Logs an error message to our custom log target.
     */
    public static function error($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('site-module', $message, $attributes);
        }

        Craft::error($message, 'site-module');
    }

    public function getNews(): NewsService
    {
        return $this->get('news');
    }

    public function getSettings(): Settings
    {
        return new Settings();
    }

    // Private Functions
    // =========================================================================

    /**
     * Registers a custom log target, keeping the format as simple as possible.
     */
    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'site-module',
            'categories' => ['site-module'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => true,
            'formatter' => new LineFormatter(
                format: "%datetime% %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
