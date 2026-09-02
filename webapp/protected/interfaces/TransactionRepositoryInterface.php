<?php

/**
 * Contrato: operaciones sobre transacciones.
 *
 * findByFromAccountId y findByToAccountId son queries especificas de
 * banca: no existen en una tabla generica, existen porque una
 * transaccion bancaria tiene direccion (de donde sale, a donde entra)
 * y necesitamos poder consultar cada lado por separado o combinado
 * (findByAccountId, para el extracto de una cuenta).
 */
interface TransactionRepositoryInterface
{
    /** @return Transaction|null */
    public function findById($id);

    /** Query de negocio: movimientos donde la cuenta es el origen. @return Transaction[] */
    public function findByFromAccountId($accountId, $limit = 10);

    /** Query de negocio: movimientos donde la cuenta es el destino. @return Transaction[] */
    public function findByToAccountId($accountId, $limit = 10);

    /** Query de negocio: movimientos en ambas direcciones (extracto). @return Transaction[] */
    public function findByAccountId($accountId, $limit = 10);

    /** CRUD generico con condicion sobre una columna. @return Transaction[] */
    public function findByStatus($status);

    /** @return bool */
    public function save(Transaction $transaction);

    /** Idempotencia: la transaccion ya procesada con esa key, si existe. @return Transaction|null */
    public function findByIdempotencyKey($idempotencyKey);

    /**
     * Query de negocio: una transaccion cambia de estado como parte de
     * su ciclo de vida (pending -> completed/failed/reversed). No es
     * "editar cualquier campo", es una transicion de estado del dominio.
     *
     * @return bool
     */
    public function updateStatus($transactionId, $status);
}
