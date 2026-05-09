<?php

declare(strict_types=1);

namespace Admin\Models;

use Framework\Database\Model;

class PluginSetting extends Model
{
    protected string $table = 'plugin_settings';

    protected string $primaryKey = 'name';

    public $incrementing = false;

    protected string $keyType = 'string';

    protected array $fillable = ['name', 'enabled'];

    protected array $casts = [
        'enabled' => 'boolean',
    ];
}