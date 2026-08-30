<?php

interface TransactionObserverInterface
{
    /**
     * Called after a transaction is executed successfully
     * @param Transaction $transaction
     */
    public function onTransactionExecuted(Transaction $transaction);
}
