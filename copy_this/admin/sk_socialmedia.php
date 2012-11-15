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
 *
 * @copyright Tr0nYx 2012, knornschild@sitzdesign.de
 */
class sk_socialmedia extends oxAdminDetails {

	protected $_sThisTemplate = "sk_socialmedia.tpl";

	public function render(){
		parent::render();
		$this->_aViewData['edit'] = $oArticle = oxNew( 'oxarticle' );
		$soxId = $this->getEditObjectId();
		// all categories
        if ( $soxId != "-1" && isset( $soxId ) ) {
            // load object
            $oArticle->loadInLang( $this->_iEditLang, $soxId );


            // load object in other languages
            $oOtherLang = $oArticle->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oArticle->loadInLang( key($oOtherLang), $soxId );
            }
        }
		return $this->_sThisTemplate;
	}
	
	/**
     * Saves modified extended article parameters.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = oxConfig::getParameter( "editval");
        // checkbox handling
        if ( !isset( $aParams['oxarticles__fbpublished'])) {
            $aParams['oxarticles__fbpublished'] = 0;
        }
        if ( !isset( $aParams['oxarticles__smdontpublish'])) {
            $aParams['oxarticles__smdontpublish'] = 1;
        }

        $oArticle = oxNew( "oxarticle" );
        $oArticle->loadInLang( $this->_iEditLang, $soxId);

        $oArticle->setLanguage(0);
        $oArticle->assign( $aParams);
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->save();

    }
}
?>