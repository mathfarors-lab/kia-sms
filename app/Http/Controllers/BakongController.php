<?php

namespace App\Http\Controllers;

use App\Services\BakongWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BakongController extends Controller
{
    public function __construct(private BakongWebhookService $webhookService) {}

    public function webhook(Request $request)
    {
        // This push-webhook surface is disabled when the system uses the pull/polling model.
        // Set BAKONG_DISABLE_WEBHOOK=false only when your bank/PSP provides a real PUSH webhook
        // AND polling is not active. Never run both paths as live payment routes simultaneously.
        if (config('services.bakong.disable_webhook', true)) {
            abort(404);
        }

        // Capture raw body BEFORE $request->all() so signature verification
        // uses the exact bytes Bakong signed, not the re-encoded version.
        $rawBody = $request->getContent();
        $payload = $request->all();
        $payload['rawBody']   = $rawBody;
        // Header name is configurable — match it to the provider's actual spec.
        $sigHeader            = config('services.bakong.signature_header', 'X-Bakong-Signature');
        $payload['signature'] = $request->header($sigHeader, '');

        Log::info('Bakong webhook received', ['ref' => $payload['transaction_id'] ?? null]);

        try {
            $this->webhookService->handle($payload);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e; // Let Laravel handle abort() responses
        } catch (\Exception $e) {
            Log::error('Bakong webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'processing_error'], 500);
        }

        return response()->json(['received' => true]);
    }
}
