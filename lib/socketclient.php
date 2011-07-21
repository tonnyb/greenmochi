<?
/*
phpSocketDaemon 1.0
Copyright (C) 2006 Chris Chabot <chabotc@xs4all.nl>
See http://www.chabotc.nl/ for more information

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/
abstract class socketClient extends socket {
	private static $verbose = 0;
	public $remote_address = null;
	public $remote_port    = null;
	public $connecting     = false;
	public $disconnected   = false;
	public $read_buffer    = '';
	public $write_buffer   = '';

	public function connect($remote_address, $remote_port)
	{
		$this->connecting = true;
		try {
			parent::connect($remote_address, $remote_port);
		} catch (socketException $e) {
			print("socketClient:connect: caught exception: ".$e->getMessage()."\n");
		}
	}

	public function write($buffer, $length = 4096)
	{
		$this->write_buffer .= $buffer;
		$this->do_write();
	}

	public function do_write()
	{
		$length = strlen($this->write_buffer);
		if($length > 0) {
			try {
				$written = parent::write($this->write_buffer, $length);
				if ($written < $length) {
					$this->write_buffer = substr($this->write_buffer, $written);
				} else {
					$this->write_buffer = '';
				}
				$this->on_write();
				return true;
			} catch (Exception $e) {
				$this->message(get_class($this) . ":write(" . (int)$this->socket . "): caught exception: ". $e->getMessage() . "\n" . $e->getTraceAsString()) . "\n";
				$this->disconnect();
			}
			return false;
		} else {
			return true;
		}
	}

	public function read($length = 4096)
	{
		try {
			$this->read_buffer .= parent::read($length);
			$this->on_read();
		} catch (Exception $e) {
			if(!($e instanceof socketException) || $e->getCode() != ESUCCESS) { // normal end of file
				$this->message(get_class($this) . ":read(" . (int)$this->socket . "): caught exception: ". $e->getMessage() . "\n" . $e->getTraceAsString()) . "\n";
			}
			$this->disconnect();
		}
	}

	public function disconnect() {
	  $this->close();
	  $this->disconnected = true;
	  $this->on_disconnect();
	}

	public function on_connect() {}
	public function on_disconnect() {}
	public function on_read() {}
	public function on_write() {}
	public function on_timer() {}
}
