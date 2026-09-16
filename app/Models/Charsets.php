<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\StringHelper;

class Charsets extends Model
{
    use StaticTableName;

    protected $table = 'charsets';

    public $timestamps = false;

    /**
     * Build the localized charset options used by settings forms.
     *
     * @return array
     */
    public static function getOption(): array
    {
       return Charsets::orderBy('charset')
           ->get()
           ->pluck('charset')
           ->mapWithKeys(fn (string $charset) => [
               $charset => StringHelper::charsetList($charset),
           ])
           ->toArray();
    }
}
