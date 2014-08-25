<?php

namespace ZeroRPC;

class ChannelException extends \RuntimeException {}

class Channel {
  const PROTOCOL_VERSION = 3;

  private static $channels = array();

  protected $id;
  protected $envelope;
  protected $socket;
  private $callbacks = array();

  public function __construct($id, $envelope, $socket) {
    $this->id = $id;
    $this->envelope = $envelope;
    $this->socket = $socket;

    self::$channels[$id] = $this;
  }

  public static function get($id) {
    if (isset(self::$channels[$id])) return self::$channels[$id];
  }
  
  public static function count() {
    return count(self::$channels);
  }

  public function register($callback) {
    array_push($this->callbacks, $callback);
  }

  // Called when the channel receives an event
  public function invoke(Event $event) {
    if ($event->name === '_zpc_hb') {
      // Send heartbeat response
      return $this->send('_zpc_hb');
    }

    foreach($this->callbacks as $callback) {
      $callback($event);
    }

    unset(self::$channels[$this->id]);
  }

  public function send($name, array $args = null) {
    $event = new Event($this->envelope, $this->createHeader(), $name, $args);
    $this->socket->send($event);
  }

  public function createHeader() {
    return array('v'=>PROTOCOL_VERSION, 'message_id'=>uniqid(), 'response_to'=>$this->id);
  }
}
