<?php

class CreditAccount extends Account
{
    public function init() {
        parent::init();
        $this->account_type = self::TYPE_CREDIT;
    }
}