<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\SettleGatewayPayment;
use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidGatewayNotificationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayWebhookController extends Controller
{
    /**
     * Receive an unauthenticated settlement callback from the active gateway,
     * verify it, and apply it to the matching pending payment.
     */
    public function handle(Request $request, PaymentGateway $gateway, SettleGatewayPayment $action): JsonResponse
    {
        try {
            $notification = $gateway->parseNotification($request);
        } catch (InvalidGatewayNotificationException) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $action->handle($notification);

        return response()->json(['message' => 'ok']);
    }
}
