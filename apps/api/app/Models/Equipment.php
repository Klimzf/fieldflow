<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'organization_id',
        'client_id',
        'site_id',
        'name',
        'type',
        'manufacturer',
        'model',
        'serial_number',
        'installed_at',
        'notes',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
