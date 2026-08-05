<?php

/**
 * Contrato: que operaciones puede hacer cualquier servicio con usuarios.
 *
 * Un UserService (o un controller, en pasos posteriores) programa contra
 * esta interfaz, no contra UserRepository. Eso es Dependency Inversion:
 * el codigo de alto nivel no depende de un detalle de implementacion
 * (CActiveRecord, SQL concreto), depende de una abstraccion.
 */
interface UserRepositoryInterface
{
    /**
     * CRUD generico. @return User|null
     */
    public function findById($id);

    /**
     * CRUD generico con condicion sobre una columna unica.
     * @return User|null
     */
    public function findByUsername($username);

    /**
     * CRUD generico con condicion sobre una columna unica.
     * @return User|null
     */
    public function findByEmail($email);

    /**
     * CRUD generico. @return User[]
     */
    public function findAll();

    /**
     * @return bool true si se guardo correctamente, false si fallo
     *               la validacion o la persistencia.
     */
    public function save(User $user);

    /**
     * @return bool true si se elimino, false si no existia o fallo.
     */
    public function delete($id);
}
