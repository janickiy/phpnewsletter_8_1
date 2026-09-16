<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\StringHelper;

class Settings extends Model
{
    use StaticTableName;

    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Normalize setting names to uppercase underscore-separated keys before storage.
     *
     * @param string $name
     * @return void
     */
    public function setNameAttribute(string $name): void
    {
        $this->attributes['name'] = str_replace(' ', '_', strtoupper($name));
    }

    /**
     * Create or update one setting, applying defaults required for special keys.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string $key, mixed $value): void
    {
        if ($value === null) {
            $value = '';
        }

        if ($key === 'URL' && trim((string) $value) === '') {
            $value = StringHelper::getUrl();
        }

        self::query()->updateOrCreate(
            ['name' => $key],
            ['value' => $value]
        );
    }
}
