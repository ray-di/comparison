<?php

namespace Ray\Di\Compiler;

$instance = new \PasswordProvider();
$isSingleton = false;
return $instance->get();
