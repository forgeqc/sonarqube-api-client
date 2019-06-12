<?php

namespace ForgeQC\SonarqubeApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Doctrine\Common\Cache\FilesystemCache;

class HttpClient {
   static protected $instance;
   protected $connection;
   protected $stack;
   protected $baseuri;

   public function __construct($baseuri, $token=null, $cache=false, $cacheTTL=6400) {
     $this->baseuri = $baseuri;
     $this->token = $token;

     // Create default HandlerStack
     $this->stack = HandlerStack::create();

     if ($cache) {
     // Enable caching
       $this->stack->push(
         new CacheMiddleware(
           new GreedyCacheStrategy(
             new DoctrineCacheStorage(
               new FilesystemCache(getcwd() . '/cache/')
             ),
             $cacheTTL, // the TTL in seconds
             new KeyValueHttpHeader(['Authorization']) // Optionnal - specify the headers that can change the cache key
           )
         ),
         'cache'
       );
     }

     // Initialize the client with the handler option
     if(isset($token)) {
       $this->connection = new Client(['handler' => $this->stack, 'base_uri' => $this->baseuri, 'auth' => [$this->token, '']]);
     }
     else {
       $this->connection = new Client(['handler' => $this->stack, 'base_uri' => $this->baseuri]);
     }
   }

   // proxy calls to non-existant methods on this class to GuzzleHttp Client nstance
   public function __call($method, $args) {
       $callable = array($this->connection, $method);
       if(is_callable($callable)) {
           return call_user_func_array($callable, $args);
       }
   }
}
?>
