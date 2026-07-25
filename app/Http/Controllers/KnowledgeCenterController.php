<?php

namespace App\Http\Controllers;

use App\Services\DocumentationService;
use App\Services\DocumentationValidationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeCenterController extends Controller
{
    public function index(Request $request, DocumentationService $documentation): View
    {
        return view('knowledge.index', [
            'modules' => $documentation->getSidebarModules($request->user()),
            'navigationTree' => $documentation->getNavigationTree($request->user()),
        ]);
    }

    public function search(Request $request, DocumentationService $documentation): View
    {
        $query = $request->string('q')->trim()->toString();
        $results = $query !== ''
            ? $documentation->search($request->user(), $query)
            : collect();

        return view('knowledge.search', [
            'query' => $query,
            'results' => $results,
            'navigationTree' => $documentation->getNavigationTree($request->user()),
        ]);
    }

    public function health(Request $request, DocumentationValidationService $validation, DocumentationService $documentation): View
    {
        $report = $validation->health();

        return view('knowledge.health', [
            'report' => $report,
            'navigationTree' => $documentation->getNavigationTree($request->user()),
        ]);
    }

    public function module(string $module, Request $request, DocumentationService $documentation): View
    {
        $page = $documentation->findDocument($request->user(), $module);

        abort_unless($page, 404);

        return $this->showPage($request, $documentation, $page, $module);
    }

    public function page(string $module, string $page, Request $request, DocumentationService $documentation): View
    {
        $document = $documentation->findDocument($request->user(), $module, $page);

        abort_unless($document, 404);

        return $this->showPage($request, $documentation, $document, $module);
    }

    private function showPage(Request $request, DocumentationService $documentation, array $document, string $module): View
    {
        $user = $request->user();
        $documentation->recordRecentlyViewed($document);
        $navigation = $documentation->resolvePreviousNext($user, $document);

        return view('knowledge.show', [
            'document' => $document,
            'module' => $module,
            'navigationTree' => $documentation->getNavigationTree(
                $user,
                $module,
                $document['slug']
            ),
            'relatedDocuments' => $documentation->getRelatedDocuments($user, $document),
            'recentlyViewed' => $documentation->getRecentlyViewed($user)
                ->reject(fn (array $item): bool => $item['slug'] === $document['slug'])
                ->values(),
            'previous' => $navigation['previous'],
            'next' => $navigation['next'],
        ]);
    }
}
