<div>
    <livewire:analise-inteligente.provider-ips-table
        :rows="$rows ?? ($selectedProviderIps ?? [])"
        :wire:key="'provider-ips-compat-' . md5(json_encode($rows ?? ($selectedProviderIps ?? [])))"
    />
</div>
