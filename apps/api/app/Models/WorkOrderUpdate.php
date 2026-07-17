<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WorkOrderUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkOrderUpdate extends Model
{
    /** @use HasFactory<WorkOrderUpdateFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'work_order_id',
        'user_id',
        'type',
        'message',
        'old_status',
        'new_status',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
