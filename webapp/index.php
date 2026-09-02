<?php

// Suppress PHP 8.5+ E_DEPRECATED for Yii 1.1 legacy casts compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__ . '/vendor/yiisoft/yii/framework/yii.php';

$config = require __DIR__ . '/protected/config/main.php';

Yii::createWebApplication($config)->run();
