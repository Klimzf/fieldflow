<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WorkOrderFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkOrderFile extends Model
{
    /** @use HasFactory<WorkOrderFileFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'work_order_id',
        'uploaded_by_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
