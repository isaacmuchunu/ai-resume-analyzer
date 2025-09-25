<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhooks
     */
    public function stripe(Request $request): JsonResponse
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');
            
            // Log webhook for debugging
            Log::info('Stripe webhook received', [
                'payload_length' => strlen($payload),
                'signature' => $signature,
            ]);

            // Here you would verify the webhook signature and process the event
            // For now, we'll just acknowledge receipt
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle Anthropic webhooks
     */
    public function anthropic(Request $request): JsonResponse
    {
        try {
            $payload = $request->getContent();
            
            // Log webhook for debugging
            Log::info('Anthropic webhook received', [
                'payload_length' => strlen($payload),
                'headers' => $request->headers->all(),
            ]);

            // Here you would process Anthropic-specific webhook events
            // For now, we'll just acknowledge receipt
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
            ]);

        } catch (\Exception $e) {
            Log::error('Anthropic webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }
}