<?php

class BusinessAccount extends Account
{
    public function init() {
        parent::init();
        $this->account_type = 'business';
    }
}