<?php

namespace App\Interfaces;

interface Payable
{
    public function getAmount(): float;
    public function getDescription(): string;
    public function getTransactionId(): string;
}
