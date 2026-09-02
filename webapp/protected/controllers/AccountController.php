<?php

/**
 * Responsabilidad: mapear HTTP <-> AccountService. Separacion clara:
 * este controller solo interpreta la peticion y formatea la
 * respuesta; AccountService decide que numero de cuenta se asigna,
 * que estado inicial tiene, y que cuenta puede operar.
 */
class AccountController extends JsonController
{
    /** @var AccountService */
    private $accountService;
    private $accountRepository;

    /**
     * Ver la nota en UserController::__construct sobre por que el
     * servicio es un parametro opcional: es el composition root de
     * este controller, no un contenedor de DI real (Yii 1.1 no trae uno).
     */
    public function __construct($id, $module = null, ?AccountService $accountService = null)
    {
        parent::__construct($id, $module);

        $accountRepository = new AccountRepository();
        $this->accountRepository = $accountRepository;

        $this->accountService = $accountService !== null
            ? $accountService
            : new AccountService($accountRepository, new UserRepository());
    }

    /**
     * POST /account/create
     * Body JSON: {user_id, account_type}
     */
    public function actionCreate()
    {
        $body = $this->getJsonBody();
        $missing = $this->firstMissingField($body, array('user_id', 'account_type'));

        if ($missing !== null) {
            $this->sendJson(false, null, "Missing parameter: {$missing}");
        }

        //Verificar autorización
        $authenticatedUserId = $this->getAuthenticatedUserId();
        if ((int) $body['user_id'] !== $authenticatedUserId) {
            $this->sendJson(false, null, 'Unauthorized: cannot create account for another user', 403);
        }

        $account = $this->accountService->createAccount($body['user_id'], $body['account_type']);

        if ($account === false) {
            $this->sendJson(false, null, 'No se pudo crear la cuenta (usuario inexistente o datos invalidos)');
        }

        $this->sendJson(true, array(
            'account_id' => (int) $account->id,
            'account_number' => $account->account_number,
            'balance' => $this->toEuros($account->balance),
        ));
    }

    /**
     * GET /account/<id>/balance
     * $id llega por binding automatico de Yii desde la regla de
     * urlManager (o desde ?id=X si se llama sin pretty URL).
     */
    public function actionGetBalance($id)
    {
        $authenticatedUserId = $this->getAuthenticatedUserId();
        $account = $this->accountRepository->findById($id);

        if ($account === null) {
            $this->sendJson(false, null, 'Account not found', 404);
        }

        if ((int) $account->user_id !== $authenticatedUserId) {
            $this->sendJson(false, null, 'Unauthorized: cannot access another user account', 403);
        }

        $balance = $this->accountService->getBalance($id);

        $this->sendJson(true, array(
            'account_id' => (int) $id,
            'balance' => $this->toEuros($balance),
        ));
    }

    /**
     * GET /account/list?user_id=X (opcional)
     *
     * Sin user_id devuelve todas las cuentas: es un modo de
     * testing/debug, tal como pide el enunciado de este paso. Todavia
     * no existe ninguna nocion de "quien esta autenticado" ni
     * permisos (eso queda fuera de alcance aqui a proposito).
     */
    public function actionList()
    {
        $authenticatedUserId = $this->getAuthenticatedUserId();
    
        // Solo mostrar cuentas del usuario autenticado
        $accounts = $this->accountService->listAccounts($authenticatedUserId);

        $data = array();
        foreach ($accounts as $account) {
            $data[] = array(
                'account_id' => (int) $account->id,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'balance' => $this->toEuros($account->balance),
                'status' => $account->status,
            );
        }

        $this->sendJson(true, $data);
    }

    /**
     * Frontera API: balance se guarda en centimos enteros (ver
     * Account::rules()), pero el cliente de la API sigue hablando en
     * euros con decimales.
     */
    private function toEuros($cents)
    {
        return round($cents / 100, 2);
    }
}
