<?php
namespace Config;



class CacheSystem  {
    private $client;

    public function __construct($driver = 'phpfastcache') {
        if ($driver === 'redis') {
            // Connect to Redis
            $this->client =  new \Predis\Client();
        } elseif ($driver === 'phpfastcache') {
            // Connect to PHPFastCache
            $folderPath = dirname(__DIR__);
            $filename =  $folderPath . "\logs\cache_call";
            \Phpfastcache\CacheManager::setDefaultConfig(new \Phpfastcache\Config\ConfigurationOption([
                'path' => $filename, // or a directory of your choice
              
            ]));
            $this->client = \Phpfastcache\CacheManager::getInstance("files");

        } else {
            throw new \Exception("Unsupported caching mechanism.");
        }
    }

    public function getCache($key) {
        if ($this->client instanceof \Predis\Client) {
            // Redis
            return $this->client->get($key);
        } elseif ($this->client instanceof \Phpfastcache\Drivers\Files\Driver) {
            // PHPFastCache
            $cachedItem = $this->client->getItem($key);
            return is_null($cachedItem->get())? null:$cachedItem->get();
        }else {
            throw new \Exception("Unable to get Cache.");
        }
    }

    public function setCache($key, $ttl, $value) {
        if ($this->client instanceof  \Predis\Client) {
            // Redis
            $this->client->setex($key, $ttl, $value);
        } elseif ($this->client instanceof \Phpfastcache\Drivers\Files\Driver) {
            // PHPFastCache
            $cachedItem = $this->client->getItem($key);
            $cachedItem->set($value)->expiresAfter($ttl); // Cache for 1 hour
            $this->client->save($cachedItem);
        }else {
            throw new \Exception("Unable to set Cache.");
        }
    }

    public function deleteCache($key) {
        if ($this->client instanceof \Predis\Client) {
            // Redis
            $this->client->del([$key]);
        } elseif ($this->client instanceof \Phpfastcache\Drivers\Files\Driver) {
            // PHPFastCache
            $this->client->deleteItem($key);
        }else {
            throw new \Exception("Unable to delete Cache.");
        }
    }
}
