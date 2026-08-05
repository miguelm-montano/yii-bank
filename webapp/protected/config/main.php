<?php

/**
 * Configuracion principal de la aplicacion.
 *
 * PASO 1: solo se registran los modelos (entidades) y la conexion a BD.
 * Controllers, layouts, componentes de negocio, etc. se anadiran
 * en los siguientes pasos.
 */
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Yii Bank',

    // Autocarga de todas las clases dentro de protected/models
    'import' => array(
        'application.models.*',
    ),

    'components' => array(
        'db' => array_merge(
            array('class' => 'CDbConnection'),
            require(dirname(__FILE__) . '/database.php')
        ),
    ),
);
