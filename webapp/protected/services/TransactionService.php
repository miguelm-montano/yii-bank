<?php

/**
 * Responsabilidad: orquestar operaciones de negocio que mueven dinero
 * — depositar, retirar, transferir y revertir. No sabe COMO se
 * persiste una cuenta o una transaccion (eso es de los repositories,
 * inyectados por interfaz: Dependency Inversion) ni QUE formula de
 * interes usar (eso es de InterestCalculationStrategyInterface:
 * Strategy Pattern). Solo sabe orquestar el orden correcto de pasos y
 * las reglas de negocio que los gobiernan.
 */
class TransactionService
{
    /**
     * Simplificacion deliberada de esta etapa: Account todavia no
     * modela una tasa de interes propia (por tipo de cuenta, por
     * cliente, por promocion...), asi que se usa una tasa y un
     * periodo fijos para toda la app. Buen candidato para un ticket
     * futuro: mover esto a un atributo de Account o a una tabla de
     * tasas por account_type.
     */
    const DEFAULT_INTEREST_RATE_PERCENT = 2.0;
    const DEFAULT_INTEREST_PERIOD_MONTHS = 1;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /** @var AccountRepositoryInterface */
    private $accountRepository;

    /** @var InterestCalculationStrategyInterface */
    private $defaultInterestStrategy;

    /** @var TransactionObserverInterface[] */
    private $observers = [];

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        AccountRepositoryInterface $accountRepository,
        InterestCalculationStrategyInterface $defaultInterestStrategy
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->accountRepository = $accountRepository;
        $this->defaultInterestStrategy = $defaultInterestStrategy;
    }

    /**
     * Deposita en una cuenta y acredita el interes generado usando la
     * estrategia recibida (o la inyectada por defecto en el
     * constructor si no se pasa ninguna). Esto es el Strategy Pattern
     * en accion: el mismo metodo produce resultados distintos segun
     * el algoritmo que se le entregue (simple, compuesto...), sin que
     * TransactionService cambie una sola linea.
     *
     * @return array{transaction_id:int,new_balance:float,interest_earned:float}|false
     */
    public function deposit($accountId, $amount, ?InterestCalculationStrategyInterface $interestStrategy = null, $idempotencyKey = null)
    {
        if ($idempotencyKey !== null) {
            $existing = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

            if ($existing !== null) {
                $account = $this->accountRepository->findById($existing->to_account_id);

                return array(
                    'transaction_id' => (int) $existing->id,
                    'new_balance' => $account !== null ? (int) $account->balance : null,
                    // el interes original no se conserva en la transaccion guardada
                    'interest_earned' => 0,
                );
            }
        }

        if ($amount <= 0) {
            return false; // no se deposita un importe invalido
        }

        $account = $this->accountRepository->findById($accountId);

        if ($account === null || $account->status !== Account::STATUS_ACTIVE) {
            return false; // no se opera sobre una cuenta inexistente, frozen o closed
        }

        $strategy = $interestStrategy !== null ? $interestStrategy : $this->defaultInterestStrategy;

        // balance/amount son centimos enteros; el resultado de la
        // estrategia es fraccionario (formula de interes en base 100),
        // asi que se redondea a centimos antes de sumarlo o el balance
        // deja de ser entero y falla la validacion de Account::rules().
        $interest = (int) round($strategy->calculate(
            (float) $account->balance,
            self::DEFAULT_INTEREST_RATE_PERCENT,
            self::DEFAULT_INTEREST_PERIOD_MONTHS
        ));

        $newBalance = (int) $account->balance + (int) $amount + $interest;

        if (!$this->accountRepository->updateBalance($accountId, $newBalance)) {
            return false;
        }

        $transaction = new Transaction();
        $transaction->to_account_id = $accountId;
        $transaction->amount = $amount;
        $transaction->transaction_type = Transaction::TYPE_DEPOSIT;
        $transaction->status = Transaction::STATUS_COMPLETED;
        $transaction->description = sprintf('Deposito de %.2f + interes de %.2f', $amount, $interest);
        $transaction->idempotency_key = $idempotencyKey;

        if (!$this->transactionRepository->save($transaction)) {
            return false;
        }

        $this->notifyObservers($transaction);

        return array(
            'transaction_id' => (int) $transaction->id,
            'new_balance' => $newBalance,
            'interest_earned' => $interest,
        );
    }

    /**
     * Retira de una cuenta si las reglas de negocio lo permiten.
     *
     * NOTA de diseno: esta validacion duplica intencionalmente la de
     * AccountService::canWithdraw(). Tal como esta definida esta
     * etapa, TransactionService solo depende de
     * AccountRepositoryInterface, no de AccountService, asi que no
     * puede reutilizar esa logica sin crear una dependencia nueva.
     * Es una duplicacion conocida y un buen candidato a ticket futuro
     * (extraer una politica de retiro compartida entre ambos
     * servicios).
     *
     * @return array{transaction_id:int,new_balance:float}|false
     */
    public function withdraw($accountId, $amount, $idempotencyKey = null)
    {
        if ($idempotencyKey !== null) {
            $existing = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

            if ($existing !== null) {
                $account = $this->accountRepository->findById($existing->from_account_id);

                return array(
                    'transaction_id' => (int) $existing->id,
                    'new_balance' => $account !== null ? (int) $account->balance : null,
                );
            }
        }

        if ($amount <= 0) {
            return false;
        }

        $account = $this->accountRepository->findById($accountId);

        if ($account === null || $account->status !== Account::STATUS_ACTIVE) {
            return false;
        }

        if ((float) $account->balance < (float) $amount) {
            return false; // no se permite dejar la cuenta en negativo
        }

        if ($account->account_type === Account::TYPE_SAVINGS && (int) $account->withdrawal_count >= 3) {
            return false; // limite de retiros para cuentas de ahorro: maximo 3 por mes
        }

        $newBalance = (float) $account->balance - (float) $amount;

        if (!$this->accountRepository->updateBalance($accountId, $newBalance)) {
            return false;
        }

        $transaction = new Transaction();
        $transaction->from_account_id = $accountId;
        $transaction->amount = $amount;
        $transaction->transaction_type = Transaction::TYPE_WITHDRAWAL;
        $transaction->status = Transaction::STATUS_COMPLETED;
        $transaction->idempotency_key = $idempotencyKey;

        if (!$this->transactionRepository->save($transaction)) {
            return false;
        }

        $this->notifyObservers($transaction);

        $account->withdrawal_count = (int) $account->withdrawal_count + 1;
        $this->accountRepository->updateWithdrawalCount($accountId, $account->withdrawal_count);

        return array(
            'transaction_id' => (int) $transaction->id,
            'new_balance' => $newBalance,
        );
    }

    /**
     * Mueve dinero entre dos cuentas. Debe ser atomico: o se aplican
     * los dos cambios de saldo o no se aplica ninguno.
     *
     * NOTA de diseno (fuera de alcance en este paso, segun se indico
     * explicitamente): esto NO usa una transaccion de base de datos
     * real (CDbConnection::beginTransaction()). Lo de abajo es una
     * compensacion manual: si el segundo updateBalance falla, se
     * intenta revertir el primero a mano. Eso NO es atomico de
     * verdad — si el proceso muere entre los dos updateBalance, o hay
     * otra escritura concurrente sobre la misma cuenta, el sistema
     * puede quedar inconsistente. Es exactamente el tipo de bug que
     * se resuelve con un ticket real ("las transferencias a veces
     * descuadran el saldo") en la siguiente etapa.
     *
     * @return array{transaction_id:int,from_balance:float,to_balance:float}|false
     */
    public function transfer($fromAccountId, $toAccountId, $amount, $idempotencyKey = null)
    {
        if ($idempotencyKey !== null) {
            $existing = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

            if ($existing !== null) {
                $fromAccount = $this->accountRepository->findById($existing->from_account_id);
                $toAccount = $this->accountRepository->findById($existing->to_account_id);

                return array(
                    'transaction_id' => (int) $existing->id,
                    'from_balance' => $fromAccount !== null ? (int) $fromAccount->balance : null,
                    'to_balance' => $toAccount !== null ? (int) $toAccount->balance : null,
                );
            }
        }

        if ($fromAccountId === $toAccountId) {
            return false; // transferirse a si mismo no es una operacion valida
        }

        if ($amount <= 0) {
            return false;
        }

        $fromAccount = $this->accountRepository->findById($fromAccountId);
        $toAccount = $this->accountRepository->findById($toAccountId);

        if ($fromAccount === null || $toAccount === null) {
            return false;
        }

        if ($fromAccount->status !== Account::STATUS_ACTIVE || $toAccount->status !== Account::STATUS_ACTIVE) {
            return false;
        }

        if ((float) $fromAccount->balance < (float) $amount) {
            return false;
        }

        $originalFromBalance = (float) $fromAccount->balance;
        $newFromBalance = $originalFromBalance - (float) $amount;

        $transaction = Yii::app()->db->beginTransaction();

        try {
            if (!$this->accountRepository->updateBalance($fromAccountId, $newFromBalance)) {
                throw new \Exception('Failed to update from account balance');
            }

            $newToBalance = (float) $toAccount->balance + (float) $amount;

            if (!$this->accountRepository->updateBalance($toAccountId, $newToBalance)) {
                // Compensacion manual, no un rollback real: ver nota de diseno arriba.
                throw new \Exception('Failed to update to account balance');
            }

            // Una sola fila representa la transferencia: el modelo
            // Transaction ya tiene from_account_id y to_account_id, crear
            // dos filas duplicaria el mismo evento de negocio.
            $transactionModel = new Transaction();
            $transactionModel->from_account_id = $fromAccountId;
            $transactionModel->to_account_id = $toAccountId;
            $transactionModel->amount = $amount;
            $transactionModel->transaction_type = Transaction::TYPE_TRANSFER;
            $transactionModel->status = Transaction::STATUS_COMPLETED;
            $transactionModel->idempotency_key = $idempotencyKey;

            if (!$this->transactionRepository->save($transactionModel)) {
                $transaction->rollBack();
                throw new \Exception('Failed to save transaction record');
            }

            $transaction->commit();

            $this->notifyObservers($transactionModel);

            return array(
                'transaction_id' => (int) $transactionModel->id,
                'from_balance' => $newFromBalance,
                'to_balance' => $newToBalance,
            );
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false; // Re-throw the exception after rolling back
        }
    }

    /**
     * Revierte una transaccion completada, devolviendo el dinero
     * moviendolo en sentido contrario segun el tipo de movimiento, y
     * marca la transaccion original como reversed.
     *
     * Solo se puede revertir una transaccion completed: revertir una
     * pending no tiene sentido (nunca se aplico un cambio de saldo) y
     * revertir una ya reversed la revertiria dos veces.
     *
     * @return bool
     */
    public function reverseTransaction($transactionId)
    {
        $transaction = $this->transactionRepository->findById($transactionId);

        if ($transaction === null || $transaction->status !== Transaction::STATUS_COMPLETED) {
            return false;
        }

        $amount = (float) $transaction->amount;
        $ok = false;

        switch ($transaction->transaction_type) {
            case Transaction::TYPE_DEPOSIT:
                $account = $this->accountRepository->findById($transaction->to_account_id);
                $ok = $account !== null && $this->accountRepository->updateBalance(
                    $account->id,
                    (float) $account->balance - $amount
                );
                break;

            case Transaction::TYPE_WITHDRAWAL:
                $account = $this->accountRepository->findById($transaction->from_account_id);
                $ok = $account !== null && $this->accountRepository->updateBalance(
                    $account->id,
                    (float) $account->balance + $amount
                );
                break;

            case Transaction::TYPE_TRANSFER:
                $fromAccount = $this->accountRepository->findById($transaction->from_account_id);
                $toAccount = $this->accountRepository->findById($transaction->to_account_id);
                $ok = $fromAccount !== null && $toAccount !== null
                    && $this->accountRepository->updateBalance($fromAccount->id, (float) $fromAccount->balance + $amount)
                    && $this->accountRepository->updateBalance($toAccount->id, (float) $toAccount->balance - $amount);
                break;
        }

        if (!$ok) {
            return false;
        }

        $this->notifyObservers($transaction);

        return $this->transactionRepository->updateStatus($transactionId, Transaction::STATUS_REVERSED);
    }

    public function getHistory($accountId)
    {
        return $this->transactionRepository->findByAccountId($accountId, 50);
    }

        public function attachObserver(TransactionObserverInterface $observer)
    {
        $this->observers[] = $observer;
    }

    private function notifyObservers(Transaction $transaction)
    {
        foreach ($this->observers as $observer) {
         $observer->onTransactionExecuted($transaction);
        }
    }
}
