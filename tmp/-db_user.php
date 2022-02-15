<?php

namespace Ray\Di\Compiler;

$instance = new \UserProvider();
$isSingleton = false;
return $instance->get();
