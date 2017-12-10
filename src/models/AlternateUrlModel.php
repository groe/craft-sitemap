<?php

namespace groe\sitemap\models;

use DOMElement;

/**
 * AlternateUrlModel class
 *
 * @package groe\sitemap\models
 */
class AlternateUrlModel extends BaseModel {
    /**
     * hrefLang
     *
     * @var \groe\sitemap\models\LocaleModel|null|string
     */
    protected $hrefLang;

    /**
     * href
     *
     * @var null|string
     */
    protected $href;

    /**
     * Constructor.
     *
     * @param string|LocaleModel $hrefLang
     * @param string             $href
     */
    public function __construct( $hrefLang = null, string $href = null ) {
        parent::__construct();
        $this->hrefLang = $hrefLang;
        $this->href     = $href;
    }

    /**
     * @return \groe\sitemap\models\LocaleModel|null|string
     */
    public function getHrefLang() {
        return $this->hrefLang;
    }

    /**
     * @return null|string
     */
    public function getHref() {
        return $this->href;
    }

    /**
     * Returns a DOM Element from a document
     *
     * @param \DOMDocument $document
     *
     * @return \DOMElement
     */
    public function getDomElement( \DOMDocument $document ): DOMElement {
        $element = $document->createElement( 'xhtml:link' );
        $element->setAttribute( 'rel', 'alternate' );
        $element->setAttribute( 'hrefLang', $this->hrefLang );
        $element->setAttribute( 'href', $this->href );

        return $element;
    }

    /**
     * {@inheritdoc} BaseModel::rules()
     */
    public function rules(): array {
        $rules   = parent::rules();
        $rules[] = [ 'href', 'CUrlValidator' ];

        return $rules;
    }

    /**
     * {@inheritdoc} BaseModel::defineAttributes()
     */
    protected function defineAttributes() {
        return [
            'hrefLang' => AttributeType::Locale,
            'href'     => AttributeType::Url,
        ];
    }
}
