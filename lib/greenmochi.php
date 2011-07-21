<?
import('daemon');

class GreenMochi {

	public static function setup() {
		// Uses System_Daemon from pear
		// TODO: Own System Daemon

		// Make it possible to test in source directory
		// This is for PEAR developers only
		//ini_set('include_path', ini_get('include_path').':..');

		// Include Class
		error_reporting(E_ALL);
		import("Daemon");

		// Bare minimum setup
		$options = array(
			'appName' => 'greenmochi',
			'appDir' => BASE_PATH,
			'appDescription' => 'greenMochi!! Anime Downloader',
			'authorName' => 'Tonny Buse',
			'authorEmail' => 'tonnyb@casema.nl',
			'sysMaxExecutionTime' => '0',
			'sysMaxInputTime' => '0',
			'sysMemoryLimit' => '256M',
			'appRunAsGID' => 1001,
			'appRunAsUID' => 1001,
			'logLocation' => BASE_VAR . 'greenmochi.log',
			'appPidLocation' => BASE_VAR . '/greenmochi/greenmochi.pid',
		);
		System_Daemon::setOptions($options);

		System_Daemon::setSigHandler(SIGTERM, 'signalHandler');
	}

	public static function writeinit() {
		$initd_location = System_Daemon::writeAutoRun();
	}

	public static function run() {
		self::setup();

		//System_Daemon::log(System_Daemon::LOG_INFO, "Daemon not yet started so ".  "this will be written on-screen");
		//System_Daemon::start();
		System_Daemon::init();
		$runningOkay = true;

		$start = true;
		$tasks = array(
			'VersionChecker',
			'EpisodeManager',
			'PostProcessor',
			'Backlog',
			'httpd',
		);
		import('taskmanager');

		while (!System_Daemon::isDying() && $runningOkay) {
			$runningOkay = true;

			if ( $start ) {

				// Adding the tasks
				$taskmanager = new TaskManager();
				foreach ( $tasks as $task ) {
					$class = "Task" . $task;
					import('task.'.$task);
					try {
						$taskmanager->add_task(new $class());
					}
					catch (Exception $e) {
						$runningOkay = false;
					}
				}

				if ( $runningOkay ) $taskmanager->run();
				$start = false;
			}

			if (!$runningOkay) {
				System_Daemon::err('mochi() produced an error, '.  'so this will be my last run');
			}
		 
			//posix_kill(posix_getpid(), SIGTERM);
			// Relax the system by sleeping for a little bit
			// iterate also clears statcache
			System_Daemon::iterate(2);
		 
		}

		System_Daemon::stop();
	}

}


