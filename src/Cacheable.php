<?php
/**
 * Implements blueprints of a cacheable resource
 */
interface Cacheable {
	/**
	 * Gets etag that matches resource.
	 * 
	 * @return string
	 */
	function getEtag();
	
	/**
	 * Gets last modified time that applies to resource.
	 * 
	 * @return integer
	 */
	function getTime();
}