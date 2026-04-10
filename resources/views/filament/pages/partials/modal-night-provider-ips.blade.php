<div>
    <livewire:App\Livewire\AnaliseInteligente\NightProviderIpsTable
        :rows="$rows"
        :wire:key="'night-provider-ips-' . md5(json_encode($rows))"
    />
</div>
