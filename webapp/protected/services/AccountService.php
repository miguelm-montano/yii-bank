<?php

/**
 * Responsabilidad: reglas de negocio sobre CUENTAS — que cuenta se
 * puede abrir, que numero de cuenta se le asigna, que cuenta puede
 * operar. Depende de AccountRepositoryInterface y
 * UserRepositoryInterface (no de las implementaciones concretas),
 * porque necesita confirmar datos de ambas entidades sin saber como
 * se persisten.
 */
class AccountService
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;

    /** @var UserRepositoryInterface */
    private $userRepository;

    public function __construct(AccountRepositoryInterface $accountRepository, UserRepositoryInterface $userRepository)
    {
        $this->accountRepository = $accountRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Logica de negocio: abrir una cuenta no es solo un INSERT.
     * - No se puede abrir una cuenta para un usuario que no existe.
     * - El numero de cuenta lo decide el sistema, no quien la crea,
     *   y debe ser unico (se reintenta hasta encontrar uno libre).
     * - Toda cuenta nueva nace con saldo 0 y estado active: no se
     *   permite fondear una cuenta en el momento de crearla, eso es
     *   un deposito posterior (TransactionService::deposit).
     *
     * @return Account|false la cuenta creada, o false si el usuario no existe o fallo el guardado
     */
    public function createAccount($userId, $accountType)
    {
        if ($this->userRepository->findById($userId) === null) {
            return false;
        }

        $account = new Account();
        $account->user_id = $userId;
        $account->account_number = $this->generateUniqueAccountNumber();
        $account->account_type = $accountType;
        $account->balance = 0;
        $account->status = Account::STATUS_ACTIVE;

        if (!$this->accountRepository->save($account)) {
            return false;
        }

        return $account;
    }

    /**
     * @return float|null el saldo actual, o null si la cuenta no existe
     */
    public function getBalance($accountId)
    {
        $account = $this->accountRepository->findById($accountId);

        if ($account === null) {
            return null;
        }

        return (float) $account->balance;
    }

    /**
     * Validaciones de negocio: que cuenta puede retirar que importe.
     * No basta con "hay saldo suficiente" — una cuenta frozen o closed
     * no opera aunque tenga saldo, y un importe invalido (0 o negativo)
     * nunca es una operacion permitida.
     *
     * @return bool
     */
    public function canWithdraw($accountId, $amount)
    {
        $account = $this->accountRepository->findById($accountId);

        if ($account === null) {
            return false;
        }

        if ($account->status !== Account::STATUS_ACTIVE) {
            return false;
        }

        $strategy = $this->getWithdrawalStrategy($account->account_type);
        return $strategy->canWithdraw($account, $amount);
    }

    public function getWithdrawalStrategy($accountType)
    {
        switch ($accountType) {
            case Account::TYPE_CHECKING:
                return new CheckingWithdrawalStrategy();
            case Account::TYPE_SAVINGS:
                return new SavingsWithdrawalStrategy();
            default:
                return new CreditWithdrawalStrategy(); // Default strategy for credit accounts
        }
    }

    /**
     * Listado usado por AccountController::actionList (modo
     * testing/debug, sin nocion de permisos todavia). Con $userId
     * filtra por usuario; sin el, devuelve todas las cuentas.
     *
     * @return Account[]
     */
    public function listAccounts($userId = null)
    {
        if ($userId !== null) {
            return $this->accountRepository->findAllByUserId($userId);
        }

        return $this->accountRepository->findAll();
    }

    /**
     * Genera un numero de cuenta con formato simple (ES + 10 digitos)
     * y garantiza unicidad consultando al repository antes de
     * devolverlo. Es responsabilidad del service, no del modelo:
     * Account::rules() solo puede validar que el numero YA asignado
     * sea unico en BD, no decidir como se genera uno nuevo.
     */
    private function generateUniqueAccountNumber()
    {
        do {
            $candidate = 'ES' . str_pad((string) mt_rand(0, 99999999), 10, '0', STR_PAD_LEFT);
        } while ($this->accountRepository->findByAccountNumber($candidate) !== null);

        return $candidate;
    }
}
