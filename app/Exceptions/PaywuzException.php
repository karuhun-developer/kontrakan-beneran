<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when a Paywuz API call fails. Surfaces through the same Throwable path
 * InitiateGatewayPayment already handles (marks the payment failed, rethrows).
 */
class PaywuzException extends Exception {}
