<?php

/**
 * Configuracion de la aplicacion de consola (yiic).
 * Mismo import/db que main.php; sin urlManager porque no aplica
 * fuera de peticiones web.
 */
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Yii Bank Console',

    'import' => array(
        'application.models.*',
        'application.interfaces.*',
        'application.repositories.*',
        'application.services.*',
        'application.services.InterestCalculationStrategy.*',
        'application.strategies.*',
        'application.factories.*',
        'application.observers.*',
    ),

    'components' => array(
        'db' => array_merge(
            array('class' => 'CDbConnection'),
            require(dirname(__FILE__) . '/database.php')
        ),
    ),
);
