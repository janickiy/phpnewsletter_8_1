<?php

namespace App\Helpers;

use App\Models\Macros;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class StringHelper
{
    private const CHARSET_TRANSLATIONS = [
        'utf-8' => 'frontend.str.charutf8',
        'iso-8859-1' => 'frontend.str.iso88591',
        'iso-8859-2' => 'frontend.str.iso88592',
        'iso-8859-3' => 'frontend.str.iso88593',
        'iso-8859-4' => 'frontend.str.iso88594',
        'iso-8859-5' => 'frontend.str.iso88595',
        'koi8-r' => 'frontend.str.koi8r',
        'koi8-u' => 'frontend.str.koi8u',
        'iso-8859-6' => 'frontend.str.iso88596',
        'iso-8859-7' => 'frontend.str.iso88597',
        'iso-8859-8' => 'frontend.str.iso88598',
        'iso-8859-9' => 'frontend.str.iso88599',
        'iso-8859-10' => 'frontend.str.iso885910',
        'iso-8859-13' => 'frontend.str.iso885913',
        'iso-8859-14' => 'frontend.str.iso885914',
        'iso-8859-15' => 'frontend.str.iso885915',
        'iso-8859-16' => 'frontend.str.iso885916',
        'windows-1250' => 'frontend.str.windows1250',
        'windows-1251' => 'frontend.str.windows1251',
        'windows-1252' => 'frontend.str.windows1252',
        'windows-1253' => 'frontend.str.windows1253',
        'windows-1254' => 'frontend.str.windows1254',
        'windows-1255' => 'frontend.str.windows1255',
        'windows-1256' => 'frontend.str.windows1256',
        'windows-1257' => 'frontend.str.windows1257',
        'windows-1258' => 'frontend.str.windows1258',
        'windows-874' => 'frontend.str.windows874',
        'gb2312' => 'frontend.str.gb2312',
        'big5' => 'frontend.str.big5',
        'iso-2022-jp' => 'frontend.str.iso2022jp',
        'ks_c_5601-1987' => 'frontend.str.ksc56011987',
        'euc-kr' => 'frontend.str.euckr',
    ];

    /**
     * Generate a random alphanumeric string of the requested maximum length.
     *
     * @param int $max
     * @return null|string
     */
    public static function randomText(int $max = 6): ?string
    {
        $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
        $size = strlen($chars) - 1;
        $text = null;

        while ($max--)
            $text .= $chars[rand(0, $size)];

        return $text;
    }

    /**
     * Generate a random token suitable for subscriber confirmation links.
     *
     * @return string
     */
    public static function token(): string
    {
        return md5(Str::random(30));
    }

    /**
     * Shorten text to the requested character limit and append an ellipsis when needed.
     *
     * @param string $str
     * @param int $chars
     * @return string
     */
    public static function shortText(string $str, int $chars = 500): string
    {
        $string = str_replace(' ', '', $str);
        $pos = mb_strpos(mb_substr($string, $chars), " ");
        $srtStrlen = mb_strlen($string) > $chars ? '...' : '';

        return mb_substr($str, 0, $chars + $pos) . ($srtStrlen ?? '');
    }

    /**
     * Calculate the effective upload limit from PHP upload, post, and memory settings.
     *
     * @return int
     */
    public static function detectMaxUploadFileSize(): int
    {
        /**
         * Converts shorthands like "2M" or "512K" to bytes
         *
         * @param $size
         * @return false|float|int
         */
        $normalize = function ($size) {
            if (preg_match('/^(-?[\d\.]+)(|[KMG])$/i', $size, $match)) {
                $pos = array_search($match[2], ["", "K", "M", "G"]);
                $size = $match[1] * pow(1024, $pos);
            } else {
                return false;
            }
            return $size;
        };
        $limits = [];
        $limits[] = $normalize(ini_get('upload_max_filesize'));
        if (($max_post = $normalize(ini_get('post_max_size'))) != 0) {
            $limits[] = $max_post;
        }
        if (($memory_limit = $normalize(ini_get('memory_limit'))) != -1) {
            $limits[] = $memory_limit;
        }
        $maxFileSize = min($limits);

        return (int)$maxFileSize;
    }

    /**
     * Format the effective PHP upload limit as a human-readable file size.
     *
     * @return string
     */
    public static function maxUploadFileSize(): string
    {
        $maxUploadFileSize = self::detectMaxUploadFileSize();

        if (!$maxUploadFileSize or $maxUploadFileSize == 0) {
            $maxUploadFileSize = 2097152;
        }

        return Number::fileSize($maxUploadFileSize);
    }

    /**
     * Validate an email address against the application's accepted address pattern.
     *
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        if (preg_match("/^([a-z0-9_\.\-]{1,70})@([a-z0-9\.\-]{1,70})\.([a-z]{2,12})$/i", $email))
            return true;
        else
            return false;
    }

    /**
     * Resolve a file extension to its MIME type with a download-safe fallback.
     *
     * @param string $ext
     * @return string
     */
    public static function getMimeType(string $ext): string
    {
        $ext = ltrim(mb_strtolower(trim($ext)), '.');

        if ($ext === '') {
            return 'application/force-download';
        }

        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($ext);

        return $mimeTypes[0] ?? 'application/force-download';
    }

    /**
     * Randomly replace visually similar Cyrillic characters with Latin equivalents.
     *
     * @param string $str
     * @return string
     */
    static public function encodeString(string $str): string
    {
        $replace = [
            "А" => "A",
            "В" => "B",
            "Е" => "E",
            "К" => "K",
            "М" => "M",
            "Н" => "H",
            "О" => "O",
            "Р" => "P",
            "С" => "C",
            "Т" => "T",
            "Х" => "X",
            "х" => "x",
            "а" => "a",
            "е" => "e",
            "о" => "o",
            "с" => "c",
            "у" => "y"];


        $result = [];

        foreach (preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $result[] = random_int(0, 1) === 1
                ? strtr($char, $replace)
                : $char;
        }

        return implode('', $result);
    }

    /**
     * Remove HTML markup and decode entities into plain text.
     *
     * @param string $str
     * @return string
     */
    static public function removeHtmlTags(string $str): string
    {
        $str = strip_tags($str);
        return html_entity_decode($str);
    }

    /**
     * Extract the host component from a URL, falling back to its path.
     *
     * @param string $url
     * @return string
     */
    static public function getDomain(string $url): string
    {
        $parse = parse_url($url);
        return isset($parse['host']) ? $parse['host'] : $parse['path'];
    }

    /**
     * Extract the URL scheme, defaulting to HTTP when none is present.
     *
     * @param string $url
     * @return string
     */
    static public function getScheme(string $url): string
    {
        $parse = parse_url($url);
        return isset($parse['scheme']) ? $parse['scheme'] : 'http';
    }

    /**
     * Build the current application base URL from server request variables.
     *
     * @return string
     */
    public static function getUrl(): string
    {
        if (dirname($_SERVER['SCRIPT_NAME']) == '/' | dirname($_SERVER['SCRIPT_NAME']) == '\\')
            $dir = '/';
        else
            $dir = dirname($_SERVER['SCRIPT_NAME']) . '/';

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir;
        $url = explode('?', $url);

        return $url[0] ?? '';
    }

    /**
     * Convert PHP runtime information into a nested associative array.
     *
     * @return array
     */
    static public function phpinfoArray(): array
    {
        ob_start();
        phpinfo();
        $info_arr = [];
        $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
        $cat = "General";
        foreach ($info_lines ?? [] as $line) {
            // new cat?
            preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
            if (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
                $info_arr[$cat][$val[1]] = $val[2];
            } elseif (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
                $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
            }
        }

        return $info_arr;
    }

    /**
     * Render a nested object or array as an HTML tree list.
     *
     * @param object|array $el
     * @param bool $first
     * @return string
     */
    public static function tree(object|array $el, bool $first = true): string
    {
        if (is_object($el)) $el = (array)$el;

        if ($el) {
            $out = $first
                ? '<ul id="tree-checkbox" class="tree-checkbox treeview">'
                : '<ul>';

            foreach ($el as $k => $v) {
                if (is_object($v)) $v = (array)$v;
                if ($v) {
                    $out .= "<li><strong> " . $k . " :</strong> ";
                    if (is_array($v)) {
                        $out .= self::tree($v, false);
                    } else {
                        $out .= $v;
                    }
                    $out .= "</li>";
                }
            }
            $out .= "</ul>";

            return $out;
        } else {
            return '';
        }
    }

    /**
     * Translate a charset identifier to its localized display label when available.
     *
     * @param string|null $str
     * @return string|null
     */
    public static function charsetList(?string $str): ?string
    {
        if ($str === null || trim($str) === '') {
            return $str;
        }

        $normalized = mb_strtolower(trim($str));
        $translationKey = self::CHARSET_TRANSLATIONS[$normalized] ?? null;

        return $translationKey ? __($translationKey) : $str;
    }

    /**
     * Replace or append one key in the application's environment file.
     *
     * @param string $envKey
     * @param string $envValue
     * @return void
     */
    public static function setEnvironmentValue(string $envKey, string $envValue): void
    {
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $envKey)) {
            throw new \InvalidArgumentException('Invalid environment key.');
        }

        $path = app()->environmentFilePath();
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the environment file.');
        }

        $escapedValue = str_replace(
            ["\\", '"', "\r", "\n"],
            ["\\\\", '\\"', '\r', '\n'],
            $envValue
        );
        $environmentLine = sprintf('%s="%s"', $envKey, $escapedValue);
        $pattern = '/^[ \t]*' . preg_quote($envKey, '/') . '[ \t]*=.*$/m';

        $updated = preg_replace_callback(
            $pattern,
            static fn (): string => $environmentLine,
            $contents,
            1,
            $replacementCount
        );

        if ($updated === null) {
            throw new \RuntimeException('Unable to update the environment file.');
        }

        if ($replacementCount === 0) {
            $separator = $updated === '' || str_ends_with($updated, "\n")
                ? ''
                : PHP_EOL;
            $updated .= $separator . $environmentLine . PHP_EOL;
        }

        if (@file_put_contents($path, $updated, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write the environment file.');
        }
    }

    /**
     * Replace configured macro placeholders in a string with their rendered values.
     *
     * @param string $str
     * @return string
     */
    public static function macrosReplacement(string $str): string
    {
        $search = [];
        $replace = [];

        foreach (Macros::query()->get() as $macros) {
            $search[] = '{{' . $macros->name . '}}';
            $replace[] = $macros->getValueByType();
        }

        return str_replace($search, $replace, $str);
    }
}
