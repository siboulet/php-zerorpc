#!/usr/bin/env nodejs

var zerorpc = require('zerorpc');

var server = new zerorpc.Server({
  sleep: function(seconds, reply) {
    console.log('Sleeping for '+seconds+' seconds');
    setTimeout(function() {
      reply(null, 'Slept for '+seconds+' seconds');
    }, seconds*1000);
  }
});

server.bind('tcp://127.0.0.1:18181');
