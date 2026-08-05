<?php

/**
 * Implementacion de AccountRepositoryInterface usando CActiveRecord.
 */
class AccountRepository implements AccountRepositoryInterface
{
    /**
     * CRUD generico: busqueda por clave primaria.
     */
    public function findById($id)
    {
        return Account::model()->findByPk($id);
    }

    /**
     * CRUD generico: busqueda por una columna unica.
     */
    public function findByAccountNumber($accountNumber)
    {
        return Account::model()->findByAttributes(array('account_number' => $accountNumber));
    }

    /**
     * CRUD generico: todas las cuentas de un usuario, sin filtrar estado.
     */
    public function findAllByUserId($userId)
    {
        return Account::model()->findAllByAttributes(array('user_id' => $userId));
    }

    /**
     * CRUD generico: todas las cuentas, sin filtrar. Solo para
     * testing/debug (AccountController::actionList sin user_id).
     */
    public function findAll()
    {
        return Account::model()->findAll();
    }

    /**
     * Query de negocio: "activa" es un concepto del dominio bancario
     * (una cuenta frozen o closed no deberia listarse aqui aunque
     * exista en la tabla). Por eso tiene nombre propio en vez de dejar
     * que cada service arme su propio WHERE status = 'active'.
     */
    public function findActiveByUserId($userId)
    {
        return Account::model()->findAllByAttributes(array(
            'user_id' => $userId,
            'status' => Account::STATUS_ACTIVE,
        ));
    }

    public function save(Account $account)
    {
        if (!$account->validate()) {
            return false;
        }

        try {
            return $account->save(false);
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.AccountRepository');
            return false;
        }
    }

    public function delete($id)
    {
        $account = $this->findById($id);

        if ($account === null) {
            return false;
        }

        try {
            return (bool) $account->delete();
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.AccountRepository');
            return false;
        }
    }

    /**
     * updateBalance es un metodo especifico de negocio, no CRUD generico:
     * actualizar el saldo no es "cambiar un atributo cualquiera", es LA
     * operacion central de una cuenta. Aislarla aqui permite manana
     * anadir un UPDATE atomico (balance = balance + X) o locking
     * optimista sin que TransactionService, que es quien la llama,
     * tenga que cambiar una sola linea.
     */
    public function updateBalance($accountId, $newBalance)
    {
        $account = $this->findById($accountId);

        if ($account === null) {
            return false;
        }

        $account->balance = $newBalance;

        if (!$account->validate(array('balance'))) {
            return false;
        }

        try {
            return $account->save(false, array('balance'));
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.AccountRepository');
            return false;
        }
    }
}
