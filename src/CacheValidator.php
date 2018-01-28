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
	 * Validates resource according to IETF specifications.
	 * 
	 * @param Cacheable $cacheable Cached representation of requested resource.
	 * @return integer HTTP status code
	 */
	public function validate(Cacheable $cacheable) {
		// only GET requests are cached
		if(strtoupper($_SERVER['REQUEST_METHOD'])!="GET") {
			return 200;
		}
		
		// if no-cache or no-store @ cache-control, staleness is automatically assumed
		if($this->request->isNoCache() || $this->request->isNoStore()) {
			return 200;
		}
		
		$age = time() - $cacheable->getTime();
		$freshness = ($this->request->getMaxAge() && $this->request->getMaxAge()!=-1?$this->request->getMaxAge():0);
		
		if(!$this->isFreshEnough($age, $freshness)) {
			return 200;
		}
		
		// if tag no longer matches return
		if($this->request->getMatchingEtag()) {
			if($this->request->getMatchingEtag()!="*" && $this->request->getMatchingEtag()!=$cacheable->getEtag()) {
				return 412;
			}
		}
		
		// if it has been modified since return
		if($this->request->getNotModifiedSince()) {
			if($cacheable->getTime() > $this->request->getNotModifiedSince()) {
				return 412;
			}
		}
		
		// checks conditionals
		if(!$this->allConditionalsMatch($cacheable)) {
			return 200;
		}
		
		if($this->request->getMaxAge()) {
			if($age > $this->request->getMaxAge()) {
				return 200;
			}
		}
		
		if($this->request->getMaxStaleAge()!==null) {
			if($freshness > $this->request->getMaxStaleAge()) {
				return 200;
			}
		}
		
		if($this->request->getMinFreshAge()!==null) {
			if($this->request->getMinFreshAge()==-1) {
				return 200;
			}
			if($freshness - $age < $this->request->getMinFreshAge()) {
				return 200;
			}
		}
		
		return 304;
	}
	
	/**
	 * Checks if requested resource is fresh enough
	 * 
	 * @param integer $age Staleness age of requested resource.
	 * @param integer $freshness Freshness age of requested resource.
	 * @return boolean
	 */
	private function isFreshEnough($age, $freshness) {
		if($age < $freshness) {
			return true;
		}
		if ($this->request->getMaxStaleAge() == -1) {
			return false;
		}
		$staleness = ($age <= $freshness?0:$age - $freshness);
		if($this->request->getMaxStaleAge() && $this->request->getMaxStaleAge() > $staleness) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Checks if IF-MATCH & IF-MODIFIED-SINCE conditions are both met.
	 * 
	 * @param Cacheable $cacheable Cached entry that matches requested resource.
	 * @return boolean
	 */
	private function allConditionalsMatch(Cacheable $cacheable) {
		$noneMatch = $this->request->getNotMatchingEtag();
		$modifiedSince = $this->request->getModifiedSince();
		if(!$noneMatch && !$modifiedSince) {
			return true;
		}
		$etagValidatorMatches = ($noneMatch && ($noneMatch == "*" || $noneMatch == $cacheable->getEtag()));
		$lastModifiedValidatorMatches = ($modifiedSince && ($modifiedSince > time() || $cacheable->getTime() > $modifiedSince?false:true));
		if($noneMatch && $modifiedSince && !($etagValidatorMatches && $lastModifiedValidatorMatches)) {	
			return false;
		} else if($noneMatch && !$etagValidatorMatches) {
			return false;
		} else if($modifiedSince && !$lastModifiedValidatorMatches) {
			return false;
		} else {
			return true;
		}
	}
}