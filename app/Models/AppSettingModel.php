<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table            = 'app_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'setting_key',
        'setting_value',
        'description',
        'updated_by',
    ];

    // Dates
    protected $useTimestamps = false; // We manually handle updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = null;
    protected $updatedField  = 'updated_at';
    protected $deletedField  = null;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get setting value by key
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return string|null Setting value or default
     */
    public function getSetting(string $key, $default = null): ?string
    {
        $setting = $this->where('setting_key', $key)->first();
        
        return $setting ? $setting['setting_value'] : $default;
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
        // Check if setting exists
        $existing = $this->where('setting_key', $key)->first();
        
        $data = [
            'setting_value' => $value,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        if ($existing) {
            // Update existing setting
            return $this->update($existing['id'], $data) !== false;
        } else {
            // Insert new setting (shouldn't normally happen, but handle it)
            $data['setting_key'] = $key;
            return $this->insert($data) !== false;
        }
    }

    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public function getAllSettings(): array
    {
        return $this->findAll();
    }

    /**
     * Check if a boolean setting is enabled (value is '1')
     * 
     * @param string $key Setting key
     * @return bool True if setting value is '1', false otherwise
     */
    public function isEnabled(string $key): bool
    {
        $value = $this->getSetting($key, '0');
        return $value === '1';
    }
}

