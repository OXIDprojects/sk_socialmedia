<?php
/**
 *    This file is part of OXID eShop Community Edition.
 *
 *    OXID eShop Community Edition is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    OXID eShop Community Edition is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @package   main
 * @copyright (C) OXID eSales AG 2003-2012
 * @version OXID eShop CE
 * @version   SVN: $Id: theme.php 25466 2010-02-01 14:12:07Z alfonsas $
 */

/**
 * Module information
 */
$aModule = array(
    'id'           => 'sk_socialmedia',
    'title'        => 'Post 2 Social Media Pages',
    'description'  => 'Post new Articles 2 Social Media Pages',
    'version'      => '1.2.1',
    'author'       => 'sitzdesign.de',
    'extend'       => array(
        'oxadmindetails'	=> 'sk_socialmedia/sk_socialmedia',
    ),
    'files' => array(
    ),
    'settings' => array(
        array('group' => 'socialmedia', 'name' => 'webUrl', 'type' => 'str',  'value' => ''),
        array('group' => 'socialmedia', 'name' => 'categoryId', 'type' => 'str',  'value' => ''),
        array('group' => 'socialmedia', 'name' => 'descLength', 'type' => 'str',  'value' => '300'),
        array('group' => 'facebook', 'name' => 'authCode', 'type' => 'str',  'value' => ''),
        array('group' => 'facebook', 'name' => 'groupId', 'type' => 'str',  'value' => ''),
        array('group' => 'facebook', 'name' => 'pageId', 'type' => 'str',  'value' => ''),
        array('group' => 'twitter', 'name' => 'consumer_key', 'type' => 'str',  'value' => ''),
        array('group' => 'twitter', 'name' => 'consumer_secret', 'type' => 'str',  'value' => ''),
        array('group' => 'twitter', 'name' => 'user_token', 'type' => 'str',  'value' => ''),
        array('group' => 'twitter', 'name' => 'user_secret', 'type' => 'str',  'value' => ''),
        array('group' => 'twitter', 'name' => 'useKeywordsAsHashTags', 'type' => 'bool',  'value' => true),
    ),
    'templates' => array(
        'sk_socialmedia.tpl' => 'sk_socialmedia/sk_socialmedia.tpl',
    ),
);