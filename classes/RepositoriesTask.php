<?php
/**
 * Helper for repository related tasks
 *
 * @category  PHP
 * @package   Greenpeace\Registry
 * @author    Planet4 Team <planet4-group@greenpeace.org>
 * @copyright 2017 Greenpeace International
 * @license   GPL v3 or higher
 * @link      https://github.com/greenpeace/planet4-registry
 */
namespace Greenpeace\Registry;

use GitWrapper\GitWrapper;
use Composer\Script\Event;

/**
 * Packages class for the composer scripts.
 */
class RepositoriesTask extends BaseTask
{
	const DIRNAME = 'repositories';
	const DIRPATH = __DIR__ . '/../repositories';

	/**
	 * Delete existing repositories and init back to default state
	 */
	public function cleanRepositories() {
		FileUtility::rrmdir(self::DIRNAME);
		mkdir(self::DIRNAME);
	}

	/**
	 * Get the list of the repositories and clone them
	 */
	public static function cloneRepositories (Event $event) {
		// use this class to handle the script call
		$config = SatisFileTask::getConfig($event);
		$task = new self($config);

		$task->cleanRepositories();
		$repos = $task->getLocalRepositories();
		$git = new GitWrapper();

		foreach ($repos as $path => $repo) {
			echo 'cloning ' . $path . PHP_EOL;
			try {
				$git->clone($config['repositoryRemoteUrl'] . $repo . '.git', self::DIRPATH . DS . $path);
			} catch (\GitWrapper\GitException $e) {
				echo 'Could not clone.' . PHP_EOL . $e->getMessage();
				return;
			}
		}
	}

	/**
	 * Return the list of local repositories
	 * If the statis file contains repositories starting with 'repositories/' it means it needs to be
	 * cloned and server locally. The others, composer can access directly.
	 */
	public function getLocalRepositories() {
		$repos = [];
		$satis = FileUtility::readJsonFile($this->satisFile);
		foreach ($satis['repositories'] as $i => $repository) {
			if (substr($repository['url'], 0, strlen('repositories')) === 'repositories') {
				$repoName = explode(DS, $repository['url']);
				$repoLink = $repoName[1];
				if (!in_array($repoLink, $repos)) {
					$repos[$repoName[1]] = $repoLink;
				}
			}
		}
		return $repos;
	}
}