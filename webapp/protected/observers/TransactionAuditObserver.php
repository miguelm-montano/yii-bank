<?php

class TransactionAuditObserver implements TransactionObserverInterface
{
    public function onTransactionExecuted(Transaction $transaction)
    {
        $auditLog = new AuditLog();
        $auditLog->transaction_id = $transaction->id;
        $auditLog->action = $transaction->transaction_type;
        $auditLog->timestamp = time();
        $auditLog->save();
    }
}
