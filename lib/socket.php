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

import('socketClient');
import('socketServer');
import('socketServerClient');
import('socketDaemon');

define('ESUCCESS', 0);
define('EAGAIN', 11);
define('EALREADY', 114);
define('EINPROGRESS', 115);

class socketException extends Exception {}

abstract class socket {
	public $socket;
	public $bind_address;
	public $bind_port;
	public $domain;
	public $type;
	public $protocol;
	public $local_addr;
	public $local_port;
	public $read_buffer    = '';
	public $write_buffer   = '';

	public function __construct($bind_address = 0, $bind_port = 0, $domain = AF_INET, $type = SOCK_STREAM, $protocol = SOL_TCP)
	{
		$this->bind_address = $bind_address;
		$this->bind_port    = $bind_port;
		$this->domain       = $domain;
		$this->type         = $type;
		$this->protocol     = $protocol;
		if (($this->socket = @socket_create($domain, $type, $protocol)) === false) {
			$this->throw_error("Could not create socket: ");
		}
		if (!@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
			$this->throw_error("Could not set SO_REUSEADDR: ");
		}
		if (!@socket_bind($this->socket, $bind_address, $bind_port)) {
			$this->throw_error("Could not bind socket to [$bind_address - $bind_port]: ");
		}
		if (!@socket_getsockname($this->socket, $this->local_addr, $this->local_port)) {
			$this->throw_error("Could not retrieve local address & port: ");
		}
		$this->set_non_block(true);
	}

	public function message($string) {
		print($string);
	}

	public function create_socket() {
    $domain = AF_INET;
    $type = SOCK_STREAM;
    $protocol = SOL_TCP;

		assert(!isset($this->socket));

    if(($this->socket = @socket_create($domain, $type, $protocol)) === false) {
      $this->throw_error("Could not create socket: ");
    }
    if(!@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
      $this->throw_error("Could not set SO_REUSEADDR: ");
    }
    $this->set_non_block(true);
	}

	public function __destruct()
	{
		if (is_resource($this->socket)) {
			$this->close();
		}
	}

	public function throw_error($message)
	{
		$errno = socket_last_error($this->socket);
		$error = socket_strerror(socket_last_error($this->socket)) . ' (errno ' . $errno . ')';
		socket_clear_error($this->socket);
		throw new socketException($message . $error, $errno);
	}

	public function close()
	{
		$old_socket = (int)$this->socket;
		if (is_resource($this->socket)) {
			@socket_shutdown($this->socket, 2);
			@socket_close($this->socket);
		}
		$this->socket = $old_socket;
	}

	public function write($buffer, $length = 4096)
	{
		$ret = 0;
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (($ret = @socket_write($this->socket, $buffer, $length)) === false) {
		  if(socket_last_error($this->socket) != EAGAIN) {
				$this->throw_error("Could not write to socket: ");
			}
		}
		return $ret;
	}

	public function read($length = 4096)
	{
		$ret = '';
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (($ret = @socket_read($this->socket, $length, PHP_BINARY_READ)) == false) {
		  if(socket_last_error($this->socket) != EAGAIN &&
				 socket_last_error($this->socket) != EINPROGRESS) {
				$this->throw_error("Could not read from socket: ");
			}
		}
		return $ret;
	}

	public function connect($remote_address, $remote_port)
	{
		$this->remote_address = $remote_address;
		$this->remote_port    = $remote_port;
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_connect($this->socket, $remote_address, $remote_port)) {
		  if(socket_last_error($this->socket) != EINPROGRESS) {
				$this->throw_error("Could not connect to {$remote_address} - {$remote_port}: ");
			}
		}
	}

	public function listen($backlog = SOMAXCONN)
	{
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_listen($this->socket, $backlog)) {
			$this->throw_error("Could not listen to {$this->bind_address} - {$this->bind_port}: ");
		}
	}

	public function accept()
	{
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (($client = socket_accept($this->socket)) === false) {
			$this->throw_error("Could not accept connection to {$this->bind_address} - {$this->bind_port}: ");
		}
		return $client;
	}

	public function set_non_block()
	{
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_set_nonblock($this->socket)) {
			$this->throw_error("Could not set socket non_block: ");
		}
	}

	public function set_block()
	{
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_set_block($this->socket)) {
			$this->throw_error("Could not set socket non_block: ");
		}
	}

	public function set_recieve_timeout($sec, $usec)
	{
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $sec, "usec" => $usec))) {
			$this->throw_error("Could not set socket recieve timeout: ");
		}
	}

	public function set_reuse_address($reuse = true)
	{
		$reuse = $reuse ? 1 : 0;
		if (!is_resource($this->socket)) {
			throw new socketException("Invalid socket or resource");
		} elseif (!@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, $reuse)) {
			$this->throw_error("Could not set SO_REUSEADDR to '$reuse': ");
		}
	}
}
