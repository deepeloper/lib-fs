<?php

/**
 * Tools class unit tests.
 *
 * @author [deepeloper](https://github.com/deepeloper)
 * @license [MIT](https://opensource.org/licenses/mit-license.php)
 */

declare(strict_types=1);

namespace deepeloper\Lib\FileSystem;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

/**
 * Tools class unit tests.
 */
class ToolsTest extends TestCase
{
    /**
     * Access rights for temporary directory
     */
    public const RIGHTS = 0777;

    /**
     * Temporary directory path
     *
     * @var string
     *
     * @see self::createDirStructure()
     * @see self::cutTempPath()
     */
    protected $tempPath;

    /**
     * Directory structure
     *
     * @var array
     *
     * @see self::testRecursiveWalkDir()
     * @see self::buildDirStructure()
     */
    protected $dirStructure;

    /**
     * Search results
     *
     * @var array
     *
     * @see self::testSearchingCallback()
     * @see self::storeSearchResults()
     */
    protected $searchResults;

    /**
     * Tests exception when passed wrong path.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Tools::walkDir()
     */
    public function testInvalidDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Passed path \"./tests/invalid/path\" (false) isn't a directory");

        Tools::walkDir("./tests/invalid/path", [$this, "buildDirStructure"]);
    }

    /**
     * Tests recursive walking by directory.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Tools::walkDir()
     */
    public function testWalking(): void
    {
        $this->dirStructure = [];
        $this->createDirStructure();
        Tools::walkDir($this->tempPath, [$this, "buildDirStructure"]);
        sort($this->dirStructure);
        $this->assertEquals(
            [
                "[D] dir1",
                "[D] dir1/dir11",
                "[D] dir1/dir11/dir111",
                "[D] dir2",
                "[D] dir2/dir22",
                "[D] dir3",
                "[F] dir1/dir11/dir111/deepFile",
                "[F] file",
            ],
            $this->dirStructure
        );
    }

    /**
     * Tests recursive removing of directory.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Tools::removeDir()
     */
    public function testRemoving(): void
    {
        $this->createDirStructure();
        Tools::removeDir($this->tempPath);
        $this->assertFalse(is_dir($this->tempPath));
    }

    /**
     * Tests recursive searching.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Tools::search()
     */
    public function testSearching(): void
    {
        $this->createDirStructure();

        $this->assertEquals(
            [],
            Tools::search("")
        );

        $expected = ["file"];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        array_pop($expected);
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, GLOB_ONLYDIR, ["*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            "dir1",
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11"]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111"]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile"]),
            "dir2",
            implode(DIRECTORY_SEPARATOR, ["dir2", "dir22"]),
            "dir3",
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["*"], ["*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["*", ".*"], ["*", ".*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            "dir1",
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11"]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111"]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile"]),
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["d*"], ["dir1*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile"]),
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search(
                $this->tempPath,
                0,
                ["*"],
                ["*"],
                "/cont/i"
            )
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search(
                $this->tempPath,
                0,
                ["*"],
                ["*"],
                "CONT"
            )
        );
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests recursive searching using callback.
     *
     * @return void
     *
     * @cover deepeloper\Lib\FileSystem\Tools::search()
     */
    public function testSearchingCallback(): void
    {
        $this->createDirStructure();
        $this->searchResults = [];
        Tools::search(
            $this->tempPath,
            0,
            ["*"],
            ["*"],
            "CONT",
            [$this, "storeSearchResults"]
        );
        Tools::search(
            $this->tempPath,
            0,
            ["*"],
            ["*"],
            "/cont/i",
            [$this, "storeSearchResults"]
        );
        Tools::search(
            $this->tempPath,
            0,
            ["*"],
            ["*"],
            "notFound",
            [$this, "storeSearchResults"]
        );
        $expected = [
            [
                "path"   => "file",
                "needle" => "CONT",
            ],
            [
                "path"   => "file",
                "needle" => "/cont/i",
            ],
            [
                "path"   => implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile"]),
                "needle" => "/cont/i",
            ],
        ];
        $this->assertEquals($expected, $this->searchResults);
    }

    /**
     * Callback using for building directory structure.
     *
     * @param SplFileInfo $file
     *
     * @return void
     *
     * @see self::testRemove()
     */
    public function buildDirStructure(SplFileInfo $file): void
    {
        $this->dirStructure[] = str_replace(DIRECTORY_SEPARATOR, "/", sprintf(
            "[%s] %s",
            $file->isDir() ? "D" : "F",
            substr($file->getRealPath(), strlen($this->tempPath) + 1)
        ));
    }

    /**
     * Callback using to store search results.

     * @param string $path
     * @param array  $args
     *
     * @return void
     *
     * @see self::testSearchingCallback()
     */
    public function storeSearchResults(string $path, array $args): void
    {
        $this->searchResults[] = [
            "path"   => $this->cutTempPath($path),
            "needle" => $args["needle"],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (!is_null($this->tempPath) && is_dir($this->tempPath)) {
            Tools::removeDir($this->tempPath);
        }

        parent::tearDown();
    }

    /**
     * Creates directory structure.
     *
     * Sets {@see self::$tempPath}.
     *
     * @return void
     */
    protected function createDirStructure(): void
    {
        $this->tempPath = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "deepeloper", "tests", "lib-fs", uniqid()]
        );
        $deepPath = implode(
            DIRECTORY_SEPARATOR,
            [$this->tempPath, "dir1", "dir11", "dir111"]
        );
        $umask = umask(0);
        mkdir($deepPath, self::RIGHTS, true);
        mkdir(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "dir2", "dir22"]
            ),
            self::RIGHTS,
            true
        );
        mkdir(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "dir3"]
            ),
            self::RIGHTS,
            true
        );
        umask($umask);
        file_put_contents(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "file"]
            ),
            "someCONTEnt"
        );
        file_put_contents(
            implode(
                DIRECTORY_SEPARATOR,
                [$deepPath, "deepFile"]
            ),
            "contraception"
        );
    }

    /**
     * Callback cutting $this->tempPath.

     * @param string $path
     *
     * @return string
     *
     * @see self::testSearching()
     */
    protected function cutTempPath(string $path): string
    {
        /*
        $len = strlen($this->tempPath);
        if (strlen($path) != $len) {
            $result = substr($path, $len + 1);
        } else {
            $result = "";
        }
        $result = substr($path, strlen($this->tempPath) + 1);
        return $result;
        */
        return substr($path, strlen($this->tempPath) + 1);
    }
}
