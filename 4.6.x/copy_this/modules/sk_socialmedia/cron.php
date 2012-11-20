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

	protected $AppId = null;
	
	protected $AppSecret = null;
	
	protected $webUrl = null;
	
	protected $authCode = null;
	
	protected $pageId = null;
	
	protected $groupId = null;
	
	protected $categoryId = null;
	
	protected $descLength = null;
	
	protected $host = '';
	
	protected $soxId = null;
	
	protected $useKeywordsAsHashTags = null;
	
	protected $consumer_key	= null;
	
	protected $consumer_secret = null;
	
	protected $user_token = null;
	
	protected $user_secret = null;
	
	public function render(){
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		
		$oDB = oxDb::getDB(true);
		$this->_aViewData['edit'] = $oArticle = oxNew( 'oxarticle' );
		$myConfig  = $this->getConfig();
		$this->AppId 					= $myConfig->getConfigParam( "sFbAppId" );
		$this->AppSecret				= $myConfig->getConfigParam( "sFbSecretKey" );
		$this->webUrl					= $myConfig->getConfigParam( "webUrl");
		$this->authCode					= $myConfig->getConfigParam( "authCode");
		$this->pageId					= $myConfig->getConfigParam( "pageId");
		$this->groupId					= $myConfig->getConfigParam( "groupId");
		$this->categoryId				= $myConfig->getConfigParam( "categoryId");
		$this->descLength				= $myConfig->getConfigParam( "descLength");
		$this->useKeywordsAsHashTags	= $myConfig->getConfigParam( "useKeywordsAsHashTags");
		$this->consumer_key				= $myConfig->getConfigParam( "consumer_key");
		$this->consumer_secret			= $myConfig->getConfigParam( "consumer_secret");
		$this->user_token				= $myConfig->getConfigParam( "user_token");
		$this->user_secret				= $myConfig->getConfigParam( "user_secret");
				
		if ($this->webUrl) {
			preg_match('@^(?:http://)?([^/]+)@i', $this->webUrl, $matches);
			$this->host = $matches[1] ? $matches[1] . '/' : '';
		}
		
		if ($this->host == '') {
			$this->host = $myConfig->getConfigParam( "sShopURL" );
		}
		
		// Select all news which not yet publish to facebook and post them to
		// the wall of the group page and fan page
		$inCat = '';
		$where = "WHERE (";
		if ($this->authCode != "")
			$where .= "fbpublished = 0";
		if ($this->authCode && $this->consumer_secret && $this->user_secret)
			$where .= " OR ";
		if ($this->consumer_secret && $this->user_secret)
			$where .= " tweetpublished = 0";
		$where .= ") AND smdontpublish = 0 AND oxactive = 1";
		$sSelect = 'SELECT * FROM oxarticles '.$where;
		
			// Get and validate the list of selected news categories
		$selectedCategories = array();
		$selectedCategories = explode(',', $this->categoryId, TRUE);
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
				$this->soxId = $aF['OXID'];
				if ( $this->soxId != "-1" && isset( $this->soxId ) ) {
					// load object
					$oArticle->loadInLang( $this->_iEditLang, $this->soxId );
					// load object in other languages
					$oOtherLang = $oArticle->getAvailableInLangs();
					if (!isset($oOtherLang[$this->_iEditLang])) {
						$oArticle->loadInLang( key($oOtherLang), $this->soxId );
					}

					// variant handling
					if ( $oArticle->oxarticles__oxparentid->value) {
						$oParentArticle = oxNew( 'oxarticle' );
						$oParentArticle->load( $oArticle->oxarticles__oxparentid->value);
						$this->_aViewData["parentarticle"] = $oParentArticle;
						$this->_aViewData["oxparentid"]    = $oArticle->oxarticles__oxparentid->value;
					}
					if ($oArticle->oxarticles__fbpublished->value == '0' && $this->authCode != "")
						$this->post2facebook($oArticle);
					
					if ($oArticle->oxarticles__tweetpublished->value == '0' && $this->consumer_secret != "" && $this->user_secret != "")
						$this->tweet($oArticle);
				}
				$rs->moveNext();
			}
		}
		return $this->_sThisTemplate;
	}
	
	protected function post2facebook($oArticle)
	{
		require($this->getConfig()->getModulesDir()."sk_socialmedia/lib/facebook/facebook.php");
		$oDB = oxDb::getDB(true);
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = FALSE;

		// Create Application instance
		$facebook = new Facebook(
			array(
			  		'appId'  => $this->AppId,
			  		'secret' => $this->AppSecret,
			  		'cookie' => TRUE,
			)
		);
		
		// Get string responsed by Facebook 
		$url = 'https://graph.facebook.com/oauth/access_token?client_id='. $this->AppId 
			 . '&redirect_uri=' . $host . '&client_secret=' . $this->AppSecret
			 . '&code=' . $this->authCode;
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$groupAccessToken = curl_exec($ch);
		curl_close($ch);
		
		$groupList = array();
		$groupList = explode(',', $this->groupId);
		$pageList = explode(',', $this->pageId);

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
		$artLink = $oArticle->getBaseStdLink( $this->_iEditLang, true);

		// Create attachment to post to FB by Graph API
		$attachment = array(
				'access_token' => $groupAccessToken,
				'link' => $artLink,
				'name' => $oArticle->oxarticles__oxtitle->value,
		);
		
		// If the description length is set , it will show the description text in post
		if(intval($this->descLength) > 0) {
			$desc = strip_tags($oArticle->oxarticles__oxshortdesc->value ? $oArticle->oxarticles__oxshortdesc->value : $oArticle->oxarticles__oxlongdesc->value);
			$desc = preg_replace('/\s+/', ' ', $desc);
			$desc = substr($desc, 0, intval($this->descLength));  // abcd
			$attachment['description'] = $desc;
		}
		
		$artImagePath = 'out/pictures/generated/product/thumb/';
		// Get the image path
		if($oArticle->oxarticles__oxthumb->value) {
			// Only one Picture in News, this will be taken
			$imagePath = $this->webUrl . $artImagePath . '/' . $oArticle->oxarticles__oxthumb->value;
			$attachment['picture'] = $oArticle->getThumbnailUrl();
		}

		// Post feed to all group
		if(count($groupList) > 0 && $groupList != "") {
			foreach ($groupList as $singleGroupID) {
				if ($singleGroupID != ""){
					$facebook->api('/' . $singleGroupID . '/feed/', 'post', $attachment);
					$this->writetolog('Article ' . $oArticle->oxarticles__oxtitle->value . ' was published to facebook.');
				}
			}
		}

		// Post feed to all page
		if(count($pageAccessTokenArray) > 0) {
			foreach($pageAccessTokenArray as $pageId => $pageAccessToken) {
				$attachment['access_token'] = $pageAccessToken;
				$facebook->api('/' . $pageId . '/feed/', 'post', $attachment);
				$this->writetolog('Article ' . $oArticle->oxarticles__oxtitle->value . ' was published to facebook.');
			}
		}
		$sql = 'UPDATE oxarticles SET fbpublished = 1 WHERE oxid LIKE "' . $this->soxId .'"';
		$oDB->execute($sql);
	}
	
	protected function tweet($oArticle){
		$artLink = $oArticle->getBaseStdLink( $this->_iEditLang, true);
		$singleUrl = $this->createShortUrl($artLink);
		$myConfig  = $this->getConfig();
		$urlLen = strlen($artLink);
		$msg = htmlspecialchars_decode(strip_tags($oArticle->oxarticles__oxtitle->value),ENT_QUOTES);
		$msg = str_replace(array('<','>','&'), array(' ',' ',' and '), $msg);
		$msg = $this->isUTF8($msg) ? $msg : utf8_encode($msg);
		$msg = (strlen($msg)+$urlLen > 117) ? substr($msg, 0, 117-$urlLen).'...': $msg;
		if ($this->useKeywordsAsHashTags) {
			$keywords = $oArticle->oxarticles__oxsearchkeys->value;
			if ($keywords) {
				$keywords = $this->isUTF8($keywords) ? $keywords : utf8_encode($keywords);
				$keywords = str_replace(' ', ',', $keywords);
				$keywords = str_replace('.', '', $keywords);
				$keywords = explode(',', $keywords);
				foreach ($keywords as $keyword) {
					if (strlen($msg)+strlen(' #'.$keyword)+$urlLen > 137) {
						break;
					}
					else {
						$msg.=' #'.$keyword;
					}
				}
			}
		}
		$msg = $msg." ".$singleUrl;
		$this->twit($msg,$oArticle);
	}
	
	protected function twit($twitter_data,$oArticle) {
		$oDB = oxDb::getDB(true);
		$httpcode = $this->post_tweet($twitter_data);
		if ($httpcode != 200) {
			$this->writetolog("Errorcode: ".$httpcode." Something went wrong, and the tweet ".$oArticle->oxarticles__oxtitle->value." wasn't posted correctly.");
		}
		else {
			$sql = 'UPDATE oxarticles SET tweetpublished = 1 WHERE oxid LIKE "' . $this->soxId .'"';
			$oDB->execute($sql);
		}
	}
	
	protected function post_tweet($tweet_text) {
		$myConfig  = $this->getConfig();
		// Use Matt Harris' OAuth library to make the connection
		// This lives at: https://github.com/themattharris/tmhOAuth
		require_once($this->getConfig()->getModulesDir()."sk_socialmedia/lib/twitter/tmhOAuth.php");
		  
		// Set the authorization values
		// In keeping with the OAuth tradition of maximum confusion, 
		// the names of some of these values are different from the Twitter Dev interface
		// user_token is called Access Token on the Dev site
		// user_secret is called Access Token Secret on the Dev site
		// The values here have asterisks to hide the true contents 
		// You need to use the actual values from Twitter
		$connection = new tmhOAuth(array(
		'consumer_key' => $this->consumer_key,
		'consumer_secret' => $this->consumer_secret,
		'user_token' => $this->user_token,
		'user_secret' => $this->user_secret,
		)); 

		// Make the API call
		$connection->request('POST', $connection->url('1/statuses/update'), array(
			'status' => $tweet_text
		));
		return $connection->response['code'];
	}
	
	public function writetolog($messages){
		$myConfig  = $this->getConfig();
		$logFilePath = $myConfig->getModulesDir()."sk_socialmedia/log.txt";
		$logFile = fopen($logFilePath, 'a');
		$time = date('d-M-Y H:i:s');
		fprintf($logFile, "%s %s \n", $time, $messages);
		fclose($logFile);
	}
	
	function createShortUrl($longURL)
	{
		$url = 'http://tinyurl.com/api-create.php?url='.$longURL;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_HEADER,false);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$shortURL = @curl_exec($ch);
		curl_close($ch);
		$shortURL = preg_replace('%^((http://)(www\.))%', '$3', $shortURL);
		return $shortURL;
	}
	
	function isUTF8($str) {
		return preg_match('/\A(?:([\09\0A\0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*)\Z/x', $str);
	}
}
$cronjob = new sk_socialmedia_cron();
$cronjob->render();
?>