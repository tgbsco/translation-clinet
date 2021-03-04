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
     * @param string $keyword
     * @param string $lang
     * @return string
     */
    public function getByLang(string $keyword, string $lang)
    {
        $cache = $this->getTranslateCache($keyword, $lang);
        if ( $cache ) {
            return $cache;
        }

        $queryParams = [
            'keywords' => [$keyword],
            'lang' => [$lang]
        ];

        try {
            $response = $this->httpClient->get('translate', ['query' => $queryParams] );
            $response = json_decode($response->getBody(), true);

            if ( isset($response[$keyword][$lang]) ) {
                // set response to cache
                $this->setTranslateCache($response);
                $response = $response[$keyword][$lang];
            }
            else{
                $response = $keyword;
            }
        }
        catch (\Exception $e){
            // server is down, we have to fill response with current translation
            $this->sentryHub->captureException($e);
            $response = $keyword;
        }

        return $response;
    }

    /**
     * get all available translation languages of the keyword
     * @param string $keyword
     * @return array
     */
    public function getAll(string $keyword)
    {
        $cache = $this->getTranslateCache($keyword, 'all');
        if ( $cache ) {
            return unserialize($cache);
        }

        $response = [];
        try {
            $response = $this->httpClient->get('translate', ['query' => ['keyword' => [$keyword]]] );
            $response = json_decode($response->getBody(), true);

            $this->setAllTranslateCache($keyword, serialize($response));
        }
        catch (\Exception $e){
            // server is down, we have to fill response with current translation
            $this->sentryHub->captureException($e);
        }
        return $response;
    }

    public function search(string $keyword, string $lang)
    {
        $cache = $this->getSearchCache($keyword, $lang);
        if ($cache) { // the keyword cache exist
            return $cache;
        }

        $queryParams = ['keyword' => $keyword, 'lang' => $lang];
        try {
            $response = $this->httpClient->get('search', ['query' => $queryParams] );
            $response = json_decode($response->getBody(), true);

            if ( !empty($response) ) {
                // set response to cache
                $this->setSearchCache($keyword, $lang, $response);
            }
        }
        catch (\Exception $e){
            // server is down, we have to fill response with english translation
            $this->sentryHub->captureException($e);
            $response = [];
        }

        return $response;
    }

    protected function setSearchCache(string $keyword, string $lang, array $translation)
    {
        return $this->cacheAdapter->set(self::searchFormatKey($keyword, $lang), json_encode($translation));
    }

    protected function getSearchCache(string $keyword, string $lang)
    {
        $cache = $this->cacheAdapter->get(self::searchFormatKey($keyword, $lang));
        return $cache ? json_decode($cache, true) : null;
    }

    protected function setTranslateCache(array $keywordResult)
    {
        foreach ($keywordResult as $keyword => $translations) {
            foreach ($translations as $lang => $translation) {
                $this->cacheAdapter->set(self::translateFormatKey($keyword, $lang), $translation);
            }
        }
    }

    protected function setAllTranslateCache(string $keyword, string $translation)
    {
        $this->cacheAdapter->set(self::translateFormatKey($keyword, 'all'), $translation);
    }

    protected function getTranslateCache(string $keyword, string $lang)
    {
        return $this->cacheAdapter->get(self::translateFormatKey($keyword, $lang));
    }

    public static function translateFormatKey(string $keyword, string $lang): string
    {
        return sprintf('translation_service_%s_%s', str_replace(' ', '-', $keyword), $lang);
    }

    public static function searchFormatKey(string $keyword, string $lang): string
    {
        return sprintf('translation_service_search_%s_%s', md5(str_replace(' ', '-', $keyword)), $lang);
    }
}