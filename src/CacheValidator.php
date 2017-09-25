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
	 * Validates resource: passes through or exits with HTTP error codes.
	 * 
	 * @param CachedResource $resource
	 * @return integer HTTP status code OR MAYBE RETURN STATUS CODE IMMEDIATELY?
	 */
	public function validate(Cacheable $cacheable) {
		if($this->request->isNoStore()) {
			return 200; // returning from cache is explicitly forbidden  
		}
// 		if($this->request->isNoCache()) {
// 			// if element
// 		}
		$status = 200;
		$age = time() - $cacheable->getTime();
		if($this->request->getMaxAge()!==null) {
			if($this->request->getMaxAge()==0) {
				return 200;
			}
			if($this->request->getMaxAge() < $age) {
				if($this->request->getMaxStaleAge()!==null) {
					if($this->request->getMaxStaleAge() && ($this->request->getMaxAge()+$this->request->getMaxStaleAge()) < $age) {
						return 110;	// warning
					}
				} else {
					return 110; // warning
				}
			}
		}
		if($this->request->getMaxStaleAge()!==null) {
			
		}
	}
}