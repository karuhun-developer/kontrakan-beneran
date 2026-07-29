<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Manager;

/**
 * Resolves the configured payment gateway driver. Add a new provider by adding
 * a createXxxDriver() method and a matching entry in config/payment.php.
 */
class PaymentGatewayManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default', 'null');
    }

    protected function createMidtransDriver(): PaymentGateway
    {
        return new MidtransGateway($this->config->get('payment.gateways.midtrans', []));
    }

    protected function createNullDriver(): PaymentGateway
    {
        return new NullGateway;
    }
}
