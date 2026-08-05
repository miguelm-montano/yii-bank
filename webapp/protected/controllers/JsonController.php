<?php

/**
 * Controller base compartido por los controllers de la API.
 *
 * Unica responsabilidad: dar forma a la respuesta HTTP (el envoltorio
 * {success, data, error}, siempre igual) y leer el body JSON de la
 * peticion. Ningun controller concreto deberia repetir esta plomeria
 * ni saber como se decodifica una peticion o se serializa una
 * respuesta — eso no es logica de negocio, es protocolo HTTP.
 *
 * Nota: Yii 1.1 ya trae CHttpRequest::$enableCsrfValidation en false
 * por defecto (a diferencia de Yii2), asi que no hace falta
 * desactivarlo aqui para poder recibir POST con JSON.
 */
abstract class JsonController extends CController
{
    public $layout = false;

    /**
     * Decodifica el body JSON de la peticion. Si no es JSON valido,
     * retorna un array vacio (asi el caller lo trata igual que
     * "faltan todos los campos" en vez de un tipo de error distinto).
     */
    protected function getJsonBody()
    {
        $raw = Yii::app()->request->getRawBody();
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @return string|null el nombre del primer campo requerido que
     *                      falta o viene vacio, o null si estan todos.
     */
    protected function firstMissingField(array $body, array $requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                return $field;
            }
        }

        return null;
    }

    /**
     * Unico punto de salida de la API: mismo sobre {success, data,
     * error} siempre, mismo Content-Type siempre, y termina la
     * ejecucion (sin views, sin layout — esto es una API, no HTML).
     */
    protected function sendJson($success, $data, $error = null, $httpStatus = null)
    {
        if ($httpStatus === null) {
            $httpStatus = $success ? 200 : 400;
        }

        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');

        echo CJSON::encode(array(
            'success' => $success,
            'data' => $data,
            'error' => $error,
        ));

        Yii::app()->end();
    }
}
