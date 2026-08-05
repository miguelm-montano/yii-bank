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

    // Autocarga de modelos, interfaces y repositories.
    // Nota: esto es solo resolucion de rutas de clase (Yii::import),
    // no inyeccion de dependencias. El wiring de "que implementacion
    // concreta recibe cada interfaz" llega en un paso posterior.
    'import' => array(
        'application.models.*',
        'application.interfaces.*',
        'application.repositories.*',
    ),

    'components' => array(
        'db' => array_merge(
            array('class' => 'CDbConnection'),
            require(dirname(__FILE__) . '/database.php')
        ),
    ),
);
