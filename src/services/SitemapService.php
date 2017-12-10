<?php

namespace groe\sitemap\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use craft\models\Section;
use DateTime;
use Exception;
use groe\sitemap\models\Settings;
use groe\sitemap\models\UrlModel;
use groe\sitemap\Sitemap;

/**
 * SitemapService class
 *
 * @package groe\sitemap\services
 */
class SitemapService extends Component {

    /**
     * settings
     *
     * @var \groe\sitemap\models\Settings
     */
    protected $settings;

    /**
     * Array of Sitemap_UrlModel instances.
     *
     * @var array
     */
    protected $urls = [];

    /**
     * @return void
     */
    public function init() {
        parent::init();

        // load plugin settings
        $this->settings = $this->getPluginSettings();
    }

    /**
     * Gets the plugin settings.
     *
     * @return array
     */
    protected function getPluginSettings(): Settings {
        return Sitemap::getInstance()->getSettings();
    }

    /**
     * Returns all sections that have URLs.
     *
     * @return array An array of Section instances
     */
    public function getSectionsWithUrls(): array {
        return array_filter( Craft::$app->sections->getAllSections(), function( $section ) {
            return $section->isHomepage() || $section->urlFormat;
        } );
    }

    /**
     * Return the sitemap as a string.
     *
     * @return string
     */
    public function getSitemap(): string {
        $settings = $this->settings;

        // Loop through and add the sections checked in the plugin settings
        foreach ( $this->sectionsWithUrls as $section ) {
            if ( ! empty( $settings['sections'][ $section->id ] ) ) {
                $changeFrequency = $settings['sections'][ $section->id ]['changeFrequency'];
                $priority        = $settings['sections'][ $section->id ]['priority'];
                $includeIfField  = $settings['sections'][ $section->id ]['includeIfField'];
                $this->addSection( $section, $changeFrequency, $priority, $includeIfField );
            }
        }

        // Hook: renderSitemap
        craft()->plugins->call( 'renderSitemap' );

        // Use DOMDocument to generate XML
        $document = new \DOMDocument( '1.0', 'utf-8' );

        // Format XML output when devMode is active for easier debugging
        if ( Craft::$app->config->general->devMode ) {
            $document->formatOutput = true;
        }

        // Append a urlSet node
        $urlSet = $document->createElement( 'urlset' );
        $urlSet->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
        $urlSet->setAttribute( 'xmlns:xhtml', 'http://www.w3.org/1999/xhtml' );
        $document->appendChild( $urlSet );

        // Loop through and append Sitemap_UrlModel elements
        foreach ( $this->urls as $url ) {
            $urlElement = $url->getDomElement( $document );
            $urlSet->appendChild( $urlElement );
        }

        return $document->saveXML();
    }

    /**
     * Adds all entries in the section to the sitemap.
     *
     * @param SectionModel $section
     * @param string       $changeFrequency
     * @param string       $priority
     */
    public function addSection( Section $section, $changeFrequency = null, $priority = null, $includeIfField = null ) {
        $criteria          = Craft::$app->elements->getCriteria( ElementType::Entry );
        $criteria->section = $section;
        $criteria->limit   = 0;
        if ( $includeIfField != null && ! empty( $includeIfField ) ) {
            $criteria->$includeIfField = 1;
        }
        foreach ( $criteria->find( [ 'limit' => - 1 ] ) as $element ) {
            $this->addElement( $element, $changeFrequency, $priority );
        }
    }

    /**
     * Adds an element to the sitemap.
     *
     * @param \craft\base\Element $element
     * @param string              $changeFrequency
     * @param string              $priority
     *
     * @throws \Exception
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidParamException
     */
    public function addElement( Element $element, string $changeFrequency = null, string $priority = null ) {
        $locales     = Craft::$app->elements->getEnabledLocalesForElement( $element->id );
        $locale_urls = [];
        foreach ( $locales as $locale ) {
            $locale_urls[ $locale ] = Sitemap::getInstance()->sitemap->getElementUrlForLocale( $element, $locale );
        }

        if ( defined( 'CRAFT_LOCALE' ) ) {
            // Render sitemap for one specific locale only (single locale domain), e.g. example.de/sitemap.xml

            $url = $this->addUrl( $element->url, $element->dateUpdated, $changeFrequency, $priority );

            foreach ( $locale_urls as $locale => $locale_url ) {
                $url->addAlternateUrl( $locale, $locale_url );
            }
        } else {
            // Render sitemap for all locales (multi-locale domain), e.g. example.com/sitemap.xml

            foreach ( $locale_urls as $locale => $locale_url ) {
                $url = $this->addUrl( $locale_url, $element->dateUpdated, $changeFrequency, $priority );

                foreach ( $locale_urls as $locale => $locale_url ) {
                    $url->addAlternateUrl( $locale, $locale_url );
                }
            }
        }
    }

    /**
     * Gets a element URL for the specified locale.
     *
     * @param Element            $element
     * @param string|LocaleModel $locale
     *
     * @return string
     * @throws \Exception
     */
    public function getElementUrlForLocale( Element $element, $locale ): string {
        $this->validateLocale( $locale );

        $oldLocale       = $element->locale;
        $oldUri          = $element->uri;
        $element->locale = $locale;
        $element->uri    = craft()->elements->getElementUriForLocale( $element->id, $locale );
        $url             = $element->getUrl();
        $element->locale = $oldLocale;
        $element->uri    = $oldUri;

        return $url;
    }

    /**
     * Ensures that the requested locale is valid.
     *
     * @param string|LocaleModel $locale
     *
     * @return void
     * @throws \Exception
     */
    protected function validateLocale( string $locale ): bool {
        if ( ! in_array( $locale, craft()->i18n->siteLocales ) ) {
            throw new Exception( Craft::t( '“{locale}” is not a valid site locale.', [ 'locale' => $locale ] ) );
        }
    }

    /**
     * Adds a URL to the sitemap.
     *
     * @param string    $loc
     * @param \DateTime $lastModification
     * @param string    $changeFrequency
     * @param string    $priority
     *
     * @return UrlModel
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidParamException
     */
    public function addUrl(
        string $loc,
        DateTime $lastModification,
        string $changeFrequency = null,
        string $priority = null
    ): UrlModel {
        $url = new UrlModel( $loc, $lastModification, $changeFrequency, $priority );

        if ( $url->validate() ) {
            $this->urls[ $url->loc ] = $url;
        }

        return $url;
    }

    /**
     * Adds all categories in the group to the sitemap.
     *
     * @param CategoryGroup $categoryGroup
     * @param string        $changeFrequency
     * @param string        $priority
     *
     * @return void
     */
    public function addCategoryGroup(
        CategoryGroup $categoryGroup,
        string $changeFrequency = null,
        string $priority = null
    ) {
        $criteria        = Craft::$app->elements->getCriteria( ElementType::Category );
        $criteria->group = $categoryGroup->handle;

        $categories = $criteria->find( [ 'limit' => - 1 ] );
        foreach ( $categories as $category ) {
            $this->addElement( $category, $changeFrequency, $priority );
        }
    }

    /**
     * Gets a URL for the specified locale.
     *
     * @param string $path
     * @param string $locale
     *
     * @return string
     * @throws \Exception
     */
    public function getUrlForLocale( string $path, string $locale ): string {
        $this->validateLocale( $locale );

        // Get the site URL for the current locale
        $siteUrl = Craft::$app->config->general->siteUrl;

        if ( UrlHelper::isFullUrl( $path ) ) {
            // Return $path if it’s a remote URL
            if ( ! stripos( $path, $siteUrl ) ) {
                return $path;
            }

            // Remove the current locale siteUrl
            $path = str_replace( $siteUrl, '', $path );
        }

        // Get the site URL for the specified locale
        $localizedSiteUrl = Craft::$app->sites->getSiteByHandle( $locale )->baseUrl;

        // Trim slahes
        $localizedSiteUrl = rtrim( $localizedSiteUrl, '/' );
        $path             = trim( $path, '/' );

        return UrlHelper::url( $localizedSiteUrl . '/' . $path );
    }
}
