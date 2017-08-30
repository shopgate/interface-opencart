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

class ProductStock extends ShopgateObject
{
    /**
     * @var ShopgateConfigOpencart
     */
    protected $_config;

    public function __construct(ShopgateConfigOpencart $config = null)
    {
        $this->_config = $config;
    }

    /**
     * @param mixed $productConfigSubtract
     *
     * @return bool
     */
    public function shouldProductStockBeReduced($productConfigSubtract)
    {
        // $this->item['subtract'] is not available in 1.3.0 but it will be available as ''
        // $this->_config->getOpencartDatabase()->getConfigStockSubtract() will return false/null for a not found configuration and 1 for true, 0 for false
        $configStockSubtract = $this->_config->getOpencartDatabase()->getConfigStockSubtract();
        if (($productConfigSubtract != '' || $productConfigSubtract == null) && !$productConfigSubtract
            || ($configStockSubtract !== false && $configStockSubtract !== null && !$configStockSubtract)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed          $productConfigSubtract
     * @param ProductVariant $productVariant
     *
     * @return int
     */
    public function buildUseStock($productConfigSubtract, ProductVariant $productVariant = null)
    {
        $useStock = 1;
        if (is_null($productVariant)) {
            // product without options
            return !$this->_config->getOpencartDatabase()->getConfigStockCheckout()
                && !$this->shouldProductStockBeReduced($productConfigSubtract);
        } else {
            $useStock = !$this->_config->getOpencartDatabase()->getConfigStockCheckout();

            if (!$this->shouldProductStockBeReduced($productConfigSubtract)
                && !$productVariant->getIsAnyOptionSetToSubtract()
                && $productVariant->getQuantity() > 0
            ) {
                // OPENCART-190: Special rule because the product can be only purchased with an amount of $quantity per order because the stock didn't get reduced
                return 0;
            }

            if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.4.0')
                && $productVariant->getIsEveryOptionSetToSubtract()
            ) {
                // OPENCART-190: bug/behaviour in OpenCart which is part until version 1.4.0
                return 0;
            }
        }

        return $useStock;
    }

    /**
     * @param int            $parentQuantity
     * @param mixed          $productConfigSubtract
     * @param ProductVariant $productVariant
     *
     * @return int
     */
    public function buildMaximumOrderQuantity(
        $parentQuantity,
        $productConfigSubtract,
        ProductVariant $productVariant = null
    ) {
        $maximumOrderQuantity = 0;
        if (is_null($productVariant)) {
            // product without options
            if (!$this->shouldProductStockBeReduced($productConfigSubtract)) {
                $maximumOrderQuantity = $parentQuantity > 0
                    ? $parentQuantity
                    : 0;
            } else {
                $maximumOrderQuantity = 0;
            }
        } else {
            $maximumOrderQuantity = 0;
            if (!$this->shouldProductStockBeReduced($productConfigSubtract)
                && !$productVariant->getIsAnyOptionSetToSubtract()
                && $productVariant->getQuantity() > 0
            ) {
                // OPENCART-190: Special rule because the product can be only purchased with an amount of $quantity per order because the stock didn't get reduced
                $maximumOrderQuantity = $productVariant->getQuantity();
            }

            if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.4.0')
                && $productVariant->getIsEveryOptionSetToSubtract()
            ) {
                // OPENCART-190: bug/behaviour in OpenCart which is part until OpenCart version 1.4.0
                $maximumOrderQuantity = $productVariant->getQuantity();
            }

            if (!$productVariant->getQuantityChangedBecauseOfOptionQuantity()
                && $productVariant->getIsEveryOptionSetToSubtract()
            ) {
                // OPENCART-190: special behavior because of a parent product stock that is lower then the stock in options was given
                $maximumOrderQuantity = $productVariant->getQuantity() > 0
                    ? $productVariant->getQuantity()
                    : 0;
            }
        }

        return $maximumOrderQuantity;
    }

    /**
     * @param int            $parentQuantity
     * @param ProductVariant $productVariant
     *
     * @return int
     */
    public function buildStockQuantity($parentQuantity, ProductVariant $productVariant = null)
    {
        $stockQuantity = 0;
        if (is_null($productVariant)) {
            $stockQuantity = $parentQuantity > 0
                ? $parentQuantity
                : 0;
        } else {
            $stockQuantity = $productVariant->getQuantity();
            if (!$productVariant->getQuantityChangedBecauseOfOptionQuantity()
                && $productVariant->getIsEveryOptionSetToSubtract()
            ) {
                //OPENCART-190: special behavior because of a parent product stock that is lower then the stock in options was given
                $stockQuantity = $productVariant->getLowestOptionsQuantity();
            }
        }

        return $stockQuantity;
    }

    /**
     * @param int   $parentQuantity
     * @param mixed $productConfigSubtract
     *
     * @return bool
     */
    public function buildInStock($parentQuantity, $productConfigSubtract)
    {
        return (bool)$parentQuantity > 0 || !$this->buildUseStock($productConfigSubtract);
    }

    /**
     * @param int   $parentQuantity
     * @param mixed $productConfigSubtract
     * @param bool  $productStatus
     *
     * @return bool
     */
    public function buildIsAvailable($parentQuantity, $productConfigSubtract, $productStatus)
    {
        return (bool)$productStatus && $this->buildInStock($parentQuantity, $productConfigSubtract);
    }

    /**
     * @param bool  $useStock
     * @param int   $parentQuantity
     * @param mixed $productConfigSubtract
     * @param bool  $productStatus
     *
     * @return bool
     */
    public function buildIsSaleable($useStock, $parentQuantity, $productConfigSubtract, $productStatus)
    {
        return $useStock
            ? (int)$this->buildIsAvailable($parentQuantity, $productConfigSubtract, $productStatus)
            : 1;
    }

    /**
     * @param ShopgateConfigOpencart $config
     */
    public function setConfig($config)
    {
        $this->_config = $config;
    }

    /**
     * @param int               $productId
     * @param int               $parentQuantity
     * @param ShopgateOrderItem $shopgateOrderItem
     *
     * @return null|ProductVariant
     */
    public function calculateProductOptions($productId, $parentQuantity, $shopgateOrderItem)
    {
        $productVariant     = null;
        $options            = $shopgateOrderItem->getOptions();
        $internalOrderInfos = $shopgateOrderItem->getInternalOrderInfo();
        if ($internalOrderInfos) {
            $internalOrderInfos = $this->jsonDecode($internalOrderInfos, true);
            if (isset($internalOrderInfos['option_selection']) && is_array($internalOrderInfos['option_selection'])) {
                $productOptions  = $this->getPreparedProductOptions($productId);
                $selectedOptions = array();

                foreach ($internalOrderInfos['option_selection'] as $option) {
                    $addOption       = true;
                    $productOptionId = $option['product_option_id'];
                    $accessKey       = $productOptionId . '_' . $option['product_option_value_id'];
                    switch ($option['type']) {
                        case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX:
                            $accessKey = $productOptionId;
                            if ($option['product_option_value_id'] != '1') {
                                $addOption = false;
                            }
                            break;
                        default:
                            break;
                    }

                    if (
                        (!empty($productOptions[$accessKey])
                            || !empty(
                                $productOptions['key_' . $option['product_option_name'] . '_' . $option['product_option_value_name']]
                            )
                        )
                        && $addOption
                    ) {
                        $selectedOptions[] = !empty($productOptions[$accessKey])
                            ? $productOptions[$accessKey]
                            : $productOptions['key_' . $option['product_option_name'] . '_'
                            . $option['product_option_value_name']];
                    }
                }
                $productVariant = new ProductVariant($selectedOptions);
                $productVariant->calculateVariant($parentQuantity);
            }
        } elseif (!empty($options)) {
            $productOptions  = $this->getPreparedProductOptions($productId);
            $selectedOptions = array();

            foreach ($options as $productOption) {
                $productOptionsKey = $productOption->getOptionNumber() . '_' . $productOption->getValueNumber();
                $selectedOptions[] = $productOptions[$productOptionsKey];
            }

            $productVariant = new ProductVariant($selectedOptions);
            $productVariant->calculateVariant($parentQuantity);
        }

        return $productVariant;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    protected function getPreparedProductOptions($productId)
    {
        $productOptionsSource = $this->_config->getOpencartDatabase()->getProductOptions(
            $productId,
            $this->_config->getLanguageId()
        );

        $productOptions = array();
        foreach ($productOptionsSource as $productOptionSource) {
            $productOptions[$productOptionSource['product_option_id'] . '_'
            . $productOptionSource['product_option_value_id']] = $productOptionSource;
            $productOptions['key_' . $productOptionSource['option_name'] . '_'
            . $productOptionSource['option_value_name']]       = $productOptionSource;
        }

        return $productOptions;
    }

    /**
     * @param ShopgateOrderItem $shopgateOrderItem
     *
     * @return ShopgateCartItem
     */
    public function createShopgateCartItem(ShopgateOrderItem $shopgateOrderItem)
    {
        $responseItem = new ShopgateCartItem();
        $responseItem->setItemNumber($shopgateOrderItem->getItemNumber());
        $responseItem->setOptions($shopgateOrderItem->getOptions());
        $responseItem->setInputs($shopgateOrderItem->getInputs());
        $responseItem->setAttributes($shopgateOrderItem->getAttributes());

        $productId = $this->getId($shopgateOrderItem->getItemNumber());
        $product   = $this->_config->getOpencartDatabase()->getProduct($productId);

        if (empty($product)) {
            $responseItem->setStockQuantity(0);
            $responseItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);

            return $responseItem;
        }

        $productVariant  = $this->calculateProductOptions(
            $productId,
            $product['quantity'],
            $shopgateOrderItem
        );
        $productSubtract = !empty($product['subtract'])
            ? $product['subtract']
            : null;

        $useStock             = $this->buildUseStock($productSubtract, $productVariant);
        $maximumOrderQuantity =
            $this->buildMaximumOrderQuantity($product['quantity'], $productSubtract, $productVariant);
        $isBuyable            =
            $this->buildIsSaleable($useStock, $product['quantity'], $productSubtract, $product['status']);
        $stockQuantity        = $this->buildStockQuantity($product['quantity'], $productVariant);

        $responseItem->setIsBuyable((int)$isBuyable && (!$useStock || $useStock && $stockQuantity > 0));
        $responseItem->setStockQuantity($this->buildStockQuantity($product['quantity'], $productVariant));
        if ($maximumOrderQuantity > 0) {
            $responseItem->setStockQuantity(
                $maximumOrderQuantity > $responseItem->getStockQuantity()
                    ? $responseItem->getStockQuantity()
                    : $maximumOrderQuantity
            );
        }

        if ($responseItem->getStockQuantity() <= 0 && !$isBuyable) {
            $responseItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
        }

        return $responseItem;
    }

    /**
     * @param $itemNumber
     *
     * @return mixed
     */
    public function getId($itemNumber)
    {
        $explodedItemNumber = explode('_', $itemNumber);

        return !empty($explodedItemNumber[0])
            ? $explodedItemNumber[0]
            : $itemNumber;
    }
}
