<?php

namespace App\Services\Billing;

class InvoiceNumberGenerator
{
    public function make(string $prefix = 'INV'): string
    {
        return sprintf('%s-%s-%04d', $prefix, now()->format('Ymd'), random_int(1, 9999));
    }
}

