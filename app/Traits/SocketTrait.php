<?php

namespace App\Traits;


trait SocketTrait{

	public function message($channel, $message){

		try{

			$ip = '127.0.0.1';

			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

			socket_bind($socket, $ip);

			$result = socket_connect($socket, $ip, 8002);

			if (!$result) {
				die('cannot connect ' . socket_strerror(socket_last_error()) . PHP_EOL);
			}

			$msg = array('channel' => $channel, 'message' => $message);
			$bytes = socket_write($socket, json_encode($msg));

		
			socket_close($socket);

		}catch(\Exception $e){
			
		}
				
	}

}


?>


