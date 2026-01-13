<?php

namespace App\Services;

/**
 * MenuService
 * 
 * Service responsible for generating menu items based on user role and permissions.
 * Returns a structured array that can be used to render the sidebar menu.
 */
class MenuService
{
    /**
     * Role level mappings
     */
    protected array $roleLevels = [
        'admin'      => 100,
        'director'   => 80,
        'manager'    => 50,
        'executant'  => 20,
        'auditor'    => 10,
    ];

    /**
     * Get menu items for a user
     * 
     * @param int|null $userId User ID
     * @param string|null $role User role (admin, director, etc.)
     * @param int|null $roleLevel User role level (100, 80, etc.)
     * @return array Structured menu items
     */
    public function getMenuItems(?int $userId = null, ?string $role = null, ?int $roleLevel = null): array
    {
        // Get user data from session if not provided
        $session = service('session');

        if ($userId === null) {
            $userId = $session->get('user_id');
        }

        if ($role === null) {
            $role = $session->get('role');
        }

        if ($roleLevel === null) {
            $roleLevel = $session->get('role_level');
        }

        if (!$userId || !$role || !$roleLevel) {
            return [];
        }

        $menu = [];

        // Main standalone links (available to all logged-in users)
        $menu[] = [
            'label' => 'Acasă',
            'icon' => 'bi-house-door',
            'url' => site_url('/dashboard'),
        ];

        // Centrala - only for specific user
        $userEmail = $session->get('email');
        if ($userEmail === 'vlad.maican@supercom.ro') {
            $menu[] = [
                'label' => 'Centrala',
                'icon' => 'bi-building',
                'url' => site_url('/centrala'),
            ];
        }

        // Principal section - Operational items
        $principalItems = [];

        // Contracte - CRUD for Director/Manager/Admin (level 50+), view for Auditor
        if ($roleLevel >= 10) {
            $principalItems[] = [
                'label' => 'Contracte',
                'icon' => 'bi-file-earmark-text',
                'url' => site_url('/contracts'),
                'minRoleLevel' => 10,
            ];
        }

        // Subdiviziuni - CRUD for Director/Manager/Admin (level 50+)
        if ($roleLevel >= 50) {
            $principalItems[] = [
                'label' => 'Activități',
                'icon' => 'bi-diagram-3',
                'url' => site_url('/subdivisions'),
                'minRoleLevel' => 50,
            ];
        }

        // Task-uri - Available to all roles
        if ($roleLevel >= 10) {
            $principalItems[] = [
                'label' => 'Sarcini',
                'icon' => 'bi-check2-square',
                'url' => site_url('/tasks'),
                'minRoleLevel' => 10,
            ];
        }

        // Filter and add principal items
        $principalItems = array_filter($principalItems, function ($item) use ($roleLevel) {
            return $roleLevel >= ($item['minRoleLevel'] ?? 0);
        });

        if (!empty($principalItems)) {
            $menu[] = [
                'section' => 'Principal',
                'items' => array_values($principalItems),
            ];
        }

        // Administrativ section - Management items
        $adminItems = [];

        // Utilizatori - Admin and Director (level 80+)
        if ($roleLevel >= 80) {
            $adminItems[] = [
                'label' => 'Utilizatori',
                'icon' => 'bi-people',
                'url' => site_url('/users'),
                'minRoleLevel' => 80,
            ];
        }

        // Rapoarte - Admin and Director (level 80+)
        if ($roleLevel >= 80) {
            $adminItems[] = [
                'label' => 'Rapoarte',
                'icon' => 'bi-file-earmark-bar-graph',
                'url' => site_url('/reports'),
                'minRoleLevel' => 80,
            ];
        }

        // Problematice - Admin and Director (level 80+)
        if ($roleLevel >= 80) {
            $adminItems[] = [
                'label' => 'Problematici',
                'icon' => 'bi-info-circle',
                'url' => site_url('/issues'),
                'minRoleLevel' => 80,
            ];
        }

        // Admin-only items (level 100)
        if ($roleLevel >= 100) {
            $adminItems[] = [
                'label' => 'Sucursale',
                'icon' => 'bi-geo-alt-fill',
                'url' => site_url('/regions'),
                'minRoleLevel' => 100,
            ];

            $adminItems[] = [
                'label' => 'Departamente',
                'icon' => 'bi-building',
                'url' => site_url('/departments'),
                'minRoleLevel' => 100,
            ];

            $adminItems[] = [
                'label' => 'Setări Aplicatie',
                'icon' => 'bi-gear',
                'url' => site_url('/settings'),
                'minRoleLevel' => 100,
            ];
        }

        // Filter and add admin items
        $adminItems = array_filter($adminItems, function ($item) use ($roleLevel) {
            return $roleLevel >= ($item['minRoleLevel'] ?? 0);
        });

        if (!empty($adminItems)) {
            $menu[] = [
                'section' => 'Administrativ',
                'items' => array_values($adminItems),
            ];
        }

        // Remove minRoleLevel from final items (not needed in view)
        $menu = $this->cleanMenuItems($menu);

        return $menu;
    }

    /**
     * Remove internal keys from menu items (like minRoleLevel)
     * 
     * @param array $menu
     * @return array
     */
    protected function cleanMenuItems(array $menu): array
    {
        $cleaned = [];

        foreach ($menu as $item) {
            if (isset($item['section'])) {
                // Section with items
                $cleanedItems = [];
                foreach ($item['items'] as $menuItem) {
                    $cleanedItem = [
                        'label' => $menuItem['label'],
                        'icon' => $menuItem['icon'],
                        'url' => $menuItem['url'] ?? site_url('/'),
                    ];
                    $cleanedItems[] = $cleanedItem;
                }
                $cleaned[] = [
                    'section' => $item['section'],
                    'items' => $cleanedItems,
                ];
            } else {
                // Standalone link
                $cleaned[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => $item['url'] ?? site_url('/'),
                ];
            }
        }

        return $cleaned;
    }

    /**
     * Check if user has access to a menu item
     * 
     * @param array $menuItem Menu item with minRoleLevel
     * @param int $userRoleLevel User's role level
     * @return bool
     */
    protected function hasAccess(array $menuItem, int $userRoleLevel): bool
    {
        $requiredLevel = $menuItem['minRoleLevel'] ?? 0;
        return $userRoleLevel >= $requiredLevel;
    }
}
