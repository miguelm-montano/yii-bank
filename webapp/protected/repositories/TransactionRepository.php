<?php

/**
 * Implementacion de TransactionRepositoryInterface usando CActiveRecord.
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    public function findById($id)
    {
        return Transaction::model()->findByPk($id);
    }

    /**
     * Query de negocio: el orden DESC por fecha no es un detalle
     * accidental, es requisito del dominio (el movimiento mas
     * reciente primero). Por eso vive con nombre propio en el
     * repository, no como un findAllByAttributes suelto en el service.
     */
    public function findByFromAccountId($accountId, $limit = 10)
    {
        return Transaction::model()->findAll(array(
            'condition' => 'from_account_id = :accountId',
            'params' => array(':accountId' => $accountId),
            'order' => 'created_at DESC',
            'limit' => $limit,
        ));
    }

    public function findByToAccountId($accountId, $limit = 10)
    {
        return Transaction::model()->findAll(array(
            'condition' => 'to_account_id = :accountId',
            'params' => array(':accountId' => $accountId),
            'order' => 'created_at DESC',
            'limit' => $limit,
        ));
    }

    /**
     * Query de negocio especifica de banca: un extracto de cuenta
     * necesita movimientos en ambas direcciones (dinero que entra y
     * que sale), algo que no tiene sentido pedirle a una tabla generica.
     */
    public function findByAccountId($accountId, $limit = 10)
    {
        return Transaction::model()->findAll(array(
            'condition' => 'from_account_id = :accountId OR to_account_id = :accountId',
            'params' => array(':accountId' => $accountId),
            'order' => 'created_at DESC',
            'limit' => $limit,
        ));
    }

    /**
     * CRUD generico: busqueda por una columna simple.
     */
    public function findByStatus($status)
    {
        return Transaction::model()->findAllByAttributes(array('status' => $status));
    }

    public function save(Transaction $transaction)
    {
        if (!$transaction->validate()) {
            return false;
        }

        try {
            return $transaction->save(false);
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.TransactionRepository');
            return false;
        }
    }

    /**
     * updateStatus es un metodo de negocio, no CRUD generico: una
     * transaccion cambia de estado como parte de su ciclo de vida
     * (pending -> completed / failed / reversed). No es "editar un
     * campo cualquiera", es una transicion de estado del dominio que
     * en el futuro podria llevar reglas (p.ej. no permitir pasar de
     * completed a pending) sin que el caller tenga que saberlo.
     */
    public function updateStatus($transactionId, $status)
    {
        $transaction = $this->findById($transactionId);

        if ($transaction === null) {
            return false;
        }

        $transaction->status = $status;

        if (!$transaction->validate(array('status'))) {
            return false;
        }

        try {
            return $transaction->save(false, array('status'));
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.TransactionRepository');
            return false;
        }
    }
}
