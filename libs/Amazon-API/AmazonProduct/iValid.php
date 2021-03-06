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
 * iValid
 *
 * This file contains the interface AmazonProduct_iValid
 *
 * @author Matthew John Denton <matt@mdbitz.com>
 * @package com.mdbitz.amazon.product
 */

/**
 * Defines isValid method to test if object is valid
 * Amazon Product Objects
 *
 * @package com.mdbitz.amazon.product
 */
interface AmazonProduct_iValid {

   /**
     * is object Valid
     *
     * @return boolean
     */
   public function isValid( );
    
}
?>
