<?php

namespace App\Livewire\AnaliseInteligente;

use Livewire\Component;

class GroupsCards extends Component
{
    /**
     * Array de grupos (groups_rows).
     * Estrutura esperada:
     * [
     *   ['id'=>..., 'assunto'=>..., 'membros'=>..., 'criacao'=>..., 'descricao'=>..., 'tipo'=>...],
     * ]
     */
    public array $rows = [];

    public int $perPage = 20;
    public int $page = 1;

    public function mount(array $rows = [], int $perPage = 20): void
    {
        $this->rows = $rows;
        $this->perPage = max(5, min(100, $perPage));
        $this->page = 1;
    }

    public function nextPage(): void
    {
        if ($this->page < $this->lastPage()) {
            $this->page++;
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function gotoPage(int $page): void
    {
        $page = max(1, min($this->lastPage(), $page));
        $this->page = $page;
    }

    public function lastPage(): int
    {
        $total = count($this->rows);
        return max(1, (int) ceil($total / $this->perPage));
    }

    public function getPagedRowsProperty(): array
    {
        $offset = ($this->page - 1) * $this->perPage;
        return array_slice($this->rows, $offset, $this->perPage);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.groups-cards', [
            'pagedRows' => $this->pagedRows,
            'total' => count($this->rows),
            'lastPage' => $this->lastPage(),
        ]);
    }
}
