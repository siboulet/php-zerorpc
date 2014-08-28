<?php

namespace ZeroRPC;

class EventException extends \RuntimeException {}

class Event {
  public $envelope;
  public $header;
  public $name;
  public $args;

  public function __construct($envelope, array $header, $name, $args = null) {
    $this->envelope = $envelope;
    $this->header = $header;
    $this->name = $name;
    $this->args = $args;
  }

  public function serialize() {
    $payload = array($this->header, $this->name, $this->args);
    $message = ($this->envelope) ? $this->envelope : array(null);
    array_push($message, msgpack_pack($payload));
    return $message;
  }

  public static function deserialize($envelope, $payload) {
    $event = msgpack_unpack($payload);

    if (!is_array($event) || count($event) !== 3) {
      throw new EventException('Expected array of size 3');
    } else if (!is_array($event[0]) || !array_key_exists('message_id', $event[0])) {
      throw new EventException('Bad header');
    } else if (!is_string($event[1])) {
      throw new EventException('Bad name');
    }

    return new Event($envelope, $event[0], $event[1], $event[2]);
  }
}
