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
 * Review XML export class
 */
class ShopgateOpencartExportReviewXml extends ShopgateOpencartExportReview
{
    /**
     * set id
     */
    public function setUid()
    {
        parent::setUid($this->item['review_id']);
    }

    /**
     * set product id for the review
     */
    public function setItemUid()
    {
        parent::setItemUid($this->item['product_id']);
    }

    /**
     * set score for the review
     */
    public function setScore()
    {
        parent::setScore($this->_buildRating());
    }

    /**
     * set username for the review
     */
    public function setReviewerName()
    {
        parent::setReviewerName($this->item['author']);
    }

    /**
     * set text for the review
     */
    public function setDate()
    {
        parent::setDate($this->item['date_added']);
    }

    /**
     * set title for the review
     */
    public function setTitle()
    {
        parent::setTitle($this->_buildTitle());
    }

    /**
     * set text for the review
     */
    public function setText()
    {
        parent::setText($this->item['text']);
    }
}
