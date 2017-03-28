<?php
/**
 * This file allow building registry tasks
 * You can build new tasks by extending this one
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

/**
 * Packages class for the composer scripts.
 */
class BaseTask
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
     * The configuration key for the packages directory
     *
     * @var string
     */
    const REPOSITORY_DIRECTORY_EXTRAKEY = 'repositories-directory';

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
     * Return baseline configuration for
     *
     * @param Event $event
     * @return array
     */
    public static function getConfig(Event $event)
    {
        $config = [];

        $config['vendorDirectory'] = $event->getComposer()->getConfig()->get('vendor-dir');
        $config['baseDirectory'] = dirname($config['vendorDirectory']) . DIRECTORY_SEPARATOR;

        $extra = $event->getComposer()->getPackage()->getExtra();

        $config['satisFile'] = isset($extra[ self::SATIS_FILE_EXTRAKEY ])
            ? $extra[ self::SATIS_FILE_EXTRAKEY ]
            : 'satis.json';

        $config['satisExtendedFile'] = isset($extra[ self::SATIS_EXTENDED_FILE_EXTRAKEY ])
            ? $extra[ self::SATIS_EXTENDED_FILE_EXTRAKEY ]
            : 'satis.extended.json';

        $config['packagesDirectory'] = isset($extra[ self::PACKAGE_DIRECTORY_EXTRAKEY ])
            ? $extra[ self::PACKAGE_DIRECTORY_EXTRAKEY ]
            : 'packages';

        $config['repositoryDirectory'] = isset($extra[ self::REPOSITORY_DIRECTORY_EXTRAKEY ])
            ? $extra[ self::REPOSITORY_DIRECTORY_EXTRAKEY ]
            : 'repositories';

        return $config;
    }

    /**
     * Create a new instance of the Package class.
     *
     * @param $config array
     */
    public function __construct($config)
    {
        $this->satisFile = $config['baseDirectory'] . $config['satisFile'];
        $this->packagesDirectory = $config['baseDirectory'] . $config['packagesDirectory'];
        $this->packagesDirectory = FileUtility::normalizeDirectory($this->packagesDirectory);
    }
}
