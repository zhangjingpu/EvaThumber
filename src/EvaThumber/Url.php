<?php


namespace EvaThumber;

class Url
{
    protected $scheme;

    protected $host;

    protected $query;

    protected $urlString;

    protected $urlPath;

    protected $urlPrefix;

    protected $urlScriptName;

    protected $urlImagePath;

    protected $urlImageName;

    protected $urlRewriteEnabled;

    protected $urlRewritePath;

    protected $imagePath;

    protected $imageName;

    public function toArray()
    {
        return array(
            'urlString' => $this->urlString,
            'urlPath' => $this->getUrlPath(),
            'scheme' => $this->getScheme(),
            'host' => $this->getHost(),
            'query' => $this->getQuery(),
            'urlScriptName' => $this->getUrlScriptName(), //from $_SERVER
            'urlRewritePath' => $this->getUrlRewritePath(),
            'urlPrefix' => $this->getUrlPrefix(),
            'urlKey' => $this->getUrlKey(),
            'urlImagePath' => $this->getUrlImagePath(),
            'urlImageName' => $this->getUrlImageName(),
            'urlRewriteEnabled' => $this->getUrlRewriteEnabled(),
            'imagePath' => $this->getImagePath(),
            'imageName' => $this->getImageName(),
        );
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getUrlRewriteEnabled()
    {
        if($this->urlRewriteEnabled !== null){
            return $this->urlRewriteEnabled;
        }

        $urlPath = $this->getUrlPath();
        if(false === strpos($urlPath, '.php')){
            return $this->urlRewriteEnabled = true;
        }
        return $this->urlRewriteEnabled = false;
    }

    public function getUrlPath()
    {
        if($this->urlPath){
            return $this->urlPath;
        }

        if(!$this->urlString){
            return '';
        }

        $url = $this->urlString;
        $url = parse_url($url);
        return $this->urlPath = $url['path'];
    }

    public function getUrlPrefix()
    {
        $urlImagePath = $this->getUrlImagePath();
        $urlImagePathArray = explode('/', ltrim($urlImagePath, '/'));
        if(count($urlImagePathArray) < 2){
            return '';
        }
        return $this->urlPrefix = array_shift($urlImagePathArray);
    }

    public function getUrlKey()
    {
        $urlImagePath = $this->getUrlImagePath();
        $urlImagePathArray = explode('/', ltrim($urlImagePath, '/'));
        if(count($urlImagePathArray) < 3){
            return '';
        }
        return $this->urlKey = $urlImagePathArray[1];
    }

    public function getUrlScriptName()
    {
        if($this->urlScriptName){
            return $this->urlScriptName;
        }

        if(isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']){
            $scriptName = $_SERVER['SCRIPT_NAME'];

            //Nginx maybe set SCRIPT_NAME as pull url path
            if(($scriptNameEnd = substr($scriptName, -4)) && $scriptNameEnd === '.php'){
                return $this->urlScriptName = $scriptName;
            } else {
                $scriptNameArray = explode('/', $scriptName);
                $scriptName = array();
                foreach($scriptNameArray as $scriptNamePart){
                    $scriptName[] = $scriptNamePart;
                    if(false !== strpos($scriptNamePart, '.php')){
                        break;
                    }
                }
                return $this->urlScriptName = implode('/', $scriptName);
            }
        }

        return '';
    }

    public function getUrlImagePath()
    {
        if($this->urlImagePath){
            return $this->urlImagePath;
        }

        $urlPath = $this->getUrlPath();
        if(!$urlPath){
            return '';
        }

        $urlScriptName = $this->getUrlScriptName();


        if($urlScriptName){
            $urlRewriteEnabled = $this->getUrlRewriteEnabled();
            if($urlRewriteEnabled) {
                $rewitePath = $this->getUrlRewritePath();
                $pos = strpos($urlPath, $rewitePath);
                //replace first match only
                if ($pos === 0) {
                    return $this->urlImagePath = substr_replace($urlPath, '', $pos, strlen($rewitePath));
                }
            } else {
                $pos = strpos($urlPath, $urlScriptName);
                if ($pos === 0) {
                    return $this->urlImagePath = substr_replace($urlPath, '', $pos, strlen($urlScriptName));
                }
            }
        } else {
            return $this->urlImagePath = $urlPath;
        }
    }

    public function getUrlImageName()
    {
        if($this->urlImageName){
            return $this->urlImageName;
        }

        $urlImagePath = $this->getUrlImagePath();
        $urlImagePathArray = explode('/', $urlImagePath);
        return $this->urlImageName = array_pop($urlImagePathArray);
    }

    public function setUrlImageName($imageName)
    {
        $this->urlImageName = $imageName;
        return $this;
    }

    public function getImagePath()
    {
        $urlImagePath = $this->getUrlImagePath();
        $urlImagePathArray = explode('/', ltrim($urlImagePath, '/'));
        if(count($urlImagePathArray) < 4){
            return '';
        }

        //remove url prefix
        array_shift($urlImagePathArray);
        //remove url key
        array_shift($urlImagePathArray);
        //remove imagename
        array_pop($urlImagePathArray);
        return $this->imagePath = '/'. implode('/', $urlImagePathArray);
    
    }

    public function getImageName()
    {
        $urlImageName = $this->getUrlImageName();
        if(!$urlImageName){
            return '';
        }

        $fileNameArray = explode('.', $urlImageName);
        if(!$fileNameArray || count($fileNameArray) < 2){
            throw new Exception\InvalidArgumentException('File name not correct');
        }
        $fileExt = array_pop($fileNameArray);
        $fileNameMain = implode('.', $fileNameArray);
        $fileNameArray = explode(',', $fileNameMain);
        if(!$fileExt || !$fileNameArray || !$fileNameArray[0]){
            throw new Exception\InvalidArgumentException('File name not correct');
        }
        $fileNameMain = array_shift($fileNameArray);

        return $this->imageName = $fileNameMain . '.' . $fileExt;
    }

    public function getUrlRewritePath()
    {
        $scriptName = $this->getUrlScriptName();
        if(false === $this->getUrlRewriteEnabled()){
            return $this->urlRewritePath = $scriptName;
        }

        $rewitePathArray = explode('/', $scriptName);
        array_pop($rewitePathArray);
        return $this->urlRewritePath = implode('/', $rewitePathArray);
    }

    public function toString()
    {
        $path = $this->getUrlRewritePath();
        if($prefix = $this->getUrlPrefix()){
            $path .= "/$prefix"; 
        }

        if($urlKey = $this->getUrlKey()){
            $path .= "/$urlKey";
        }

        if($imagePath = $this->getImagePath()){
            $path .= $imagePath;
        }

        $path .= '/' . $this->getUrlImageName();

        $url = $this->getScheme() . '://' . $this->getHost() . $path;
        $url .= $this->getQuery() ? '?' . $this->getQuery() : '';
        return $url;
    }

    public function __construct($url = null)
    {
        $url = $url ? $url : $this->getCurrentUrl();
        $this->urlString = $url;
        if($url){
            $url = parse_url($url);
            $this->scheme = isset($url['scheme']) ? $url['scheme'] : null;
            $this->host = isset($url['host']) ? $url['host'] : null;
            $this->query = isset($url['query']) ? $url['query'] : null;
            $this->urlPath = isset($url['path']) ? $url['path'] : null;
        }
    }

    public function getCurrentUrl()
    {
        $pageURL = 'http';

        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"){
            $pageURL .= "s";
        }
        $pageURL .= "://";

        if ($_SERVER["SERVER_PORT"] != "80"){
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        }
        else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }
}
