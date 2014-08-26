<?php

namespace ZeroRPC;

class ChannelException extends \RuntimeException {}

class Channel {
  const PROTOCOL_VERSION = 3;

  private static $channels = array();

  private $expire;
  private $callbacks = array();

  protected $id;
  protected $envelope;
  protected $socket;
  protected $timeout;

  public function __construct($id, $envelope, $socket, $timeout) {
    $this->id = $id;
    $this->envelope = $envelope;
    $this->socket = $socket;
    $this->timeout = $timeout;

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
    if (microtime(true) > $this->expire) throw new ChannelException('Timed out');

    if ($event->name === '_zpc_hb') {
      // Send heartbeat response
      return $this->socket->send(new Event($this->envelope, $this->createHeader(), '_zpc_hb'));
    }

    foreach($this->callbacks as $callback) {
      $callback($event);
    }

    unset(self::$channels[$this->id]);
  }

  public function send($name, array $args = null) {
    $this->expire = microtime(true) + ($this->timeout / 1000);
    $event = new Event($this->envelope, $this->createHeader(), $name, $args);
    $this->socket->send($event);
  }

  public function createHeader() {
    return array('v'=>self::PROTOCOL_VERSION, 'message_id'=>uniqid(), 'response_to'=>$this->id);
  }
}
