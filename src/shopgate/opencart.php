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
 * Opencart major class
 */
final class ShopgateOpencart
{
    /**
     * @param string $modelClass
     *
     * @return Object
     */
    public static function getModel($modelClass = '')
    {
        if (empty($GLOBALS['registry'])) {
            return Registry::get($modelClass);
        }

        return $GLOBALS['registry']->get($modelClass);
    }

    /**
     * A hack to load language files from e.g. the /admin section although frontend context and vice versa.
     *
     * @param string   $path     The language path/context, e.g. 'module/shopgate'.
     * @param string   $folder   The folder name, e.g. "catalog" or "admin".
     * @param Language $language The language object the language file should be loaded into or null to create a new
     *                           one.
     *
     * @return Language The new or manipulated Language object.
     */
    public static function loadLanguageFrom($path, $folder = 'catalog', Language $language = null)
    {
        if (empty($language)) {
            $language = self::getModel('language');
        }

        $database = new ShopgateOpencartDatabase();
        $language->load(
            '../../../' . $folder . '/language/' . $database->getLanguageDirectory(
                self::getModel('config')->get('config_admin_language')
            ) . '/' . $path
        );

        return $language;
    }
}
