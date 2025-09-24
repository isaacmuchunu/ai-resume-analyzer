<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Handle Stripe webhooks
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            Log::warning('Stripe webhook missing signature');
            return response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->paymentService->handleWebhook(
                json_decode($payload, true),
                $signature
            );

            if ($result['success']) {
                return response('OK', Response::HTTP_OK);
            }

            Log::error('Stripe webhook processing failed', [
                'result' => $result,
                'payload_preview' => substr($payload, 0, 200)
            ]);

            return response('Webhook processing failed', Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'payload_preview' => substr($payload, 0, 200)
            ]);

            return response('Webhook error', Response::HTTP_BAD_REQUEST);
        }
    }
}