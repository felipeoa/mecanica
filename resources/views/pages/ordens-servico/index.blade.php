<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Os;
use App\Models\Estoque;
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public $busca = '';
    public $statusFiltro = '';
    public $osIdParaExclusao;

    // Ordenação
    public $sortBy = 'codigoOs';
    public $sortDirection = 'desc';

    public function sort($column) 
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function ordens()
    {
        $query = Os::with(['veiculo.cliente']);

        if ($this->busca) {
            $query->whereHas('veiculo.cliente', function ($q) {
                $q->where('nomeCliente', 'like', '%' . $this->busca . '%');
            })
            ->orWhereHas('veiculo', function ($q) {
                $q->where('modeloVeiculo', 'like', '%' . $this->busca . '%')
                  ->orWhere('placaVeiculo', 'like', '%' . $this->busca . '%');
            })
            ->orWhere('statusOs', 'like', '%' . $this->busca . '%');
        }

        return $query->orderBy('codigoOs', 'desc')->paginate(5);
    }

    public function aprovarOrcamento($id)
    {
        $os = Os::findOrFail($id);
        $os->update(['statusOs' => 'Aprovada']);
        session()->flash('success', 'Orçamento aprovado pelo cliente! Ordem de Serviço iniciada.');
    }

    public function finalizarOs($id)
    {
        $os = Os::with('itens_os')->findOrFail($id);

        // Transação Atômica ACID: Garante integridade do banco caso falte estoque de algum item
        DB::transaction(function () use ($os) 
        {
            foreach ($os->itens_os as $item) 
            {
                if ($item->tipoItem === 'produto') 
                {
                    $produto = Estoque::findOrFail($item->codigoItem);
                    $produto->decrement('quantidadeProduto', $item->quantidadeItem);
                }
            }

            $os->update(['statusOs' => 'Finalizada']);
        });

        session()->flash('success', 'Ordem de serviço concluída e estoque atualizado com sucesso!');
    }

    public function confirmarExclusao($id)
    {
        $this->osIdParaExclusao = $id;
        Flux::modal('modal-exclusao-os')->show();
    }

    public function deletar()
    {
        if ($this->osIdParaExclusao) 
        {
            $os = Os::findOrFail($this->osIdParaExclusao);
            
            // Altera o status para Cancelada
            $os->update(['statusOs' => 'Cancelada']);

            session()->flash('success', 'Ordem de Serviço cancelada com sucesso!');
        }

        $this->reset('osIdParaExclusao');
        Flux::modal('modal-exclusao-os')->close();
    }

    public function render()
    {
        return view('pages.ordens-servico.index');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Ordens de Serviço</flux:heading>
        
        <flux:button href="{{ route('ordens-servico.create') }}" wire:navigate variant="primary" icon="plus">
            Nova Ordem de Serviço
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Buscar por cliente, veículo ou placa..." />

        <flux:select wire:model.live="busca" placeholder="Todos os Status">
            <flux:select.option value="">Todos os Status</flux:select.option>
            <flux:select.option value="Orçamento">Orçamento</flux:select.option>
            <flux:select.option value="Aprovado">Aprovado</flux:select.option>
            <flux:select.option value="Em andamento">Em andamento</flux:select.option>
            <flux:select.option value="Aguardando peças">Aguardando peças</flux:select.option>
            <flux:select.option value="Finalizada">Finalizada</flux:select.option>
            <flux:select.option value="Cancelada">Cancelada</flux:select.option>
        </flux:select>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->ordens">
            <flux:table.columns>
                <flux:table.column>OS #</flux:table.column>
                <flux:table.column>Cliente</flux:table.column>
                <flux:table.column>Veículo / Placa</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Valor Total</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->ordens as $os)
                    @php
                        $badgeVariant = match($os->statusOs) 
                        {
                            'Finalizada' => 'green',
                            'Em andamento' => 'blue',
                            'Aprovado' => 'indigo',
                            'Aguardando peças' => 'yellow',
                            'Cancelada' => 'red',
                            default => 'lime',
                        };
                    @endphp
                    <flux:table.row :key="$os->codigoOs">
                        <flux:table.cell class="flex items-center gap-3">
                            #{{ str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4 whitespace-nowrap">
                            {{ $os->veiculo->cliente->nomeCliente ?? 'N/I' }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">
                            {{ $os->veiculo->modeloVeiculo ?? 'N/I' }}
                            <span class="text-xs text-gray-400 block font-mono">{{ $os->veiculo->placaVeiculo ?? '' }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">
                            <flux:badge color="{{ $badgeVariant }}" size="sm">{{ $os->statusOs }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">
                            R$ {{ number_format($os->totalOs, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button href="{{ route('ordens-servico.pagamento', $os->codigoOs) }}" wire:navigate variant="filled" color="emerald" size="sm" icon="currency-dollar" title="Pagamentos">
                                    Pagamento
                                </flux:button>
                                <flux:button href="{{ route('ordens-servico.imprimir', $os->codigoOs) }}" target="_blank" variant="ghost" size="sm" icon="printer" />
                                <flux:button href="{{ route('ordens-servico.create', $os->codigoOs) }}" variant="ghost" size="sm" icon="pencil-square" />
                                <flux:button wire:click="confirmarExclusao({{ $os->codigoOs }})" variant="ghost" class="text-red-500 hover:text-red-700" size="sm" icon="trash" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao-os" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Cancelar / Remover OS</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja cancelar esta Ordem de Serviço?
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Voltar</flux:button>
            </flux:modal.close>
            <flux:button wire:click="deletar" variant="danger">Confirmar Cancelamento</flux:button>
        </div>
    </flux:modal>
</div>