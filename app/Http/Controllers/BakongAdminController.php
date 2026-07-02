<?php

namespace App\Http\Controllers;

use App\Models\BakongCallback;
use App\Models\BakongFailedVerification;
use App\Services\BakongWebhookService;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;

class BakongAdminController extends Controller
{
    public function __construct(private BakongWebhookService $webhookService) {}

    public function failedList()
    {
        Gate::authorize(Permissions::SETTINGS_MANAGE);

        $recent       = BakongFailedVerification::latest()->paginate(50);
        $count24h     = BakongFailedVerification::where('created_at', '>=', now()->subDay())->count();
        $count7d      = BakongFailedVerification::where('created_at', '>=', now()->subWeek())->count();
        $flagged      = BakongCallback::whereNotNull('flag_reason')->latest()->paginate(25, ['*'], 'flagged_page');
        $flaggedCount = BakongCallback::whereNotNull('flag_reason')->count();

        return view('admin.bakong-failed', compact('recent', 'count24h', 'count7d', 'flagged', 'flaggedCount'));
    }

    public function replay(BakongFailedVerification $verification)
    {
        Gate::authorize(Permissions::SETTINGS_MANAGE);

        // Reconstruct the payload for re-verification using stored raw bytes.
        $replayPayload                = $verification->raw_payload ?? [];
        $replayPayload['rawBody']     = $verification->raw_body ?? json_encode($verification->raw_payload);
        $replayPayload['signature']   = $verification->received_signature ?? '';

        $ref = $this->webhookService->extractRef($replayPayload);
        if (!$ref) {
            return back()->withErrors(['Cannot replay: no transaction reference in stored payload.']);
        }

        [$sigValid, $reason] = $this->webhookService->verifySignature($replayPayload);

        if (!$sigValid) {
            $verification->update([
                'replayed_at'   => now(),
                'replay_result' => "still-invalid:{$reason}",
            ]);
            return back()->with('warning', "Signature still fails ({$reason}). Check BAKONG_WEBHOOK_SECRET and signing scheme in config.");
        }

        $callback = $this->webhookService->applyVerifiedCallback($ref, $replayPayload);
        $result   = ($callback && $callback->wasRecentlyCreated) ? 'applied' : 'duplicate';

        $verification->update(['replayed_at' => now(), 'replay_result' => $result]);

        return back()->with(
            'success',
            $result === 'applied'
                ? 'Payment applied successfully.'
                : 'Already applied — callback was a duplicate, no action taken.'
        );
    }
}
