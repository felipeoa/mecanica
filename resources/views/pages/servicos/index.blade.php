<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Servico;

new class extends Component
{
    public $busca = '';
    public $servicoId;
    public $servicoIdParaExclusao;
    public $descricaoServico, $valorServico;

    //Ordernação
    public $sortBy = 'codigoServico';
    public $sortDirection = 'desc';

    protected function rules()
    {
        return 
        [
            'descricaoServico' => 'required|string|max:150',
            'valorServico' => 'required|numeric',
        ];
    }

    public function sort($column) 
    {
        if ($this->sortBy === $column) 
        {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } 
        else 
        {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function servicos()
    {
        if ($this->busca)
        {
            return Servico::where('descricaoServico', 'like', '%' . $this->busca . '%')
                ->orderBy('descricaoServico', 'asc')
                ->paginate(5);
        }
        else
        {
            return Servico::query()
                ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
                ->paginate(5);
        }      
    } 
    
    public function editar($id)
    {
        $prod = Servico::findOrFail($id);
        
        $this->servicoId = $prod->codigoServico;
        $this->descricaoServico = $prod->descricaoServico;
        $this->valorServico = $prod->valorServico;
    }

    public function cancelarEdicao()
    {
        $this->reset(['servicoId', 'descricaoServico', 'valorServico']);
    }

    public function salvar()
    {
        $this->validate();

        Servico::withTrashed()->updateOrCreate(
            ['codigoServico' => $this->servicoId],
            [                
                'descricaoServico' => $this->descricaoServico,
                'valorServico' => $this->valorServico,
            ]
        );
        
        if ($this->servicoId) 
        {
            session()->flash('success', 'Serviço atualizado!');
        } 
        else 
        {
            session()->flash('success', 'Serviço cadastrado');
        }

        $this->cancelarEdicao();
    }

    public function confirmarExclusao($id)
    {
        $this->servicoIdParaExclusao = $id;
        
        // Abre o modal que declaramos na View com o name="modal-exclusao"
        Flux::modal('modal-exclusao')->show();
    }

    public function deletar()
    {
        if ($this->servicoIdParaExclusao) 
        {
            $servico = Servico::findOrFail($this->servicoIdParaExclusao);
            $servico->delete();

            session()->flash('success', 'Serviço excluído com sucesso!');
        }

        // Reseta a variável e fecha o modal
        $this->reset('servicoIdParaExclusao');
        Flux::modal('modal-exclusao')->close();
    }

    public function render()
    {
        return view('pages.servicos.index');
    }    
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl" class="mb-6">{{ $servicoId ? 'Editar Serviço' : 'Serviços' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif


    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field class="md:col-span-2">
                <flux:label>Descrição do Serviço</flux:label>
                <flux:input wire:model="descricaoServico" type="text" />
                <flux:error name="descricaoServico" />
            </flux:field>

            <flux:field>
                <flux:label>Valor</flux:label>
                <flux:input wire:model="valorServico" type="number" step="0.01" icon="currency-dollar" />
                <flux:error name="valorServico" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            @if($servicoId)
                <flux:button wire:click="cancelarEdicao" type="button" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Atualizar</flux:button>
            @else
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            @endif
        </div>
    </form>
    
    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por serviço..." />
    </div> 
    
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->servicos">
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'descricaoServico'" :direction="$sortDirection" wire:click="sort('descricaoServico')">Serviço</flux:table.column>
                <flux:table.column>Valor</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->servicos as $servico)
                    <flux:table.row :key="$servico->codigoServico">
                        <flux:table.cell class="px-6 py-4">{{ $servico->codigoServico }}</flux:table.cell>
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $servico->descricaoServico }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">R$ {{ number_format($servico->valorServico, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $servico->codigoServico }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $servico->codigoServico }})" variant="danger" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Serviço</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este serviço?
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="primary" color="rose" icon="x-mark">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="deletar" variant="primary" icon="trash">Excluir</flux:button>
        </div>
    </flux:modal>    
</div>