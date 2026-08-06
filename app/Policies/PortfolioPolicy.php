<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.portfolios.view')
            || $user->hasPermission('projects.portfolios.manage');
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('projects.portfolios.view', $portfolio->organization)
            || $user->hasPermission('projects.portfolios.manage', $portfolio->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.portfolios.create')
            || $user->hasPermission('projects.portfolios.manage');
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('projects.portfolios.update', $portfolio->organization)
            || $user->hasPermission('projects.portfolios.manage', $portfolio->organization);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('projects.portfolios.delete', $portfolio->organization)
            || $user->hasPermission('projects.portfolios.manage', $portfolio->organization);
    }

    public function archive(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }

    public function attachProject(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }

    public function viewDashboard(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
