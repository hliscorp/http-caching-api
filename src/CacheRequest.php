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
	
	/**
	 * Triggers private setters to populate information about caching request 
	 */
	public function __construct() {
		foreach($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
				switch($name) {
					case "if-match":
						$this->setMatchingEtag($value);
						break;
					case "if-none-match":
						$this->setNotMatchingEtag($value);
						break;
					case "if-modified-since":
						$this->setModifiedSince($value);
						break;
					case "if-unmodified-since":
						$this->setNotModifiedSince($value);
						break;
					case "cache-control":
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
		$this->matching_etag = $etag;
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
		$this->not_matching_etag = $etag;
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
		$time = strtotime($date);
		if($time) {
			$this->modified_since = $time;
		}		
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
	 * @param unknown $date
	 */
	private function setNotModifiedSince($date) {
		$time = strtotime($date);
		if($time) {
			$this->not_modified_since= $time;
		}		
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
		//I believe max-age=0 simply tells caches (and user agents) the response is stale from the get-go and so they SHOULD revalidate the response
		$age = (integer) $value;
		$this->max_age = $age<0?0:$age;
	}
	
	/**
	 * Gets value of max-age cache-control directive.
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
	private function setMaxStaleAge($age) {
		$age = (integer) $value;
		$this->max_stale= $age<0?0:$age;
	}
	
	/**
	 * Gets max number of seconds until an entry becomes stale. If 0 
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
		$age = (integer) $value;
		$this->min_fresh= $age<0?0:$age;
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
}