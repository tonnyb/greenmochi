<?php

/**
 *  a simple task management framework using pcntl_fork, pcntl_wait.
 *
 *  - see at bottom for a sample usage.
 *  - you shoud overring Task class (SleepingClass is an example), and manage them in a pool, using taskManager
 */

error_reporting(E_ALL);

/*
class SleepingTask extends Task{
	public function run(){
		parent::run();
		echo "> in child {$this->pid}\n";

		# print_r($this);

		sleep(rand(1,5));
		echo "> child done {$this->pid}\n";
		exit(0);
	}
}
*/

class TaskManager{

	protected $pool;

	public function __construct(){
		$this->pool = array();
	}

	public function add_task($task){
		$this->pool[] = $task;
	}

	public function run(){

		foreach($this->pool as $task){
			$task->fork();
			usleep(100000);
		}

		# print_r($this);
		# sleep(60);

		while(1){
			//echo "waiting\n";
			$pid = pcntl_wait($extra);
			if($pid == -1)
				break;

			//echo ": task done : $pid\n";
			$this->finish_task($pid);
			System_Daemon::iterate(2);
		}

		//echo "processes done ; exiting\n";
		exit(0);
	}

	public function finish_task($pid){
		if($task = $this->pid_to_task($pid))
			$task->finish();
	}

	public function pid_to_task($pid){
		foreach($this->pool as $task){
			if($task->pid() == $pid)
				return $task;
		}
		return false;
	}
}

#$manager = new TaskManager();
#for($i=0 ; $i<10 ; $i++)
#    $manager->add_task(new SleepingTask());
#
#$manager->run();

?>
