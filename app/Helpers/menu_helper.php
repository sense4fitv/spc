<?php

/**
 * Menu Helper
 * 
 * Provides helper functions for menu generation and management.
 */

if (!function_exists('getMenuItems')) {
    /**
     * Get menu items for the current logged-in user
     * 
     * @return array Menu items array
     */
    function getMenuItems(): array
    {
        $session = service('session');
        
        if (!$session->get('is_logged_in')) {
            return [];
        }

        $menuService = new \App\Services\MenuService();
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $roleLevel = $session->get('role_level');

        return $menuService->getMenuItems($userId, $role, $roleLevel);
    }
}

