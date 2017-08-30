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
 * Category export
 */
class ShopgateOpencartExportCategory extends Shopgate_Model_Catalog_Category
{
    /**
     * @var null|int
     */
    protected $_highestSort = null;

    /**
     * @param int $sort
     */
    public function setHighestSort($sort)
    {
        $this->_highestSort = $sort;
    }

    /**
     * @return int|null
     */
    protected function _buildSortOrder()
    {
        return $this->_highestSort
            ? $this->_highestSort - $this->item['sort_order']
            : $this->item['sort_order'];
    }

    /**
     * @return string
     */
    protected function _buildDeeplink()
    {
        $path = "";
        if ($this->item['parent_id'] != 0) {
            $path = $this->item['parent_id'] . "_";
        }
        $path = $path . $this->item['category_id'];
        /** @var Url $urlModel */
        $urlModel = ShopgateOpencart::getModel('url');
        if (!empty($urlModel) && method_exists($urlModel, 'link')) {
            $url = $urlModel->link('product/category', 'path=' . $path);
        } elseif (!empty($urlModel) && method_exists($urlModel, 'http')) {
            $url = $urlModel->http('product/category') . '&path=' . $path;
        } else {
            $url = HTTP_SERVER . 'index.php?route=product/category&path=' . $path;
        }

        return htmlspecialchars_decode($url);
    }
}
