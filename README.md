# http-caching-api

HTTP caching for non-static resources (static ones are generally cached through client-server negociation before they reach your code) is seldom employed by PHP applications. The main reason is general ignorance of HTTP caching among developers: by caching, they automatically assume memcache or any other server-side caching solutions. The other reason is misunderstanding how HTTP caching works: it is generally assumed latter means clients will for a time period see old versions of resource until cache is renewed. This API is designed to curb that ignorance, explain HTTP caching in great detail and create a platform where client and server "talk" in language of caching.

The architecture of HTTP Caching API is very simple:

- Caching-related communication between client and server is encapsulated by CacheRequest class. This class centralizes headers received from client, which will later on fall subject of validation. If header values are malformed, corresponding headers are ignored.
- Cache validation (deciding whether or not requested resource has changed) is performed by CacheValidator class. Since API by itself cannot map a request to a cacheable resource and validation requires this, a Cacheable interface is put forward to mask mapping complexity. It up to API's user to implement this interface, which bases on the common sense idea any cacheable resource MUST HAVE an ETAG representation as well as a last modified date. Both will be matched with client's conditional headers, if any, in order to check if resoure has changed.
- If validation finds out resource hasn't changed, a 304 Not Modified status code is returned, otherwise a 200 OK status is returned. API does not send any headers, only informs API's user of validation outcome. Normally, latter must immediately stop program and send back response headers for any validation status other than 200. If resource has changed or no conditional headers were received, it means server must issue a complete response.
- Caching-related communication between server and client is encapsulated by CacheResponse class. This class centralizes headers sent by server to client following later's request.

More information here:<br/>
http://www.lucinda-framework.com/http-caching-api
