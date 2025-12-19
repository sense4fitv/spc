<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;

class AuthFilter implements FilterInterface
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
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $currentUrl = current_url();

        log_message('debug', "AuthFilter::before - URL: {$currentUrl}");

        // Check if user is logged in
        $isLoggedIn = $session->get('is_logged_in');
        log_message('debug', "AuthFilter::before - is_logged_in: " . var_export($isLoggedIn, true));

        if (!$isLoggedIn) {
            log_message('debug', "AuthFilter::before - Not logged in, redirecting to login");
            // Store the intended URL to redirect after login
            $session->setFlashdata('redirect_after_login', $currentUrl);
            return redirect()->to('/auth/login');
        }

        // If no roles specified, just check if logged in
        if (empty($arguments)) {
            log_message('debug', "AuthFilter::before - No roles specified, allowing access");
            return;
        }

        // Get user's role and role level from session
        $userRole = $session->get('role');
        $userRoleLevel = $session->get('role_level');

        log_message('debug', "AuthFilter::before - User role: " . var_export($userRole, true));
        log_message('debug', "AuthFilter::before - User role_level (raw): " . var_export($userRoleLevel, true));
        log_message('debug', "AuthFilter::before - Arguments: " . var_export($arguments, true));

        if (!$userRole || !$userRoleLevel) {
            log_message('error', "AuthFilter::before - Missing role or role_level, destroying session");
            $session->destroy();
            return redirect()->to('/auth/login')->with('error', 'Sesiunea a expirat. Te rugăm să te autentifici din nou.');
        }

        // Convert role_level to integer (session might store it as string)
        $userRoleLevel = (int)$userRoleLevel;
        log_message('debug', "AuthFilter::before - User role_level (int): {$userRoleLevel}");

        // Parse allowed roles from filter arguments
        // In CodeIgniter 4, when using 'auth:admin,director', arguments can come as:
        // - Array with each role as a separate element: ['admin', 'director', 'manager', 'executant', 'auditor']
        // - Array with first element as comma-separated string: ['admin,director,manager,executant,auditor']
        // - String directly: 'admin,director,manager,executant,auditor'
        $allowedRoles = [];

        if (is_array($arguments) && !empty($arguments)) {
            // Check if first element is an array (nested) or string
            if (is_array($arguments[0])) {
                // Nested array: use it directly
                $allowedRoles = array_filter(array_map('trim', $arguments[0]));
            } elseif (is_string($arguments[0]) && strpos($arguments[0], ',') !== false) {
                // First element is a comma-separated string
                $allowedRoles = array_filter(array_map('trim', explode(',', $arguments[0])));
            } else {
                // Arguments is an array with each role as a separate element
                $allowedRoles = array_filter(array_map('trim', $arguments));
            }
        } elseif (is_string($arguments)) {
            // Direct string argument
            $allowedRoles = array_filter(array_map('trim', explode(',', $arguments)));
        }

        log_message('debug', "AuthFilter::before - Allowed roles (parsed): " . json_encode($allowedRoles));

        // Admin (level 100) can access everything
        if ($userRoleLevel >= 100) {
            log_message('debug', "AuthFilter::before - Admin access granted");
            return;
        }

        // Check if user's role is directly in the allowed list
        if (in_array($userRole, $allowedRoles)) {
            log_message('debug', "AuthFilter::before - Direct role match found: {$userRole}");
            return;
        }

        log_message('debug', "AuthFilter::before - No direct role match. Checking hierarchical access...");

        // Check hierarchical access: find minimum level required
        // User with higher level can access routes for lower levels
        $minRequiredLevel = PHP_INT_MAX;
        foreach ($allowedRoles as $role) {
            $role = trim($role);
            if (isset($this->roleLevels[$role])) {
                $minRequiredLevel = min($minRequiredLevel, $this->roleLevels[$role]);
            }
        }

        log_message('debug', "AuthFilter::before - Min required level: {$minRequiredLevel}");

        // If user level is >= minimum required level, grant access
        if ($minRequiredLevel !== PHP_INT_MAX && $userRoleLevel >= $minRequiredLevel) {
            log_message('debug', "AuthFilter::before - Hierarchical access granted (user: {$userRoleLevel} >= required: {$minRequiredLevel})");
            return;
        }

        // No access granted - redirect to login instead of dashboard to avoid redirect loops
        log_message('warning', "AuthFilter::before - Access denied for role: {$userRole} (level: {$userRoleLevel}), required: {$minRequiredLevel}");
        $session->setFlashdata('error', 'Nu ai permisiunea de a accesa această pagină.');
        return redirect()->to('/auth/login');
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an exception or error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
        return $response;
    }
}
