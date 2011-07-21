<?

class Task {
	protected $pid;
	protected $ppid;

	public function __construct(){ }

	public function getTaskName() {
		return str_replace("Task", "", get_class($this));
	}

	public function fork(){
		$pid = pcntl_fork();
		if ($pid == -1)
			throw new Exception ('fork error on Task object');
		elseif ($pid) {
			# we are in parent class
			$this->pid = $pid;
			# echo "< in parent with pid {$his->pid}\n";
		} else{
			# we are is child
			$this->run();
		}
	}

	public function run(){
		# echo "> in child {$this->pid}\n";
		# sleep(rand(1,3));
		$this->logInfo('Starting');
		$this->ppid = posix_getppid();
		$this->pid = posix_getpid();
	}

	# call when a task in finished (in parent)
	public function finish(){
		//echo "task finished {$this->pid}\n";
	}

	public function pid(){
		return $this->pid;
	}

	private function getLogTaskName() {
		return strtoupper( $this->getTaskName() );
	}

	public function logWarning( $message = '' ) {
		System_Daemon::warning( $this->getLogTaskName() . ' :: ' . $message );
	}

	public function logError( $message = '' ) {
		System_Daemon::error( $this->getLogTaskName() . ' :: ' . $message );
	}

	public function getDB() {
		return System_Daemon::DB();
	}

	public function logInfo( $message = '' ) {
		$args = func_get_args();
		$args[0] = sprintf("%15s", $this->getLogTaskName()) . ' :: ' . $message;
		if ( class_exists("System_Daemon") && $this->pid ) call_user_func_array( "System_Daemon::info", $args );
		else {
			$args[0] .= "\n";
			call_user_func_array( "printf", $args );
		}
	}

	public function iterate( $sleepSeconds = 0 ) {
		if ( class_exists("System_Daemon") ) {
			System_Daemon::iterate( $sleepSeconds );
		}
		else {
			if ($sleepSeconds !== 0) {
				usleep($sleepSeconds*1000000);
			}
			clearstatcache();

			// Garbage Collection (PHP >= 5.3)
			if (function_exists('gc_collect_cycles')) {
				gc_collect_cycles();
			}
		}
	}
}

