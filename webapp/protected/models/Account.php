<?php

/**
 * This is the model class for table "accounts".
 *
 * Entidad pura. NO implementa logica de transacciones aqui.
 * Eso va en TransactionService.
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $account_number
 * @property string $account_type
 * @property string $balance
 * @property string $status
 * @property string $currency
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 */
class Account extends CActiveRecord
{
    const TYPE_CHECKING = 'checking';
    const TYPE_SAVINGS = 'savings';

    const STATUS_ACTIVE = 'active';
    const STATUS_FROZEN = 'frozen';
    const STATUS_CLOSED = 'closed';

    /**
     * @param string $className
     * @return Account
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'accounts';
    }

    public function rules()
    {
        return array(
            array('user_id, account_number, account_type', 'required'),
            array('user_id', 'numerical', 'integerOnly' => true),
            array('account_number', 'length', 'max' => 34),
            array('account_number', 'unique'),
            array('account_type', 'in', 'range' => array(self::TYPE_CHECKING, self::TYPE_SAVINGS)),
            array('balance', 'numerical'),
            array('status', 'in', 'range' => array(self::STATUS_ACTIVE, self::STATUS_FROZEN, self::STATUS_CLOSED)),
            array('currency', 'length', 'is' => 3),
            array('id, created_at, updated_at', 'safe', 'on' => 'search'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'user_id' => 'Usuario',
            'account_number' => 'Numero de cuenta',
            'account_type' => 'Tipo de cuenta',
            'balance' => 'Saldo',
            'status' => 'Estado',
            'currency' => 'Moneda',
            'created_at' => 'Creado el',
            'updated_at' => 'Actualizado el',
        );
    }

    public function relations()
    {
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
        );
    }
}
