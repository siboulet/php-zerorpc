<?php
function __autoload($name) {
  $file = str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php';
  include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$file;
}

use ZeroRPC\Client as ZeroRPC;

$test = new ZeroRPC('tcp://127.0.0.1:18181');

// Example 1
// This will sleep for ~6 seconds
$start = microtime(true);

print $test->sleep(2).PHP_EOL;
print $test->sleep(3).PHP_EOL;
print $test->sleep(1).PHP_EOL;

print 'Example 1: Slept for a total of '.(microtime(true)-$start).' ms'.PHP_EOL;

// Example 2
// The calls being made in parralel, this will sleep for ~3 seconds
$start = microtime(true);

$test->invoke('sleep', array(2), function($event) {
  print $event->args[0].PHP_EOL;
});
$test->invoke('sleep', array(3), function($event) {
  print $event->args[0].PHP_EOL;
});
$test->invoke('sleep', array(1), function($event) {
  print $event->args[0].PHP_EOL;
});

$test->dispatch();

print 'Example 2: Slept for a total of '.(microtime(true)-$start).' ms'.PHP_EOL;
