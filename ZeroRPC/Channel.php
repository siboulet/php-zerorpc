<?php

namespace ZeroRPC;

class ChannelException extends \RuntimeException {}

class Channel {
  const PROTOCOL_VERSION = 3;
  const DEFAULT_CAPACITY = 100;

  private static $channels = array();

  private $expire;
  private $callbacks = array();

  protected $id;
  protected $envelope;
  protected $socket;
  protected $timeout;
  protected $capacity;

  public function __construct($id, $envelope, $socket, $timeout, $capacity = self::DEFAULT_CAPACITY) {
    $this->id = $id;
    $this->envelope = $envelope;
    $this->socket = $socket;
    $this->timeout = $timeout;
    $this->capacity = $capacity;

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
    
    if ($event->name === 'STREAM') {
      $this->send('_zpc_more', array($this->capacity));
    } else {
      unset(self::$channels[$this->id]);
    }
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
