<?php

namespace Ray\Di\Compiler;

$instance = new \PDO($prototype('-db_dsn', array('PDO', '__construct', 'dsn')), null, $prototype('-db_password', array('PDO', '__construct', 'password')), null);
$isSingleton = false;
return $instance;
