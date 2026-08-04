<?php

declare(strict_types=1);

namespace Tests\Feature\WorkOrders;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkOrderServiceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_service_report(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this
            ->get("/api/work-orders/{$workOrder->id}/service-report/download")
            ->assertUnauthorized();
    }

    public function test_member_can_download_service_report_for_own_organization_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create([
            'title' => 'PDF test work order',
            'description' => 'Work order for PDF generation test.',
            'status' => 'completed',
            'priority' => 'medium',
            'scheduled_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        $user = User::factory()->create();

        $workOrder->organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->get("/api/work-orders/{$workOrder->id}/service-report/download")
            ->assertOk();

        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );

        $this->assertStringContainsString(
            "service-report-work-order-{$workOrder->id}.pdf",
            (string) $response->headers->get('content-disposition'),
        );

        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_non_member_cannot_download_foreign_organization_service_report(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $user = User::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->get("/api/work-orders/{$workOrder->id}/service-report/download")
            ->assertNotFound();
    }
}
