<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Estoque;
use App\Models\Fornecedor;

new class extends Component
{
    use WithPagination;

    public $busca = '';
    public $estoqueId;
    public $estoqueIdParaExclusao;
    public $codigoProduto, $descricaoProduto, $categoriaProduto, $quantidadeProduto, $estoqueMinimoProduto, $valorProduto, $codigoFornecedor;

    //Ordernação
    public $sortBy = 'codigoEstoque';
    public $sortDirection = 'desc';

    // Array para armazenar múltiplos IDs de fornecedores
    public array $fornecedoresSelecionados = [];
   
    protected function rules()
    {
        return 
        [
            'codigoProduto' => 'required|unique:estoque,codigoProduto,' . $this->codigoProduto. ',codigoProduto',
            'descricaoProduto' => 'required|string|max:150',
            'categoriaProduto' => 'required',
            'quantidadeProduto' => 'required|integer',
            'estoqueMinimoProduto' => 'required|integer',
            'valorProduto' => 'required|numeric',
            #'codigoFornecedor' => 'nullable|exists:fornecedores,codigoFornecedor'
            'codigoFornecedor' => 'nullable|array',
            'codigoFornecedor.*' => 'exists:fornecedores,codigoFornecedor',
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
    public function estoques()
    {
        if ($this->busca)
        {
            return Estoque::with('fornecedor')
                ->where('descricaoProduto', 'like', '%' . $this->busca . '%')
                ->orWhere('codigoProduto', 'like', '%' . $this->busca . '%')
                ->orderBy('descricaoProduto', 'asc')
                ->paginate(5);
        }
        else
        {
            return Estoque::query()
                ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
                ->paginate(5);
        }      
    }

    public function editar($id)
    {
        $prod = Estoque::with('fornecedores')->findOrFail($id);
        
        $this->estoqueId = $prod->codigoEstoque;
        $this->codigoProduto = $prod->codigoProduto;
        $this->descricaoProduto = $prod->descricaoProduto;
        $this->categoriaProduto = $prod->categoriaProduto;
        $this->unidadeMedidaProduto = $prod->unidadeMedidaProduto;
        $this->quantidadeProduto = $prod->quantidadeProduto;
        $this->estoqueMinimoProduto = $prod->estoqueMinimoProduto;
        $this->valorProduto = $prod->valorProduto;

        // Carrega os IDs dos fornecedores associados
        $this->fornecedoresSelecionados = $prod->fornecedores->pluck('codigoFornecedor')->map(fn($id) => (string) $id)->toArray();
    }

    public function cancelarEdicao()
    {
        $this->reset(['estoqueId', 'codigoProduto', 'descricaoProduto', 'categoriaProduto', 'quantidadeProduto', 'estoqueMinimoProduto', 'valorProduto', 'fornecedoresSelecionados']);
    }

    public function salvar()
    {
        $this->validate();

        $prod = Estoque::withTrashed()->updateOrCreate(
            ['codigoEstoque' => $this->estoqueId],
            [                
                'codigoProduto' => $this->codigoProduto,
                'descricaoProduto' => $this->descricaoProduto,
                'categoriaProduto' => $this->categoriaProduto,
                'quantidadeProduto' => $this->quantidadeProduto,
                'estoqueMinimoProduto' => $this->estoqueMinimoProduto,
                'valorProduto' => $this->valorProduto,
            ]
        );
        
        if ($this->estoqueId) 
        {
            session()->flash('success', 'Item de estoque atualizado!');
        } 
        else 
        {
            session()->flash('success', 'Insumo adicionado ao inventário!');
        }

        // Sincroniza a tabela pivô com os fornecedores escolhidos
        $prod->fornecedores()->sync($this->fornecedoresSelecionados);

        $this->cancelarEdicao();
    }

    public function confirmarExclusao($id)
    {
        $this->estoqueIdParaExclusao = $id;
        
        // Abre o modal que declaramos na View com o name="modal-exclusao"
        Flux::modal('modal-exclusao')->show();
    }

    public function deletar()
    {
        if ($this->estoqueIdParaExclusao) 
        {
            $estoque = Estoque::findOrFail($this->estoqueIdParaExclusao);
            $estoque->fornecedores()->detach(); // Remove associações na pivô
            $estoque->delete();

            session()->flash('success', 'Item de Estoque excluído com sucesso!');
        }

        // Reseta a variável e fecha o modal
        $this->reset('estoqueIdParaExclusao');
        Flux::modal('modal-exclusao')->close();
    }

    public function render()
    {
        $todosFornecedores = Fornecedor::orderBy('nomeFantasiaFornecedor')->get();

        return view('pages.estoques.index', 
        [
            'todosFornecedores' => $todosFornecedores,
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl" class="mb-6">{{ $estoqueId ? 'Editar Item do Estoque' : 'Controle de Estoque e Inventário' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <flux:field>
                <flux:label>Código Interno</flux:label>
                <flux:input wire:model="codigoProduto" type="text" placeholder="Ex: FRE-PA01" />
                <flux:error name="codigoProduto" />
            </flux:field>

            <flux:field class="md:col-span-2">
                <flux:label>Descrição da Peça</flux:label>
                <flux:input wire:model="descricaoProduto" type="text" />
                <flux:error name="descricaoProduto" />
            </flux:field>

            <flux:field>
                <flux:label>Categoria</flux:label>
                <flux:input wire:model="categoriaProduto" type="text" />
                <flux:error name="categoriaProduto" />
            </flux:field>

            <flux:field>
                <flux:label>Unidade de Medida</flux:label>
                <flux:select wire:model="unidade_medida">
                    <flux:select.option value="UN">Unidade (UN)</flux:select.option>
                    <flux:select.option value="L">Litro (L)</flux:select.option>
                    <flux:select.option value="ML">Mililitro (ML)</flux:select.option>
                    <flux:select.option value="KG">Quilograma (KG)</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 items-end">
            <flux:field>
                <flux:label>Quantidade</flux:label>
                <flux:input wire:model="quantidadeProduto" type="number" />
                <flux:error name="quantidadeProduto" />
            </flux:field>

            <flux:field>
                <flux:label>Alerta Mínimo</flux:label>
                <flux:input wire:model="estoqueMinimoProduto" type="number" />
                <flux:error name="estoqueMinimoProduto" />
            </flux:field>

            <flux:field>
                <flux:label>Preço de Venda</flux:label>
                <flux:input wire:model="valorProduto" type="number" step="0.01" icon="currency-dollar" />
                <flux:error name="valorProduto" />
            </flux:field>

            <flux:field>
                <flux:label>Fornecedores</flux:label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg max-h-40 overflow-y-auto border dark:border-gray-700">
                    @forelse($todosFornecedores as $forn)
                        <label class="flex items-center space-x-2 text-sm cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                value="{{ (string) $forn->codigoFornecedor }}" 
                                wire:model="fornecedoresSelecionados" 
                                wire:key="fornecedor-checkbox-{{ $forn->codigoFornecedor }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700"
                            >
                            <span class="text-gray-700 dark:text-gray-300">{{ $forn->nomeFantasiaFornecedor }}</span>
                        </label>
                    @empty
                        <span class="text-xs text-gray-400 col-span-3">Nenhum fornecedor cadastrado no sistema.</span>
                    @endforelse
                </div>
                <flux:error name="fornecedoresSelecionados" />
            </flux:field>            
        </div>

        <div class="flex justify-end gap-2">
            @if($estoqueId)
                <flux:button wire:click="cancelarEdicao" type="button" variant="ghost">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Atualizar Estoque</flux:button>
            @else
                <flux:button type="submit" variant="primary">Adicionar ao Estoque</flux:button>
            @endif
        </div>
    </form>

    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por código ou produto..." />
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="$this->estoques">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'codigoProduto'" :direction="$sortDirection" wire:click="sort('codigoProduto')">Código</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'descricaoProduto'" :direction="$sortDirection" wire:click="sort('descricaoProduto')">Peça</flux:table.column>
                <flux:table.column>Qtd</flux:table.column>
                <flux:table.column>Valor</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->estoques as $estoque)
                    <flux:table.row :key="$estoque->codigoEstoque">
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $estoque->codigoProduto }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $estoque->descricaoProduto }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $estoque->quantidadeProduto }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">R$ {{ number_format($estoque->valorProduto, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $estoque->codigoEstoque }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $estoque->codigoEstoque }})" variant="danger" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Item de Estoque</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este item de estoque?
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