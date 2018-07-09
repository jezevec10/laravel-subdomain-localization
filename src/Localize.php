<?php namespace LaurentEsc\Localization;

use Illuminate\Contracts\Encryption\DecryptException;

class Localize
{

    /**
     * If the detected locale is different from the url locale, we should redirect
     *
     * @return bool
     */
    public function shouldRedirect()
    {
        $request = app()['request'];
        if(!$request->isMethod('get') || $request->ajax()){
          return false;
        }
        
        $loc=$this->getCurrentLocale();
        return ($loc!='en' && $loc != $this->getUrlLocale());
    }

    /**
     * Detect the current locale:
     *
     * - parsing the requested URL
     * - checking cookies
     * - using browser parameters
     * - using application settings
     */
    public function detectLocale()
    {

        // Get the current locale from the URL
        $locale = $this->getUrlLocale();

        if(!$this->isLocaleAvailable($locale) && app()['request']->has("langSwitch")){
            $locale = app()['request']->get("langSwitch");
        }
        
        // Get the current locale from the cookies
        if (!$this->isLocaleAvailable($locale) && $this->isCookieLocalizationEnabled()) {
            $locale = $this->getCookieLocale();
        }

        // Get the current locale from the browser
        if (!$this->isLocaleAvailable($locale) && $this->isBrowserLocalizationEnabled()) {
            $locale = $this->getBrowserLocale();
        }

        // Get the current locale from the application settings
        if (!$this->isLocaleAvailable($locale)) {
            $locale = $this->getFallbackLocale();
        }

        $this->setLocale($locale);
    }

    /**
     * Get available locales from package config
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        return app()['config']->get('localization.available_locales');
    }

    /**
     * Get cookie localization status from package config
     *
     * @return array
     */
    protected function isCookieLocalizationEnabled()
    {
        return app()['config']->get('localization.cookie_localization');
    }

    /**
     * Get browser localization status from package config
     *
     * @return array
     */
    protected function isBrowserLocalizationEnabled()
    {
        return app()['config']->get('localization.browser_localization');
    }

    /**
     * Set cookie and application locale
     *
     * @param $locale
     */
    protected function setLocale($locale)
    {
        app()->setLocale($locale);

        if ($locale != $this->getCookieLocale() && $this->isCookieLocalizationEnabled()) {
            $this->setCookieLocale($locale);
        }
    }

    /**
     * Get current application locale
     *
     * @return string
     */
    protected function getCurrentLocale()
    {
        return app()->getLocale();
    }

    /**
     * Get default locale
     *
     * @return mixed
     */
    protected function getFallbackLocale()
    {
        return app()['config']->get('app.fallback_locale');
    }

    /**
     * Get browser locale
     *
     * @return mixed
     */
    protected function getBrowserLocale()
    {
        return app()['request']->getPreferredLanguage($this->getAvailableLocales());
    }

    /**
     * Get locale from the url
     *
     * @return mixed
     */
    protected function getUrlLocale()
    {
        $segments = explode('.', app()['request']->getHttpHost());

        return $segments[0];
    }

    /**
     * Set cookie locale
     *
     * @param $locale
     */
    protected function setCookieLocale($locale)
    {
        $ver=app()['request']->cookie(app()['config']->get('localization.cookie_version'));
        app()['cookie']->queue(app()['cookie']->forever(app()['config']->get('localization.cookie_name'), $ver."|".$locale));
    }

    /**
     * Get cookie locale
     *
     * @return mixed
     */
    protected function getCookieLocale()
    {
        $str=app()['request']->cookie(app()['config']->get('localization.cookie_name'));
        $ver=app()['request']->cookie(app()['config']->get('localization.cookie_version'));
        $content="";
        try {
            $content = app()['Crypt']->decrypt($str);
        } catch (DecryptException $e) {
            $content="";
        }
        $arr=explode("|",$content);
        if(count($arr)==2 && $arr[0]==$ver){
            return $arr[1];
        }else{
            return "";
        }
    }

    /**
     * Check if the given locale is accepted by the application
     *
     * @param $locale
     * @return bool
     */
    protected function isLocaleAvailable($locale)
    {
        return in_array($locale, $this->getAvailableLocales());
    }

}