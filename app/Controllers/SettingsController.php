<?php

namespace App\Controllers;

use App\Services\SettingsService;

class SettingsController extends BaseController
{
    protected SettingsService $settingsService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * Display settings page
     * GET /settings
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');
        $currentUserRole = $this->session->get('role');
        $currentUserRoleLevel = $this->session->get('role_level');

        // Check permissions - only Admin can access settings
        if (!$currentUserId || $currentUserRoleLevel < 100) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi setările aplicației.');
        }

        // Get all settings for display
        $settings = $this->settingsService->getAllSettingsForDisplay();

        $data = [
            'settings' => $settings,
        ];

        return view('settings/index', $data);
    }

    /**
     * Update settings
     * POST /settings/update
     */
    public function update()
    {
        $currentUserId = $this->session->get('user_id');
        $currentUserRoleLevel = $this->session->get('role_level');

        // Check permissions - only Admin can update settings
        if (!$currentUserId || $currentUserRoleLevel < 100) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să modifici setările aplicației.');
        }

        // Validation rules
        $rules = [
            'send_welcome_email' => [
                'rules' => 'required|in_list[0,1]',
                'errors' => [
                    'required' => 'Setarea este obligatorie.',
                    'in_list' => 'Valoarea setării nu este validă.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Get setting value from POST
        $sendWelcomeEmail = $this->request->getPost('send_welcome_email');

        // Update setting
        if ($this->settingsService->setSetting('send_welcome_email', $sendWelcomeEmail, $currentUserId)) {
            return redirect()->to('/settings')->with('success', 'Setările au fost actualizate cu succes.');
        }

        return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea setărilor.');
    }
}

