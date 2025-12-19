<?php

if (!function_exists('renderBreadcrumbs')) {
    /**
     * Render breadcrumbs for navigation
     * 
     * @param array $items Array of breadcrumb items ['label' => string, 'url' => string|null]
     * @return string HTML breadcrumb navigation
     */
    function renderBreadcrumbs(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
        
        foreach ($items as $index => $item) {
            $isLast = ($index === count($items) - 1);
            $label = esc($item['label'] ?? '');
            $url = $item['url'] ?? null;
            
            if ($isLast) {
                // Last item is not clickable
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . $label . '</li>';
            } else {
                if ($url) {
                    $html .= '<li class="breadcrumb-item"><a href="' . esc($url, 'attr') . '" class="text-decoration-none">' . $label . '</a></li>';
                } else {
                    $html .= '<li class="breadcrumb-item">' . $label . '</li>';
                }
            }
        }
        
        $html .= '</ol></nav>';
        
        return $html;
    }
}

if (!function_exists('getBreadcrumbsForRegion')) {
    /**
     * Get breadcrumbs for region view
     * 
     * @param array $region Region data
     * @return array Breadcrumb items
     */
    function getBreadcrumbsForRegion(array $region): array
    {
        return [
            ['label' => 'Dashboard', 'url' => site_url('/dashboard')],
            ['label' => esc($region['name'] ?? 'Regiune'), 'url' => null],
        ];
    }
}

if (!function_exists('getBreadcrumbsForContract')) {
    /**
     * Get breadcrumbs for contract view
     * 
     * @param array $contract Contract data with region info
     * @return array Breadcrumb items
     */
    function getBreadcrumbsForContract(array $contract): array
    {
        $items = [
            ['label' => 'Dashboard', 'url' => site_url('/dashboard')],
        ];
        
        if (!empty($contract['region'])) {
            $region = $contract['region'];
            $items[] = [
                'label' => esc($region['name'] ?? 'Regiune'),
                'url' => site_url('/dashboard/region/' . ($region['id'] ?? ''))
            ];
        }
        
        $items[] = [
            'label' => esc($contract['name'] ?? 'Contract'),
            'url' => null
        ];
        
        return $items;
    }
}

if (!function_exists('getBreadcrumbsForSubdivision')) {
    /**
     * Get breadcrumbs for subdivision view
     * 
     * @param array $subdivision Subdivision data with contract and region info
     * @return array Breadcrumb items
     */
    function getBreadcrumbsForSubdivision(array $subdivision): array
    {
        $items = [
            ['label' => 'Dashboard', 'url' => site_url('/dashboard')],
        ];
        
        if (!empty($subdivision['region'])) {
            $region = $subdivision['region'];
            $items[] = [
                'label' => esc($region['name'] ?? 'Regiune'),
                'url' => site_url('/dashboard/region/' . ($region['id'] ?? ''))
            ];
        }
        
        if (!empty($subdivision['contract'])) {
            $contract = $subdivision['contract'];
            $items[] = [
                'label' => esc($contract['name'] ?? 'Contract'),
                'url' => site_url('/dashboard/contract/' . ($contract['id'] ?? ''))
            ];
        }
        
        $items[] = [
            'label' => esc($subdivision['name'] ?? 'Subdiviziune'),
            'url' => null
        ];
        
        return $items;
    }
}

if (!function_exists('getBreadcrumbsForDepartment')) {
    /**
     * Get breadcrumbs for department view
     * 
     * @param array $department Department data with region info
     * @return array Breadcrumb items
     */
    function getBreadcrumbsForDepartment(array $department): array
    {
        $items = [
            ['label' => 'Dashboard', 'url' => site_url('/dashboard')],
        ];
        
        if (!empty($department['region'])) {
            $region = $department['region'];
            $items[] = [
                'label' => esc($region['name'] ?? 'Regiune'),
                'url' => site_url('/dashboard/region/' . ($region['id'] ?? ''))
            ];
        }
        
        $items[] = [
            'label' => esc($department['name'] ?? 'Departament'),
            'url' => null
        ];
        
        return $items;
    }
}

