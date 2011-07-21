<?

import('task');
import('socketdaemon');
import("socket");
import("httpserver");
class Taskhttpd extends Task {
	public function run() {
		parent::run();

		$config = Config::getInstance();

		ini_set('mbstring.func_overload', '0');
		ini_set('output_handler', '');
		@ob_end_flush();
		set_time_limit(0);
		$daemon = new socketDaemon();
		$server = $daemon->create_server('httpdServer', 'httpdServerClient', 0, $config->get('webinterface', 'http_port'));
		$daemon->process();
	}
}
