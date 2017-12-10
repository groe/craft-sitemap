<?php

namespace groe\sitemap\controllers;

use craft\web\Controller;
use groe\sitemap\Sitemap;
use yii\web\Response;

/**
 * Sitemap controller
 *
 * @package groe\sitemap
 */
class SitemapController extends Controller {

    /**
     * @inheritdoc
     */
    public $defaultAction = 'output';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    /**
     * Outputs the returned sitemap.
     *
     * @return \yii\web\Response
     */
    public function actionOutput(): Response {
        return $this->asXml( Sitemap::getInstance()->sitemap->getSitemap() );
    }
}
