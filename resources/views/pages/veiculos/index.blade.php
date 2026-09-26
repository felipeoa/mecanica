<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Veiculo;
use App\Models\Cliente;

new class extends Component
{
    use WithPagination;

    public $busca = '';
    public $veiculoId; // Armazena o ID se estiver a editar
    public $veiculoIdParaExclusao;
    public $codigoCliente, $placaVeiculo, $marcaVeiculo, $modeloVeiculo, $anoVeiculo, $corVeiculo;

    //Ordernação
    public $sortBy = 'codigoCliente';
    public $sortDirection = 'desc';

    protected function rules() 
    {
        return
        [
            'codigoCliente' => 'required|exists:clientes,codigoCliente',
            'placaVeiculo' => 'required|string|max:10',
            'marcaVeiculo' => 'required|string|max:100',
            'modeloVeiculo' => 'required|string|max:100',
            'anoVeiculo' => 'nullable|integer|min:1900|max:2099',
            'corVeiculo' => 'nullable|string|max:50',
        ];
    }

    protected function messages()
    {
        return 
        [
            'codigoCliente.required' => 'Cliente é obrigatório.',
            'placaVeiculo.required' => 'Placa é obrigatório.',
            'placaVeiculo.unique' => 'Placa já cadastrada!',
            'marcaVeiculo.required' => 'Marca é obrigatório.',
            'modeloVeiculo.required' => 'Modelo é obrigatório.',
            'corVeiculo.required' => 'Cor é obrigatório.',
        ];
    }
    
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
    public function veiculos()
    {
        if ($this->busca)
        {
            return Veiculo::with('cliente')
                        ->where('placaVeiculo', 'like', '%' . $this->busca . '%')
                        ->orWhere('modeloVeiculo', 'like', '%' . $this->busca . '%')
                        ->orWhere('marcaVeiculo', 'like', '%' . $this->busca . '%')
                        ->orWhereHas('cliente', function ($q) 
                        {
                            $q->where('nomeCliente', 'like', '%' . $this->busca . '%');
                        })
                        ->paginate(15);
        }
        else
        {
            return Veiculo::query()->with('cliente')
                ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
                ->paginate(15);
        }      
    }

    public function editar($id)
    {
        $veiculo = Veiculo::findOrFail($id);
        $this->veiculoId = $veiculo->codigoVeiculo;
        $this->codigoCliente = $veiculo->codigoCliente;
        $this->placaVeiculo = $veiculo->placaVeiculo;
        $this->marcaVeiculo = $veiculo->marcaVeiculo;
        $this->modeloVeiculo = $veiculo->modeloVeiculo;
        $this->anoVeiculo = $veiculo->anoVeiculo;
        $this->corVeiculo = $veiculo->corVeiculo;
    }

    public function cancelarEdicao()
    {
        $this->reset(['veiculoId', 'codigoCliente', 'placaVeiculo', 'marcaVeiculo', 'modeloVeiculo', 'anoVeiculo', 'corVeiculo']);
    }

    public function salvar()
    {
        $this->validate();

        $dados = 
        [
            'codigoCliente' => $this->codigoCliente,
            'placaVeiculo' => $this->placaVeiculo,
            'marcaVeiculo' => $this->marcaVeiculo,
            'modeloVeiculo' => $this->modeloVeiculo,
            'anoVeiculo' => $this->anoVeiculo,
            'corVeiculo' => $this->corVeiculo,
        ];

        if ($this->veiculoId) 
        {
            Veiculo::findOrFail($this->veiculoId)->update($dados);
            session()->flash('success', 'Veículo atualizado com sucesso!');
        } 
        else 
        {
            Veiculo::create($dados);
            session()->flash('success', 'Veiculo cadastrado com sucesso!');
        }

        $this->cancelarEdicao();
    }

    public function confirmarExclusao($id)
    {
        $this->veiculoIdParaExclusao = $id;
        
        // Abre o modal que declaramos na View com o name="modal-exclusao"
        Flux::modal('modal-exclusao')->show();
    } 
    
    public function deletar()
    {
        if ($this->veiculoIdParaExclusao) 
        {
            $cliente = Veiculo::findOrFail($this->veiculoIdParaExclusao);
            $cliente->delete();

            session()->flash('success', 'Veículo excluído com sucesso!');
        }

        // Reseta a variável e fecha o modal
        $this->reset('veiculoIdParaExclusao');
        Flux::modal('modal-exclusao')->close();
    }

    public function render()
    {
        $clientes = Cliente::orderBy('nomeCliente')->get();

        return view('pages.veiculos.index', 
        [
            'clientes' => $clientes,
        ]);
    }    
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ $veiculoId ? 'Editar Veículo' : 'Veículos' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
            <flux:field>
                <flux:label>Cliente</flux:label>
                <flux:select wire:model="codigoCliente" placeholder="Selecione o cliente...">
                    <flux:select.option value="">Selecione...</flux:select.option>
                    @foreach($clientes as $c)
                        <flux:select.option value="{{ $c->codigoCliente }}">{{ $c->nomeCliente }} ({{ $c->cpfCliente }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="codigoCliente" />
            </flux:field>
        </div>            

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field>
                <flux:label>Placa</flux:label>
                <flux:input wire:model="placaVeiculo" placeholder="ABC1D23" class="uppercase" />
                <flux:error name="placaVeiculo" />
            </flux:field>

            <flux:field>
                <flux:label>Marca</flux:label>
                <flux:input wire:model="marcaVeiculo" placeholder="Ex: Chevrolet, VW, Fiat..." />
                <flux:error name="marcaVeiculo" />
            </flux:field> 
            
            <flux:field>
                <flux:label>Modelo</flux:label>
                <flux:input wire:model="modeloVeiculo" placeholder="Ex: Onix 1.0" />
                <flux:error name="modeloVeiculo" />
            </flux:field>            
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field>
                <flux:label>Ano</flux:label>
                <flux:input wire:model="anoVeiculo" type="number" placeholder="2020" />
                <flux:error name="anoVeiculo" />
            </flux:field>

            <flux:field>
                <flux:label>Cor</flux:label>
                <flux:input wire:model="corVeiculo" placeholder="Ex: Preto" />
                <flux:error name="corVeiculo" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            @if($veiculoId)
                <flux:button wire:click="cancelarEdicao" icon="x-mark" type="button" variant="primary" color="rose">Cancelar</flux:button>
                <flux:button type="submit" icon="arrow-path" variant="primary">Atualizar</flux:button>
            @else
                <flux:button type="submit" icon="check" variant="primary">Salvar</flux:button>
            @endif
        </div>        
    </form>

    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por placa, modelo, marca ou cliente..." />
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->veiculos">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'placaVeiculo'" :direction="$sortDirection" wire:click="sort('placaVeiculo')">Placa</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'nomeCliente'" :direction="$sortDirection" wire:click="sort('nomeCliente')">Cliente</flux:table.column>
                <flux:table.column>Veículo</flux:table.column>
                <flux:table.column>Ano / Cor</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->veiculos as $veiculo)
                    <flux:table.row :key="$veiculo->codigoVeiculo">                                        
                        <flux:table.cell class="px-6 py-4 whitespace-nowrap">{{ $veiculo->placaVeiculo }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">
                            {{ $veiculo->cliente->nomeCliente ?? 'Não informado' }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $veiculo->marcaVeiculo }} {{ $veiculo->modeloVeiculo }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $veiculo->anoVeiculo ?? '-' }} / {{ $veiculo->corVeiculo ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $veiculo->codigoVeiculo }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $veiculo->codigoVeiculo }})" variant="danger" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Veículo</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este veículo?
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