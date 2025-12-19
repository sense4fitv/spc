<?php

namespace App\Controllers;

use App\Services\GlobalSearchService;

class SearchController extends BaseController
{
    protected GlobalSearchService $searchService;

    public function __construct()
    {
        $this->searchService = new GlobalSearchService();
    }

    /**
     * Global search endpoint
     * GET /api/search?q=cluj
     */
    public function search()
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $regionId = $session->get('region_id');

        // Require authentication
        if (!$userId) {
            return $this->response->setJSON([
                'error' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $query = $this->request->getGet('q');
        
        if (empty($query) || strlen(trim($query)) < 2) {
            return $this->response->setJSON([
                'results' => []
            ]);
        }

        // Perform search
        $results = $this->searchService->search(
            $query,
            $userId,
            $role,
            $regionId,
            5 // Limit per category
        );

        return $this->response->setJSON([
            'results' => $results,
            'query' => $query,
        ]);
    }
}

