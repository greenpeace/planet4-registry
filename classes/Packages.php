<?php
/**
 * This file stores the Planet 4 Packages class.
 *
 * This class is used with Composer scripts to enhance a static satis file with
 * a directory that contains additional dependency information.
 *
 * PHP Version 5
 *
 * @category  PHP
 * @package   Greenpeace\Registry
 * @author    Planet4 Team <planet4-group@greenpeace.org>
 * @copyright 2017 Greenpeace International
 * @license   GPL v3 or higher
 * @link      https://github.com/greenpeace/planet4-registry
 */
namespace Greenpeace\Registry;

use Composer\Script\Event;
use Exception;

/**
 * Packages class for the composer scripts.
 */
class Packages
{
    /**
     * The configuration key for the satis file
     *
     * @var string
     */
    const SATIS_FILE_EXTRAKEY = 'satis-file';

    /**
     * The configuration key for the extended satis file
     *
     * @var string
     */
    const SATIS_EXTENDED_FILE_EXTRAKEY = 'satis-extended-file';

    /**
     * The configuration key for the packages directory
     *
     * @var string
     */
    const PACKAGE_DIRECTORY_EXTRAKEY = 'packages-directory';

    /**
     * Stores the path to the satis file.
     *
     * @var string
     */
    protected $satisFile;

    /**
     * Stores the path to the package directory.
     *
     * @var string
     */
    protected $packagesDirectory;

    /**
     * Composer event that is called from the script.
     *
     * This method is called from Composer once the script is executed.
     *
     * @param  Event $event The event of Composer
     * @return void
     */
    public static function extendSatisFile(Event $event)
    {
        $vendorDirectory = $event->getComposer()->getConfig()->get('vendor-dir');
        $baseDirectory = dirname($vendorDirectory) . DIRECTORY_SEPARATOR;
        $extra = $event->getComposer()->getPackage()->getExtra();

        $satisFile = isset($extra[ self::SATIS_FILE_EXTRAKEY ])
         ? $extra[ self::SATIS_FILE_EXTRAKEY ]
         : 'satis.json';
        $satisExtendedFile = isset($extra[ self::SATIS_EXTENDED_FILE_EXTRAKEY ])
         ? $extra[ self::SATIS_EXTENDED_FILE_EXTRAKEY ]
         : 'satis.extended.json';
        $packagesDirectory = isset($extra[ self::PACKAGE_DIRECTORY_EXTRAKEY ])
         ? $extra[ self::PACKAGE_DIRECTORY_EXTRAKEY ]
         : 'packages';

        // use this class to handle the script call
        $packages = new self( $baseDirectory . $satisFile, $baseDirectory . $packagesDirectory );
        $result = $packages->combine();

        // write the result into the extended file
        file_put_contents($baseDirectory . $satisExtendedFile, $result);
    }

    /**
     * Create a new instance of the Package class.
     *
     * @param $satisFile The satis file to read the information from
     * @param $packagesDirectory The package directory for the static information
     */
    public function __construct($satisFile, $packagesDirectory)
    {
        $this->satisFile = $satisFile;
        $this->packagesDirectory = $this->normalizeDirectory($packagesDirectory);
    }

    /**
     * Normalize a directory path.
     *
     * This metod will make sure a directory ends with a platform specific
     * slash.
     *
     * @param  string $directory The directory to normalize
     * @return string The normalized directory
     */
    protected function normalizeDirectory($directory)
    {
        return rtrim($directory, '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * Read a JSON file.
     *
     * This method will return an array with the information that was parsed
     * from the JSON content inside the given file.
     *
     * @param  string $file The file that should be read
     * @return array The array structure from the JSON file
     * @throws Exception Thrown when the file does not exist
     * @throws Exception Thrown when the file cannot be parsed
     */
    protected function readJsonFile($file)
    {
        if (!is_file($file)) {
            throw new Exception('Package file ' . $file . ' does not exist.');
        }

        $content = json_decode(file_get_contents($file), true);
        if (json_last_error()) {
            throw new Exception('Cannot read package file ' . $file . ': ' . json_last_error_msg());
        }

        return $content;
    }

    /**
     * Combines the satis file and the directory information.
     *
     * This method will return the combined result of the registry. It will
     * read the configured satis file, process the packages directory and return
     * a new JSON string with the result.
     *
     * @return string combined satis information
     */
    public function combine()
    {
        $satis = $this->readJsonFile($this->satisFile);
        if (!isset($satis['repositories'])) {
            $satis['repositories'] = array();
        }

        // todo: add some checks that will prevent duplicate repositories
        $satis['repositories'] = array_merge(
            $satis['repositories'],
            $this->packagesFromDirectory($this->packagesDirectory)
        );
        return json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Read all version files from a directory.
     *
     * This method will read all the version information from the given
     * directory. The result will be an array with all package files.
     * It will only return JSON files, and it will walk recursivly into
     * sub-directories.
     *
     * @param string $directory The directory to inspect
     * @return array The version files found inside the given directory.
     * @throws Exception is $directory is not a directory
     */
    protected function versionFilesFromDirectory($directory)
    {
        $directory = $this->normalizeDirectory($directory);
        if (!is_dir($directory)) {
            throw new Exception('Cannot read packages from directory ' . $directory . '.');
        }

        $result = array();
        foreach (scandir($directory) as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }

            $path = $directory . $file;
            if (is_dir($path)) {
                $result = array_merge($result, $this->versionFilesFromDirectory($path));
                continue;
            }

            if (substr($file, -5) != '.json') {
                continue;
            }

            $result[] = $path;
        }

        return $result;
    }

    /**
     * Returns the packages from the version file.
     *
     * This method will read the given JSON file and return the extracted
     * packages. As Composer, this method assumes that they are defined inside
     * the `repository` section.
     *
     * It will return a key value array in which the key is the name of the
     * package, and the value the repository information for this package.
     *
     * @param  string $file The file to parse
     * @return array Package and repository information
     */
    protected function packagesFromFile($file)
    {
        $content = $this->readJsonFile($file);
        if (empty($content['repositories'])) {
            return array();
        }

        $result = array();
        foreach ((array) $content['repositories'] as $repository) {
            if (empty($repository['package']) || empty($repository['type']) || $repository['type'] != 'package') {
                continue;
            }

            $package = $repository['package'];
            if (empty($package['name']) || empty($package['version'])) {
                continue;
            }

            $identifier = $package['name'] . '@' . $package['version'];
            $result[ $identifier ] = $repository;
        }

        return $result;
    }

    /**
     * Load all package information from a directory.
     *
     * This method will walk recursively through a directory of package files
     * and combine the found information into one array.
     *
     * @param  string $directory The directory that should be processed
     * @return array The package information from that directory
     * @uses   packagesFromFile
     * @uses   versionFilesFromDirectory
     */
    protected function packagesFromDirectory($directory)
    {
        $result = array();

        if (!isset($directory)) {
            $directory = $this->packagesDirectory;
        }
        $versionFiles = $this->versionFilesFromDirectory($directory);
        foreach ($versionFiles as $versionFile) {
            $result = array_merge($result, $this->packagesFromFile($versionFile));
        }

        return array_values($result);
    }
}
