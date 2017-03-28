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
class SatisFileTask extends BaseTask
{
    /**
     * Composer event that is called from the script.
     *
     * This method is called from Composer once the script is executed.
     *
     * @param  Event $event The event of Composer
     * @throws Exception if could not write the file on disk
     * @return void
     */
    public static function extendSatisFile(Event $event)
    {
        // use this class to handle the script call
        $config = SatisFileTask::getConfig($event);
        $task = new self($config);

        // combine the packages
        // write the result into the extended file
        $result = $task->combine();
        $satisExtendedFile = $config['baseDirectory'] . $config['satisExtendedFile'];
        if(!file_put_contents($satisExtendedFile, $result)) {
            throw new Exception('Error, could not write ' . $satisExtendedFile . 'please check permissions');
        }
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
        $satis = FileUtility::readJsonFile($this->satisFile);
        if (!isset($satis['repositories'])) {
            $satis['repositories'] = array();
        }

        // todo: add some checks that will prevent duplicate repositories
        $satis['repositories'] = array_merge(
            $satis['repositories'],
            FileUtility::getPackagesFromDirectory($this->packagesDirectory)
        );
        return json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
