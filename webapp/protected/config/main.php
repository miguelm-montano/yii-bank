<?php

/**
 * Configuracion principal de la aplicacion.
 */
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Yii Bank',

    // Autocarga de modelos, interfaces y repositories.
    // Nota: esto es solo resolucion de rutas de clase (Yii::import),
    // no inyeccion de dependencias. El wiring de "que implementacion
    // concreta recibe cada interfaz" llega en un paso posterior.
    'import' => array(
        'application.models.*',
        'application.interfaces.*',
        'application.repositories.*',
        'application.services.*',
        'application.services.InterestCalculationStrategy.*',
        // JsonController es la clase base de los controllers concretos;
        // sin registrar el path, el autoloader de Yii no la encuentra
        // al resolver "class UserController extends JsonController".
        'application.controllers.*',
    ),

    'components' => array(
        'db' => array_merge(
            array('class' => 'CDbConnection'),
            require(dirname(__FILE__) . '/database.php')
        ),

        // API pura: sin vistas ni forms con token CSRF, asi que
        // usamos 'path' solo para las dos rutas que el enunciado pide
        // como pretty URL; el resto cae al patron generico
        // <controller>/<action>.
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => true,
            'rules' => array(
                'account/<id:\d+>/balance' => 'account/getBalance',
                'transaction/history' => 'transaction/getHistory',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),
    ),
);
