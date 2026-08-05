<?php

/**
 * This is the model class for table "users".
 *
 * Esta clase es responsable SOLO de validar datos de usuario.
 * La logica de negocio (crear usuario, encriptar contrasena) va en UserService.
 *
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $created_at
 * @property string $updated_at
 */
class User extends CActiveRecord
{
    /**
     * @param string $className
     * @return User
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return array(
            array('username, email, password_hash', 'required'),
            array('username', 'length', 'min' => 3, 'max' => 50),
            array('username', 'unique'),
            array('email', 'email'),
            array('email', 'length', 'max' => 100),
            array('email', 'unique'),
            array('password_hash', 'length', 'max' => 255),
            array('id, created_at, updated_at', 'safe', 'on' => 'search'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'username' => 'Usuario',
            'email' => 'Correo electronico',
            'password_hash' => 'Contrasena',
            'created_at' => 'Creado el',
            'updated_at' => 'Actualizado el',
        );
    }
}
