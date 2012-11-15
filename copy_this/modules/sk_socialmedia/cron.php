<?
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
 *
 * @copyright Tr0nYx 2012, knornschild@sitzdesign.de
 */
function getShopBasePath()
{
	if ($_SERVER["DOCUMENT_ROOT"] == ''){
		return dirname(__FILE__).'/../../';
	} else {
		return $_SERVER["DOCUMENT_ROOT"].'/';
	}
}
include getShopBasePath().'core/oxfunctions.php';
include getShopBasePath().'core/oxsupercfg.php';
include getShopBasePath().'core/oxdb.php';

class sk_socialmedia_cron extends oxAdminDetails {

	public function render(){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		
		$oDB = oxDb::getDB(true);
		$this->_aViewData['edit'] = $oArticle = oxNew( 'oxarticle' );
		$myConfig  = $this->getConfig();
		require($myConfig->getModulesDir()."sk_socialmedia/lib/facebook.php");
		$fbAppSettings['AppId'] = $myConfig->getConfigParam( "sFbAppId" );
		$fbAppSettings['AppSecret'] = $myConfig->getConfigParam( "sFbSecretKey" );
		$fbAppSettings['webUrl'] = $myConfig->webUrl;
		$fbAppSettings['authCode'] = $myConfig->authCode;
		$fbAppSettings['pageId'] = $myConfig->pageId;
		$fbAppSettings['groupId'] = $myConfig->groupId;
		$fbAppSettings['categoryId'] = $myConfig->categoryId;
		$fbAppSettings['descLength'] = $myConfig->descLength;
				
		// Check if user input a valid webUrl in extension configuration
		$host = '';
		if ($fbAppSettings['webUrl']) {
			preg_match('@^(?:http://)?([^/]+)@i', $fbAppSettings['webUrl'], $matches);
			$host = $matches[1] ? $matches[1] . '/' : '';
		}
		
		if ($host == '') {
			$host = $myConfig->getConfigParam( "sShopURL" );
		}

		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = FALSE;

		// Create Application instance
		$facebook = new Facebook(
			array(
			  		'appId'  => $fbAppSettings['AppId'],
			  		'secret' => $fbAppSettings['AppSecret'],
			  		'cookie' => TRUE,
			)
		);
		
		// Get string responsed by Facebook 
		$url = 'https://graph.facebook.com/oauth/access_token?client_id='. $fbAppSettings['AppId'] 
			 . '&redirect_uri=' . $host . '&client_secret=' . $fbAppSettings['AppSecret']
			 . '&code=' . $fbAppSettings['authCode'];
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$groupAccessToken = curl_exec($ch);
		curl_close($ch);
		
		$groupList = array();
		$groupList = explode(',', $fbAppSettings['groupId']);
		$pageList = explode(',', $fbAppSettings['pageId']);

		$messages = array();

			// We need only the access token code so cut the word "access_token="
			// This access token is valid for group or non-owner of the page
		$groupAccessToken = substr($groupAccessToken, 13);
		if (preg_match('/expires/',$groupAccessToken))
		{
			$groupAccessToken = substr($groupAccessToken,0,-16);
		}

		// Generate new access token for page - this help to post attachment to FB as admin of the page
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me/accounts?access_token=' . $groupAccessToken);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$pagesResponse = json_decode(curl_exec($ch));
		curl_close($ch);
	
		// If json_decode returns null, cannot post the page as admin
		if(is_null($pagesResponse)) {
			$pageAccessTokenArray = $groupAccessToken;
		}
		else {
			$pageAccessTokenArray = array();
			foreach($pagesResponse->data as $pageData) {
					//Check page authentication if we can post as admin or not
				if (in_array($pageData->id , $pageList)) {
					// Store full access page with id and its access_token - because it uses its own access_token
					$pageAccessTokenArray[$pageData->id] = $pageData->access_token;
					
					// Search and remove full access page from pageList array
					$indexToRemove = array_search($pageData->id, $pageList);
					unset($pageList[$indexToRemove]);
				}
			}
			// Merge group with normal page - because it uses the same access_token
			$groupList = array_merge($groupList, $pageList);
		}
		
		// Select all news which not yet publish to facebook and post them to
		// the wall of the group page and fan page
		$inCat = '';
		$where = 'WHERE fbpublished = 0  AND smdontpublish = 0 AND oxactive = 1';
		$sSelect = 'SELECT * FROM oxarticles '.$where;
		
			// Get and validate the list of selected news categories
		$selectedCategories = array();
		$selectedCategories = explode(',', $fbAppSettings['categoryId'], TRUE);
		foreach($selectedCategories as $key => $value) {
			if($value < 1) {
				unset($selectedCategories[$key]);
			}
		}

		// If the categories set, select the articles by the allowed categories
		if(count($selectedCategories) > 0) {
			$inCat = ' AND oxobjectid IN (' . implode(',', $selectedCategories) .') ';
			$sSelect = 'SELECT * FROM oxarticles LEFT JOIN oxobject2category ON oxobject2category.oxobjectid = oxarticles.oxid '.$where . $inCat;
		}

		$rs = $oDB->execute($sSelect);

		if ($rs != false && $rs->recordCount() > 0) {
			while ( !$rs->EOF) {
				$aF = $rs->fields;
				$soxId = $aF['OXID'];
				if ( $soxId != "-1" && isset( $soxId ) ) {
					// load object
					$oArticle->loadInLang( $this->_iEditLang, $soxId );
					// load object in other languages
					$oOtherLang = $oArticle->getAvailableInLangs();
					if (!isset($oOtherLang[$this->_iEditLang])) {
						// echo "language entry doesn't exist! using: ".key($oOtherLang);
						$oArticle->loadInLang( key($oOtherLang), $soxId );
					}

					// variant handling
					if ( $oArticle->oxarticles__oxparentid->value) {
						$oParentArticle = oxNew( 'oxarticle' );
						$oParentArticle->load( $oArticle->oxarticles__oxparentid->value);
						$this->_aViewData["parentarticle"] = $oParentArticle;
						$this->_aViewData["oxparentid"]    = $oArticle->oxarticles__oxparentid->value;
					}
					
					$artLink = $oArticle->getLink( $this->_iEditLang, true, false );

					// Create attachment to post to FB by Graph API
					$attachment = array(
							'access_token' => $groupAccessToken,
							'link' => $artLink,
							'name' => $oArticle->oxarticles__oxtitle->value,
					);
					
					// If the description length is set , it will show the description text in post
					if(intval($fbAppSettings['descLength']) > 0) {
						$desc = strip_tags($oArticle->oxarticles__oxshortdesc->value ? $oArticle->oxarticles__oxshortdesc->value : $oArticle->oxarticles__oxlongdesc->value);
						$desc = preg_replace('/\s+/', ' ', $desc);
						$desc = substr($desc, 0, intval($fbAppSettings['descLength']));  // abcd
						$attachment['description'] = $desc;
					}
					
					$artImagePath = 'out/pictures/generated/product/thumb/';
					// Get the image path
					if($oArticle->oxarticles__oxthumb->value) {
						// Only one Picture in News, this will be taken
						$imagePath = $fbAppSettings['webUrl'] . $artImagePath . '/' . $oArticle->oxarticles__oxthumb->value;
						$attachment['picture'] = $oArticle->getThumbnailUrl();
					}

					// Post feed to all group
					if(count($groupList) > 0 && $groupList != "") {
						foreach ($groupList as $singleGroupID) {
							if ($singleGroupID != ""){
								$facebook->api('/' . $singleGroupID . '/feed/', 'post', $attachment);
								array_push($messages, 'Article ' . $oArticle->oxarticles__oxtitle->value . ' was published.');
							}
						}
					}

					// Post feed to all page
					if(count($pageAccessTokenArray) > 0) {
						foreach($pageAccessTokenArray as $pageId => $pageAccessToken) {
							$attachment['access_token'] = $pageAccessToken;
							$facebook->api('/' . $pageId . '/feed/', 'post', $attachment);
							array_push($messages, 'Article ' . $oArticle->oxarticles__oxtitle->value . ' was published.');
						}
					}
					$sql = 'UPDATE oxarticles SET fbpublished = 1 WHERE oxid LIKE "' . $soxId ."'";
					array_push($messages, $sql);
					$oDB->execute($sql);
				}
				$rs->moveNext();
			}
		}

		$logFilePath = $myConfig->getModulesDir()."sk_socialmedia/log.txt";
		$logFile = fopen($logFilePath, 'a');
		$time = date('d-M-Y H:i:s');
		$i = 0;
		while ($i < count($messages)) {
			fprintf($logFile, "%s %s \n", $time, $messages[$i]);
			$i++;
		}
		fclose($logFile);
		
		return $this->_sThisTemplate;
	}
}
$cronjob = new sk_socialmedia_cron();
$cronjob->render();
?>