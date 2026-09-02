<?php

// Suppress PHP 8.5+ E_DEPRECATED for Yii 1.1 legacy casts compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

$yiic = dirname(__FILE__) . '/../vendor/yiisoft/yii/framework/yiic.php';
$config = dirname(__FILE__) . '/config/console.php';

require_once($yiic);
