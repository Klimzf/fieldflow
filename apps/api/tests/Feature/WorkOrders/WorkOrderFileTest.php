<?php

declare(strict_types=1);

namespace Tests\Feature\WorkOrders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WorkOrderFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_work_order_files(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this
            ->getJson("/api/work-orders/{$workOrder->id}/files")
            ->assertUnauthorized();
    }

    public function test_member_can_list_files_from_own_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        $file = WorkOrderFile::factory()
            ->forWorkOrder($workOrder)
            ->uploadedBy($user)
            ->create([
                'original_name' => 'manual.pdf',
                'mime_type' => 'application/pdf',
                'size' => 2048,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/files")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file->id)
            ->assertJsonPath('data.0.original_name', 'manual.pdf')
            ->assertJsonPath('data.0.uploaded_by.id', $user->id);
    }

    public function test_non_member_cannot_list_files_from_foreign_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/files")
            ->assertNotFound();
    }

    public function test_member_can_upload_file_to_own_work_order(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        $uploadedFile = UploadedFile::fake()->create('photo.jpg', 512, 'image/jpeg');

        $this
            ->actingAs($user, 'sanctum')
            ->post("/api/work-orders/{$workOrder->id}/files", [
                'file' => $uploadedFile,
            ])
            ->assertCreated()
            ->assertJsonPath('data.original_name', 'photo.jpg')
            ->assertJsonPath('data.uploaded_by.id', $user->id);

        $file = WorkOrderFile::query()->firstOrFail();

        Storage::disk('local')->assertExists($file->path);

        $this->assertDatabaseHas('work_order_files', [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
            'uploaded_by_id' => $user->id,
            'original_name' => 'photo.jpg',
        ]);
    }

    public function test_non_member_cannot_upload_file_to_foreign_work_order(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $uploadedFile = UploadedFile::fake()->create('photo.jpg', 512, 'image/jpeg');

        $this
            ->actingAs($user, 'sanctum')
            ->post("/api/work-orders/{$workOrder->id}/files", [
                'file' => $uploadedFile,
            ])
            ->assertNotFound();
    }

    public function test_member_can_download_file_from_own_work_order(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        Storage::disk('local')->put('work-orders/test-file.txt', 'test content');

        $file = WorkOrderFile::factory()
            ->forWorkOrder($workOrder)
            ->uploadedBy($user)
            ->create([
                'disk' => 'local',
                'path' => 'work-orders/test-file.txt',
                'original_name' => 'test-file.txt',
                'mime_type' => 'text/plain',
                'size' => 12,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->get("/api/work-order-files/{$file->id}/download")
            ->assertOk();
    }

    public function test_non_member_cannot_download_foreign_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $file = WorkOrderFile::factory()->create();

        Storage::disk('local')->put($file->path, 'test content');

        $this
            ->actingAs($user, 'sanctum')
            ->get("/api/work-order-files/{$file->id}/download")
            ->assertNotFound();
    }

    public function test_uploader_can_delete_own_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        Storage::disk('local')->put('work-orders/photo.jpg', 'test content');

        $file = WorkOrderFile::factory()
            ->forWorkOrder($workOrder)
            ->uploadedBy($user)
            ->create([
                'disk' => 'local',
                'path' => 'work-orders/photo.jpg',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->deleteJson("/api/work-order-files/{$file->id}")
            ->assertNoContent();

        Storage::disk('local')->assertMissing('work-orders/photo.jpg');

        $this->assertDatabaseMissing('work_order_files', [
            'id' => $file->id,
        ]);
    }

    public function test_admin_can_delete_another_users_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($admin, 'admin');
        $uploader = $this->createOrganizationMember($workOrder->organization, 'technician');

        Storage::disk('local')->put('work-orders/admin-delete.jpg', 'test content');

        $file = WorkOrderFile::factory()
            ->forWorkOrder($workOrder)
            ->uploadedBy($uploader)
            ->create([
                'disk' => 'local',
                'path' => 'work-orders/admin-delete.jpg',
            ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/work-order-files/{$file->id}")
            ->assertNoContent();

        Storage::disk('local')->assertMissing('work-orders/admin-delete.jpg');
    }

    public function test_technician_cannot_delete_another_users_file(): void
    {
        Storage::fake('local');

        $technician = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($technician, 'technician');
        $uploader = $this->createOrganizationMember($workOrder->organization, 'technician');

        Storage::disk('local')->put('work-orders/foreign.jpg', 'test content');

        $file = WorkOrderFile::factory()
            ->forWorkOrder($workOrder)
            ->uploadedBy($uploader)
            ->create([
                'disk' => 'local',
                'path' => 'work-orders/foreign.jpg',
            ]);

        $this
            ->actingAs($technician, 'sanctum')
            ->deleteJson("/api/work-order-files/{$file->id}")
            ->assertForbidden();

        Storage::disk('local')->assertExists('work-orders/foreign.jpg');
    }

    private function createWorkOrderForUser(User $user, string $role): WorkOrder
    {
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => $role,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $site = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
        ]);

        return WorkOrder::factory()
            ->forSite($site)
            ->create();
    }

    private function createOrganizationMember(
        Organization $organization,
        string $role = 'technician',
    ): User {
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => $role,
        ]);

        return $user;
    }
}
