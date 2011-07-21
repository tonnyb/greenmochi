<?

import('task');
class TaskBacklog extends Task {
	public function run() {
		parent::run();

		while( $this->pid ) {

			$this->iterate(5);
		}

	}
}

?>
