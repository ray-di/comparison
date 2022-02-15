<?php

namespace Ray\Di\Compiler;

$instance = new \Foo($prototype('PDO-'), 'bar-right', 'baz-right');
$isSingleton = false;
return $instance;
