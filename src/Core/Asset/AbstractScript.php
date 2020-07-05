<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\Core\Router\RouteBuilderInterface;
use Windwalker\DI\RawWrapper;
use Windwalker\Utilities\Assert\TypeAssert;
use function Windwalker\raw;
use Windwalker\Utilities\Arr;

/**
 * The ScriptManager class.
 *
 * @see    ScriptManager
 *
 * @since  3.0
 */
abstract class AbstractScript
{
    /**
     * Property asset.
     *
     * @var  callable|ScriptManager
     */
    public static $instance;

    /**
     * Property packageClass.
     *
     * @var  string
     */
    protected static $packageClass;

    /**
     * inited
     *
     * @param   string $name
     * @param   mixed  ...$data
     *
     * @return bool
     */
    protected static function inited($name, ...$data)
    {
        return static::getInstance()->inited($name, ...$data);
    }

    /**
     * available
     *
     * @param mixed ...$data
     *
     * @return  bool
     *
     * @since  3.5.19
     */
    protected static function available(...$data): bool
    {
        $debug = debug_backtrace();
        $stack = $debug[1];

        $method = $stack['class'] . '::' . $stack['function'];

        return static::inited($method, ...$data);
    }

    /**
     * getInitedId
     *
     * @param   mixed ...$data
     *
     * @return  string
     */
    protected static function getInitedId(...$data)
    {
        return static::getInstance()->getInitedId(...$data);
    }

    /**
     * getAsset
     *
     * @return  AssetManager
     */
    protected static function getAsset()
    {
        return static::getInstance()->getAsset();
    }

    /**
     * packageName
     *
     * @param null $class
     *
     * @return  string|\Windwalker\Core\Package\AbstractPackage
     */
    protected static function packageName($class = null)
    {
        $class = $class ?: static::$packageClass;

        return PackageHelper::getAlias($class);
    }

    /**
     * addStyle
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function addCSS($url, $options = [], $attribs = [])
    {
        return static::getAsset()->addCSS($url, $options, $attribs);
    }

    /**
     * addScript
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function addJS($url, $options = [], $attribs = [])
    {
        return static::getAsset()->addJS($url, $options, $attribs);
    }

    /**
     * import
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function import($url, array $options = [], array $attribs = [])
    {
        return static::getAsset()->import($url, $options, $attribs);
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  AssetManager
     */
    protected static function internalCSS($content)
    {
        return static::getAsset()->internalCSS($content);
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  AssetManager
     */
    protected static function internalJS($content)
    {
        return static::getAsset()->internalJS($content);
    }

    /**
     * Check asset uri exists in system and return actual path.
     *
     * @param string $uri    The file uri to check.
     * @param bool   $strict Check .min file or un-min file exists again if input file not exists.
     *
     * @return  bool|string
     *
     * @since  3.3
     */
    protected static function exists($uri, $strict = false)
    {
        return static::getAsset()->exists($uri, $strict);
    }

    /**
     * getJSObject
     *
     * @param mixed ...$data The data to merge.
     * @param bool  $quote   Quote object key or not, default is false.
     *
     * @return  string
     */
    public static function getJSObject(...$data)
    {
        $quote = array_pop($data);

        if (!is_bool($quote)) {
            $data[] = $quote;
            $quote  = false;
        }

        if (count($data) > 1) {
            $result = [];

            foreach ($data as $array) {
                $result = static::mergeOptions($result, $array);
            }
        } else {
            $result = $data[0];
        }

        return static::getAsset()->getJSObject($result, $quote);
    }

    /**
     * wrapFunction
     *
     * @param string $body
     * @param string $interface
     *
     * @return  string
     *
     * @since  3.4
     */
    protected static function wrapFunction($body, $interface = '')
    {
        return raw(
            <<<JS
function ($interface) {
    $body
}
JS
        );
    }

    /**
     * raw
     *
     * @param mixed $value
     *
     * @return  RawWrapper
     *
     * @since  3.5.5
     */
    protected static function raw($value): RawWrapper
    {
        return raw($value);
    }

    /**
     * mergeOptions
     *
     * @param array $options1
     * @param array $options2
     * @param bool  $recursive
     *
     * @return  array
     */
    public static function mergeOptions($options1, $options2, $recursive = true)
    {
        if (!$recursive) {
            return array_merge($options1, $options2);
        }

        return Arr::mergeRecursive($options1, $options2);
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param   string $method The method name.
     * @param   array  $args   The arguments of method call.
     *
     * @return  mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getInstance();

        return $instance->$method(...$args);
    }

    /**
     * getInstance
     *
     * @return  ScriptManager
     */
    protected static function getInstance()
    {
        if (is_callable(static::$instance)) {
            $callable = static::$instance;

            static::$instance = $callable();
        }

        if (!static::$instance instanceof ScriptManager) {
            throw new \LogicException('Instance of ScriptManager should be ' . ScriptManager::class);
        }

        return static::$instance;
    }

    /**
     * getRouter
     *
     * @param string|AbstractPackage|null $package
     *
     * @return  RouteBuilderInterface
     *
     * @since  3.5.8
     */
    protected static function getRouter($package = null): RouteBuilderInterface
    {
        $package = PackageHelper::getPackage($package);

        if ($package) {
            return $package->router;
        }

        return CoreRouter::getInstance();
    }
}
