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
 * Product export
 */
class ShopgateOpencartExportProduct extends Shopgate_Model_Catalog_Product
{
    /**
     * @var null|int
     */
    protected $_highestSort = null;

    protected $_optionsCache = null;

    /** @var  Tax */
    protected $_taxModel;

    /**
     * @var ShopgateConfigOpencart
     */
    protected $_config;

    /**
     * @var ProductStock
     */
    protected $productStock;

    /**
     * @var array
     */
    protected $_dataCache = array();

    /**
     * @var Language An object of the OpenCart Language class.
     */
    protected $language;

    public function __construct(Language $language)
    {
        parent::__construct();

        $this->language     = $language;
        $this->productStock = new ProductStock();
    }

    /**
     * @param int $sort
     */
    public function setHighestSort($sort)
    {
        $this->_highestSort = $sort;
    }

    /**
     * @param Tax | Cart\Tax $model
     */
    public function setTaxCalculationModel($model)
    {
        $this->_taxModel = $model;
    }

    /**
     * @param ShopgateConfigOpencart $configuration
     */
    public function setConfiguration(ShopgateConfigOpencart $configuration)
    {
        $this->_config = $configuration;
        $this->productStock->setConfig($configuration);
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
     * @return array
     */
    protected function _getProductImages()
    {
        if ($this->item['image']) {
            $images = array(HTTP_SERVER . "image/" . $this->item['image']);
        } else {
            $images = array();
        }
        $productImages = $this->_getOpencartDatabase()->getProductImages($this->item['id']);
        foreach ($productImages as $productImage) {
            $images[] = HTTP_SERVER . "image/" . $productImage['image'];
        }

        return $images;
    }

    /**
     * @return array
     */
    protected function _getProductOptions()
    {
        // return cached options per product or cache first if not cached - saves time and resources on multiple calls
        return isset($this->_optionsCache)
            ? $this->_optionsCache
            : ($this->_optionsCache = $this->_getOpencartDatabase()->getProductOptions(
                $this->item['id'],
                $this->_getConfiguration()->getLanguageId()
            )
            );
    }

    /**
     * @return array
     */
    protected function _getProductInputFields()
    {
        return $this->_getOpencartDatabase()->getProductPersonalisations(
            $this->item['id'],
            $this->_getConfiguration()->getLanguageId()
        );
    }

    /**
     * @return array
     */
    protected function _getProductProperties()
    {
        return $this->_getOpencartDatabase()->getProductProperties(
            $this->item['id'],
            $this->_getConfiguration()->getLanguageId()
        );
    }

    /**
     * @return array
     */
    protected function _getProductCategoryIds()
    {
        return $this->_getOpencartDatabase()->getProductCategoryIds(
            $this->item['id']
        );
    }

    protected function _buildFreeShipping()
    {
        return abs($this->item['shipping'] - 1);
    }

    /**
     * @return string
     */
    protected function _buildDeeplink()
    {
        /** @var Url $urlModel */
        $urlModel = ShopgateOpencart::getModel('url');
        if (!empty($urlModel) && method_exists($urlModel, 'link')) {
            $url = $urlModel->link('product/product', 'product_id=' . $this->item['id']);
        } else {
            if (!empty($urlModel) && method_exists($urlModel, 'http')) {
                $url = $urlModel->http('product/product') . '&product_id=' . $this->item['id'];
            } else {
                $url = HTTP_SERVER . 'index.php?route=product/product&product_id=' . $this->item['id'];
            }
        }

        return htmlspecialchars_decode($url);
    }

    /**
     * @return bool
     */
    protected function _shouldProductStockBeReduced()
    {
        // $this->item['subtract'] is not available in 1.3.0 but it will be available as ''
        // $this->_config->getOpencartDatabase()->getConfigStockSubtract() will return false/null for a configuration not being found, 1 for true and 0 for false
        $configStockSubtract = $this->_config->getOpencartDatabase()->getConfigStockSubtract();
        if ($this->item['subtract'] != '' && !$this->item['subtract']
            || ($configStockSubtract !== false
                && $configStockSubtract !== null
                && !$configStockSubtract)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function _buildUseStock()
    {
        if (empty($this->_dataCache['use_stock'])) {
            $this->_dataCache['use_stock'] =
                !$this->_getOpencartDatabase()->getConfigStockCheckout() && $this->_shouldProductStockBeReduced();
        }

        return $this->_dataCache['use_stock'];
    }

    /**
     * @return bool
     */
    protected function _buildInStock()
    {
        if (empty($this->_dataCache['in_stock'])) {
            $this->_dataCache['in_stock'] = (bool)$this->item['quantity'] > 0 || !$this->_buildUseStock();
        }

        return $this->_dataCache['in_stock'];
    }

    /**
     * @return bool
     */
    protected function _buildIsAvailable()
    {
        if (empty($this->_dataCache['is_available'])) {
            $this->_dataCache['is_available'] = (bool)$this->item['status'] && $this->_buildInStock();
        }

        return $this->_dataCache['is_available'];
    }

    /**
     * @return string
     */
    protected function _buildWeightUnit()
    {
        $rowWeight = $this->_getOpencartDatabase()->getWeightClass($this->item['weight_class_id']);

        return $rowWeight['unit'] == 'lb'
            ? 'lbs'
            : $rowWeight['unit'];
    }

    /**
     * @return string
     */
    protected function _buildAvailableText()
    {
        $showAvailable = true;
        if ($this->item['subtract'] && $this->item['quantity'] <= 0) {
            $showAvailable = false;
        }

        return $this->productStock->buildIsAvailable(
            $this->item['quantity'],
            $this->item['subtract'],
            $this->item['status']
        )
        && $this->productStock->buildInStock($this->item['quantity'], $this->item['subtract'])
        && $showAvailable
            ? $this->language->get('text_instock')
            : $this->item['status_name'];
    }

    /**
     * @return array
     */
    protected function _getRelatedProductIds()
    {
        return $this->_getOpencartDatabase()->getRelatedProductIds($this->item['id']);
    }

    /**
     * @return float
     */
    protected function _buildTaxPercent()
    {
        $taxPercent = 0.00;

        if (method_exists($this->_taxModel, 'getRates')) {
            $rates = $this->_taxModel->getRates(1, $this->item['tax_class_id']);
            foreach ($rates as $rate) {
                if ($rate['type'] == "P") {
                    $taxPercent += $rate['rate'];
                }
            }
        } else {
            $taxPercent = $this->_taxModel->getRate($this->item['tax_class_id']);
        }

        return $taxPercent;
    }

    /**
     * @return array
     */
    protected function _getTaxByTaxClassId()
    {
        return $this->_getOpencartDatabase()->getTaxByProductTaxClassId(
            $this->item['tax_class_id']
        );
    }

    /**
     * @return float
     */
    protected function _buildPrice()
    {
        if (empty($this->_dataCache['price'])) {
            $now          = strtotime(date('Y-m-d'));
            $promoRules   = $this->_getOpencartDatabase()->getPromoRules($this->item['id']);
            $currencyRate = $this->_getConfiguration()->getCurrencyRate();
            $price        = $this->item['normal_price'] * $currencyRate;

            foreach ($promoRules as $promoRule) {
                $promoStart = strtotime($promoRule['date_start']);
                $promoEnd   = strtotime($promoRule['date_end']);
                if (($promoStart <= $now && $now < $promoEnd)
                    || ($promoRule['date_start'] == '0000-00-00' && $promoRule['date_end'] == '0000-00-00')
                    || ($promoRule['date_start'] == '0000-00-00' && $now < $promoEnd)
                    || ($promoRule['date_end'] == '0000-00-00' && $promoStart <= $now)
                ) {
                    $price = $promoRule['price'] * $currencyRate;
                }
            }
            $this->_dataCache['price'] = $price;
        }

        return $this->_dataCache['price'];
    }

    /**
     * @return float
     */
    protected function _buildPriceOld()
    {
        if (empty($this->_dataCache['price_old'])) {
            $currencyRate                  = $this->_getConfiguration()->getCurrencyRate();
            $priceOld                      = $this->item['normal_price'] * $currencyRate;
            $priceOld                      = $this->_taxModel->calculate($priceOld, $this->item['tax_class_id'], 1);
            $this->_dataCache['price_old'] = round($priceOld, 2);
        }

        return $this->_dataCache['price_old'];
    }

    /**
     * @return float
     */
    protected function _buildPriceOldNet()
    {
        if (empty($this->_dataCache['price_old_net'])) {
            $currencyRate                      = $this->_getConfiguration()->getCurrencyRate();
            $priceOld                          = $this->item['normal_price'] * $currencyRate;
            $this->_dataCache['price_old_net'] = round($priceOld, 2);
        }

        return $this->_dataCache['price_old_net'];
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function _mapInputType($type)
    {
        switch ($type) {
            case 'textarea':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA;
                break;
            case 'file':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE;
                break;
            case 'select':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case 'radio':
            case 'image':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case 'checkbox':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case 'date':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE;
                break;
            case 'datetime':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME;
                break;
            case 'time':
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME;
                break;
            default:
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
                break;
        }

        return $inputType;
    }
}
