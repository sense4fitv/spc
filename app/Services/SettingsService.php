<?php

namespace App\Services;

use App\Models\AppSettingModel;

/**
 * SettingsService
 * 
 * Service responsible for application settings business logic.
 */
class SettingsService
{
    protected AppSettingModel $appSettingModel;

    public function __construct()
    {
        $this->appSettingModel = new AppSettingModel();
    }

    /**
     * Get setting value by key
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return string|null Setting value or default
     */
    public function getSetting(string $key, $default = null): ?string
    {
        return $this->appSettingModel->getSetting($key, $default);
    }

    /**
     * Set setting value
     * 
     * @param string $key Setting key
     * @param string $value Setting value
     * @param int $userId User ID who updated the setting
     * @return bool Success status
     */
    public function setSetting(string $key, string $value, int $userId): bool
    {
        return $this->appSettingModel->setSetting($key, $value, $userId);
    }

    /**
     * Get all settings formatted for display
     * 
     * @return array All settings with labels and descriptions
     */
    public function getAllSettingsForDisplay(): array
    {
        $settings = $this->appSettingModel->getAllSettings();
        
        // Map settings keys to display labels
        $labels = [
            'send_welcome_email' => 'Trimite email de activare cont',
        ];
        
        $descriptions = [
            'send_welcome_email' => 'Dacă este activat, utilizatorii noi vor primi un email cu datele de logare la crearea contului.',
        ];
        
        $result = [];
        foreach ($settings as $setting) {
            $result[] = [
                'key' => $setting['setting_key'],
                'value' => $setting['setting_value'],
                'label' => $labels[$setting['setting_key']] ?? $setting['setting_key'],
                'description' => $setting['description'] ?? ($descriptions[$setting['setting_key']] ?? ''),
            ];
        }
        
        return $result;
    }

    /**
     * Check if a boolean setting is enabled (value is '1')
     * 
     * @param string $key Setting key
     * @return bool True if setting value is '1', false otherwise
     */
    public function isSettingEnabled(string $key): bool
    {
        return $this->appSettingModel->isEnabled($key);
    }
}

