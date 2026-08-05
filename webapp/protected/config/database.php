<?php

/**
 * Configuracion especifica del driver de base de datos (SQLite).
 * Separada de main.php para poder cambiar de entorno (test, prod)
 * sin tocar el resto de la configuracion de la aplicacion.
 */
return array(
    'connectionString' => 'sqlite:' . dirname(__FILE__) . '/../data/bank.db',
    'charset' => 'utf8',
    'enableProfiling' => false,
    'enableParamLogging' => true,
);
