<?php
/**
 * This file provide some utilities function for writting / reading on the filesystem
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

use Exception;

const DS = DIRECTORY_SEPARATOR;

/**
 * Packages class for the composer scripts.
 */
class FileUtility
{
    /**
     * Normalize a directory path.
     *
     * This metod will make sure a directory ends with a platform specific
     * slash.
     *
     * @param  string $directory The directory to normalize
     * @return string The normalized directory
     */
    public static function normalizeDirectory($directory)
    {
        return rtrim($directory, '\\/') . DS;
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
    public static function readJsonFile($file)
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
     * Create a directory if it doesn't already exist
     * @throws Exception
     */
    public static function createDir($directory)
    {
        if (!is_dir($directory)) {
            // chmod is set to read/write for current user and group
            if (mkdir($directory, 0770)) {
                echo 'Created directory ' . $directory . "\n";
            } else {
                $msg = 'Error, could not create directory ' . $directory . ' please check permissions.';
                throw new \Exception($msg);
            }
        }
    }

    public static function createFile($file, $content)
    {
        if (!file_exists($file)) {
            $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (file_put_contents($file, $json)) {
                echo 'Created file ' . $file . "\n";
            } else {
                throw new Exception('Error, could not write ' . $file . ' please check permissions');
            }
        }
    }

    /**
     * Read all composer files from a directory.
     *
     * This method will read all the version information from the given
     * directory. The result will be an array with all package files.
     * It will only return composer JSON files, and it will walk recursively into
     * sub-directories.
     *
     * @param string $directory The directory to inspect
     * @return array The version files found inside the given directory.
     * @throws Exception is $directory is not a directory
     */
    public static function getComposerFiles($directory)
    {
        $directory = FileUtility::normalizeDirectory($directory);
        if (!is_dir($directory)) {
            throw new Exception('Cannot read composer files from directory ' . $directory . '.');
        }

        $result = array();
        foreach (scandir($directory) as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $path = $directory . $file;
            if (is_dir($path)) {
                $result = array_merge($result, FileUtility::getComposerFiles($path));
                continue;
            }
            if (substr($file, -13) != 'composer.json') {
                continue;
            }
            $result[] = $path;
        }

        return $result;
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
    public static function getVersionFiles( $directory ) {
        $directory = FileUtility::normalizeDirectory($directory);
        if ( !is_dir( $directory ) ) {
            throw new Exception( 'Cannot read packages from directory ' . $directory . '.' );
        }

        $result = array();
        foreach( scandir( $directory ) as $file ) {
            if ( substr( $file, 0, 1 ) == '.' ) {
                continue;
            }
            $path = $directory . $file;
            if ( is_dir( $path ) ) {
                $result = array_merge( $result, FileUtility::getVersionFiles($path));
                continue;
            }
            if ( substr( $file, -5 ) != '.json' ) {
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
    public static function getPackagesFromFile($file)
    {
        $content = FileUtility::readJsonFile($file);
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
     * Returns the whole file if it contains packages or false otherwise
     *
     * @param  string $file The file to parse
     * @return mixed false or array json file information
     */
    public static function getFileWithPackages($file)
    {
        $content = FileUtility::readJsonFile($file);

        // packages are only included in repositories section
        if (!empty($content['repositories'])) {
            foreach ((array) $content['repositories'] as $repository) {
                // repository type should be package
                if (empty($repository['package']) || empty($repository['type']) || $repository['type'] != 'package') {
                    continue;
                }
                // name and version must me specified
                $package = $repository['package'];
                if (empty($package['name']) || empty($package['version'])) {
                    continue;
                }
                return $content;
            }
        }

        return false;
    }

    /**
     * Load all package information from a directory.
     *
     * This method will walk recursively through a directory of package files
     * and combine the found information into one array.
     *
     * @param  string $directory The directory that should be processed
     * @return array The package information from that directory
     * @uses   getPackagesFromFile
     * @uses   getComposerFiles
     */
    public static function getPackagesFromDirectory($directory)
    {
        $result = array();
        $versionFiles = FileUtility::getVersionFiles($directory);

        foreach ($versionFiles as $versionFile) {
            $result = array_merge($result, FileUtility::getPackagesFromFile($versionFile));
        }

        return array_values($result);
    }

    /**
     * Get the composer files that are composed of packages.
     *
     * @param  string $directory The directory that should be processed
     * @return array The package information from that directory
     * @uses   getPackagesFromFile
     * @uses   getComposerFiles
     */
    public static function getComposerFileWithPackages($directory)
    {
        $result = array();

        $versionFiles = FileUtility::getComposerFiles($directory);
        foreach ($versionFiles as $versionFile) {
            $result = array_merge($result, FileUtility::getPackagesFromFile($versionFile));
        }

        return array_values($result);
    }

	public static function rrmdir($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir . "/" . $object))
						self::rrmdir($dir . "/" . $object);
					else
						unlink($dir . "/" . $object);
				}
			}
			rmdir($dir);
		}
	}
}
