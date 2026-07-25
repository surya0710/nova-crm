<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingAttribution;
use App\Models\MarketingConversion;
use App\Models\MarketingTouch;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MarketingAttributionController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage',
            ]),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $attributions = collect();
        $bySource = collect();
        $conversions = collect();

        if (Schema::hasTable('marketing_attributions')) {
            $attributions = MarketingAttribution::query()
                ->with(['lead', 'customer', 'visitor'])
                ->where('organization_id', $organization->id)
                ->latest('attributed_at')
                ->limit(50)
                ->get();
        }

        if (Schema::hasTable('marketing_touches')) {
            $bySource = MarketingTouch::query()
                ->whereHas('session.visitor', fn ($q) => $q->where('organization_id', $organization->id))
                ->select('source', 'medium', 'channel', DB::raw('count(*) as total'))
                ->groupBy('source', 'medium', 'channel')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        }

        if (Schema::hasTable('marketing_conversions')) {
            $conversions = MarketingConversion::query()
                ->where('organization_id', $organization->id)
                ->select('event_name', DB::raw('count(*) as total'), DB::raw('sum(event_value) as value'))
                ->groupBy('event_name')
                ->orderByDesc('total')
                ->get();
        }

        return view('marketing.attribution.index', [
            'attributions' => $attributions,
            'bySource' => $bySource,
            'conversions' => $conversions,
            'model' => config('marketing.attribution.default_model', 'first_touch'),
        ]);
    }
}
