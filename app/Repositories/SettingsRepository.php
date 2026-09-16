<?php

namespace App\Repositories;

use App\DTO\Update\SettingsUpdateData;
use App\Models\CustomHeaders;
use App\Models\Settings;

class SettingsRepository extends BaseRepository
{
    /**
     * Initialize settings persistence with the settings model.
     */
    public function __construct(Settings $model)
    {
        parent::__construct($model);
    }

    /**
     * Persist application settings and replace all validated custom mail headers.
     *
     * @param SettingsUpdateData $settings
     * @return void
     */
    public function setSettings(SettingsUpdateData $settings): void
    {
        $data = $settings->toArray();
        $headerNames = (array) ($data['header_name'] ?? []);
        $headerValues = (array) ($data['header_value'] ?? []);
        unset($data['header_name'], $data['header_value']);

        $array = $data;
        $array['REQUIRE_SUB_CONFIRMATION'] = isset($data['REQUIRE_SUB_CONFIRMATION']) && $data['REQUIRE_SUB_CONFIRMATION'] ? 1 : 0;
        $array['SHOW_UNSUBSCRIBE_LINK'] = isset($data['SHOW_UNSUBSCRIBE_LINK']) && $data['SHOW_UNSUBSCRIBE_LINK']  ? 1 : 0;
        $array['REQUEST_REPLY'] = isset($data['SHOW_UNSUBSCRIBE_LINK']) && $data['SHOW_UNSUBSCRIBE_LINK'] ? 1 : 0;
        $array['NEW_SUBSCRIBER_NOTIFY'] = isset($data['NEW_SUBSCRIBER_NOTIFY']) && $data['NEW_SUBSCRIBER_NOTIFY'] ? 1 : 0;
        $array['RANDOM_SEND'] = isset($data['RANDOM_SEND']) && $data['RANDOM_SEND']  ? 1 : 0;
        $array['RENDOM_REPLACEMENT_SUBJECT'] = isset($data['RENDOM_REPLACEMENT_SUBJECT']) && $data['RENDOM_REPLACEMENT_SUBJECT'] ? 1 : 0;
        $array['RANDOM_REPLACEMENT_BODY'] = isset($data['RANDOM_REPLACEMENT_BODY']) && $data['RANDOM_REPLACEMENT_BODY'] ? 1 : 0;
        $array['ADD_DKIM'] = isset($data['ADD_DKIM']) && $data['ADD_DKIM'] ? 1 : 0;
        $array['LIMIT_SEND'] = isset($data['LIMIT_SEND']) && $data['LIMIT_SEND']  ? 1 : 0;
        $array['REQUEST_REPLY'] = isset($data['REQUEST_REPLY']) && $data['REQUEST_REPLY']  ? 1 : 0;
        $array['REMOVE_SUBSCRIBER'] = isset($data['REMOVE_SUBSCRIBER']) && $data['REMOVE_SUBSCRIBER']  ? 1 : 0;

        foreach ($array ?? [] as $key => $value) {
            $this->model->setValue($key, $value);
        }

        CustomHeaders::query()->delete();

        if (!empty($headerNames)) {
            for ($i = 0; $i < count($headerNames); $i++) {
                $name = trim((string) ($headerNames[$i] ?? ''));
                $value = trim((string) ($headerValues[$i] ?? ''));

                if ($name === '' || $value === '') {
                    continue;
                }

                if (preg_match('/^[\\-a-zA-Z]+$/', $name)) {
                    $value = str_replace([';', ':'], '', $value);

                    CustomHeaders::create([
                        'name' => $name,
                        'value' => $value,
                    ]);
                }
            }
        }
    }
}
