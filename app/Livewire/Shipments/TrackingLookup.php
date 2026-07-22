<?php

namespace App\Livewire\Shipments;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class TrackingLookup extends Component
{
    public string $trackingIdentifier = '';

    public function submit(): void
    {
        $this->validate([
            'trackingIdentifier' => ['required', 'string', 'max:100'],
        ], [
            'trackingIdentifier.required' => __('shipments.tracking.validation.required'),
        ]);

        $this->redirectRoute('shipments.show', [
            'trackingIdentifier' => trim($this->trackingIdentifier),
        ], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.shipments.tracking')->layout('layouts.app', [
            'title' => __('shipments.tracking.title'),
        ]);
    }
}
