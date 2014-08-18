<?php

use \ZeroRPC\Channel;
use \ZeroRPC\Socket;

class ZeroRPCException extends RuntimeException {}
class ZeroRPCProtocolException extends ZeroRPCException {}
class ZeroRPCTimeoutException extends ZeroRPCException {}

class ZeroRPCRemoteException extends ZeroRPCException {
  public function __construct(array $error) {
    $this->name = $error[0];
    parent::__construct(is_string($error[1]) ? $error[1] : null , intval($error[0]));
  }

  public function getName() { return $this->name; }
}

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

    // Try to be clever on what type of response we received. Both
    // array and object will be encoded as array. If the response is
    // an array, check if it's associative, and cast it to an object.
    if (is_array($response) && is_string(key($response))) {
      return (object) $response;
    }

    return $response;
  }
}
