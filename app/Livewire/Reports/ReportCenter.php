<?php

namespace App\Livewire\Reports;

use App\Application\Reports\ReportDefinitions;
use App\Domain\ClientAccount\Models\ClientAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReportCenter extends Component
{
    public bool $canExport = false;

    public function mount(): void
    {
        $account = $this->currentClientAccount();
        Gate::authorize('report.view', $account);
        $this->canExport = Gate::allows('report.export', $account);
    }

    public function render(): View
    {
        return view('livewire.reports.center', ['reports' => ReportDefinitions::all()])->layout('layouts.app', ['title' => __('navigation.reports')]);
    }

    private function currentClientAccount(): ClientAccount
    {
        return app(ClientAccount::class);
    }
}
