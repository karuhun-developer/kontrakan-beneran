<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a gateway webhook payload fails signature or integrity checks.
 */
class InvalidGatewayNotificationException extends RuntimeException {}
