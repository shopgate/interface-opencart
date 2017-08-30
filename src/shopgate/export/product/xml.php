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
 * Product XML export class
 */
class ShopgateOpencartExportProductXml extends ShopgateOpencartExportProduct
{
    const EXPORT_VALUE_NOT_INITIALIZED = null; // makes "isset" return false as well as true for "empty"
    const EXPORT_OPTION_TYPE_INPUTS     = 1;
    const EXPORT_OPTION_TYPE_ATTRIBUTES = 2;

    /** @var int|null */
    protected $_grossMarket = self::EXPORT_VALUE_NOT_INITIALIZED;

    /** @var int|null */
    protected $_exportOptionType = self::EXPORT_VALUE_NOT_INITIALIZED;

    /** @var array|null */
    protected $_textInputCache = self::EXPORT_VALUE_NOT_INITIALIZED;

    public function setUid()
    {
        parent::setUid($this->item['id']);
    }

    public function setLastUpdate()
    {
        parent::setLastUpdate(date(DateTime::ISO8601, strtotime($this->item['date_modified'])));
    }

    public function setName()
    {
        parent::setName(html_entity_decode($this->item['products_name']));
    }

    public function setTaxPercent()
    {
        parent::setTaxPercent($this->_buildTaxPercent());
    }

    public function setTaxClass()
    {
        parent::setTaxClass($this->item['tax_class_id']);
    }

    public function setCurrency()
    {
        parent::setCurrency($this->_getConfiguration()->getCurrencyId());
    }

    public function setDescription()
    {
        parent::setDescription(html_entity_decode($this->item['product_description']));
    }

    public function setDeeplink()
    {
        parent::setDeeplink($this->_buildDeeplink());
    }

    public function setWeight()
    {
        parent::setWeight($this->item['weight']);
    }

    public function setWeightUnit()
    {
        parent::setWeightUnit($this->_buildWeightUnit());
    }

    public function setPrice()
    {
        $priceOld = $this->item['normal_price'] * $this->_getConfiguration()->getCurrencyRate();
        $price    = $this->_taxModel->calculate($this->_buildPrice(), $this->item['tax_class_id'], false);
        $priceOld = $this->_taxModel->calculate($priceOld, $this->item['tax_class_id'], false);

        $priceModel = new Shopgate_Model_Catalog_Price();
        $priceModel->setSalePrice(round($price, 2));
        $priceModel->setPrice(round($priceOld, 2));
        $priceModel->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET);

        foreach ($this->_getOpencartDatabase()->getProductDiscount($this->item['id']) as $tier) {
            $fixedTierPrice = $this->_taxModel->calculate($tier['price'], $this->item['tax_class_id'], false);
            $tierPrice      = new Shopgate_Model_Catalog_TierPrice();

            $tierPrice->setFromQuantity($tier['quantity']);
            $tierPrice->setReduction($priceModel->getSalePrice() - $fixedTierPrice);
            $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
            $tierPrice->setAggregateChildren(true);

            if (!empty($tier['customer_group_id']) && $tier['customer_group_id'] != 1) {
                $tierPrice->setCustomerGroupUid($tier['customer_group_id']);
            }
            $priceModel->addTierPriceGroup($tierPrice);
        }

        parent::setPrice($priceModel);
    }

    public function setShipping()
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setIsFree($this->_buildFreeShipping());

        parent::setShipping($shipping);
    }

    public function setManufacturer()
    {
        $title = $this->item['manufacturer_name'];
        if (!empty($title)) {
            $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
            $manufacturer->setTitle($title);
            parent::setManufacturer($manufacturer);
        }
    }

    public function setVisibility()
    {
        $visibility = new Shopgate_Model_Catalog_Visibility();
        $visibility->setLevel(Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH);
        $visibility->setMarketplace(true);

        parent::setVisibility($visibility);
    }

    public function setStock()
    {
        $useStock = $this->productStock->buildUseStock($this->item['subtract']);

        $stock = new Shopgate_Model_Catalog_Stock();
        $stock->setUseStock((int)$useStock);
        $stock->setMinimumOrderQuantity($this->item['minimum']);
        if ($useStock) {
            $stock->setMaximumOrderQuantity(
                $this->productStock->buildMaximumOrderQuantity($this->item['quantity'], $this->item['subtract'])
            );
        }
        $stock->setIsSaleable(
            $this->productStock->buildIsSaleable(
                $useStock,
                $this->item['quantity'],
                $this->item['subtract'],
                $this->item['status']
            )
        );

        $stock->setStockQuantity($this->productStock->buildStockQuantity($this->item['quantity']));
        $stock->setAvailabilityText($this->_buildAvailableText());

        parent::setStock($stock);
    }

    public function setImages()
    {
        $result = array();
        $images = $this->_getProductImages();
        if (!empty($images)) {
            foreach ($images as $imageUrl) {
                $imagesItemObject = new Shopgate_Model_Media_Image();
                $imagesItemObject->setUrl($imageUrl);
                $result[] = $imagesItemObject;
            }
        }
        parent::setImages($result);
    }

    public function setCategoryPaths()
    {
        $result      = array();
        $categoryIds = $this->_getProductCategoryIds();
        foreach ($categoryIds as $categoryId) {
            $sortOrder          = $this->_highestSort - $this->item['sort_order'];
            $categoryItemObject = new Shopgate_Model_Catalog_CategoryPath();
            $categoryItemObject->setUid($categoryId);
            $categoryItemObject->setSortOrder($sortOrder);

            $result[] = $categoryItemObject;
        }

        parent::setCategoryPaths($result);
    }

    public function setProperties()
    {
        $result     = array();
        $properties = $this->_getProductProperties();
        foreach ($properties as $property) {
            $propertyItemObject = new Shopgate_Model_Catalog_Property();
            $propertyItemObject->setUid(bin2hex($property["attr_desc"]));
            $propertyItemObject->setLabel($property["attr_desc"]);
            $propertyItemObject->setValue($property["text"]);
            $result[] = $propertyItemObject;
        }

        parent::setProperties($result);
    }

    public function setIdentifiers()
    {
        $result = array();

        $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
        $identifierItemObject->setType('SKU');
        $identifierItemObject->setValue($this->item['model']);
        $result[] = $identifierItemObject;

        if (!empty($this->item['ean'])) {
            $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
            $identifierItemObject->setType('EAN');
            $identifierItemObject->setValue($this->item['ean']);
            $result[] = $identifierItemObject;
        }
        if (!empty($this->item['upc'])) {
            $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
            $identifierItemObject->setType('UPC');
            $identifierItemObject->setValue($this->item['upc']);
            $result[] = $identifierItemObject;
        }
        if (!empty($this->item['isbn'])) {
            $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
            $identifierItemObject->setType('ISBN');
            $identifierItemObject->setValue($this->item['isbn']);
            $result[] = $identifierItemObject;
        }

        parent::setIdentifiers($result);
    }

    public function setTags()
    {
        $tags = array();
        foreach (explode(',', $this->item['product_tags']) as $tag) {
            $tagItemObject = new Shopgate_Model_Catalog_Tag();
            $tagItemObject->setValue(trim($tag));
            $tags[] = $tagItemObject;
        }
        parent::setTags($tags);
    }

    public function setRelations()
    {
        $result     = array();
        $relatedIds = $this->_getRelatedProductIds();

        if (!empty($relatedIds)) {
            $relatedRelation = new Shopgate_Model_Catalog_Relation();
            $relatedRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $relatedRelation->setValues($relatedIds);
            $result[] = $relatedRelation;
        }

        parent::setRelations($result);
    }

    public function setAttributeGroups()
    {
        $result = array();

        if ($this->getExportOptionType() == self::EXPORT_OPTION_TYPE_ATTRIBUTES) {
            $options = $this->_getProductOptions();

            if (!empty($options)) {
                $preparedOptions = array();

                // extract option groups from option/option-value pairs (avoid duplicate entries by this)
                foreach ($options as $option) {
                    $preparedId   = $option['product_option_id'];
                    $preparedName = $option['option_name'];

                    // checkboxes need a special treatment (yes/no selections)
                    if ($option['type'] == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX) {
                        $preparedId   .= '_' . $option['product_option_value_id'];
                        $preparedName .= ' - ' . $option['option_value_name'];
                    }

                    // build structure (avoid duplicate options and account for checkbox options)
                    $preparedOptions[$preparedId] = array(
                        'id'          => $preparedId,
                        'name'        => $preparedName,
                        'type'        => $option['type'],
                        'option_data' => $option, // might be useful for custom adaptions
                    );
                }

                // finally create the attribute groups from prepared groups before
                foreach ($preparedOptions as $preparedOption) {
                    $attributeItem = new Shopgate_Model_Catalog_AttributeGroup();

                    $attributeItem->setUid($preparedOption['id']);
                    $attributeItem->setLabel($preparedOption['name']);

                    array_push($result, $attributeItem);
                }
            }
        }

        parent::setAttributeGroups($result);
    }

    public function setInputs()
    {
        $result = array();

        if ($this->getExportOptionType() == self::EXPORT_OPTION_TYPE_INPUTS) {
            $preparedOptions = array();
            $options         = $this->_getProductOptions();

            foreach ($options as $option) {
                $option['price'] =
                    $this->_taxModel->calculate($option['price'], $this->item['tax_class_id'], false);

                if ($option['subtract'] == 1
                    && $option['quantity'] == 0
                ) {
                    continue;
                }

                if (empty($preparedOptions[$option['product_option_id']])) {
                    $preparedOptions[$option['product_option_id']] = array(
                        'label'      => $option['option_name'],
                        'required'   => $option['required'],
                        'input_type' => $this->_mapInputType($option['type']),
                        'values'     => array(
                            $option['product_option_value_id'] => array(
                                'label' => $option['option_value_name'],
                                'price' => $option['price_prefix'] . $option['price'],
                            ),
                        ),
                    );
                } else {
                    $preparedOptions[$option['product_option_id']]['values'][$option['product_option_value_id']] =
                        array(
                            'label' => $option['option_value_name'],
                            'price' => $option['price_prefix'] . $option['price'],
                        );
                }
            }

            foreach ($preparedOptions as $id => $option) {
                if ($option['input_type'] == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE
                    && $option['required']
                ) {
                    throw new ShopgateProductSkippedException(
                        'Can not export product with id ' . $this->item['id'] . ': contains option if type '
                        . $option['input_type']
                    );
                }

                $inputItem = new Shopgate_Model_Catalog_Input();
                $inputItem->setUid($id);
                $inputItem->setType($option['input_type']);
                $inputItem->setLabel($option['label']);
                $inputItem->setRequired($option['required']);
                $inputItem->setOptions($this->_buildInputOptions($option['input_type'], $option));
                $result[] = $inputItem;
            }
        }

        // text and date input fields always need to be exported as inputs, even if child products are to be exported
        $textInputs = $this->getTextInputs();
        if (!empty($textInputs)) {
            $result += $textInputs;
        }

        parent::setInputs($result);
    }

    /**
     * @param string $inputType
     * @param array  $option
     *
     * @return array
     */
    protected function _buildInputOptions($inputType, $option)
    {
        $optionValues = array();

        switch ($inputType) {
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME:
                $inputOption = new Shopgate_Model_Catalog_Option();
                $inputOption->setAdditionalPrice(
                    $option['price_prefix'] . $option['price']
                );
                $optionValues[] = $inputOption;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE:
                foreach ($option['values'] as $id => $value) {
                    /** @var Mage_Catalog_Model_Product_Option_Value $value */
                    $inputOption = new Shopgate_Model_Catalog_Option();
                    $inputOption->setUid($id);
                    $inputOption->setLabel($value['label']);
                    $inputOption->setAdditionalPrice($value['price']);
                    $optionValues[] = $inputOption;
                }
                break;
        }

        return $optionValues;
    }

    /**
     * @return array|null
     *
     * @throws ShopgateProductSkippedException
     */
    protected function getTextInputs()
    {
        // get text inputs only once per product (and cache the result for child products)
        if (!isset($this->_textInputCache)) {
            $this->_textInputCache = array();

            $personalisations = $this->_getProductInputFields();
            foreach ($personalisations as $personalisation) {
                $inputType = $this->_mapInputType($personalisation['type']);
                if ($inputType == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE
                    && $personalisation['required']
                ) {
                    throw new ShopgateProductSkippedException(
                        'Can not export product with id ' . $this->item['id'] . ': contains option if type '
                        . $inputType
                    );
                }

                $inputItem = new Shopgate_Model_Catalog_Input();
                $inputItem->setUid($personalisation['product_option_id']);
                $inputItem->setType($inputType);
                $inputItem->setLabel($personalisation['option_name']);
                $inputItem->setRequired($personalisation['required']);

                if ($inputType == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE) {
                    $inputItem->setInfoText('YYYY-MM-DD');
                }
                if ($inputType == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME) {
                    $inputItem->setInfoText('HH:MM');
                }
                if ($inputType == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME) {
                    $inputItem->setInfoText('YYYY-MM-DD HH:MM');
                }
                $this->_textInputCache[] = $inputItem;
            }
        }

        return $this->_textInputCache;
    }

    public function setChildren()
    {
        $children = array();

        if ($this->getExportOptionType() == self::EXPORT_OPTION_TYPE_ATTRIBUTES) {
            $options = $this->_getProductOptions();

            $helper = $this->getHelper(self::HELPER_DATASTRUCTURE);

            if (!empty($options)) {
                // reformat data structures to be able to build child products
                $preparedOptions = $this->prepareOptions($options);

                $childList = $helper->arrayCross($preparedOptions);

                foreach ($childList as $childData) {
                    // build child
                    $child = new ShopgateOpencartExportProductXml($this->language);
                    $child->setIsChild(true);

                    // create a unique uid for each created child product and calculate weight and quantity
                    $stock               = clone $this->getStock();
                    $parentStockQuantity = $stock->getStockQuantity();

                    $productVariant = new ProductVariant($childData);
                    $productVariant->calculateVariant($parentStockQuantity);
                    $stockQuantity = $this->productStock->buildStockQuantity($parentStockQuantity, $productVariant);
                    $useStock      = $this->productStock->buildUseStock($this->item['subtract'], $productVariant);

                    if ($useStock == 1 && $stockQuantity <= 0) {
                        // Do not export product variants without stock
                        continue;
                    }

                    $childId = rtrim($productVariant->getChildId(), '_');
                    $child->setData('uid', $this->item['id'] . '_' . $childId);
                    $child->setData('weight', $this->item['weight'] + $productVariant->getAdditionalWeight());

                    // calculate the stock and his rules again for each children
                    $stock->setStockQuantity($stockQuantity);
                    $stock->setMaximumOrderQuantity(
                        $this->productStock->buildMaximumOrderQuantity(
                            $parentStockQuantity,
                            $this->item['subtract'],
                            $productVariant
                        )
                    );
                    $stock->setUseStock($useStock);

                    $child->setData('stock', $stock);

                    // set child attributes
                    $child->setAttributes($productVariant->getAttributes());

                    // calculate new prices (assuming the parent's prices have already been calculated at this point)
                    $childPrices = new Shopgate_Model_Catalog_Price();
                    $childPrices->setPrice($this->getPrice()->data['price'] + $productVariant->getAdditionalPrice());
                    $childPrices->setSalePrice(
                        $this->getPrice()->data['sale_price'] + $productVariant->getAdditionalPrice()
                    );
                    $child->setData('price', $childPrices);

                    // set sku (take product model or id from parent, depending on what is actually available)
                    $childIdentifier = new Shopgate_Model_Catalog_Identifier();
                    $childIdentifier->setType('SKU');
                    $childIdentifier->setValue(
                        !empty($this->item['model'])
                            ? $this->item['model']
                            : $this->item->id
                    );
                    $child->setData('identifiers', array($childIdentifier));

                    // add internal order info data to identify the option selection
                    $internalOrderInfo    = $this->getInternalOrderInfo();
                    $internalOrderInfoArr = array();
                    if (!empty($internalOrderInfo)) {
                        $internalOrderInfoArr = $this->jsonDecode($internalOrderInfo, true);
                    }
                    $internalOrderInfoArr['option_selection'] = $productVariant->getOptionSelection();
                    $child->setInternalOrderInfo($this->jsonEncode($internalOrderInfoArr));

                    // don't overwrite inputs from parent (take over)

                    array_push($children, $child);
                }
            }
        }

        parent::setChildren($children);
    }

    /**
     * @return int|null
     */
    protected function _isGrossMarket()
    {
        if (is_null($this->_grossMarket)) {
            $grossMarket = 0;
            if ($this->_getConfiguration()->getModel('config')->get('config_tax')) {
                $grossMarket = 1;
            }
            $this->_grossMarket = $grossMarket;
        }

        return $this->_grossMarket;
    }

    /**
     * Prepares a set of options to be able to be fed to the helper method "arrayCross"
     *
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options = array())
    {
        $preparedOptions = array();

        foreach ($options as $option) {
            $preparedId = $option['product_option_id'];
            if ($option['type'] == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX) {
                $preparedId .= '_' . $option['product_option_value_id'];
            }
            if (empty($preparedOptions[$preparedId])) {
                $preparedOptions[$preparedId] = array();
            }
            // add different selectors for checkbox options
            if ($option['type'] != Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX) {
                $preparedOptions[$preparedId][] = $option;
            } else {
                if (!$option['required']) {
                    // overwrite all necessary fields to cause no impact on the price and weight
                    $selectionNo                            = $option;
                    $selectionNo['product_option_id']       = $preparedId;
                    $selectionNo['product_option_value_id'] = '0';
                    $selectionNo['option_name']             =
                        $option['option_name'] . ' - ' . $option['option_value_name'];
                    $selectionNo['option_value_name']       = ucfirst($this->language->get('text_no'));
                    $selectionNo['weight']                  = '0.00000000';
                    $selectionNo['price_prefix']            = '+';
                    $selectionNo['price']                   = '0.0000';
                    $preparedOptions[$preparedId][]         = $selectionNo;
                }

                $selectionYes                            = $option;
                $selectionYes['product_option_id']       = $preparedId;
                $selectionYes['product_option_value_id'] =
                    '1'; // this must be 1 because of identification if the checkbox was set or not. We need it for checkStock and checkCart
                $selectionYes['option_name']             =
                    $option['option_name'] . ' - ' . $option['option_value_name'];
                $selectionYes['option_value_name']       = ucfirst($this->language->get('text_yes'));
                $selectionYes['raw_option_name']         = $option['option_name'];
                $selectionYes['raw_option_value_name']   = $option['option_value_name'];
                $preparedOptions[$preparedId][]          = $selectionYes;
            }
        }

        return $preparedOptions;
    }

    protected function getExportOptionType()
    {
        if (!$this->_exportOptionType) {
            if (!$this->_getOpencartDatabase()->assertMinimumVersion('1.3.4')) {
                // export inputs only for versions that do not support additional data like weight for option values
                $this->_exportOptionType = self::EXPORT_OPTION_TYPE_INPUTS;
            } else {
                // check if child products are necessary
                $options = $this->_getProductOptions();

                $this->_exportOptionType = self::EXPORT_OPTION_TYPE_INPUTS;
                if (!empty($options)) {
                    // calculate child item count by preparing the options
                    $preparedOptions = $this->prepareOptions($options);

                    // export inputs if the max children count is exceeded
                    $childItemCount = self::EXPORT_VALUE_NOT_INITIALIZED;
                    foreach ($preparedOptions as $optionValues) {
                        $valueCount = count($optionValues);
                        if ($valueCount) {
                            if (!isset($childItemCount)) {
                                $childItemCount = 1;
                            }
                            $childItemCount *= $valueCount;
                        }
                    }
                    if (!isset($childItemCount)) {
                        $childItemCount = 0;
                    }

                    if ($childItemCount > $this->_config->getMaxAttributes()) {
                        $this->_exportOptionType = self::EXPORT_OPTION_TYPE_INPUTS;
                    } else {
                        // check if any option-value pair modifies the weight and set to export attributes in that case
                        $parentQuantity = $this->getStock()->data['stock_quantity'];
                        foreach ($options as $optKey => $option) {
                            if ((!empty($option['weight'])
                                    && floatval($option['weight']) > 0)
                                || (!empty($option['quantity'])
                                    && intval($option['quantity']) != intval($parentQuantity))
                            ) {
                                $this->_exportOptionType = self::EXPORT_OPTION_TYPE_ATTRIBUTES;

                                // stop here since one single weight change forces attributes to be used
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $this->_exportOptionType;
    }
}
