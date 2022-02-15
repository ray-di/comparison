<?php

namespace Ray\Di\Compiler;

$instance = new \DsnProvider();
$isSingleton = false;
return $instance->get();
