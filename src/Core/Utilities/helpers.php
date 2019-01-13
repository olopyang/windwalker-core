<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace {

    use Windwalker\Core\DateTime\Chronos;
    use Windwalker\Core\Language\Translator;
    use Windwalker\Core\Utilities\Debug\Dumper;

    if (!function_exists('env')) {
        /**
         * Translate
         *
         * @param string $name
         * @param mixed  $default
         *
         * @return  string
         *
         * @since  3.3
         */
        function env(string $name, $default = null)
        {
            return $_ENV[$name] ?? $default;
        }
    }

    if (!function_exists('__')) {
        /**
         * Translate
         *
         * @param string $text
         * @param array  ...$args
         *
         * @return  string
         *
         * @since  3.3
         */
        function __($text, ...$args)
        {
            if ($args === []) {
                return Translator::translate($text);
            }

            return Translator::sprintf($text, ...$args);
        }
    }

    if (!function_exists('trans')) {
        /**
         * trans
         *
         * @param string $text
         * @param array  ...$args
         *
         * @return  string
         *
         * @since  3.3
         */
        function trans($text, ...$args)
        {
            if ($args === []) {
                return Translator::translate($text);
            }

            return Translator::sprintf($text, ...$args);
        }
    }

    if (!function_exists('__plural')) {
        /**
         * __plural
         *
         * @param string $text
         * @param int    $number
         * @param array  ...$args
         *
         * @return  string
         *
         * @since  3.3
         */
        function __plural($text, $number = 1, ...$args)
        {
            return Translator::plural($text, $number, ...$args);
        }
    }

    if (!function_exists('date_compare')) {
        /**
         * date_compare
         *
         * @param string|\DateTimeInterface $date1
         * @param string|\DateTimeInterface $date2
         * @param string                    $operator
         *
         * @return  bool|int
         *
         * @since  3.3
         * @throws Exception
         */
        function date_compare($date1, $date2, $operator = null)
        {
            return Chronos::compare($date1, $date2, $operator);
        }
    }

    if (!function_exists('html_escape')) {
        /**
         * html_escape
         *
         * @param string $string
         * @param bool   $nl2br
         * @param bool   $doubleEncode
         *
         * @return  string
         *
         * @since  3.4
         */
        function html_escape($string, $nl2br = false, $doubleEncode = true)
        {
            $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8', $doubleEncode);

            if ($nl2br) {
                $string = nl2br($string);
            }

            return $string;
        }
    }

    if (!function_exists('only_debug')) {
        /**
         * only_debug
         *
         * @param string $string
         *
         * @return  string
         *
         * @since  3.4.2
         */
        function only_debug($string)
        {
            return WINDWALKER_DEBUG ? $string : '';
        }
    }

    if (!function_exists('ds')) {
        /**
         * Dump to server.
         *
         * If server not running, will dump to HTML.
         *
         * @param mixed ...$args
         *
         * @return  void
         *
         * @since  3.4.6
         */
        function ds(...$args)
        {
            $dumper = new Dumper();

            $dumper->dump(...$args);
        }
    }
}

namespace Windwalker {

    use Windwalker\Data\Collection;
    use Windwalker\Filesystem\Filesystem;
    use Windwalker\Utilities\Iterator\ArrayObject;

    /**
     * Support node style double star finder.
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return  array
     *
     * @since  3.5
     */
    function glob(string $pattern, int $flags = 0): array
    {
        return Filesystem::glob($pattern, $flags);
    }

    /**
     * collect
     *
     * @param array  $data
     * @param int    $flags
     * @param string $iteratorClass
     *
     * @return  Collection
     *
     * @since  __DEPLOY_VERSION__
     */
    function arr(
        array $data = [],
        int $flags = ArrayObject::STD_PROP_LIST,
        string $iteratorClass = \ArrayIterator::class
    ): Collection {
        return new Collection($data, $flags, $iteratorClass);
    }
}
