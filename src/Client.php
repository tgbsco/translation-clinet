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

    public function __construct(
        string $baseUrl,
        string $redisHost,
        int $redisPort = 6379,
        HubInterface $sentryHub,
        ?HandlerStack $handlerStack = null
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

            if ( isset($HttpResponse[0]) ) {
                // set response to cache
                $this->setTranslateCache($sportmobId, $lang, $HttpResponse[0]['translation']);
                $response = $HttpResponse[0]['translation'];
            }
        }
        catch (\Exception $e){
            $this->sentryHub->captureException($e);
        }

        return $response;
    }


    protected function setTranslateCache(string $id, string $lang, string $translation)
    {
        $this->cacheAdapter->set(self::translateFormatKey($id, $lang), $translation);
    }

    protected function getTranslateCache(string $id, string $lang)
    {
        return $this->cacheAdapter->get(self::translateFormatKey($id, $lang));
    }

    public static function translateFormatKey(string $id, string $lang): string
    {
        return str_replace(['{id}','{lang}'], [$id, $lang],
            'translation_service_{id}_{lang}');
    }
}