<?php

namespace Odahcam\Plates\Extension;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class Includer implements ExtensionInterface
{
    /**
     * Instance of the current template.
     * @var Template
     */
    public $template;

    /**
     * Path to asset directory.
     * @var string
     */
    public $path;

    /**
     * Enables the filename method.
     * @var boolean
     */
    public $filenameMethod;

    /**
     * Create new Asset instance.
     * @param string  $path
     * @param boolean $filenameMethod
     */
    public function __construct($path, $filenameMethod = false)
    {
        $this->path = rtrim($path, '/');
        $this->filenameMethod = $filenameMethod;
    }

    /**
     * Register extension function.
     * @param Engine $engine
     * @return null
     */
    public function register(Engine $engine)
    {
        $engine->registerFunction('assetUrl', [$this, 'cachedAssetUrl']);

        $engine->registerFunction('linkCSS', [$this, 'linkCSS']);
        $engine->registerFunction('linkJS', [$this, 'linkJS']);
        $engine->registerFunction('inlineCSS', [$this, 'inlineCSS']);
        $engine->registerFunction('inlineJS', [$this, 'inlineJS']);
    }

    /**
     * Create "cache busted" asset URL.
     * @param  string $url
     * @return string
     */
    public function cachedAssetUrl($url)
    {
        $filePath = $this->getFilePath($url);

        $lastUpdated = filemtime($filePath);
        $pathInfo = pathinfo($url);

        if ($pathInfo['dirname'] === '.') {
            $directory = '';
        } elseif ($pathInfo['dirname'] === '/') {
            $directory = '/';
        } else {
            $directory = $pathInfo['dirname'] . '/';
        }

        if ($this->filenameMethod) {
            return $directory.$pathInfo['filename'].'.'.$lastUpdated.'.'.$pathInfo['extension'];
        }

        return $directory.$pathInfo['filename'].'.'.$pathInfo['extension'].'?v='.$lastUpdated;
    }

    /**
     * @param {string} $url
     * @return string
     */
    public function linkCSS(string $url)
    {
        return '<link rel="preload" href="'.$this->cachedAssetUrl($url).'" as="style" onload="this.rel=\'stylesheet\'">';
    }

    /**
     * @param {string} $url
     * @return string
     */
    public function linkJS(string $url)
    {
        return '<script defer type="text/javascript" src="'.$this->cachedAssetUrl($url).'"></script>';
    }

    /**
     * @param {string} $url
     * @return string
     */
    public function inlineCSS(string $url)
    {
        $fileContents = $this->getFileContents($url);

        $string = <<<HTML
<style type="text/css">
    {$fileContents}
</style>
HTML;

        return $string;
    }

    /**
     * @param {string} $url
     * @return string
     */
    public function inlineJS(string $url)
    {
        $fileContents = $this->getFileContents($url);

        $string = <<<HTML
<script type="text/javascript">
    {$fileContents}
</script>
HTML;

        return $string;
    }

    /**
     * @param {string} $url
     * @return string
     */
    private function getFilePath(string $url)
    {
        $filePath = $this->path.'/'.ltrim($url, '/');

        if (!file_exists($filePath)) {
            throw new \LogicException('Unable to locate the asset "' . $url . '" in the "' . $this->path . '" directory.');
        }

        return $filePath;
    }

    /**
     * @param {string} $url
     * @return string
     */
    private function getFileContents(string $url)
    {
        $filePath = $this->getFilePath($url);
        return file_get_contents($filePath);
    }
}
