<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Abstract data class with helper methods
 */
abstract class ShopgateOpencartAbstract extends ShopgateObject
{
    /**
     * default identifier voucher
     */
    const DEFAULT_IDENTIFIER_VOUCHER = 'voucher';
    /**
     * default identifier coupon
     */
    const DEFAULT_IDENTIFIER_COUPON = 'coupon';

    /** @var  mixed */
    protected $_data;

    /** @var  ShopgateConfigOpencart */
    protected $_config;

    /**
     * @param array|object|null $data
     */
    public function __construct($data = null)
    {
        $this->_data = $data;
    }

    /**
     * @param ShopgateConfigOpencart $configuration
     */
    public function setConfiguration(ShopgateConfigOpencart $configuration)
    {
        $this->_config = $configuration;
    }

    /**
     * @return ShopgateConfigOpencart
     */
    protected function _getConfiguration()
    {
        return $this->_config;
    }

    /**
     * @return ShopgateOpencartDatabase
     */
    protected function _getOpencartDatabase()
    {
        return $this->_getConfiguration()->getOpencartDatabase();
    }

    /**
     * @param string $model
     */
    protected function _loadModel($model)
    {
        $this->_getModel('load')->model($model);
    }

    /**
     * @param string $model
     *
     * @return mixed
     */
    protected function _getModel($model)
    {
        return $this->_getConfiguration()->getModel($model);
    }

    /**
     * @param string $configuration
     *
     * @return mixed
     */
    protected function _getConfig($configuration)
    {
        return $this->_getModel('config')->get($configuration);
    }

    /**
     * Returns identifier if the coupon or voucher is redeemable
     * Vouchers are available from version 1.5.0.0 and up
     *
     * @param $code - code of the coupon or voucher to validate against
     *
     * @return string
     */
    protected function _getDiscountTypeByCode($code)
    {
        if (is_array($this->_getOpencartDatabase()->getCoupon($code))) {
            return self::DEFAULT_IDENTIFIER_COUPON;
        }

        if ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
            && is_array($this->_getOpencartDatabase()->getVoucher($code))
        ) {
            return self::DEFAULT_IDENTIFIER_VOUCHER;
        }

        return '';
    }

    abstract public function generateData();
}
