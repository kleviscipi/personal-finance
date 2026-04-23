<?php

namespace App\Http\Controllers;

use App\Services\AccountExportService;
use App\Support\ActiveAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountExportController extends Controller
{
    public function __construct(
        private AccountExportService $accountExportService
    ) {}

    public function download(Request $request): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);

        if (! $account) {
            return redirect()->route('accounts.create');
        }

        $referenceMonth = $this->resolveReferenceMonth($request);
        $contents = $this->accountExportService->buildWorkbook($account, $request->user(), $referenceMonth);
        $filename = $this->accountExportService->buildFileName($account, $referenceMonth);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
        ]);
    }

    private function resolveReferenceMonth(Request $request): Carbon
    {
        $currentMonth = now()->startOfMonth();
        $month = (string) $request->query('month', '');

        if ($month === '' || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return $currentMonth;
        }

        try {
            $referenceMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return $currentMonth;
        }

        if ($referenceMonth->greaterThan($currentMonth)) {
            return $currentMonth;
        }

        return $referenceMonth;
    }
}
