<?php

/**
 * This is the model class for table "transactions".
 *
 * Representa una transaccion. Validacion basica aqui.
 * Logica de procesamiento en TransactionService.
 *
 * @property integer $id
 * @property integer $from_account_id
 * @property integer $to_account_id
 * @property integer $amount
 * @property string $transaction_type
 * @property string $status
 * @property string $description
 * @property string $created_at
 *
 * @property Account $fromAccount
 * @property Account $toAccount
 */
class Transaction extends CActiveRecord
{
    const TYPE_TRANSFER = 'transfer';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    /**
     * @param string $className
     * @return Transaction
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'transactions';
    }

    public function rules()
    {
        return array(
            array('amount, transaction_type', 'required'),
            array('from_account_id, to_account_id', 'numerical', 'integerOnly' => true),
            array('amount', 'numerical', 'integerOnly' => true, 'min' => 1),
            array('transaction_type', 'in', 'range' => array(self::TYPE_TRANSFER, self::TYPE_DEPOSIT, self::TYPE_WITHDRAWAL)),
            array('status', 'in', 'range' => array(self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_REVERSED)),
            array('description', 'length', 'max' => 255),
            array('id, from_account_id, to_account_id, created_at', 'safe', 'on' => 'search'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'from_account_id' => 'Cuenta origen',
            'to_account_id' => 'Cuenta destino',
            'amount' => 'Importe',
            'transaction_type' => 'Tipo',
            'status' => 'Estado',
            'description' => 'Descripcion',
            'created_at' => 'Creado el',
        );
    }

    public function relations()
    {
        return array(
            'fromAccount' => array(self::BELONGS_TO, 'Account', 'from_account_id'),
            'toAccount' => array(self::BELONGS_TO, 'Account', 'to_account_id'),
        );
    }
}
