<?php

namespace ZeroRPC;

class Channel {
  private $socket;
  private $id = null;

  public function __construct($socket) {
    $this->socket = $socket;
  }

  public function recv($timeout = 10) {
    $start = time();

    while (($ttl = $timeout - (time() - $start)) > 0) {
      if (! ($event = $this->socket->recv($ttl))) {
        continue;
      }

      if ($event[0]['response_to'] !== $this->id) {
        // Received an event for another channel
        continue;
      }

      switch ($event[1]) {
        case 'OK':
          return $event[2][0];
          break;

        case 'ERR':
          throw new \ZeroRPCRemoteException($event[2][1]);
          break;

        case '_zpc_hb':
          // Send heartbeat response
          $this->send('_zpc_hb');
          break;
      }
    }

    throw new \ZeroRPCTimeoutException('Timeout after '. (time() - $start).' seconds');
  }

  public function send($name, $args = array(null)) {
    if (!$this->id) {
      $this->id = uniqid();
      // First message contain channel id as message_id
      $header = array('v' => 3, 'message_id' => $this->id);
    } else {
      // Subsequent use reponse_to for channel id and generate new message_id
      $header = array('v' => 3, 'message_id' => uniqid(), 'response_to' => $this->id);
    }
    $event = array($header, $name, $args);
    $this->socket->send($event);
  }
}
