<?php

namespace App\Traits\WebSocket;


trait SocketTrait{

	public static function sendSwiftMessage($action, $msg_id){

		try{

			$ip = 'chat.terawork.com';
			// $ip = '35.246.72.76';
			// $ip = '172.20.10.3';
			$local_ip = '127.0.0.1';

			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

			// socket_bind($socket, $local_ip);

			$result = socket_connect($socket, $ip, 8444);

			if (!$result) {
				die('cannot connect ' . socket_strerror(socket_last_error()) . PHP_EOL);
			}

			$message = array('action' => $action, 'msg_id' => $msg_id);
			// $message = array('action' => 'resend-message', 'msg_id' => 'EgRSr2jEMzvgzffIYWFX');
			
			$bytes = socket_write($socket, json_encode($message));

			socket_close($socket);

		}catch(\Exception $e){
			report($e);			
		}
				
	}

}


?>


