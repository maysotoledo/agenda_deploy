<div>
    <livewire:App\Livewire\AnaliseInteligente\NightCompanyEventsTable
        :rows="$rows"
        :wire:key="'night-company-events-' . md5(json_encode($rows))"
    />
</div>
