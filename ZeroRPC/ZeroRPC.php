<?php

use \ZeroRPC\Channel;
use \ZeroRPC\Socket;

class ZeroRPCException extends RuntimeException {}
class ZeroRPCRemoteException extends ZeroRPCException {}
class ZeroRPCTimeoutException extends ZeroRPCException {}

class ZeroRPC {
  private $socket;

  public function __construct($endpoint) {
    $this->socket = new Socket($endpoint);
  }

  public function __call($name, $args) {
    return $this->call($name, $args);
  }

  public function call($name, $args, $timeout = 10) {
    $channel = new Channel($this->socket);
    $channel->send($name, $args);
    $response = $channel->recv($timeout);

    // Convert to object
    return json_decode(json_encode($response), false);
  }
}
