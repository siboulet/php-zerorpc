<?php

namespace ZeroRPC;

class ClientException extends \RuntimeException {};

class Client {
  const DEFAULT_TIMEOUT = 10000; // 10 seconds

  private $socket;
  private $timeout;

  public function __construct($endpoint, $timeout = self::DEFAULT_TIMEOUT) {
    $this->socket = new Socket($endpoint, $timeout);
    $this->timeout = $timeout;
  }

  public function __call($name, $args) {
    $response = null;

    $this->invoke($name, $args, function($event) use (&$response) {
      if ($event->name === 'ERR') {
        throw new ClientException(is_string($event->args[1]) ? $event->args[1] : null , intval($event->args[0]));
      }
      
      if ($event->name !== 'OK') {
        throw new ClientException('Unexpected event');
      }

      $response = $event->args[0];

      // Try to be clever on what type of response we received. Both
      // array and object will be encoded as array. If the response is
      // an array, check if it's associative, and cast it to an object.
      if (is_array($response) && is_string(key($response))) {
        $response = (object) $response;
      }
    });

    $this->socket->dispatch();

    return $response;
  }

  public function invoke($name, array $args = null, $callback = null) {
    $channel = new ClientChannel($this->socket, $this->timeout);
    if ($callback) $channel->register($callback);
    $channel->send($name, $args);
  }

  public function dispatch() {
    return $this->socket->dispatch();
  }
}
