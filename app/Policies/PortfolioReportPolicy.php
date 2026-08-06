<?php

namespace App\Policies;

use App\Models\PortfolioReport;
use App\Models\User;

class PortfolioReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.portfolio_reports.view')
            || $user->hasPermission('projects.portfolio_reports.generate');
    }

    public function view(User $user, PortfolioReport $report): bool
    {
        return $user->hasPermission('projects.portfolio_reports.view', $report->organization)
            || $user->hasPermission('projects.portfolio_reports.generate', $report->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.portfolio_reports.generate');
    }

    public function download(User $user, PortfolioReport $report): bool
    {
        return $this->view($user, $report);
    }

    public function delete(User $user, PortfolioReport $report): bool
    {
        return $user->hasPermission('projects.portfolio_reports.generate', $report->organization);
    }
}
