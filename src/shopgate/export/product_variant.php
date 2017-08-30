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

class ProductVariant
{
    /** @var array */
    private $options;

    /** @var bool */
    private $isAnyOptionSetToSubtract;

    /** @var bool */
    private $isEveryOptionSetToSubtract;

    /** @var bool */
    private $quantityChangedBecauseOfOptionQuantity;

    /** @var float */
    private $additionalPrice;

    /** @var float */
    private $additionalWeight;

    /** @var int */
    private $quantity;

    /** @var array */
    private $attributes;

    /** @var int */
    private $lowestOptionsQuantity;

    /** @var array */
    private $optionSelection;

    /** @var int */
    private $childId;

    /**
     * @param array $options - Array (  )
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param int $parentQuantity
     */
    public function calculateVariant($parentQuantity)
    {
        $this->quantity                               = $parentQuantity;
        $this->lowestOptionsQuantity                  = null;
        $this->childId                                = '';
        $this->additionalWeight                       = 0;
        $this->additionalPrice                        = 0;
        $this->optionSelection                        = array();
        $this->attributes                             = array();
        $this->isAnyOptionSetToSubtract               = false;
        $this->isEveryOptionSetToSubtract             = true;
        $this->quantityChangedBecauseOfOptionQuantity = false;

        foreach ($this->options as $option) {
            // assemble uid and create option selection
            $optId         = $option['product_option_id'];
            $optValId      = $option['product_option_value_id'];
            $this->childId .= "{$optId}-{$optValId}_";

            $optionName = isset($option['raw_option_name'])
                ? $option['raw_option_name']
                : $option['option_name'];

            $optionValueName = isset($option['raw_option_value_name'])
                ? $option['raw_option_value_name']
                : $option['option_value_name'];

            $this->optionSelection[$optId] = array(
                'product_option_id'         => $optId,
                'product_option_value_id'   => $optValId,
                'type'                      => $option['type'],
                'product_option_name'       => $optionName,
                'product_option_value_name' => $optionValueName,
            );

            // create attributes
            $attribute = new Shopgate_Model_Catalog_Attribute();
            $attribute->setGroupUid($optId);
            $attribute->setUid($optValId);
            $attribute->setLabel($option['option_value_name']);
            array_push($this->attributes, $attribute);

            // calculate weight (each option-value can add up)
            $this->additionalWeight += floatval($option['weight_prefix'] . $option['weight']);

            if ($option['quantity'] < $this->quantity && $option['subtract'] && $optValId > 0) {
                // $optValId is 0 in case we add a "no selection" value for a checkbox
                $this->quantity                               = $option['quantity'];
                $this->quantityChangedBecauseOfOptionQuantity = true;
            }

            if ((is_null($this->lowestOptionsQuantity) || $option['quantity'] < $this->lowestOptionsQuantity)
                && $optValId > 0
            ) {
                $this->lowestOptionsQuantity = $option['quantity'];
            }

            if ($option['subtract']) {
                $this->isAnyOptionSetToSubtract = true;
            } else {
                $this->isEveryOptionSetToSubtract = false;
            }

            // calculate price (can be negative)
            $this->additionalPrice += floatval($option['price_prefix'] . $option['price']);
        }

        $this->quantity = $this->quantity > 0
            ? $this->quantity
            : 0;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return null|bool
     */
    public function getIsAnyOptionSetToSubtract()
    {
        return $this->isAnyOptionSetToSubtract;
    }

    /**
     * @return null|bool
     */
    public function getIsEveryOptionSetToSubtract()
    {
        return $this->isEveryOptionSetToSubtract;
    }

    /**
     * @return null|int
     */
    public function getQuantityChangedBecauseOfOptionQuantity()
    {
        return $this->quantityChangedBecauseOfOptionQuantity;
    }

    /**
     * @return null|float
     */
    public function getAdditionalPrice()
    {
        return $this->additionalPrice;
    }

    /**
     * @return null|float
     */
    public function getAdditionalWeight()
    {
        return $this->additionalWeight;
    }

    /**
     * @return null|int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return null|array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return null|int
     */
    public function getLowestOptionsQuantity()
    {
        return $this->lowestOptionsQuantity;
    }

    /**
     * @return null|array
     */
    public function getOptionSelection()
    {
        return $this->optionSelection;
    }

    /**
     * @return string
     */
    public function getChildId()
    {
        return $this->childId;
    }
}
