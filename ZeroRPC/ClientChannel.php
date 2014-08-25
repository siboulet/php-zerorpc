<?php

namespace ZeroRPC;

class ClientChannel extends Channel {
  private $fresh;

  public function __construct($socket) {
    parent::__construct(uniqid(), null, $socket);
    $this->fresh = true;
  }

  public function createHeader() {
    if ($this->fresh) {
      $this->fresh = false;
      return array('v'=>parent::PROTOCOL_VERSION, 'message_id'=>$this->id);
    } else {
      return parent::createHeader();
    }
  }
}
