<?php

namespace ZeroRPC;

use ZMQ;

class Socket {
  private $zmq;

  public function __construct($endpoint) {
    $this->zmq = new \ZMQSocket(new \ZMQContext(), ZMQ::SOCKET_XREQ);
    $this->zmq->connect($endpoint);
  }

  public function recv($timeout = 10) {
    $this->zmq->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, $timeout * 1000);
    $this->zmq->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);

    if (($recv = $this->zmq->recvMulti())) {
      if (strlen($recv[count($recv)-2]) !== 0) {
        throw new \ZeroRPCProtocolException('Expected second to last argument to be an empty buffer, but it is not');
      }

      return msgpack_unpack($recv[1]);
    }

    return null;
  }

  public function send($event) {
    $this->zmq->sendMulti(array(null, msgpack_pack($event)));
  }
}
