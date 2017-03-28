<?php
/**
 * This class is used with Composer script to update the packages version files
 * from the repositories composer files.
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
class PackageFileTask extends BaseTask
{
    /**
     * Composer event that is called from the script.
     *
     * This method is called from Composer once the script is executed.
     *
     * @param  Event $event The event of Composer
     * @throws Exception if the directory could not be created
     * @throws Exception if the file could not be written
     * @return void
     */
    public static function extractFromRepositories(Event $event)
    {
        // use this class to handle the script call
        $config = PackageFileTask::getConfig($event);
        //$packages = FileUtility::packagesFromDirectory($config['repositoryDirectory']);
        $composerFiles = (FileUtility::getComposerFiles($config['repositoryDirectory']));
        foreach ($composerFiles as $composerFile) {
            $content = FileUtility::getFileWithPackages($composerFile);
            if (is_array($content)) {
                // only care for package on that domain
                if (preg_match('/greenpeace\/(.*)/', $content['name'], $name)) {
                    $packageDirectory = FileUtility::normalizeDirectory(
                        $config['baseDirectory'] . $config['packagesDirectory'] . DIRECTORY_SEPARATOR . $name[1]
                    );
                    FileUtility::createDir($packageDirectory);

                    $file = $packageDirectory . $content['version'] . '.json';
                    FileUtility::createFile($file, $content);
                }
            }
        }
        return;
    }
}
