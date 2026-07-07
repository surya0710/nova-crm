<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, TenantContext $tenant, SearchService $search): View
    {
        $query = $request->string('q')->trim()->toString();

        return view('search.index', [
            'organization' => $tenant->get(),
            'query' => $query,
            'results' => $query !== '' ? $search->search($request->user(), $query) : collect(),
        ]);
    }
}
