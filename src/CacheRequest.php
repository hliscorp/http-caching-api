<?php
/**
 * Encapsulates request caching headers logic. Feeds private setters based on headers received from client offers and offers 
 * public getters that correspond to each header received
 */
class CacheRequest {
	private $matching_etag;
	private $not_matching_etag;
	private $modified_since;
	private $not_modified_since;
	private $no_cache = false;
	private $no_store = false;
	private $no_transform = false;
	private $cache_only = false;
	private $max_age;
	private $max_stale;
	private $min_fresh;	
	
	private $validatable = false;
	
	/**
	 * Triggers private setters to populate information about caching request 
	 */
	public function __construct() {
		foreach($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$name = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))));
				switch($name) {
					case "if-match":
						$this->validatable = true;
						$this->setMatchingEtag($value);
						break;
					case "if-none-match":
						$this->validatable = true;
						$this->setNotMatchingEtag($value);
						break;
					case "if-modified-since":
						$this->validatable = true;
						$this->setModifiedSince($value);
						break;
					case "if-unmodified-since":
						$this->validatable = true;
						$this->setNotModifiedSince($value);
						break;
					case "cache-control":
						$this->validatable = true;
						$this->setCacheControl($value);
						break;
				}
			}
		}
	}
	
	/**
	 * Signals response that if tag matches resource server MAY perform the requested method (HTTP 200). 
	 * If it doesn't, server MUST NOT perform requested method and return with  412 (Precondition Failed) response.
	 * A request intended to update a resource (e.g., a PUT) MAY include an If-Match header field to signal that the request method MUST NOT be applied 
	 * if the entity corresponding to the If-Match value (a single entity tag) is no longer a representation of that resource.
	 * 
	 * @param string $etag A string value or "*" (which means ALL)
	 */
	private function setMatchingEtag($etag) {
		$this->matching_etag = $this->_validateEtag("If-Match", $etag);
	}
	
	/**
	 * Gets value of etag server must find a matching resource for.
	 * 
	 * @return string
	 */
	public function getMatchingEtag() {
		return $this->matching_etag;
	}
	
	
	/**
	 * Signals response that if tag matches it MAY perform the requested method (HTTP 200), otherwise it must return 304 Not Modified (for GET & HEAD requests).
	 * For not matching requests other than GET & HEAD, the server MUST respond with a status of 412 (Precondition Failed). 
	 *
	 * @param string $etag
	 */
	private function setNotMatchingEtag($etag) {
		$this->not_matching_etag = $this->_validateEtag("If-None-Match", $etag);
	}
	
	/**
	 * Gets value of etag server must find no matching resource for.
	 * 
	 * @return string
	 */
	public function getNotMatchingEtag() {
		return $this->not_matching_etag;
	}
	
	/**
	 * Signals if the requested variant has not been modified since the time specified in this field, a 304 (not modified) response will be returned without 
	 * any message-body. Otherwise server MAY perform the requested method (HTTP 200)
	 * 
	 * @param string $date
	 */
	private function setModifiedSince($date) {
		$this->modified_since = $this->_validateDate("If-Modified-Since", $date);
	}
	
	/**
	 * Gets unix last modified timestamp of resource found in client cache that server must verify it was modified since. 
	 * 
	 * @return integer
	 */
	public function getModifiedSince() {
		return $this->modified_since;
	}
	
	/**
	 * Signals if the requested resource has not been modified since the time specified in this field, the server MAY perform the requested method.
	 * Otherwise a 412 (Precondition Failed) response will be returned without any message-body. 
	 *  
	 * @param string $date
	 */
	private function setNotModifiedSince($date) {
		$this->not_modified_since= $this->_validateDate("If-Unmodified-Since", $date);
	}
	
	
	/**
	 * Gets unix last modified timestamp of resource found in client cache that server must verify it was not modified since.
	 *
	 * @return integer
	 */
	public function getNotModifiedSince() {
		return $this->not_modified_since;
	}
	
	/**
	 * Decapsulates cache-control header received in request into separate statements
	 * 
	 * @param string $value
	 */
	private function setCacheControl($value) {
		$p1 = explode(",",$value);
		foreach($p1 as $element) {
			$k = "";
			$v = "";
			$position = strpos($element, "=");
			if($position) {
				$k = trim(substr($element,0,$position));
				$v = trim(substr($element,$position+1));
			} else {
				$k = $element;
			}
			
			switch($k) {
				case "no-cache":
					$this->no_cache = true;
					break;
				case "no-store":
					$this->no_store = true;
					break;
				case "no-transform":
					$this->no_transform = true;
					break;
				case "only-if-cached":
					$this->cache_only = true;
					break;
				case "max-age":
					$this->setMaxAge($v);
					break;
				case "max-stale":
					$this->setMaxStaleAge($v);
					break;
				case "min-fresh":
					if(!empty($p2[1])) {
						$this->min_fresh = (integer) $v;
					}
					break;
				case "s-maxage":
					$this->setMaxStaleAge($v);
					break;
			}
		}
	}
	
	/**
	 * Indicates that the client is willing to accept a response whose age is no greater than the specified time in seconds.
	 * Unless max-stale directive is also included, the client is not willing to accept a stale response.
	 * Value 0 tells caches (and user agents) the response is stale
	 *
	 * @return integer
	 */
	private function setMaxAge($value) {
		$this->max_age = $this->_validateNumber("Cache-Control: max-age", $value);
	}
	
	/**
	 * Gets value of max-age cache-control directive. Value 0 tells caches (and user agents) the response is stale
	 * 
	 * @return integer|null
	 */
	public function getMaxAge() {
		return $this->max_age;
	}
	
	/**
	 * Indicates that the client is willing to accept a response that has exceeded its expiration time. If max-stale is assigned a value, 
	 * then the client is willing to accept a response that has exceeded its expiration time by no more than the specified number of seconds. 
	 * If no value is assigned to max-stale, then the client is willing to accept a stale response of any age. 
	 * 
	 * @return integer
	 */
	private function setMaxStaleAge($value) {
		$this->max_stale = $this->_validateNumber("Cache-Control: max-stale", $value);
	}
	
	/**
	 * Gets max number of seconds until an entry becomes stale. If 0, then the client is willing to accept a stale response of any age. 
	 *
	 * @return integer|null
	 */
	public function getMaxStaleAge() {
		return $this->max_stale;
	}
	
	/**
	 * Indicates that the client is willing to accept a response whose freshness lifetime is no less than its current age plus the specified time in seconds.
	 *
	 * @return integer
	 */
	private function setMinFreshAge($value) {
		$this->min_fresh= $this->_validateNumber("Cache-Control: min-fresh", $value);
	}
	
	
	/**
	 * Gets min number of seconds while an entry is fresh
	 *
	 * @return integer|null
	 */
	public function getMinFreshAge() {
		return $this->min_fresh;
	}
	
	/**
	 * Checks if cache MUST NOT use the response to satisfy a subsequent request without successful revalidation with the origin server. 
	 * 
	 * @return boolean
	 */
	public function isNoCache() {
		return $this->no_cache;
	}
	
	/**
	 * Checks if cache should not store anything about the client request or server response (to enforce privacy).
	 * 
	 * @return boolean
	 */
	public function isNoStore() {
		return $this->no_store;
	}
	
	/**
	 * PROXY: Checks if no transformations or conversions should be made to the resource
	 * 
	 * @return boolean
	 */
	public function isNoTransform() {
		return $this->no_transform;
	}
	
	/**
	 * PROXY: Checks if cache SHOULD either respond using a cached entry that is consistent with the other constraints of the request, 
	 * or respond with a 504 (Gateway Timeout) status
	 * 
	 * @return boolean
	 */
	public function isCacheOnly() {
		return $this->cache_only;
	}
	
	/**
	 * Checks if request included caching directives (and thus is subject to cache validation)
	 * 
	 * @return boolean
	 */
	public function isValidatable() {
		return $this->validatable;
	}
	
	/**
	 * Validates etag if it's strong and single.
	 * 
	 * @param string $headerName
	 * @param string $headerValue
	 * @return string|null Value of valid etag or null if etag is empty / multiple / weak.
	 */
	private function _validateEtag($headerName, $headerValue) {
		$etag = trim(str_replace('"','',$headerValue));
		$etag = str_replace(array("-gzip","-gunzip"),"",$etag); // hardcoding: remove gzip & gunzip added to each etag by apache2
		if(!$etag || stripos($etag,"w/") !== false || stripos($etag,",") !== false) {
			return null;
		}
		return $etag;
	}
	
	/**
	 * Validates http date header value
	 * 
	 * @param string $headerName
	 * @param string $headerValue
	 * @return integer|null Local UNIX time that matches requested date or null if not a valid date.
	 */
	private function _validateDate($headerName, $headerValue) {
		$time = strtotime($headerValue);
		if(!$time) {
			return null;
		}
		return $time;
	}
	
	/**
	 * Validates numeric header value
	 * 
	 * @param string $headerName
	 * @param string $headerValue
	 * @return integer|null Value of valid number or null if value is not numeric.
	 */
	private function _validateNumber($headerName, $headerValue) {
		if(!is_numeric($headerValue)) {
			return null;
		}
		$output = (integer) $headerValue;
		// overflow protection
		if($output< 0) $output= -1;
		if($output> 2147483648) $output= 2147483648;
		return $output;
	}
}
