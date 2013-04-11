<?php
define('ROOT_PATH', dirname(__FILE__));
require_once ('../webzim.php');
$applicaiton = new WebZim();
$applicaiton->run();

