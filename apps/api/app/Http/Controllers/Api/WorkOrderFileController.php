<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderFile\StoreWorkOrderFileRequest;
use App\Http\Resources\WorkOrderFileResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderFile;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WorkOrderFileController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, WorkOrder $workOrder): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $files = $workOrder
            ->files()
            ->with('uploadedBy')
            ->latest()
            ->get();

        return WorkOrderFileResource::collection($files);
    }

    public function store(StoreWorkOrderFileRequest $request, WorkOrder $workOrder): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $uploadedFile = $request->file('file');

        $disk = 'local';
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension === '' ? '' : ".{$extension}");

        $path = $uploadedFile->storeAs(
            "work-orders/{$workOrder->id}",
            $filename,
            $disk,
        );

        $file = $workOrder
            ->files()
            ->create([
                'organization_id' => $workOrder->organization_id,
                'uploaded_by_id' => $user->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);

        return (new WorkOrderFileResource($file->load('uploadedBy')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function download(Request $request, WorkOrderFile $workOrderFile): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workOrderFile = $this->tenantAccess->findWorkOrderFileForUser($user, $workOrderFile);

        abort_unless(
            Storage::disk($workOrderFile->disk)->exists($workOrderFile->path),
            Response::HTTP_NOT_FOUND,
        );

        return Storage::disk($workOrderFile->disk)->download(
            $workOrderFile->path,
            $workOrderFile->original_name,
        );
    }

    public function destroy(Request $request, WorkOrderFile $workOrderFile): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workOrderFile = $this->tenantAccess->findWorkOrderFileForUser($user, $workOrderFile);

        if ($workOrderFile->uploaded_by_id !== $user->id) {
            $organization = $this->tenantAccess->findOrganizationForUser(
                $user,
                $workOrderFile->organization,
            );

            $this->tenantAccess->assertCanManageOrganization($organization);
        }

        Storage::disk($workOrderFile->disk)->delete($workOrderFile->path);

        $workOrderFile->delete();

        return response()->noContent();
    }
}
