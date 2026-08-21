<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalesForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesForecastController extends Controller
{
    public function __invoke(Request $request, SalesForecastService $forecast): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('opportunities.view'), 403);

        return response()->json(
            $forecast->summary(
                null,
                $request->integer('year') ?: null,
                $request->integer('month') ?: null,
            )
        );
    }
}
