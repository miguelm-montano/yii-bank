<?php

require __DIR__ . '/vendor/yiisoft/yii/framework/yii.php';

$config = require __DIR__ . '/protected/config/main.php';

Yii::createWebApplication($config)->run();
