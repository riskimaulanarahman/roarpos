<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierClosureReport;
use App\Models\CashierSession;
use App\Services\CashierSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierSessionController extends Controller
{
    public function __construct(private CashierSummaryService $summaryService)
    {
    }
    public function status(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $activeSession = null;
        if ($userId) {
            $activeSession = CashierSession::where('user_id', $userId)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();
        }

        return response()->json([
            'message' => 'Cashier status retrieved',
            'data' => [
                'status' => $activeSession ? 'open' : 'closed',
                'session' => $activeSession,
            ],
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $reports = CashierClosureReport::with(['session' => function ($query) {
            $query->select([
                'id',
                'user_id',
                'opening_balance',
                'closing_balance',
                'opened_at',
                'closed_at',
                'remarks',
                'status',
            ]);
        }])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->get();

        return response()->json([
            'message' => 'Cashier closure reports retrieved',
            'data' => $reports,
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'opening_balance' => ['required', 'numeric', 'gte:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $existing = CashierSession::where('user_id', $userId)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Cashier session already open',
                'data' => $existing,
            ], 409);
        }

        try {
            $session = DB::transaction(function () use ($payload, $userId, $user) {
                return CashierSession::create([
                    'user_id' => $userId,
                    'opening_balance' => round($payload['opening_balance'], 2),
                    'opened_at' => now(),
                    'opened_by' => $user?->id,
                    'remarks' => $payload['remarks'] ?? null,
                    'status' => 'open',
                ]);
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Failed to open cashier session',
            ], 500);
        }

        return response()->json([
            'message' => 'Cashier session opened',
            'data' => $session,
        ], 201);
    }

    public function close(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'closing_balance' => ['required', 'numeric', 'gte:0'],
            'remarks' => ['nullable', 'string'],
            'timezone' => ['nullable', 'timezone'],
            'timezone_offset' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $session = CashierSession::where('user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'No active cashier session',
            ], 409);
        }

        $timezone = $payload['timezone'] ?? null;
        $timezoneOffset = $payload['timezone_offset'] ?? null;

        try {
            [$session, $summary, $report] = DB::transaction(function () use ($session, $payload, $user, $timezone, $timezoneOffset) {
                $session->update([
                    'closing_balance' => round($payload['closing_balance'], 2),
                    'closed_at' => now(),
                    'closed_by' => $user?->id,
                    'remarks' => $payload['remarks'] ?? $session->remarks,
                    'status' => 'closed',
                ]);

                $session->refresh();
                if ($timezone) {
                    $session->setAttribute('timezone', $timezone);
                }
                if ($timezoneOffset !== null) {
                    $session->setAttribute('timezone_offset', (int) $timezoneOffset);
                }

                $summary = $this->summaryService->generate($session, $timezone, $timezoneOffset);
                $report = $this->summaryService->createReport(
                    $session,
                    $summary,
                    $user?->email
                );

                return [$session, $summary, $report];
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Failed to close cashier session',
            ], 500);
        }

        $this->summaryService->dispatchEmail($report);

        $sessionResponse = $session->fresh();
        $sessionResponse->setAttribute('timezone', $summary['session']['timezone'] ?? null);
        $sessionResponse->setAttribute('timezone_offset', $summary['session']['timezone_offset'] ?? null);

        return response()->json([
            'message' => 'Cashier session closed',
            'data' => [
                'session' => $sessionResponse,
                'summary' => $summary,
                'report_id' => $report->id,
            ],
        ]);
    }

    public function resendEmail(Request $request, CashierClosureReport $report): JsonResponse
    {
        $user = $request->user();

        if (!$user || $report->user_id !== $user->id) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        $payload = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $email = $payload['email'] ?? $report->email_to;

        if (!$email) {
            return response()->json([
                'message' => 'Alamat email tidak tersedia untuk laporan ini',
            ], 422);
        }

        $report->forceFill([
            'email_to' => $email,
            'email_status' => 'pending',
            'emailed_at' => null,
            'email_error' => null,
        ])->save();

        $this->summaryService->dispatchEmail($report);

        return response()->json([
            'message' => 'Laporan akan dikirim ke email',
            'data' => [
                'report_id' => $report->id,
                'email_to' => $email,
            ],
        ]);
    }
}
