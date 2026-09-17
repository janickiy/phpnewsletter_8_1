<?php

namespace App\Helpers;

class UpdateHelper
{
    private $language;
    private string $url = 'https://license.janickiy.com/';
    private string $currentVersion;
    private bool $updateInfoLoaded = false;

    /** @var array<string, mixed> */
    private array $updateInfo = [];

    /**
     * Initialize update checks for the current locale and installed application version.
     *
     * @param string $language
     * @param string $currentVersion
     */
    public function __construct(string $language, string $currentVersion)
    {
        $this->language = $language;
        $this->currentVersion = $currentVersion;
    }

    /**
     * Determine whether the update server advertises a newer regular version.
     *
     * @return bool
     */
    public function checkNewVersion(): bool
    {
        return $this->checkVersion($this->getVersion(), $this->currentVersion);
    }

    /**
     * Determine whether the update server advertises a newer upgrade version.
     *
     * @return bool
     */
    public function checkUpgrade(): bool
    {
        return $this->checkVersion($this->getUpgradeVersion(), $this->currentVersion);
    }

    /**
     * Build the update-service information URL for this installation.
     *
     * @return string
     */
    public function getUrlInfo(): string
    {
        return $this->url . '?' . http_build_query([
                'id' => 6,
                'version' => $this->currentVersion,
                'lang' => $this->language,
                'ip' => $this->getIP(),
            ]);
    }

    /**
     * Fetch and decode update information from a remote URL.
     *
     * @param string $url
     * @param int $timeout
     * @return mixed|string
     */
    public function getDataContents(string $url, int $timeout = 10): mixed
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $userAgent = $this->getServerHeader('HTTP_USER_AGENT');
        if ($userAgent !== null) {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }

        $referer = $this->getServerHeader('HTTP_REFERER');
        if ($referer !== null) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $data = curl_exec($ch);

        curl_close($ch);

        if (!is_string($data) || $data === '') {
            return '';
        }

        $decoded = json_decode($data, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $jsonStart = strpos($data, '{');
        $jsonEnd = strrpos($data, '}');

        if ($jsonStart === false || $jsonEnd === false || $jsonEnd < $jsonStart) {
            return '';
        }

        $decoded = json_decode(substr($data, $jsonStart, $jsonEnd - $jsonStart + 1), true);

        return is_array($decoded) ? $decoded : '';
    }

    /**
     * Validate that the current version follows the supported release-tree pattern.
     *
     * @return bool
     */
    public function checkTree(): bool
    {
        if (!preg_match("/^(\d+)\.(\d+)\.(\d+)$/", $this->currentVersion, $out)) {
            return false;
        }

        if ($out[1] < $out[2]) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return the regular version reported by the update service.
     *
     * @return string
     */
    public function getVersion(): string
    {
        $out = $this->getUpdateInfo();

        return $out["version"] ?? '';
    }

    /**
     * Return the archive download URL reported by the update service.
     *
     * @return string
     */
    public function getDownloadLink(): string
    {
        $out = $this->getUpdateInfo();

        return $out['download'] ?? '';
    }

    /**
     * Return the normalized update endpoint reported by the update service.
     *
     * @return string
     */
    public function getUpdateLink(): string
    {
        $out = $this->getUpdateInfo();

        return $this->normalizeUpdateLink($out['update'] ?? '');
    }

    /**
     * Return the release creation timestamp reported by the update service.
     *
     * @return string
     */
    public function getCreated(): string
    {
        $out = $this->getUpdateInfo();

        return $out['created'] ?? '';
    }

    /**
     * Return the normalized update source reported by the update service.
     *
     * @return string
     */
    public function getUpdate(): string
    {
        $out = $this->getUpdateInfo();

        return $this->normalizeUpdateLink($out['update'] ?? '');
    }

    /**
     * Return the upgrade version reported by the update service.
     *
     * @return string
     */
    public function getUpgradeVersion(): string
    {
        $out = $this->getUpdateInfo();

        return $out['upgrade_version'] ?? '';
    }

    /**
     * Return the informational message reported by the update service.
     *
     * @return string
     */
    public function getMessage(): string
    {
        $out = $this->getUpdateInfo();

        return $out['message'] ?? '';
    }

    /**
     * Resolve the visitor IP address from trusted request header candidates.
     *
     * @return string
     */
    public function getIP(): string
    {
        foreach ($this->getIpHeaderCandidates() as $candidate) {
            $ip = $this->extractValidIp($candidate);

            if ($ip !== null) {
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Compare a candidate semantic version with the currently installed version.
     *
     * @param string $version
     * @param string $currentVersion
     * @return bool
     */
    private function checkVersion(string $version, string $currentVersion): bool
    {
        foreach ([$version, $currentVersion] as $value) {
            if (!preg_match("/^\d+\.\d+\.\d+$/", $value)) {
                return false;
            }
        }

        return version_compare($version, $currentVersion, '>');
    }

    /**
     * Normalize an update archive or directory URL to its base update endpoint.
     */
    private function normalizeUpdateLink(string $link): string
    {
        if ($link === '') {
            return '';
        }

        $path = (string)parse_url($link, PHP_URL_PATH);

        if (pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
            $link = dirname($link);
        }

        return rtrim($link, '/');
    }

    /**
     * Return a non-empty HTTP header value from the current request.
     */
    private function getServerHeader(string $key): ?string
    {
        $value = $_SERVER[$key] ?? null;

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Return request headers that may contain the end-user IP address.
     *
     * @return array<int, string|null>
     */
    private function getIpHeaderCandidates(): array
    {
        return [
            $this->getServerHeader('HTTP_CF_CONNECTING_IP'),
            $this->getServerHeader('HTTP_X_REAL_IP'),
            $this->getServerHeader('HTTP_CLIENT_IP'),
            $this->getServerHeader('HTTP_X_FORWARDED_FOR'),
            $this->getServerHeader('HTTP_FORWARDED'),
            $this->getServerHeader('REMOTE_ADDR'),
        ];
    }

    /**
     * Extract the first valid IP from single-value or comma-separated proxy headers.
     *
     * @param string|null $value
     * @return string|null
     */
    private function extractValidIp(?string $value): ?string
    {
        if ($value === null || strcasecmp($value, 'unknown') === 0) {
            return null;
        }

        foreach (explode(',', $value) as $part) {
            $part = trim($part);

            if ($part === '' || strcasecmp($part, 'unknown') === 0) {
                continue;
            }

            if (str_contains($part, 'for=')) {
                $part = preg_replace('/^.*for="?([^";,]+)"?.*$/i', '$1', $part) ?? $part;
            }

            $part = trim($part, " \t\n\r\0\x0B[]\"");

            if (filter_var($part, FILTER_VALIDATE_IP)) {
                return $part;
            }
        }

        return null;
    }

    /**
     * Load update metadata once per request.
     *
     * @return array<string, mixed>
     */
    private function getUpdateInfo(): array
    {
        if ($this->updateInfoLoaded) {
            return $this->updateInfo;
        }

        $this->updateInfoLoaded = true;
        $data = $this->getDataContents($this->getUrlInfo());
        $this->updateInfo = is_array($data) ? $data : [];

        return $this->updateInfo;
    }
}
