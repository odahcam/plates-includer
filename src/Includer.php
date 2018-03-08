<?php

namespace Odahcam\Plates\Extension;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class Includer implements ExtensionInterface
{
    /**
     * Instance of the current template.
     *
     * @var Template
     */
    public $template;

    /**
     * Path to asset directory.
     *
     * @var string
     */
    public $path;

    /**
     * Enables the filename method.
     *
     * @var boolean
     */
    public $filenameMethod;

    /**
     * Create new Asset instance.
     * @param string  $path
     * @param boolean $filenameMethod
     */
    public function __construct(string $path, $filenameMethod = false)
    {
        $this->path = rtrim($path, '/');
        $this->filenameMethod = $filenameMethod;
    }

    /**
     * Register extension function.
     *
     * @param Engine $engine
     */
    public function register(Engine $engine): void
    {
        $engine->registerFunction('assetUrl', [$this, 'cachedAssetUrl']);

        $engine->registerFunction('linkCSS', [$this, 'linkCSS']);
        $engine->registerFunction('linkJS', [$this, 'linkJS']);
        $engine->registerFunction('inlineCSS', [$this, 'inlineCSS']);
        $engine->registerFunction('inlineJS', [$this, 'inlineJS']);
        $engine->registerFunction('preloadCSS', [$this, 'preloadCSS']);
    }

    /**
     * Create "cache busted" asset URL.
     *
     * @param string $url
     */
    public function cachedAssetUrl(string $url): string
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
            return $directory . $pathInfo['filename'] . '.' . $lastUpdated . '.' . $pathInfo['extension'];
        }

        return $directory . $pathInfo['filename'] . '.' . $pathInfo['extension'] . '?v=' . $lastUpdated;
    }

    /**
     * @param string $url
     */
    public function linkCSS(string $url, array $attrs = []): string
    {
        return '<link ' . self::arrayToAttr($attrs) . ' href="' . $this->cachedAssetUrl($url) . '">';
    }

    /**
     * @param string $url
     */
    public function preloadCSS(string $url, array $attrs = []): string
    {
        $prelaod_attrs = [
            'rel' => 'preload',
            'as' => 'style',
            'onload' => 'this.onload=null;this.rel=\'stylesheet\'',
        ];

        return $this->linkCSS($url, array_merge($preload_attrs, $attrs));
    }

    /**
     * @param string $url
     */
    public function linkJS(string $url, array $attrs = []): string
    {
        return '<script ' . self::arrayToAttr($attrs) . ' type="text/javascript" src="' . $this->cachedAssetUrl($url) . '"></script>';
    }

    /**
     * @param string $url
     */
    public function inlineCSS(string $url): string
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
     * @param string $url
     */
    public function inlineJS(string $url): string
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
     * @param string $url
     */
    private function getFilePath(string $url): string
    {
        $filePath = $this->path . '/' . ltrim($url, '/');

        if (!file_exists($filePath)) {
            throw new \LogicException('Unable to locate the asset "' . $url . '" in the "' . $this->path . '" directory.');
        }

        return $filePath;
    }

    /**
     * @param string $url
     */
    private function getFileContents(string $url): ?string
    {
        $filePath = $this->getFilePath($url);
        $file_contents = file_get_contents($filePath);

        return $file_contents ? $file_contents : null;
    }

    /**
     * @param array $attrs
     */
    private static function arrayToAttr(array $attrs): string
    {
        $attr_string = '';

        foreach ($attrs as $key => $value) {

            switch (true) {

                case $value === null:
                    $attr_string .= $key;
                    break;

                case !ctype_digit($key) && is_int($key):
                    $attr_string .= $value;
                    break;

                default:
                    $attr_string .= $key . '="' . trim(json_encode($value), '"') . '"';
                    break;

            }

            $attr_string .= ' ';
        }

        return rtrim($attr_string);
    }
}
