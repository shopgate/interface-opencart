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
 * Product CSV export class
 */
class ShopgateOpencartExportProductCsv extends ShopgateOpencartExportProduct
{
    /**
     * @var null
     */
    protected $_defaultRow = null;

    /**
     * @var null
     */
    protected $_actionCache = null;

    /**
     * @return array
     */
    public function generateData()
    {
        foreach (array_keys($this->_defaultRow) as $key) {
            $action = "_set" . str_replace(" ", "", ucwords(str_replace('_', " ", $key)));
            if (empty($this->_actionCache[$action])) {
                $this->_actionCache[$action] = true;
            }
        }

        foreach (array_keys($this->_actionCache) as $_action) {
            if (method_exists($this, $_action)) {
                $this->{$_action}();
            }
        }

        $this->_generateOptions();
        $this->_generateInputFields();

        return $this->_defaultRow;
    }

    /**
     * @param array $defaultRow
     */
    public function setDefaultRow($defaultRow)
    {
        $this->_defaultRow = $defaultRow;
    }

    protected function _setItemNumber()
    {
        $this->_defaultRow['item_number'] = $this->item['id'];
    }

    protected function _setItemNumberPublic()
    {
        $this->_defaultRow['item_number_public'] = $this->item['model'];
    }

    protected function _setItemName()
    {
        $this->_defaultRow['item_name'] = html_entity_decode($this->item['products_name']);
    }

    protected function _setTaxClass()
    {
        if (array_key_exists('tax_class', $this->_defaultRow)) {
            $rowTax                         = $this->_getTaxByTaxClassId();
            $this->_defaultRow['tax_class'] = $this->item['tax_class_id'] . "=>" . $rowTax['title'];
        }
    }

    protected function _setUnitAmount()
    {
        if ($this->_getConfiguration()->getModel('config')->get('config_tax')) {
            $price                            =
                $this->_taxModel->calculate($this->_buildPrice(), $this->item['tax_class_id'], 1);
            $this->_defaultRow['unit_amount'] = round($price, 2);
        }
    }

    protected function _setUnitAmountNet()
    {
        if (!$this->_getConfiguration()->getModel('config')->get('config_tax')) {
            $this->_defaultRow['unit_amount_net'] = round($this->_buildPrice(), 2);
        }
    }

    protected function _setOldUnitAmount()
    {
        if ($this->_getConfiguration()->getModel('config')->get('config_tax')) {
            $priceOld                             =
                $this->item['normal_price'] * $this->_getConfiguration()->getCurrencyRate();
            $priceOld                             =
                $this->_taxModel->calculate($priceOld, $this->item['tax_class_id'], 1);
            $this->_defaultRow['old_unit_amount'] = round($priceOld, 2);
        }
    }

    protected function _setOldUnitAmountNet()
    {
        if (!$this->_getConfiguration()->getModel('config')->get('config_tax')) {
            $priceOld                                 =
                $this->item['normal_price'] * $this->_getConfiguration()->getCurrencyRate();
            $this->_defaultRow['old_unit_amount_net'] = round($priceOld, 2);
        }
    }

    protected function _setTaxPercent()
    {
        $this->_defaultRow['tax_percent'] = $this->_buildTaxPercent();
    }

    protected function _setManufacturerItemNumber()
    {
        $this->_defaultRow['manufacturer_item_number'] = $this->item['mpn'];
    }

    protected function _setStockQuantity()
    {
        $this->_defaultRow['stock_quantity'] = $this->item['quantity'];
    }

    protected function _setIsFreeShipping()
    {
        $this->_defaultRow['is_free_shipping'] = $this->_buildFreeShipping();
    }

    protected function _setDescription()
    {
        $this->_defaultRow['description'] = html_entity_decode($this->item['product_description']);
    }

    protected function _setCategoryNumbers()
    {
        $categoryIds = $this->_getProductCategoryIds();
        foreach ($categoryIds as $id => $categoryId) {
            $categoryIds[$id] = $categoryId . "=>" . ($this->_highestSort - $this->item['sort_order']);
        }
        $this->_defaultRow['category_numbers'] = implode('||', $categoryIds);
    }

    protected function _setRelatedShopItemNumbers()
    {
        $relatedIds                                     = $this->_getRelatedProductIds();
        $this->_defaultRow['related_shop_item_numbers'] = implode('||', $relatedIds);
    }

    protected function _setUrlDeeplink()
    {
        $this->_defaultRow['url_deeplink'] = $this->_buildDeeplink();
    }

    protected function _setManufacturer()
    {
        $this->_defaultRow['manufacturer'] = $this->item['manufacturer_name'];
    }

    protected function _setIsbn()
    {
        $this->_defaultRow['isbn'] = $this->item['isbn'];
    }

    protected function _setEan()
    {
        $this->_defaultRow['ean'] = $this->item['ean'];
    }

    protected function _setUpc()
    {
        $this->_defaultRow['upc'] = $this->item['upc'];
    }

    protected function _setAvailableText()
    {
        $this->_defaultRow['available_text'] = $this->_buildAvailableText();
    }

    protected function _setMinimumOrderQuantity()
    {
        $this->_defaultRow['minimum_order_quantity'] = $this->item['minimum'];
    }

    protected function _setLastUpdate()
    {
        $this->_defaultRow['last_update'] = $this->item['date_modified'];
    }

    protected function _setWeight()
    {
        $this->_defaultRow['weight'] = $this->item['weight'];
    }

    protected function _setWeightUnit()
    {
        $this->_defaultRow['weight_unit'] = $this->_buildWeightUnit();
    }

    protected function _setCurrency()
    {
        $this->_defaultRow['currency'] = $this->_getConfiguration()->getCurrencyId();
    }

    protected function _setUseStock()
    {
        $this->_defaultRow['use_stock'] = $this->productStock->_buildUseStock($this->item['subtract']);
    }

    protected function _setUrlsImages()
    {
        $images                           = $this->_getProductImages();
        $this->_defaultRow['urls_images'] = implode("||", $images);
    }

    protected function _setIsAvailable()
    {
        $this->_defaultRow['is_available'] = $this->_buildIsAvailable()
            ? 1
            : 0;
    }

    protected function _setActiveStatus()
    {
        $this->_defaultRow['active_status'] = $this->_buildIsAvailable()
            ? 'stock'
            : 'inactive';
    }

    protected function _setProperties()
    {
        $shopgateProperties = array();
        $properties         = $this->_getProductProperties();
        foreach ($properties as $property) {
            $shopgateProperties[] = $property["attr_desc"] . "=>" . $property["text"];
        }

        $this->_defaultRow['properties'] = implode('||', $shopgateProperties);
    }

    protected function _generateOptions()
    {
        $options    = $this->_getProductOptions();
        $optionId   = null;
        $optionEnum = 0;
        $hasOptions = 0;

        foreach ($options as $option) {
            $value = "";
            if ($option["product_option_id"] != $optionId) {
                $optionEnum++;
                $hasOptions   = 1;
                $optionId     = $option["product_option_id"];
                $priceTmp     = $option["price_prefix"] . $option["price"];
                $priceInCents = $priceTmp * 100;

                if ($option["required"] != 1) {
                    $value = "---=>0" . "||";
                } else {
                    if ($option["type"] == "file") {
                        $this->_defaultRow["active_status"] = "inactive";
                    }
                }
                $value .= $option["product_option_value_id"] . "=" .
                    $option["option_value_name"] . "=>" .
                    $option["price_prefix"] . $priceInCents;

                $this->_defaultRow['option_' . $optionEnum]             = $option["option_name"];
                $this->_defaultRow['option_' . $optionEnum . '_values'] = $value;
            } else {
                $priceTmp     = $option["price"];
                $priceInCents = $priceTmp * 100;
                $value        = $option["product_option_value_id"] . "=" .
                    $option["option_value_name"] . "=>" .
                    $option["price_prefix"] . $priceInCents;

                $this->_defaultRow['option_' . $optionEnum . '_values'] .= "||" . $value;
            }
        }

        $this->_defaultRow['has_options'] = $hasOptions;
    }

    protected function _generateInputFields()
    {
        $personalisations    = $this->_getProductInputFields();
        $hasInputFields      = 0;
        $personalisationEnum = 0;

        foreach ($personalisations as $personalisation) {
            $hasInputFields = 1;
            $personalisationEnum++;

            $this->_defaultRow['input_field_' . $personalisationEnum . '_type']     = "text";
            $this->_defaultRow['input_field_' . $personalisationEnum . '_number']   =
                $personalisation["product_option_id"];
            $this->_defaultRow['input_field_' . $personalisationEnum . '_label']    = $personalisation["option_name"];
            $this->_defaultRow['input_field_' . $personalisationEnum . '_required'] = $personalisation["required"];

            if ($personalisation["type"] == "date") {
                $this->_defaultRow['input_field_' . $personalisationEnum . '_infotext'] = "YYYY-MM-DD";
            }
            if ($personalisation["type"] == "time") {
                $this->_defaultRow['input_field_' . $personalisationEnum . '_infotext'] = "HH:MM";
            }
            if ($personalisation["type"] == "datetime") {
                $this->_defaultRow['input_field_' . $personalisationEnum . '_infotext'] = "YYYY-MM-DD HH:MM";
            }
        }

        $this->_defaultRow['has_input_fields'] = $hasInputFields;
    }
}
