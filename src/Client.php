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
     * @param array $keywords
     * @param array $lang
     * @return array|null
     * @throws \Exception
     *
     */
    public function translate(array $keywords, array $langs)
    {
        $queryParams = [
            'keywords' => $keywords,
            'lang' => $langs
        ];

        if(count($langs) === 1 && $langs[0] === 'en'){
            $response = [];
            foreach ($keywords as $keyword){
                $response[$keyword] = [
                    'en' => $keyword
                ];
            }
            return $response;
        }

        $cache = $this->getTranslateCache($keywords, $langs);
        $countOfRequested = count($queryParams['keywords']);
        $countOfCacheKeywords = count($cache);

        if ($countOfRequested === $countOfCacheKeywords) { // all keywords' cache exist
            return $cache;
        } elseif ($countOfCacheKeywords > 0) { // some keywords' cache exist
            $queryParams['keywords'] = array_diff($queryParams['keywords'], array_keys($cache));
        }

        try {
            $response = $this->httpClient->get('translate', ['query' => $queryParams] );
            $response = json_decode($response->getBody(), true);
            // set response to cache
            $this->setTranslateCache($response);
            if ($countOfCacheKeywords > 0) {
                $response = array_merge($response, $cache);
            }
        }
        catch (\Exception $e){
            // server is down, we have to fill response with english translation
            $this->sentryHub->captureException($e);
            $response = [];
            foreach ($keywords as $keyword){
                foreach ($langs as $lang){
                    $response[$keyword][$lang] = $keyword;
                }
            }
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

            // set response to cache
            $this->setSearchCache($keyword, $lang, $response);
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

    protected function getTranslateCache(array $keywords, array $langs)
    {
        $result = [];
        foreach ($keywords as $keyword) {
            foreach ($langs as $lang) {
                $get = $this->cacheAdapter->get(self::translateFormatKey($keyword, $lang));
                if (!$get){
                    break; // if there is no cache for one language, we ignore the keyword
                }
                $result[$keyword][$lang] = $get;
            }
        }
        return $result;
    }

    public static function translateFormatKey(string $keyword, string $lang, string $separator = '-'): string
    {
        return str_replace(' ', '-', $keyword) . $separator . $lang;
    }

    public static function searchFormatKey(string $keyword, string $lang, string $separator = '-'): string
    {
        return md5(str_replace(' ', '-', $keyword)) . $separator . $lang;
    }
}