<?php
/**
 * Exception thrown when request contains invalid header values
 */
class CacheRequestException extends Exception {
	private $headerName;
	private $headerValue;
	
	/**
	 * Sets name of offending header.
	 * 
	 * @param string $headerName
	 */
	public function setHeaderName($headerName){
		$this->headerName = $headerName;
	}
	
	/**
	 * Gets name of offending header.
	 * 
	 * @return string
	 */
	public function getHeaderName() {
		return $this->headerName;
	}
	
	/**
	 * Sets offending header value.
	 *
	 * @param string $headerValue
	 */
	public function setHeaderValue($headerValue){
		$this->headerValue = $headerValue;
	}
	
	/**
	 * Gets offending header value.
	 *
	 * @return string
	 */
	public function getHeaderValue() {
		return $this->headerValue;
	}
}