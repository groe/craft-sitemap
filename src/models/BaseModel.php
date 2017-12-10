<?php

namespace groe\sitemap\models;

use Craft;
use craft\base\Model;
use yii\base\Exception;

/**
 * BaseModel class
 *
 * @package groe\sitemap\models
 */
abstract class BaseModel extends Model {

    /**
     * Throws exceptions for validation errors when in devMode.
     *
     * @param null $attributes
     * @param bool $clearErrors
     *
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidParamException
     */
    public function validate( $attributes = null, $clearErrors = true ) {
        $validate = parent::validate( $attributes, $clearErrors );

        if ( ! $validate && Craft::$app->config->general->devMode ) {
            foreach ( $this->getErrors() as $attribute => $error ) {
                throw new Exception( Craft::t( 'sitemap', $error ) );
            }
        }

        return $validate;
    }
}
