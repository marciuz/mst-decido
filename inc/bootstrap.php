<?php

$selfdir = str_replace('/inc', '', __DIR__);

require_once($selfdir.'/conf/conf.php');
require_once($selfdir.'/vendor/autoload.php');


if(APPLICATION_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Session
session_start();

$vmsql = Vmsql::init();
$vmsql->connect($db1);
