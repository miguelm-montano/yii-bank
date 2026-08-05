<?php

/**
 * Implementacion de UserRepositoryInterface usando CActiveRecord.
 *
 * Toda consulta relacionada con usuarios vive aqui, no en controllers
 * ni services: si manana cambia el motor de BD o el ORM, solo se
 * toca esta clase, nadie que dependa de UserRepositoryInterface se entera.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * CRUD generico: busqueda por clave primaria, sin reglas de negocio.
     */
    public function findById($id)
    {
        return User::model()->findByPk($id);
    }

    /**
     * CRUD generico: busqueda por una columna unica.
     */
    public function findByUsername($username)
    {
        return User::model()->findByAttributes(array('username' => $username));
    }

    /**
     * CRUD generico: busqueda por una columna unica.
     */
    public function findByEmail($email)
    {
        return User::model()->findByAttributes(array('email' => $email));
    }

    public function findAll()
    {
        return User::model()->findAll();
    }

    /**
     * Valida antes de persistir y absorbe cualquier excepcion de BD:
     * el repository es quien conoce el detalle de infraestructura
     * (CDbException); a quien lo llama solo le interesa un bool.
     */
    public function save(User $user)
    {
        if (!$user->validate()) {
            return false;
        }

        try {
            return $user->save(false);
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.UserRepository');
            return false;
        }
    }

    public function delete($id)
    {
        $user = $this->findById($id);

        if ($user === null) {
            return false;
        }

        try {
            return (bool) $user->delete();
        } catch (CDbException $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'application.repositories.UserRepository');
            return false;
        }
    }
}
