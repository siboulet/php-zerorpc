<?php

namespace ZeroRPC;

use ZMQ;

class SocketException extends \RuntimeException {}

class Socket {
  private $zmq;

  public function __construct($endpoint, $timeout = 10) {
    $this->zmq = new \ZMQSocket(new \ZMQContext(), ZMQ::SOCKET_XREQ);
    $this->zmq->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, $timeout * 1000);
    $this->zmq->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
    $this->zmq->connect($endpoint);
  }

  public function send(Event $event) {
    $this->zmq->sendMulti($event->serialize());
  }

  public function dispatch() {
    do {
      if (!($recv = $this->zmq->recvMulti())) throw new SocketException('Receive timed out');

      if (strlen($recv[count($recv)-2]) !== 0) {
        throw new SocketException('Expected second to last argument to be an empty buffer, but it is not');
      }

      $envelope = array_slice($recv, 0, count($recv)-2);
      $event = Event::deserialize($envelope, $recv[count($recv)-1]);

      $channel = Channel::get($event->header['response_to']);
      if ($channel) $channel->invoke($event);
    } while (Channel::count() > 0);
  }
}
