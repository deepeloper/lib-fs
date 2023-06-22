<?php

/**
 * Logger class unit tests.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

namespace deepeloper\Lib\FileSystem;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Logger class unit tests.
 */
class LoggerTest extends TestCase
{
    /**
     * Log directory access rights
     *
     * @vat int
     */
    const LOG_DIRECTORY_RIGHTS = 0777;

    /**
     * Tests exception with empty path.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Logger::__construct()
     * @cover deepeloper\Lib\FileSystem\Logger::log()
     *
     * @return void
     */
    public function testLoggingWithEmptyPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing path");

        $logger = new Logger();
        $logger->log("");
    }

    /**
     * Tests exception with invalid path.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Logger::__construct()
     * @cover deepeloper\Lib\FileSystem\Logger::log()
     *
     * @return void
     */
    public function testLoggingWithInvalidPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid directory \"/path/to/unknown\"");

        $logger = new Logger();
        $logger->log("", "/path/to/unknown");
    }

    /**
     * Tests logging without/with rotation.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Logger::__construct()
     * @cover deepeloper\Lib\FileSystem\Logger::log()
     * @cover deepeloper\Lib\FileSystem\Logger::setDefaults()
     *
     * @return void
     */
    public function testLogging()
    {
        $dir = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "deepeloper", "tests", "lib-fs", uniqid()]
        );
        $path = implode(DIRECTORY_SEPARATOR, [$dir, "test.log"]);
        $message = sprintf("Test message%s", PHP_EOL);
        $len = strlen($message);
        $logger = new Logger();

        // No rotation {

        $this->recreateLogDirectory($dir);
        $options = ['maxSize' => $len];

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));

        $logger->log($message, $path, $options);
        $this->assertEquals(
            $message . $message,
            file_get_contents($path)
        );

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));
        $this->assertEquals(
            ["test.log"],
            $this->getDirectoryAsArray($dir)
        );

        // }
        // With rotation {

        $this->recreateLogDirectory($dir);
        $logger->setDefaults([
            'path'     => $path,
            'maxSize'  => $len,
            'rotation' => 2,
        ]);

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));

        $logger->log($message, $path, $options);
        $this->assertEquals(
            $message . $message,
            file_get_contents($path)
        );

        $logger->log($message, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1"],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message, file_get_contents($path));
        $this->assertEquals(
            $message . $message,
            file_get_contents(sprintf("%s.1", $path))
        );

        $message1 = sprintf("TEST MESSAGE%s", PHP_EOL);
        $logger->log($message1, $path, $options);
        $logger->log($message1, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1", "test.log.2"],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message1, file_get_contents($path));
        $this->assertEquals(
            $message . $message1,
            file_get_contents(sprintf("%s.1", $path))
        );
        $this->assertEquals(
            $message . $message,
            file_get_contents(sprintf("%s.2", $path))
        );
        $logger->log($message1, $path, $options);
        $options += ['rights' => LoggerTest::LOG_DIRECTORY_RIGHTS];
        $logger->log($message1, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1", "test.log.2"],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message1, file_get_contents($path));
        $this->assertEquals(
            $message1 . $message1,
            file_get_contents(sprintf("%s.1", $path))
        );
        $this->assertEquals(
            $message . $message1,
            file_get_contents(sprintf("%s.2", $path))
        );

        // }

        Tools::removeDir($dir);
    }

    /**
     * Recreates log directory.
     *
     * @param string $path
     *
     * @return void
     *
     * @see self::testLogging()
     *
     * @internal
     */
    protected function recreateLogDirectory($path)
    {
        if (file_exists($path)) {
            Tools::removeDir($path);
        }
        $umask = umask(0);
        mkdir($path, LoggerTest::LOG_DIRECTORY_RIGHTS, true);
        umask($umask);
    }

    /**
     * Returns directory files as array (not recursively).
     *
     * @param string $path
     *
     * @return array
     *
     * @see self::testLogging()
     *
     * @internal
     */
    protected function getDirectoryAsArray($path)
    {
        $files = [];
        $dir = iterator_to_array(new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS
        ));
        /**
         * @var SplFileInfo $fileInfo
         */
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = $fileInfo->getFilename();
            }
        }
        sort($files);

        return $files;
    }
}
