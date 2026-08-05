<?php

/**
 * Contrato: operaciones sobre cuentas.
 *
 * findById / findByAccountNumber / findAllByUserId son CRUD generico:
 * cualquier tabla con una FK y una columna unica los necesitaria.
 *
 * findActiveByUserId y updateBalance NO son genericos: "activa" y
 * "actualizar saldo" son conceptos del dominio bancario, no operaciones
 * de tabla. Se declaran explicitos aqui para que quien implemente la
 * interfaz sepa que son reglas de negocio, no un simple find/update.
 */
interface AccountRepositoryInterface
{
    /** @return Account|null */
    public function findById($id);

    /** @return Account|null */
    public function findByAccountNumber($accountNumber);

    /** CRUD generico: todas las cuentas del usuario, sin filtrar estado. @return Account[] */
    public function findAllByUserId($userId);

    /** Query de negocio: solo cuentas con status = active. @return Account[] */
    public function findActiveByUserId($userId);

    /** @return bool */
    public function save(Account $account);

    /** @return bool */
    public function delete($id);

    /**
     * Query de negocio: actualizar el saldo no es "cambiar un atributo
     * cualquiera", es LA operacion central de una cuenta bancaria. Se
     * aisla en su propio metodo para poder controlar como se persiste
     * (validacion de saldo, futura concurrencia, etc.) sin que el
     * caller conozca ese detalle.
     *
     * @return bool
     */
    public function updateBalance($accountId, $newBalance);
}
