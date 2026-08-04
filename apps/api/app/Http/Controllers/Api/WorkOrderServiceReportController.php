<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class WorkOrderServiceReportController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function download(Request $request, WorkOrder $workOrder): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $workOrder->load([
            'client',
            'site',
            'equipment',
            'assignments.user',
            'checklistItems.completedBy',
            'updates.user',
            'files.uploadedBy',
        ]);

        $pdf = Pdf::loadView('pdf.work-orders.service-report', [
            'workOrder' => $workOrder,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download("service-report-work-order-{$workOrder->id}.pdf");
    }
}
