<?php

/**
 * Responsabilidad: mapear HTTP <-> UserService. Este controller no
 * valida reglas de negocio, no encripta nada, no decide si un
 * username esta disponible: solo interpreta la peticion, llama a
 * UserService, y traduce el resultado a JSON. Toda decision real
 * vive en UserService (Paso 3).
 */
class UserController extends JsonController
{
    /** @var UserService */
    private $userService;

    /**
     * Yii 1.1 no tiene contenedor de DI: durante el despacho normal
     * de una peticion, el framework siempre instancia el controller
     * como `new UserController($id, $module)`, sin argumentos extra.
     * Por eso el tercer parametro es opcional: en produccion queda en
     * null y aqui mismo se compone la cadena real de dependencias
     * (este constructor es, de facto, el composition root de este
     * controller); en un test se puede inyectar un UserService con
     * un repositorio falso sin tocar esta clase.
     */
    public function __construct($id, $module = null, ?UserService $userService = null)
    {
        parent::__construct($id, $module);

        $this->userService = $userService !== null
            ? $userService
            : new UserService(new UserRepository());
    }

    /**
     * POST /user/register
     * Body JSON: {username, email, password}
     */
    public function actionRegister()
    {
        $body = $this->getJsonBody();
        $missing = $this->firstMissingField($body, array('username', 'email', 'password'));

        if ($missing !== null) {
            $this->sendJson(false, null, "Missing parameter: {$missing}");
        }

        $user = $this->userService->createUser($body['username'], $body['email'], $body['password']);

        if ($user === false) {
            $this->sendJson(false, null, 'No se pudo registrar el usuario (datos invalidos o ya en uso)');
        }

        $this->sendJson(true, array(
            'user_id' => (int) $user->id,
            'username' => $user->username,
        ));
    }

    /**
     * POST /user/login
     * Body JSON: {username, password}
     */
    public function actionLogin()
    {
        $body = $this->getJsonBody();
        $missing = $this->firstMissingField($body, array('username', 'password'));

        if ($missing !== null) {
            $this->sendJson(false, null, "Missing parameter: {$missing}");
        }

        $user = $this->userService->authenticateUser($body['username'], $body['password']);

        if ($user === false) {
            $this->sendJson(false, null, 'Credenciales invalidas');
        }

        // Guardar en sesión (ya arrancada en JsonController::beforeAction())
        $_SESSION['user_id'] = (int) $user->id;

        $this->sendJson(true, array(
            'user_id' => (int) $user->id,
            'username' => $user->username,
        )); 
    }
}
