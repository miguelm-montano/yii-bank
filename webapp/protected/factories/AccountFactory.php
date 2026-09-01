<?php

class AccountFactory
{
    public static function create($accountType)
    {
        switch ($accountType) {
            case Account::TYPE_SAVINGS:
                return new SavingsAccount();
            case Account::TYPE_CHECKING:
                return new CheckingAccount();
            default:
                return new CreditAccount(); // Default to credit account for unknown types
        }
    }
}