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
 * Category XML export class
 */
class ShopgateOpencartExportCategoryXml extends ShopgateOpencartExportCategory
{
    public function setUid()
    {
        parent::setUid($this->item['category_id']);
    }

    public function setIsActive()
    {
        parent::setIsActive(
            is_null($this->item['status'])
                ? 1
                : $this->item['status']
        );
    }

    public function setParentUid()
    {
        parent::setParentUid(
            $this->item['parent_id'] == 0
                ? ""
                : $this->item['parent_id']
        );
    }

    public function setImage()
    {
        if ($this->item['image'] != "") {
            $imageModel = new Shopgate_Model_Media_Image();
            $imageModel->setUrl(HTTP_SERVER . "image/" . $this->item['image']);
            parent::setImage($imageModel);
        }
    }

    public function setName()
    {
        parent::setName($this->item['name']);
    }

    public function setDeeplink()
    {
        parent::setDeeplink($this->_buildDeeplink());
    }

    public function setSortOrder()
    {
        parent::setSortOrder($this->_buildSortOrder());
    }
}
