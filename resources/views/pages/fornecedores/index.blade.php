<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Fornecedor;

new class extends Component
{
    use WithPagination;

    public $busca = '';
    public $fornecedorId;
    public $fornecedorIdParaExclusao;
    public $nomeFantasiaFornecedor, $razaoSocialFornecedor, $cnpjFornecedor, $telefoneFornecedor, $emailFornecedor, $vendedorFornecedor;

    //Ordernação
    public $sortBy = 'codigoFornecedor';
    public $sortDirection = 'desc';

    protected function rules()
    {
        return 
        [
            'nomeFantasiaFornecedor' => 'required|string|max:150',
            'telefoneFornecedor' => 'required|string',
            'cnpjFornecedor' => 'nullable|cnpj|unique:fornecedores,cnpjFornecedor,' . $this->fornecedorId. ',codigoFornecedor',
        ];
    }

    protected function messages()
    {
        return 
        [
            'nomeFantasiaFornecedor.required' => 'Nome Fantasia é obrigatório.',
            'cnpjFornecedor.cnpj' => 'CNPJ inválido!',
            'cnpjFornecedor.unique' => 'CNPJ já cadastrado!',
            'telefoneFornecedor.required' => 'Telefone é obrigatório.',
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
    public function fornecedores()
    {
        if ($this->busca)
        {
            return Fornecedor::where('nomeFantasiaFornecedor', 'like', '%' . $this->busca . '%')
                            ->orWhere('cnpjFornecedor', 'like', '%' . $this->busca . '%')
                            ->orderBy('nomeFantasiaFornecedor', 'asc')
                            ->paginate(5);
        }
        else
        {
            return Fornecedor::query()
                ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
                ->paginate(5);
        }      
    }

    public function editar($id)
    {
        $fornecedor = Fornecedor::findOrFail($id);
        $this->fornecedorId = $fornecedor->codigoFornecedor;
        $this->nomeFantasiaFornecedor = $fornecedor->nomeFantasiaFornecedor;
        $this->razaoSocialFornecedor = $fornecedor->razaoSocialFornecedor;
        $this->cnpjFornecedor = $fornecedor->cnpjFornecedor;
        $this->telefoneFornecedor = $fornecedor->telefoneFornecedor;
        $this->emailFornecedor = $fornecedor->emailFornecedor;
        $this->vendedorFornecedor = $fornecedor->vendedorFornecedor;
    }

    public function cancelarEdicao()
    {
        $this->reset(['fornecedorId', 'nomeFantasiaFornecedor', 'razaoSocialFornecedor', 'cnpjFornecedor', 'telefoneFornecedor', 'emailFornecedor', 'vendedorFornecedor']);
    }

    public function salvar()
    {
        $this->validate();

        $dados = 
        [
            'nomeFantasiaFornecedor' => $this->nomeFantasiaFornecedor,
            'razaoSocialFornecedor' => $this->razaoSocialFornecedor,
            'cnpjFornecedor' => $this->cnpjFornecedor,
            'telefoneFornecedor' => $this->telefoneFornecedor,
            'emailFornecedor' => $this->emailFornecedor,
            'vendedorFornecedor' => $this->vendedorFornecedor,
        ];

        if ($this->fornecedorId) 
        {
            Fornecedor::findOrFail($this->fornecedorId)->update($dados);
            session()->flash('success', 'Fornecedor atualizado com sucesso!');
        } 
        else 
        {
            Fornecedor::create($dados);
            session()->flash('success', 'Fornecedor cadastrado com sucesso!');
        }

        $this->cancelarEdicao();
    }

     public function confirmarExclusao($id)
    {
        $this->fornecedorIdParaExclusao = $id;
        
        // Abre o modal que declaramos na View com o name="modal-exclusao"
        Flux::modal('modal-exclusao')->show();
    }

    public function deletar()
    {
        if ($this->fornecedorIdParaExclusao) 
        {
            Fornecedor::findOrFail($this->fornecedorIdParaExclusao)->delete();
            session()->flash('success', 'Fornecedor excluído com sucesso!');
        }

        // Reseta a variável e fecha o modal
        $this->reset('fornecedorIdParaExclusao');
        Flux::modal('modal-exclusao')->close();
    }   

    public function render()
    {
        return view('pages.fornecedores.index');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ $fornecedorId ? 'Editar Fornecedor' : 'Fornecedores' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-4 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field class="md:col-span-2">
                <flux:label>CNPJ</flux:label>
                <flux:input wire:model="cnpjFornecedor" type="text" placeholder="00.000.000/0001-00" x-mask="99.999.999/9999-99" />
                <flux:error name="cnpjFornecedor" />
            </flux:field>

            <flux:field>
                <flux:label>Nome Fantasia</flux:label>
                <flux:input wire:model="nomeFantasiaFornecedor" type="text" />
                <flux:error name="nomeFantasiaFornecedor" />
            </flux:field>

            <flux:field>
                <flux:label>Razão Social</flux:label>
                <flux:input wire:model="razaoSocialFornecedor" type="text" />
            </flux:field>            
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:field>
                <flux:label>Telefone Comercial</flux:label>
                <flux:input wire:model="telefoneFornecedor" type="text" placeholder="(00) 00000-0000" x-mask="(99) 99999-9999" />
                <flux:error name="telefoneFornecedor" />
            </flux:field>

            <flux:field>
                <flux:label>Contato do Vendedor</flux:label>
                <flux:input wire:model="vendedorFornecedor" type="text" />
            </flux:field>            
        </div>

        <div class="flex justify-end gap-2">
            @if($fornecedorId)
                <flux:button wire:click="cancelarEdicao" type="button" icon="x-mark" type="button" variant="primary" color="rose">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="arrow-path">Atualizar</flux:button>
            @else
                <flux:button type="submit" variant="primary" icon="check">Salvar</flux:button>
            @endif
        </div>        
    </form>

    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por nome ou CNPJ..." />
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->fornecedores">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'nomeFantasiaFornecedor'" :direction="$sortDirection" wire:click="sort('nomeFantasiaFornecedor')">Nome Fantasia</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'cnpjFornecedor'" :direction="$sortDirection" wire:click="sort('cnpjFornecedor')">CNPJ</flux:table.column>
                <flux:table.column>Telefone</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->fornecedores as $fornecedor)
                    <flux:table.row :key="$fornecedor->codigoFornecedor">
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $fornecedor->nomeFantasiaFornecedor }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4 whitespace-nowrap">{{ $fornecedor->cnpjFornecedor }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $fornecedor->telefoneFornecedor }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $fornecedor->codigoFornecedor }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $fornecedor->codigoFornecedor }})" variant="primary" color="red" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Fornecedor</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este fornecedor?
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