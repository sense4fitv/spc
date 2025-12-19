<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pusher extends BaseConfig
{
    /**
     * Pusher App ID
     * 
     * @var string
     */
    public string $appId = '';

    /**
     * Pusher Key (Public Key)
     * 
     * @var string
     */
    public string $key = '';

    /**
     * Pusher Secret (Private Key)
     * 
     * @var string
     */
    public string $secret = '';

    /**
     * Pusher Cluster
     * Examples: eu, us-east, ap-southeast-1, etc.
     * 
     * @var string
     */
    public string $cluster = 'eu';

    /**
     * Use TLS encryption
     * 
     * @var bool
     */
    public bool $useTLS = true;

    /**
     * Pusher API endpoint for REST calls
     * Default: https://api-{cluster}.pusher.com
     * 
     * @var string
     */
    public string $apiEndpoint = '';

    /**
     * Constructor
     * Loads configuration from environment variables
     */
    public function __construct()
    {
        parent::__construct();

        $this->appId = env('PUSHER_APP_ID', '');
        $this->key = env('PUSHER_KEY', '');
        $this->secret = env('PUSHER_SECRET', '');
        $this->cluster = env('PUSHER_CLUSTER', 'eu');
        $this->useTLS = env('PUSHER_USE_TLS', 'true') === 'true';
        
        // Build API endpoint from cluster
        if (empty($this->apiEndpoint)) {
            $this->apiEndpoint = 'https://api-' . $this->cluster . '.pusher.com';
        }
    }

    /**
     * Get Pusher configuration as array for SDK
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'app_id' => $this->appId,
            'key' => $this->key,
            'secret' => $this->secret,
            'cluster' => $this->cluster,
            'useTLS' => $this->useTLS,
        ];
    }

    /**
     * Check if Pusher is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) 
            && !empty($this->key) 
            && !empty($this->secret);
    }
}

