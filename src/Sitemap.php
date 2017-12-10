<?php

namespace groe\sitemap;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use groe\sitemap\models\Settings;
use groe\sitemap\services\SitemapService;
use yii\base\Event;

/**
 * Sitemap class
 *
 * @property SitemapService sitemap
 * @package groe\sitemap
 */
class Sitemap extends Plugin {

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    /**
     * @var string
     */
    public $t9nCategory = 'sitemap';

    /** @noinspection SpellCheckingInspection */
    /**
     * Creates and returns the model used to store the plugin settings.
     *
     * @return \groe\sitemap\models\Settings
     */
    protected function createSettingsModel(): Settings {
        $settings = new Settings();

        // Loop through valid sections
        foreach ( $this->sitemap->getSectionsWithUrls() as $section ) {

            // Check if the section is enabled
            if ( $settings->enabled[ $section->id ] ) {

                // If it is, save the change frequency and priority values into settings
                $settings->sections[ $section->id ] = [
                    'changeFrequency' => $settings->changeFrequency[ $section->id ],
                    'priority'        => $settings->priority[ $section->id ],
                    'includeIfField'  => $settings->includeIfField[ $section->id ],
                ];
            }
        }

        return new Settings();
    }

    /**
     * initialize the plugin
     *
     * @return void
     */
    public function init() {

        // load the services
        $this->setComponents( [ 'sitemap' => SitemapService::class ] );

        // register the actions
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function( RegisterUrlRulesEvent $event ) {
                $event->rules['GET sitemap.xml'] = 'sitemap/output';
            }
        );

        Craft::info( Craft::t(
            'sitemap',
            '{name} plugin loaded',
            [ 'name' => $this->name ]
        ), __METHOD__ );
    }

    /**
     * Retrieves the plugin settings HTML
     *
     * @return null|string
     * @throws \RuntimeException
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    protected function settingsHtml(): string {
        return Craft::$app->getView()->renderTemplate( 'sitemap/settings', [
            'settings' => $this->getSettings()
        ] );
    }
}
