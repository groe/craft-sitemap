<?php

namespace Craft;

class SitemapPlugin extends BasePlugin
{
    /**
     * {@inheritdoc} IPlugin::init()
     */
    public function init()
    {
        if (craft()->request->isCpRequest()) {
            craft()->templates->includeCssResource('sitemap/settings.css');
        }
    }

    /**
     * {@inheritdoc} IPlugin::getName()
     */
    public function getName()
    {
        return 'Sitemap';
    }

    /**
     * {@inheritdoc} IPlugin::getVersion()
     */
    public function getVersion()
    {
        return '1.2.3';
    }

    /**
     * {@inheritdoc} IPlugin::getDeveloper()
     */
    public function getDeveloper()
    {
        return 'Joshua Baker';
    }

    /**
     * {@inheritdoc} IPlugin::getDeveloperUrl()
     */
    public function getDeveloperUrl()
    {
        return 'http://joshuabaker.com/';
    }

    /**
     * {@inheritdoc} BaseSavableComponentType::defineSettings()
     */
    protected function defineSettings()
    {
        return array(
            'sections' => array(),
            'addAlternateUrls' => array(AttributeType::Bool, 'default' => true),
            'currentLocaleOnly' => array(AttributeType::Bool, 'default' => false),
            'cache' => array(AttributeType::Bool, 'default' => true),
            'cacheDuration' => array(AttributeType::Number, 'default' => 43200),
        );
    }

    /**
     * {@inheritdoc} BaseSavableComponentType::getSettingsHtml()
     */
    public function getSettingsHtml()
    {
        return craft()->templates->render('sitemap/_settings', array(
            'sections' => craft()->sitemap->sectionsWithUrls,
            'settings' => $this->settings,
        ));
    }

    /**
     * {@inheritdoc} BaseSavableComponentType::prepSettings()
     */
    public function prepSettings($input)
    {
        // early exit if $input is already a complete settings array
        // like in a Schematic import
        if (!array_key_exists('enabled', $input) && isset($input['sections']) && is_array($input['sections'])) {
            return $input;
        }
        
        // We’re rewriting every time
        $settings = $this->defineSettings();

        // Loop through valid sections
        foreach (craft()->sitemap->sectionsWithUrls as $section) {
            // Check if the section is enabled
            if (array_key_exists('enabled', $input) && $input['enabled'][$section->id]) {
                // If it is, save the changefreq and priority values into settings
                $settings['sections'][$section->id] = array(
                    'changefreq' => $input['changefreq'][$section->id],
                    'priority' => $input['priority'][$section->id],
                    'includeiffield' => $input['includeiffield'][$section->id],
                );
            }
        }

        $settings['currentLocaleOnly'] = isset($input['currentLocaleOnly']) && $input['currentLocaleOnly'] == '1';
        $settings['addAlternateUrls'] = isset($input['addAlternateUrls']) && $input['addAlternateUrls'] == '1';
        $settings['cache'] = isset($input['cache']) && $input['cache'] == '1';
        $settings['cacheDuration'] = isset($input['cacheDuration']) && is_numeric($input['cacheDuration']) ? $input['cacheDuration'] : $settings['cacheDuration']['default'];

        // Return the parsed settings ready for the database
        return $settings;
    }

    /**
     * Registers the /sitemap.xml route.
     *
     * @return array
     */
    public function registerSiteRoutes()
    {
        return array(
            'sitemap.xml' => array(
                'action' => 'sitemap/sitemap/output',
            ),
        );
    }

    /**
     * {@inheritdoc} IPlugin::getReleaseFeedUrl()
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/groe/craft-sitemap/master/changelog.json';
    }
}
