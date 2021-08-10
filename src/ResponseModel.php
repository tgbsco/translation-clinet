<?php

namespace SportMob\Translation;

class ResponseModel
{
    private string $translation;
    private string $lang;
    private string $langId;

    /**
     * @return string
     */
    public function getTranslation(): string
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     * @return ResponseModel
     */
    public function setTranslation(string $translation): ResponseModel
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     * @return ResponseModel
     */
    public function setLang(string $lang): ResponseModel
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getLangId(): string
    {
        return $this->langId;
    }

    /**
     * @param string $langId
     * @return ResponseModel
     */
    public function setLangId(string $langId): ResponseModel
    {
        $this->langId = $langId;
        return $this;
    }
}