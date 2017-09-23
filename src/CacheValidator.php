<?php
require_once("Cacheable.php");

/**
 * Performs validation of requested resource based on requested headers. 
 */
class CacheValidator {
	private $request;
	
	public function __construct(CacheRequest $request) {
		$this->request = $request;
	}
	
	/**
	 * Validates resource 
	 * 
	 * @param CachedResource $resource
	 * @return integer HTTP status code OR MAYBE RETURN STATUS CODE IMMEDIATELY?
	 */
	public function validate(Cacheable $resource) {
		// TODO: implement me (this is the most complicated)
	}
}