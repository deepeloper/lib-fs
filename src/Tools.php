<?php

/**
 * File system related library.
 *
 * Tools.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\Lib\FileSystem;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * File system related library.
 *
 * Tools.
 *
 * <!-- move: index.html -->
 * <ul>
 *     <li><a href="classes/deepeloper.Lib.FileSystem.Tools.html">
 * \deepeloper\Lib\FileSystem\Tools</a> implements various recursively
 * functionality for files and directories.</li>
 *     <li><a href="classes/deepeloper.Lib.FileSystem.Logger.html">
 * \deepeloper\Lib\FileSystem\Logger</a> implements logging functionality
 * supporting files rotation.</li>
 * </ul>
 * <!-- /move -->
 *
 * @static
 */
class Tools
{
    /**
     * Used to search string in files
     *
     * @var string
     *
     * @see self::search()
     * @see self::filterFileByContents()
     *
     * @internal
     */
    protected static $needle;

    /**
     * Walks directory recursively.
     *
     * ```php
     * \deepeloper\Lib\FileSystem\Tools::walkDir(
     *     "/path/to/dir",
     *     function (\SplFileInfo $file, $key, array $args)
     *     {
     *         // $args["path"] contains passed "/path/to/dir" ($path)
     *         echo sprintf(
     *             "[%s] %s%s", $file->isDir() ? "DIR " : "file",
     *             $file->getRealPath(),
     *             PHP_EOL
     *         );
     *     }
     * );
     * ```
     *
     * @param string   $path
     * @param callback $callback
     *
     * @return void
     *
     * @throws InvalidArgumentException  If passed path isn't a directory.
     */
    public static function walkDir($path, callable $callback)
    {
        $realPath = realpath($path);
        if (!is_string($realPath) || !is_dir($realPath)) {
            throw new InvalidArgumentException(sprintf(
                "Passed path \"%s\" (%s) isn't a directory",
                $path,
                var_export($realPath, true)
            ));
        }
        $dir = new RecursiveDirectoryIterator(
            $realPath,
            FilesystemIterator::SKIP_DOTS
        );
        $files = iterator_to_array(new RecursiveIteratorIterator(
            $dir,
            RecursiveIteratorIterator::CHILD_FIRST
        ));
        array_walk($files, $callback, ["path" => $path]);
    }

    /**
     * Removes directory recursively.
     *
     * Don't forget to call {@see http://php.net/manual/en/function.clearstatcache.php php://clearstatcache()}
     * after using this method if required.
     *
     * @param string $path
     *
     * @return void
     *
     * @see http://php.net/manual/en/function.clearstatcache.php  php://clearstatcache()
     */
    public static function removeDir($path)
    {
        self::walkDir(
            $path,
            function (SplFileInfo $file) {
                $path = $file->getRealPath();
                $file->isDir() ? rmdir($path) : unlink($path);
            }
        );
        rmdir($path);
    }

    /**
     * Searches for files/directories & files contents.
     *
     * <a href="http://php.net/manual/en/function.glob.php">php://glob()</a> used to search files/directories.
     *
     * ```php
     * // Search for all directories recursively:
     * $dirs = \deepeloper\Lib\FileSystem\Tools::search(
     *     "/path/to/dir",
     *     GLOB_ONLYDIR,
     *     [],
     *     ["*", ".*"]
     * );
     *
     * // Replace contents of files:
     * \deepeloper\Lib\FileSystem\Tools::search(
     *     "/path/to/dir",
     *     0,
     *     ["*", ".*"],
     *     ["*", ".*"],
     *     "needle",
     *     function ($path, array $args)
     *     {
     *         // $args["path"] contains passed "/path/to/dir" ($dir)
     *         // $args["needle"] contains passed "needle" ($needle)
     *         $contents = file_get_contents($path);
     *         $contents = preg_replace("/needle/", "replacement", $contents);
     *         file_put_contents($path, $contents);
     *     }
     * );
     * ```
     *
     * @param string   $dir        Path to top level directory
     * @param int      $flags      Flags (php://glob())
     * @param array    $patterns   File name patterns (php://glob())
     * @param array    $recursive  Subdirectory name patterns (php://glob())
     * @param string   $needle     String to search in files, if starts with "/" processes like regular expression
     * @param callback $callback   If passed empty array will be returned and callback will be called
     *                             on every found file/directory
     * @param array    $args       Callback args
     *
     * @return array
     *
     * @see http://php.net/manual/en/function.glob.php  php://glob()
     */
    public static function search(
        $dir,
        $flags = 0,
        array $patterns = [],
        array $recursive = [],
        $needle = null,
        callable $callback = null,
        array $args = []
    ) {
        $args += [
            'path' => $dir,
            'needle' => $needle
        ];
        $return = [];

        // Files {

        foreach ($patterns as $pattern) {
            // Collect files {

            $path = implode(DIRECTORY_SEPARATOR, [$dir, $pattern]);
            $result = array_filter(
                glob($path, $flags),
                function ($path) {
                    return !is_dir($path);
                }
            );

            // } Collect files
            // Filter files by content {

            if (!is_null($needle)) {
                static::$needle = $needle;
                $result = array_filter(
                    $result,
                    function ($path) {
                        $contents = file_get_contents($path);

                        return
                            "/" == substr(static::$needle, 0, 1)
                                ? (bool)preg_match(static::$needle, $contents)
                                : false !== strpos($contents, static::$needle);
                    }
                );
                static::$needle = null;
            }

            // } Filter files by content
            // Apply callback {

            if (!is_null($callback)) {
                foreach ($result as $path) {
                    call_user_func($callback, $path, $args);
                }
            }

            // } Apply callback

            $return = array_merge($return, $result);
        }

        // } Files {
        // Dirs {

        $dirs = [];
        foreach ($recursive as $dirPattern) {
            $dirs = array_merge(
                $dirs,
                glob(
                    implode(DIRECTORY_SEPARATOR, [$dir, $dirPattern]),
                    GLOB_ONLYDIR
                )
            );
        }
        $dirs = array_filter(
            $dirs,
            function ($path) {
                return !preg_match(
                    "/" . preg_quote(DIRECTORY_SEPARATOR, "/") . "\.{1,2}$/",
                    $path
                );
            }
        );
        if (is_null($needle)) {
            $return = array_merge($return, $dirs);
        }
        foreach ($dirs as $subdir) {
            $return = array_merge(
                $return,
                self::search(
                    $subdir,
                    $flags,
                    $patterns,
                    $recursive,
                    $needle,
                    $callback,
                    $args
                )
            );
        }

        // } Dirs

        return $return;
    }
}
