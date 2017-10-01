<?php
/**
 * Encapsulates response caching headers logic. Expects to be fed by setters that generally correspond to a header and to output 
 * resulting headers that MUST later be loaded when  response is rendered (it doesn't send headers by itself).
 */
class CacheResponse {
	private $proxy_age;
	private $date_expired;
	private $last_modified_date;
	private $vary;
	private $etag;
	
	private $public;
	private $private;
	private $no_cache;
	private $no_store;
	private $no_transform;
	private $must_revalidate;
	private $proxy_revalidate;
	private $max_age;
	private $proxy_max_age;
	
	/**
	 * Specifies the maximum amount of time a resource will be considered fresh compared to time of request.
	 * The max-age directive on a response implies that the response is cacheable (i.e., "public") unless some other, 
	 * more restrictive cache directive is also present. 
	 * 
	 * @param integer $seconds
	 */
	public function setMaxAge($seconds) {
		$this->max_age = $seconds;
	}
	
	/**
	 * Sets identifier for a specific version of a resource
	 * 
	 * @param string $etag
	 */
	public function setEtag($etag) {
		$this->etag = $etag;
	}
	
	/**
	 * Sets unix time by which resource becomes stale.
	 * 
	 * If there is a Cache-Control header with the "max-age" or "s-max-age" directive in the response, the Expires header is ignored.
	 * 
	 * @param integer $timestamp Dates from the past means resource is already expired
	 */
	public function setDateExpired($timestamp) {
		$this->date_expired = gmdate('D, d M Y H:i:s T', $timestamp);
	}
	
	/**
	 * Sets unix time at which resource was last modified. 
	 * Less accurate than an ETag header, it is a fallback mechanism. Conditional requests containing If-Modified-Since or If-Unmodified-Since headers make use of this field.
	 * 
	 * @param integer $timestamp
	 */
	public function setLastModified($timestamp) {
		$this->last_modified_date = gmdate('D, d M Y H:i:s T', $timestamp);
	}
	
	/**
	 * Sets header name by which caching will vary by.
	 * 
	 * @param string $header Header name (eg: Content-Type)
	 */
	public function setVaryBy($header) {
		$this->vary = $header;
	}
	
	/**
	 * Indicates that the response MAY be cached by any cache, even if it would normally be non-cacheable or cacheable only within a non- shared cache
	 */
	public function setPublic() {
		$this->public = true;
	}
	
	/**
	 * Indicates that all or part of the response message is intended for a single user and MUST NOT be cached by a shared cache.
	 */
	public function setPrivate() {
		$this->private = true;
	}
	
	/**
	 * Indicates that cache MUST NOT use the response to satisfy a subsequent request without successful revalidation with the origin server. 
	 * This allows an origin server to prevent caching even by caches that have been configured to return stale responses to client requests. 
	 */
	public function setNoCache() {
		$this->no_cache = true;
	}
	
	/**
	 * The cache should not store anything about the client request or server response (to enforce privacy).
	 */
	public function setNoStore() {
		$this->no_store = true;
	}
	
	/**
	 * The cache must verify the status of the stale resources before using it and expired ones should not be used.
	 */
	public function setMustRevalidate() {
		$this->must_revalidate = true;
	}
	
	/**
	 * PROXY: Sets time object has been in proxy cache. A cached response is "fresh" if its age does not exceed its freshness lifetime. 
	 *
	 * @param integer $seconds Usually 0, which means it was just retrieved from proxy.
	 */
	public function setProxyAge($seconds) {
		$s = (integer) $seconds;
		if($s<0) $s = 0;
		else if($s>2147483648) $s=2147483648;
		$this->proxy_age = $s;
	}
	
	/**
	 * PROXY: No transformations or conversions should be made to the resource. The Content-Encoding, Content-Range, Content-Type headers must not be modified by a proxy
	 */
	public function setNoTransform() {
		$this->no_transform = true;
	}
	
	/**
	 * PROXY: Same as must-revalidate, but it only applies to shared caches (e.g., proxies) and is ignored by a private cache.
	 */
	public function setProxyRevalidate() {
		$this->proxy_revalidate = true;
	}
	
	/**
	 * PROXY: Overrides max-age or the expires header, but it only applies to shared caches (e.g., proxies) and is ignored by a private cache.
	 *
	 * @param integer $seconds
	 */
	public function setProxyMaxAge($seconds) {
		$this->proxy_max_age = $seconds;
	}
	
	/**
	 * Gets cache-specific response headers to iterate and load when response will be rendered
	 * 
	 * @return array[string:string] List of headers by name and value
	 */
	public function getHeaders() {
		$output = array();
		if($this->proxy_age!==null) {
			$output["Age"] = $this->proxy_age;
		}
		if($this->etag) {
			$output["ETag"] = '"'.$this->etag.'"';
		}
		if($this->date_expired) {
			$output["Expires"] = $this->date_expired;
		}
		if($this->last_modified_date) {
			$output["Last-Modified"] = $this->last_modified_date;
		}
		if($this->vary) {
			$output["Vary"] = $this->vary;
		}
		
		$cache_control = array();
		if($this->public) {
			$cache_control[]="public";
		}
		if($this->private) {
			$cache_control[]="private";
		}
		if($this->no_cache) {
			$cache_control[]="no-cache";
		}
		if($this->no_store) {
			$cache_control[]="no-store";
		}
		if($this->no_transform) {
			$cache_control[]="no-transform";
		}
		if($this->must_revalidate) {
			$cache_control[]="must-revalidate";
		}
		if($this->proxy_revalidate) {
			$cache_control[]="proxy-revalidate";
		}
		if($this->max_age) {
			$cache_control[]="max-age=".$this->max_age;
		}
		if($this->proxy_max_age) {
			$cache_control[]="s-maxage=".$this->proxy_max_age;
		}
		
		if(!empty($cache_control)) {
			$output["Cache-Control"] = implode(",",$cache_control);
		}
		return $output;
	}
}