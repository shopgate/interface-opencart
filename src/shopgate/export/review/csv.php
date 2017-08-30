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
 * Review CSV export class
 */
class ShopgateOpencartExportReviewCsv extends ShopgateOpencartExportReview
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

    protected function _setItemNumber()
    {
        $this->_defaultRow['item_number'] = $this->item['product_id'];
    }

    protected function _setUpdateReviewId()
    {
        $this->_defaultRow['update_review_id'] = $this->item['review_id'];
    }

    protected function _setScore()
    {
        $this->_defaultRow['score'] = $this->_buildRating();
    }

    protected function _setName()
    {
        $this->_defaultRow['name'] = $this->item['author'];
    }

    protected function _setDate()
    {
        $this->_defaultRow['date'] = $this->_buildDate();
    }

    protected function _setTitle()
    {
        $this->_defaultRow['title'] = $this->_buildTitle();
    }

    protected function _setText()
    {
        $this->_defaultRow['text'] = $this->item['text'];
    }
}
