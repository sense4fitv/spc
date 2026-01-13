<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected UserModel $userModel;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * Display user profile page
     * GET /profile
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get current user data
        $user = $this->userModel->find($currentUserId);

        if (!$user) {
            return redirect()->to('/dashboard')->with('error', 'Utilizatorul nu a fost găsit.');
        }

        $data = [
            'user' => $user,
            'validation' => $this->validation,
        ];

        return view('profile/index', $data);
    }

    /**
     * Update user profile (name only)
     * POST /profile/update
     */
    public function update()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        // Validate input
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validation->getErrors());
        }

        // Get user
        $user = $this->userModel->find($currentUserId);

        if (!$user) {
            return redirect()->to('/profile')->with('error', 'Utilizatorul nu a fost găsit.');
        }

        // Prepare update data (only first_name and last_name)
        $updateData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
        ];

        // Update user
        $updated = $this->userModel->update($currentUserId, $updateData);

        if (!$updated) {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea profilului.');
        }

        // Update session data
        $this->session->set('first_name', $updateData['first_name']);
        $this->session->set('last_name', $updateData['last_name']);

        return redirect()->to('/profile')->with('success', 'Profilul a fost actualizat cu succes.');
    }
}

