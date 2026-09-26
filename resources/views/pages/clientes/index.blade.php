<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;

new class extends Component
{
    use WithPagination;

    public $busca = '';
    public $clienteId; // Armazena o ID se estiver a editar
    public $clienteIdParaExclusao;
    public $nomeCliente, $cpfCliente, $dataNascimentoCliente, $telefoneCliente, $enderecoCliente;

    //Ordernação
    public $sortBy = 'codigoCliente';
    public $sortDirection = 'desc';

    protected function rules()
    {
        return 
        [
            'nomeCliente' => 'required|string|max:150',
            // Validação do CPF ignora o ID atual se for uma edição
            'cpfCliente' => 'required|cpf|max:14|unique:clientes,cpfCliente,' . $this->clienteId . ',codigoCliente',
            'dataNascimentoCliente' => 'required|date',
            'telefoneCliente' => 'required|celular_com_ddd',
            'enderecoCliente' => 'required|string',
        ];
    }

    protected function messages()
    {
        return 
        [
            'nomeCliente.required' => 'Nome é obrigatório.',
            'cpfCliente.required' => 'CPF é obrigatório.',
            'cpfCliente.cpf' => 'CPF inválido!',
            'cpfCliente.unique' => 'CPF já cadastrado!',
            'dataNascimentoCliente.required' => 'Data de Nascimento é obrigatório.',
            'telefoneCliente.required' => 'Telefone é obrigatório.',
            'enderecoCliente.required' => 'Endereço é obrigatório.',
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
    public function clientes()
    {
        if ($this->busca)
        {
            return Cliente::where('nomeCliente', 'like', '%' . $this->busca . '%')
                        ->orWhere('cpfCliente', 'like', '%' . $this->busca . '%')
                        ->orderBy('codigoCliente', 'desc')
                        ->paginate(15);
        }
        else
        {
            return Cliente::query()
                ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
                ->paginate(5);
        }      
    }

    public function editar($id)
    {
        $cliente = Cliente::findOrFail($id);
        $this->clienteId = $cliente->codigoCliente;
        $this->nomeCliente = $cliente->nomeCliente;
        $this->cpfCliente = $cliente->cpfCliente;
        $this->dataNascimentoCliente = $cliente->dataNascimentoCliente?->format('Y-m-d') ?? $cliente->dataNascimentoCliente;
        $this->telefoneCliente = $cliente->telefoneCliente;
        $this->enderecoCliente = $cliente->enderecoCliente;
    }

    public function cancelarEdicao()
    {
        $this->reset(['clienteId', 'nomeCliente', 'cpfCliente', 'dataNascimentoCliente', 'telefoneCliente', 'enderecoCliente']);
    }

    public function salvar()
    {
        $this->validate();

        $dados = 
        [
            'nomeCliente' => $this->nomeCliente,
            'cpfCliente' => $this->cpfCliente,
            'dataNascimentoCliente' => $this->dataNascimentoCliente,
            'telefoneCliente' => $this->telefoneCliente,
            'enderecoCliente' => $this->enderecoCliente,
        ];

        if ($this->clienteId) 
        {
            Cliente::findOrFail($this->clienteId)->update($dados);
            session()->flash('success', 'Cliente atualizado com sucesso!');
        } 
        else 
        {
            Cliente::create($dados);
            session()->flash('success', 'Cliente cadastrado com sucesso!');
        }

        $this->cancelarEdicao();
    }

    public function confirmarExclusao($id)
    {
        $this->clienteIdParaExclusao = $id;
        
        // Abre o modal que declaramos na View com o name="modal-exclusao"
        Flux::modal('modal-exclusao')->show();
    }

    public function deletar()
    {
        if ($this->clienteIdParaExclusao) 
        {
            $cliente = Cliente::findOrFail($this->clienteIdParaExclusao);
            $cliente->delete();

            session()->flash('success', 'Cliente excluído com sucesso!');
        }

        // Reseta a variável e fecha o modal
        $this->reset('clienteIdParaExclusao');
        Flux::modal('modal-exclusao')->close();
    }

    public function render()
    {
        return view('pages.clientes.index');
    }
}
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ $clienteId ? 'Editar Cliente' : 'Clientes' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field>
                <flux:label>Nome</flux:label>
                <flux:input wire:model="nomeCliente" type="text" placeholder="Ex: Ronaldo Nazário" />
                <flux:error name="nomeCliente" />
            </flux:field>

            <flux:field>
                <flux:label>CPF</flux:label>
                <flux:input wire:model="cpfCliente" type="text" placeholder="000.000.000-00" x-mask="999.999.999-99" />
                <flux:error name="cpfCliente" />
            </flux:field>

            <flux:field>
                <flux:label>Telefone</flux:label>
                <flux:input wire:model="telefoneCliente" type="text" placeholder="(11) 99999-9999" x-mask="(99) 99999-9999" />
                <flux:error name="telefoneCliente" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:field class="md:col-span-2">
                <flux:label>Endereço Completo</flux:label>
                <flux:input wire:model="enderecoCliente" type="text" />
                <flux:error name="enderecoCliente" />
            </flux:field>

            <flux:field>
                <flux:label>Data de Nascimento</flux:label>
                <flux:input wire:model="dataNascimentoCliente" type="date" />
                <flux:error name="dataNascimentoCliente" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            @if($clienteId)
                <flux:button wire:click="cancelarEdicao" icon="x-mark" type="button" variant="primary" color="rose">Cancelar</flux:button>
                <flux:button type="submit" icon="arrow-path" variant="primary">Atualizar</flux:button>
            @else
                <flux:button type="submit" icon="check" variant="primary">Salvar</flux:button>
            @endif
        </div>
    </form>

    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por nome ou CPF..." />
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->clientes">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'nomeCliente'" :direction="$sortDirection" wire:click="sort('nomeCLiente')">Nome</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'cpfCliente'" :direction="$sortDirection" wire:click="sort('cpfCliente')">CPF</flux:table.column>
                <flux:table.column>Telefone</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->clientes as $cliente)
                    <flux:table.row :key="$cliente->codigoCliente">
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $cliente->nomeCliente }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4 whitespace-nowrap">{{ $cliente->cpfCliente }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $cliente->telefoneCliente }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $cliente->codigoCliente }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $cliente->codigoCliente }})" variant="danger" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Cliente</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este cliente?
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