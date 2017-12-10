<?php

namespace groe\sitemap\models;

use Craft;
use DateTime;
use yii\base\Exception;

class UrlModel extends BaseModel {
    /**
     * Array of Sitemap_AlternateUrlModel instances.
     *
     * @var array
     */
    protected $alternateUrls = [];

    /**
     * Constructor.
     *
     * @param string|urlModel    $loc
     * @param \DateTimeInterface $lastModification
     * @param string             $changeFrequency
     * @param string             $priority
     */
    public function __construct( $loc, $lastModification, $changeFrequency = null, $priority = null ) {
        parent::__construct();

        $this->loc              = $loc;
        $this->lastModification = $lastModification;
        $this->changeFrequency  = $changeFrequency;
        $this->priority         = $priority;
    }

    /**
     * Returns an array of assigned Sitemap_AlternateUrlModel instances.
     *
     * @return array
     */
    public function getAlternateUrls() {
        return $this->alternateUrls;
    }

    /**
     * Generates the relevant DOMElement instances.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMElement
     */
    public function getDomElement( \DOMDocument $document ) {
        $url = $document->createElement( 'url' );

        $loc = $document->createElement( 'loc', $this->loc );
        $url->appendChild( $loc );

        $lastModification = $document->createElement( 'lastModification', $this->lastModification->w3c() );
        $url->appendChild( $lastModification );

        if ( $this->changeFrequency ) {
            $changeFrequency = $document->createElement( 'changeFrequency', $this->changeFrequency );
            $url->appendChild( $changeFrequency );
        }

        if ( $this->priority ) {
            $priority = $document->createElement( 'priority', $this->priority );
            $url->appendChild( $priority );
        }

        if ( $this->hasAlternateUrls() ) {
            foreach ( $this->alternateUrls as $alternateUrl ) {
                $link = $alternateUrl->getDomElement( $document );
                $url->appendChild( $link );
            }
        }

        return $url;
    }

    /**
     * Returns true if there’s one or more alternate URLs, excluding the current locale.
     *
     * @return bool
     */
    public function hasAlternateUrls() {
        if ( ! Craft::$app->getIsMultiSite() ) {
            return false;
        }

        return count( array_filter( $this->alternateUrls, function( $alternateUrl ) {
                return $alternateUrl->hreflang != Craft::$app->language;
            } ) ) > 0;
    }

    /**
     * {@inheritdoc} BaseModel::setAttribute()
     */
    public function setAttribute( $name, $value ) {
        if ( $name == 'loc' ) {
            $this->addAlternateUrl( Craft::$app->language, $value );
        }

        if ( $name == 'lastModification' ) {
            if ( ! $value instanceof \DateTime ) {
                try {
                    $value = new DateTime( $value );
                }
                catch ( \Exception $e ) {
                    $message = Craft::t( 'sitemap', '“{object}->{attribute}” must be a DateTime object or a valid Unix timestamp.', [
                        'object'    => get_class( $this ),
                        'attribute' => $name
                    ] );
                    throw new Exception( $message );
                }
            }
            if ( new DateTime() < $value ) {
                $message = Craft::t( 'sitemap', '“{object}->{attribute}” must be in the past.', [
                    'object'    => get_class( $this ),
                    'attribute' => $name
                ] );
                throw new Exception( $message );
            }
        }

        return parent::setAttributes( [ $name => $value ] );
    }

    /**
     * Add an alternate URL.
     *
     * @param $hrefLang
     * @param $href
     *
     * @return \groe\sitemap\models\AlternateUrlModel
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidParamException
     */
    public function addAlternateUrl( $hrefLang, $href ) {
        $alternateUrl = new AlternateUrlModel( $hrefLang, $href );

        if ( $alternateUrl->validate() ) {
            $this->alternateUrls[ $alternateUrl->hrefLang ] = $alternateUrl;
        }

        return $alternateUrl;
    }

    /**
     * {@inheritdoc} BaseModel::rules()
     */
    public function rules() {
        $rules   = parent::rules();
        $rules[] = [ 'loc', 'CUrlValidator' ];

        return $rules;
    }

    /**
     * {@inheritdoc} BaseModel::defineAttributes()
     */
    protected function defineAttributes() {
        return [
            'loc'              => AttributeType::Url,
            'lastModification' => AttributeType::DateTime,
            'changeFrequency'  => [ AttributeType::Enum, 'values' => Sitemap_ChangeFrequency::getConstants() ],
            'priority'         => [ AttributeType::Enum, 'values' => Sitemap_Priority::getConstants() ],
        ];
    }
}
