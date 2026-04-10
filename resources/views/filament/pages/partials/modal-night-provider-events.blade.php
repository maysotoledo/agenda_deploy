<livewire:analise-inteligente.night-events-table
    :rows="$rows"
    :key="'modal-night-events-' . md5(json_encode($rows))"
/>
