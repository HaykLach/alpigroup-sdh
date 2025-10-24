<?php

namespace App\Filament\Widgets;

use Illuminate\Database\Eloquent\Builder;

trait PimWidgetUpdateAgentsTrait
{
    public ?string $filterAgents = null;

    public function setFilterAgents(?string $agentId): void
    {
        $this->filterAgents = $agentId;
    }

    public function getFilterAgents(): ?string
    {
        return $this->filterAgents;
    }

    protected function applyFilterAgents(Builder $query): Builder
    {
        $agentId = $this->getFilterAgents();
        if ($agentId !== null) {
            $query = $query->byAgentId($agentId);
        }

        return $query;
    }
}
