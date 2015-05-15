<?php
/*
 * copyright (c) 2010 MDBitz - Matthew John Denton - mdbitz.com
 *
 * This file is part of AmazonProductAPI.
 *
 * AmazonProductAPI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AmazonProductAPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AmazonProductAPI. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * AmazonProduct_Locale
 *
 * This file contains the class AmazonProduct_Locale
 *
 * @author Matthew John Denton <matt@mdbitz.com>
 * @package com.mdbitz.amazon.product
 */

/**
 * AmazonProduct_Locale defines the Amazon locales
 *
 * @package com.mdbitz.amazon.product
 */
class AmazonProduct_Locale {

    /**
     * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/Locales.html
     * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html
     *
     * @var array option variables
     */
    public static $_Locales = array(
    		"BR" => "com.br",
            "CA" => "ca",
    		"CN" => "cn",
            "DE" => "de",
    		"ES" => "es",
            "FR" => "fr",
    		"IN" => "in",
    		"IT" => "it",
            "JP" => "co.jp",
            "UK" => "co.uk",
            "US" => "com"
    );

    /**
     * BR
     */
    const BR = "BR";
    /**
     * BR
     */
    const BRASIL = "BR";

    /**
     *  CA
     */
    const CA = "CA";
    /**
     *  CA
     */
    const CANADA = "CA";

    /**
     *  CN
     */
    const CN = "CN";
    /**
     *  CN
     */
    const CHINA = "CN";

    /**
     *  DE
     */
    const DE = "DE";
    /**
     *  DE
     */
    const GERMANY = "DE";

    /**
     *  ES
     */
    const ES = "ES";
    /**
     *  ES
     */
    const SPAIN = "SP";

    /**
     *  FR
     */
    const FR = "FR";
    /**
     *  FR
     */
    const FRANCE = "FR";

    /**
     *  IN
     */
    const IN = "IN";
    /**
     *  IN
     */
    const INDIA = "IN";

    /**
     *  IT
     */
    const IT = "IT";
    /**
     *  IT
     */
    const ITALY = "IT";

    /**
     *  JP
     */
    const JP = "JP";
    /**
     *  JP
     */
    const JAPAN = "JP";

    /**
     *  UK
     */
    const UK = "UK";
    /**
     *  UK
     */
    const UNITED_KINGDOMS = "UK";

    /**
     *  US
     */
    const US = "US";
    /**
     *  US
     */
    const UNITED_STATES = "US";

    /**
     * return the domain for the locale
     *
     * @return String
     */
    public static function getDomain( $locale="US" ) {
        if( array_key_exists( $locale, self::$_Locales ) ) {
            return self::$_Locales[$locale];
        } else {
            return self::$_Locales["US"];
        }
    }

    /**
     * get associative array of Supported Locales
     * @return array
     */
    public static function SupportedLocales() {
        $locales = array();
        $locales[self::BR] = "Brasil";
        $locales[self::CA] = "Canada";
        $locales[self::CN] = "China";
        $locales[self::ES] = "Spain";
        $locales[self::FR] = "France";
        $locales[self::DE] = "Germany";
        $locales[self::IN] = "India";
        $locales[self::IT] = "Italy";
        $locales[self::JP] = "Japan";
        $locales[self::UK] = "United Kingdom";
        $locales[self::US] = "United States";
        return $locales;
    }

    /**
     * is String a Valid Locale Constant
     *
     * @param obj value to test
     * @return boolean
     */
    public static function isValid($obj) {
        if( array_key_exists( $obj, self::$_Locales ) ){
            return true;
        } else {
            return false;
        }
    }
}