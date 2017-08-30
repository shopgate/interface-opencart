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
 * Category CSV export class
 */
class ShopgateOpencartExportCategoryCsv extends ShopgateOpencartExportCategory
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

        return $this->_defaultRow;
    }

    /**
     * @param array $defaultRow
     */
    public function setDefaultRow($defaultRow)
    {
        $this->_defaultRow = $defaultRow;
    }

    protected function _setParentId()
    {
        $this->_defaultRow['parent_id'] = $this->item['parent_id'] == 0
            ? ""
            : $this->item['parent_id'];
    }

    protected function _setCategoryNumber()
    {
        $this->_defaultRow['category_number'] = $this->item['category_id'];
    }

    protected function _setCategoryName()
    {
        $this->_defaultRow['category_name'] = $this->item['name'];
    }

    protected function _setOrderIndex()
    {
        $this->_defaultRow['order_index'] = $this->_buildSortOrder();
    }

    protected function _setIsActive()
    {
        $this->_defaultRow['is_active'] = is_null($this->item['status'])
            ? 1
            : $this->item['status'];
    }

    protected function _setUrlDeeplink()
    {
        $this->_defaultRow['url_deeplink'] = $this->_buildDeeplink();
    }

    protected function _setUrlImage()
    {
        $this->_defaultRow['url_image'] = $this->item['image'] == ""
            ? ""
            : HTTP_SERVER . "image/" . $this->item['image'];
    }
}
