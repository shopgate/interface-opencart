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
 * Common review class to prepare export data
 *
 * @package     ShopgateOpencartExportReview
 * @author      Stephan Recknagel <mail@recknagel.io>
 */
class ShopgateOpencartExportReview extends Shopgate_Model_Review
{
    /**
     * @return string
     */
    protected function _buildTitle()
    {
        return substr($this->item['text'], 0, 20) . "...";
    }

    /**
     * @return int
     */
    protected function _buildRating()
    {
        return $this->item['rating'] * 2;
    }

    /**
     * @return bool|string
     */
    protected function _buildDate()
    {
        return $this->item['date_added'] == 0
            ? ""
            : strftime("%Y-%m-%d", strtotime($this->item['date_added']));
    }
}
