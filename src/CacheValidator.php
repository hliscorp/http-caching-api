<?php
namespace Lucinda\Caching;

/**
 * Performs validation of Cacheable representation of requested resource based on headers encapsulated by Request already.
 */
class CacheValidator
{
    private $request;

    /**
     * Constructs a cache validator.
     *
     * @param CacheRequest $request
     */
    public function __construct(CacheRequest $request)
    {
     	$this->request = $request;
    }

    /**
     * Validates resource according to IETF specifications.
     *
     * @param Cacheable $cacheable Cached representation of requested resource.
     * @return integer HTTP status code
     */
    public function validate(Cacheable $cacheable): int
    {
     	if ($this->request->isNoCache() || $this->request->isNoStore()) {
            return 200;
        }

	$statusCode = $this->checkConditionals($cacheable, $_SERVER['REQUEST_METHOD']);

        if ($statusCode==304 && $this->checkCacheControl($cacheable)) {
            $statusCode = 200;
        }

	return $statusCode;
    }

    /**
     * Matches If-Match, If-None-Match, If-Modified-Since, If-Unmodified-Since request headers to Cacheable and returns resulting http status code.
     *
     * @param Cacheable $cacheable
     * @param string $requestMethod
     * @return int
     */
    private function checkConditionals(Cacheable $cacheable, string $requestMethod): int
    {
     	$etag = $cacheable->getEtag();
        $date = $cacheable->getTime();

        // apply If-Match
        $ifMatch = $this->request->getMatchingEtag();
        if ($ifMatch) {
            if (!$etag) {
                return 412;
            } elseif ($ifMatch == "*" || $etag == $ifMatch) {
                return 200;
            } else {
                return 412;
            }
        }

        // apply If-None-Match
        $ifNoneMatch = $this->request->getNotMatchingEtag();
        if ($ifNoneMatch) {
            if (!$etag || !in_array($requestMethod, ["GET","HEAD"])) {
                return 412;
            } elseif ($ifNoneMatch == "*" || $ifNoneMatch != $etag) {
                return 200;
            } else {
                return 304;
            }
        }

        // apply If-Unmodified-Since
        $ifUnmodifiedSince = $this->request->getNotModifiedSince();
        if ($ifUnmodifiedSince) {
            if (!$date || $date>$ifUnmodifiedSince) {// if modified since TIME
                return 412;
            } else { // if not modified since TIME
                return 200;
            }
        }

     	// apply If-Modified-Since
        $ifModifiedSince = $this->request->getModifiedSince();
        if ($ifModifiedSince && in_array($requestMethod, ["GET","HEAD"])) {
            if (!$date) {
                return 412;
            } elseif ($date>$ifModifiedSince) { // if modified after TIME
                return 200;
            } elseif ($date==$ifModifiedSince) { // if modified at TIME
                return 304;
            } else { // if modified before TIME
                // error situation (header date should NEVER be newer than source date) to answer with 200 OK, in order to force cache refresh
                return 412;
            }
        }

	return 200;
    }

    /**
     * Matches Cache-Control request header to Cacheable to see if 304 HTTP status response should actually be HTTP status 200
     *
     * @param Cacheable $cacheable
     * @return bool
     */
    private function checkCacheControl(Cacheable $cacheable): bool
    {
        $date = $cacheable->getTime();
        if (!$date) {
            // if resource has no time representation, ignore: max-age, max-stale, min-fresh
            return false;
        }

        $age = time() - $date;

        $maxAge = $this->request->getMaxAge();
        if ($maxAge!==null && ($maxAge == -1 || $age > $maxAge)) {
            return true;
     	}

        $freshness = ($maxAge?$maxAge:0);
        $staleness = ($age <= $freshness?0:$age - $freshness);

        $maxStaleAge = $this->request->getMaxStaleAge();
        if ($maxStaleAge!==null && ($maxStaleAge == -1 || $maxStaleAge > $staleness || $freshness > $maxStaleAge)) {
            return true;
        }

        $minFreshAge = $this->request->getMinFreshAge();
        if ($minFreshAge!==null && ($minFreshAge == -1 || ($freshness - $age) < $minFreshAge)) {
            return true;
        }

	return false;
    }
}

