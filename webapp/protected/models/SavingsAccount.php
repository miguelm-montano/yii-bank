<?php

class SavingsAccount extends Account
{
    public function init() {
        parent::init();
        $this->account_type = self::TYPE_SAVINGS;
    }
}