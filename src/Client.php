<?php

namespace SportMob\Translation;

use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\HandlerStack;
use Sentry\State\HubInterface;
use SportMob\Translation\CacheAdapter;

class Client
{
    protected GuzzleHttp $httpClient;
    protected CacheAdapter $cacheAdapter;
    protected array $result = [];
    protected HubInterface $sentryHub;
    protected int $ttl;

    public function __construct(
        string $baseUrl,
        string $redisHost,
        int $redisPort = 6379,
        HubInterface $sentryHub,
        ?HandlerStack $handlerStack = null,
        ?int $ttl = null
    ) {
        $this->cacheAdapter = new CacheAdapter($redisHost, $redisPort);
        $this->httpClient = new GuzzleHttp([
            // Base URI is used with relative requests
            'base_uri' => $baseUrl,
            // You can set any number of default request options.
            'timeout' => 1.0,
            'handler' => $handlerStack
        ]);
        $this->sentryHub = $sentryHub;
        $this->ttl = $ttl ?? 365 * 24 * 3600;
    }

    /**
     * @param string $sportmobId
     * @param string $lang
     * @return string
     */
    public function getByEntityId(string $sportmobId, string $lang): ?string
    {
        $cache = $this->getTranslateCache($sportmobId, $lang);
        if ( $cache ) {
            return $cache;
        }

        $queryParams = [
            'id' => $sportmobId,
            'lang' => $lang
        ];

        $response = null;
        try {
            $httpRequest = $this->httpClient->get('translate', ['query' => $queryParams] );
            $HttpResponse = json_decode($httpRequest->getBody(), true);

            if ( isset($HttpResponse) ) {
                // set response to cache
                $this->setTranslateCache($sportmobId, $lang, $HttpResponse['translation']);
                $response = $HttpResponse['translation'];
            }
        }
        catch (\Exception $e){
            $this->sentryHub->captureException($e);
        }

        return $response;
    }


    protected function setTranslateCache(string $id, string $lang, string $translation)
    {
        $translation = [
            'translation' => $translation,
            'lang' => $lang
        ];
        $this->cacheAdapter->tags([$lang])->put(self::translateFormatKey($id, $lang), json_encode($translation), $this->ttl);
    }

    protected function getTranslateCache(string $id, string $lang)
    {
        return $this->cacheAdapter->getByTags( self::translateFormatKey($id, $lang), [$lang] );
    }

    public static function translateFormatKey(string $id, string $lang): string
    {
        return str_replace(['{id}','{lang}'], [$id, $lang],
            'translation_{id}_{lang}');
    }
}