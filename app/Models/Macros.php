<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Macros extends Model
{
    use StaticTableName;

    public const TYPE_URL = 1;
    public const TYPE_EMAIL = 2;
    public const TYPE_HASH_TAGS = 3;
    public const TYPE_TAGS = 4;
    public const TYPE_WRAP_PHRASE = 5;

    protected $table = 'macros';

    protected $fillable = [
        'name',
        'value',
        'type'
    ];


    /**
     * Return the localized list of supported macro transformation types.
     *
     * @return array
     */
    public static function getOption(): array
    {
        return [
            self::TYPE_URL => __('frontend.str.macros_type_url'),
            self::TYPE_EMAIL => __('frontend.str.macros_type_email'),
            self::TYPE_HASH_TAGS => __('frontend.str.macros_type_hash_tags'),
            self::TYPE_TAGS => __('frontend.str.macros_type_tags'),
            self::TYPE_WRAP_PHRASE => __('frontend.str.macros_type_wrap_phrase'),
        ];
    }

    /**
     * Resolve this macro's numeric type to its localized label.
     *
     * @return string
     */
    public function getType(): string
    {
        switch ($this->type) {
            case 1:
                return __('frontend.str.macros_type_url');
            case 2:
                return __('frontend.str.macros_type_email');
            case 3:
                return __('frontend.str.macros_type_hash_tags');
            case 4:
                return __('frontend.str.macros_type_tags');
            case 5:
                return __('frontend.str.macros_type_wrap_phrase');
            default:
                return '';
        }
    }

    /**
     * Transform this macro's value according to its configured type.
     *
     * @return string
     */
    public function getValueByType()
    {
        switch ($this->type) {
            case 1:
                return preg_replace(
                    '/(http:\/\/|https:\/\/)?(www)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-\?\%\&]*)?/i',
                    '<a href="\1\2\3.\4$5">\\2\\3.\\4</a>',
                    $this->value
                );
            case 2:
                return preg_replace(
                    '/([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+([a-z]{2,6})/i',
                    '<a href="mailto:\\0">\\0</a>',
                    $this->value
                );
            case 3:
                return preg_replace(
                    '/\#(.*?)(\s|$)/',
                    '<a href="#$1">#$1</a>$2',
                    $this->value
                );
            case 4:
                return preg_replace(
                    '/\*(.*?)\*/',
                    '<b>$1</b>',
                    $this->value
                );
            case 5:
                return preg_replace(
                    '/\#(.*?)\#/',
                    '<a href="http://example.com">$1</a>',
                    $this->value
                );
        }
    }
}
