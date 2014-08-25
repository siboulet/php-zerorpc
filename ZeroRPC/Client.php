<?php

namespace ZeroRPC;

class ClientException extends \RuntimeException {
  public function __construct(Event $event) {
    $this->name = $event->args[0];
    parent::__construct(is_string($event->args[1]) ? $event->args[1] : null , intval($event->args[0]));
  }

  public function getName() { return $this->name; }
}

class Client {
  private $socket;

  public function __construct($endpoint, $timeout = 10) {
    $this->socket = new Socket($endpoint, $timeout);
  }

  public function __call($name, $args) {
    $response = null;

    $this->invoke($name, $args, function($event) use (&$response) {
      if ($event->name !== 'OK') {
        throw new ClientException($event);
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
    $channel = new ClientChannel($this->socket);
    if ($callback) $channel->register($callback);
    $channel->send($name, $args);
  }

  public function dispatch() {
    return $this->socket->dispatch();
  }
}
