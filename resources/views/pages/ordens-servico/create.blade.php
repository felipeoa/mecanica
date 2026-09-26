<?php

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Veiculo;
use App\Models\Estoque;
use App\Models\Servico;
use App\Models\Os;
use App\Models\ItemOs;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $codigoOs = null; // Se preenchido, modo edição

    // Campos da OS
    public $codigoCliente;
    public $codigoVeiculo;
    public $statusOs = 'Orçamento';
    public $observacoesOs;

    // Controle de adicão de itens
    public string $searchItem = '';
    public $itemSelecionadoId = null;
    public $tipoItem = 'produto';
    public $quantidadeItem = 1.0;

    // Lista temporária de itens no orçamento
    public $itensAdicionados = [];
    public $totalGeral = 0.00;

    protected function rules()
    {
        return 
        [
            'codigoCliente' => 'required',
            'codigoVeiculo' => 'required',
            'statusOs' => 'required',
            'itensAdicionados' => 'required|array|min:1',
        ];
    }

    protected $messages = 
    [
        'codigoCliente.required' => 'Selecione um cliente.',
        'codigoVeiculo.required' => 'Selecione um veículo vinculado.',
        'itensAdicionados.min' => 'Adicione ao menos um produto ou serviço ao orçamento.',
    ];

    public function mount($codigoOs = null)
    {
        // Se a rota receber o parâmetro codigoOs (ex: /ordens-servico/criar?codigoOs=5 ou via parâmetro de rota)
        if ($codigoOs) {
            $os = Os::with('itens_os')->find($codigoOs);

            if ($os) 
            {
                $veiculo = Veiculo::with('cliente')->find($os->codigoVeiculo);

                $this->codigoOs = $os->codigoOs;
                $this->codigoCliente = $veiculo->cliente->codigoCliente;
                $this->codigoVeiculo = $os->codigoVeiculo;
                $this->statusOs = $os->statusOs;
                $this->observacoesOs = $os->observacoesOs ?? '';

                // Carrega os itens já salvos
                foreach ($os->itens_os as $item) 
                {
                    if ($item->tipoItem === 'produto')    
                    {
                        $nome = Estoque::find($item->codigoItem)?->descricaoProduto;

                        $this->itensAdicionados[] = 
                        [
                            'item_id' => $item->codigoItem,
                            'tipo' => $item->tipoItem,
                            'nome' => $nome ?? 'Item não encontrado',
                            'quantidade' => $item->quantidadeItem,
                            'valor_unitario' => $item->valorItem,
                        ];
                    }
                    else
                    {
                        $nome = Servico::find($item->codigoItem)?->descricaoServico;

                        $this->itensAdicionados[] = 
                        [
                            'item_id' => $item->codigoItem,
                            'tipo' => $item->tipoItem,
                            'nome' => $nome ?? 'Item não encontrado',
                            'quantidade' => $item->quantidadeItem,
                            'valor_unitario' => $item->valorItem,
                        ];
                    }
                }

                $this->recalcularTotal();
            }
        }
    }

    /**
     * Adiciona um produto/serviço à lista temporária
     */
    public function adicionarItem()
    {
        if (!$this->itemSelecionadoId || (float) $this->quantidadeItem <= 0) 
        {
            return;
        }

        if ($this->tipoItem === 'produto') 
        {
            $prod = Estoque::find($this->itemSelecionadoId);
            
            if ($prod) 
            {
                $this->itensAdicionados[] = 
                [
                    'item_id' => $prod->codigoEstoque,
                    'tipo' => 'produto',
                    'nome' => $prod->descricaoProduto,
                    'unidade' => $prod->unidadeMedidaProduto ?? 'UN', // Exibe se é L ou UN
                    'quantidade' => (float) $this->quantidadeItem,
                    'valor_unitario' => (float) $prod->valorProduto,
                ];
            }
        } 
        else 
        {
            $srv = Servico::find($this->itemSelecionadoId);
            if ($srv) {
                $this->itensAdicionados[] = [
                    'item_id' => $srv->codigoServico,
                    'tipo' => 'servico',
                    'nome' => $srv->descricaoServico,
                    'unidade' => 'UN',
                    'quantidade' => (float) $this->quantidadeItem,
                    'valor_unitario' => (float) $srv->valorServico,
                ];
            }
        }

        $this->reset(['itemSelecionadoId', 'quantidadeItem', 'searchItem']);
        $this->quantidadeItem = 1.0;
        $this->recalcularTotal();
    }

    /**
     * Remove um item da lista temporária pelo índice
     */
    public function removerItem($index)
    {
        unset($this->itensAdicionados[$index]);
        $this->itensAdicionados = array_values($this->itensAdicionados);
        $this->recalcularTotal();
    }

    private function recalcularTotal()
    {
        $this->totalGeral = array_reduce($this->itensAdicionados, function ($acc, $item) {
            return $acc + ($item['quantidade'] * $item['valor_unitario']);
        }, 0.00);
    }

    /**
     * Hook disparado automaticamente quando $tipoItem é alterado na view.
     * Limpa o ID selecionado anteriormente para evitar conflitos de IDs entre tabelas.
     */
    public function updatedTipoItem()
    {
        $this->reset(['searchItem', 'itemSelecionadoId']);
    }

    public function selecionarItem($id, $nome)
    {
        $this->itemSelecionadoId = $id;
        $this->searchItem = $nome; // Atualiza o input com o nome do item selecionado
    }

    public function updatedSearchItem()
    {
        // A busca é atualizada dinamicamente ao digitar
    }

    /**
     * Salva (Criação ou Atualização) no banco de dados
     */
    public function salvar()
    {
        $this->validate();

        if ($this->codigoOs) {
            // Atualizar OS existente
            $os = Os::findOrFail($this->codigoOs);
            $os->update([
                'codigoVeiculo' => $this->codigoVeiculo,
                'statusOs' => $this->statusOs,
                'totalOs' => $this->totalGeral,
                'observacoesOs' => $this->observacoesOs,
            ]);

            // Recreia os itens do relacionamento
            $os->itens_os()->delete();
            foreach ($this->itensAdicionados as $item) {
                ItemOs::create([
                    'codigoOs' => $os->codigoOs,
                    'codigoItem' => $item['item_id'],
                    'tipoItem' => $item['tipo'],
                    'quantidadeItem' => $item['quantidade'],
                    'valorItem' => $item['valor_unitario'],
                ]);
            }

            session()->flash('success', 'Ordem de Serviço #' . str_pad($os->codigoCliente, 5, '0', STR_PAD_LEFT) . ' atualizada!');
        } else {
            // Criar nova OS
            $os = Os::create([
                'codigoVeiculo' => $this->codigoVeiculo,
                'statusOs' => $this->statusOs,
                'totalOs' => $this->totalGeral,
                'observacoesOs' => $this->observacoesOs,
            ]);

            foreach ($this->itensAdicionados as $item) {
                ItemOs::create([
                    'codigoOs' => $os->codigoOs,
                    'codigoItem' => $item['item_id'],
                    'tipoItem' => $item['tipo'],
                    'quantidadeItem' => $item['quantidade'],
                    'valorItem' => $item['valor_unitario'],
                ]);
            }

            session()->flash('success', 'Orçamento/OS cadastrada com sucesso!');
        }

        $urlImpressao = route('ordens-servico.imprimir', ['codigoOs' => $os->codigoOs]);
        $this->js("window.open('{$urlImpressao}', '_blank');");

        return redirect()->route('ordens-servico');
    }

    public function render()
    {
        // Filtra veículos do cliente selecionado se houver
        $veiculos = $this->codigoCliente 
            ? Veiculo::where('codigoCliente', $this->codigoCliente)->get() 
            : Veiculo::all();

        // Filtra produtos apenas se $searchItem tiver valor
        $produtosDisponiveis = Estoque::query()
            ->when($this->searchItem, function ($query) {
                $query->where('descricaoProduto', 'like', '%' . $this->searchItem . '%')
                    ->orWhere('codigoEstoque', 'like', '%' . $this->searchItem . '%');
            })
            ->limit(10) // Limita a 10 resultados para otimizar o carregamento
            ->get();

        // Filtra serviços apenas se $searchItem tiver valor
        $servicosDisponiveis = Servico::query()
            ->when($this->searchItem, function ($query) {
                $query->where('descricaoServico', 'like', '%' . $this->searchItem . '%')
                    ->orWhere('codigoServico', 'like', '%' . $this->searchItem . '%');
            })
            ->limit(10)
            ->get();

        return view('pages.ordens-servico.create', [
            'clientes' => Cliente::all(),
            'veiculosDisponiveis' => $veiculos,
            'produtosDisponiveis' => $produtosDisponiveis,
            'servicosDisponiveis' => $servicosDisponiveis,
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            {{ $codigoOs ? 'Editar Ordem de Serviço #' . str_pad($codigoOs, 5, '0', STR_PAD_LEFT) : 'Novo Orçamento' }}
        </flux:heading>

        <flux:button href="{{ route('ordens-servico') }}" wire:navigate variant="ghost" icon="arrow-left">
            Voltar para o Pátio
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <flux:heading size="lg" class="mb-4">Informações do Veículo e Cliente</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Cliente</flux:label>
                    <flux:select wire:model.live="codigoCliente">
                        <flux:select.option value="">Selecione o cliente...</flux:select.option>
                        @foreach($clientes as $c)
                            <flux:select.option value="{{ $c->codigoCliente }}">{{ $c->nomeCliente }} ({{ $c->cpfCliente }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="codigoCliente" />
                </flux:field>

                <flux:field>
                    <flux:label>Veículo</flux:label>
                    <flux:select wire:model="codigoVeiculo">
                        <flux:select.option value="">Selecione o veículo...</flux:select.option>
                        @foreach($veiculosDisponiveis as $v)
                            <flux:select.option value="{{ $v->codigoVeiculo }}">{{ $v->marcaVeiculo }} {{ $v->modeloVeiculo }} - {{ $v->placaVeiculo }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="codigoVeiculo" />
                </flux:field>

                <flux:field>
                    <flux:label>Status do Atendimento</flux:label>
                    <flux:select wire:model="statusOs">
                        <flux:select.option value="Orçamento">Orçamento</flux:select.option>
                        <flux:select.option value="Aprovado">Aprovado</flux:select.option>
                        <flux:select.option value="Em andamento">Em andamento</flux:select.option>
                        <flux:select.option value="Aguardando peças">Aguardando peças</flux:select.option>
                        <flux:select.option value="Finalizada">Finalizada</flux:select.option>
                        <flux:select.option value="Cancelada">Cancelada</flux:select.option>
                    </flux:select>
                    <flux:error name="statusOs" />
                </flux:field>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <flux:heading size="lg" class="mb-4">Itens do Orçamento / Serviço</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4 items-end">
                <flux:field>
                    <flux:label>Tipo de Item</flux:label>
                    <flux:select wire:model.live="tipoItem">
                        <flux:select.option value="">Selecione o tipo de item...</flux:select.option>
                        <flux:select.option value="produto">Peça / Insumo</flux:select.option>
                        <flux:select.option value="servico">Mão de Obra / Serviço</flux:select.option>
                    </flux:select>
                </flux:field>
  
            <flux:field class="md:col-span-2 relative z-20" x-data="{ open: false }">
                <flux:label>Item Selecionado</flux:label>
                
                <div class="relative">
                    <flux:input 
                        wire:model.live.debounce.300ms="searchItem" 
                        placeholder="Digite para buscar a peça ou serviço..." 
                        @focus="if ($wire.searchItem && $wire.searchItem.length > 0) open = true" 
                        @input="open = $event.target.value.trim().length > 0"
                        @click.outside="open = false" 
                        icon="magnifying-glass"
                        autocomplete="off"
                    />

                    {{-- A lista só renderiza e exibe se houver texto digitado na pesquisa --}}
                    @if(!empty($searchItem))
                        <div 
                            x-show="open" 
                            x-transition 
                            class="absolute z-50 left-0 right-0 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                        >
                            @if($tipoItem === 'produto')
                                @forelse($produtosDisponiveis as $p)
                                    <button 
                                        type="button"
                                        wire:click="selecionarItem('{{ $p->codigoEstoque }}', '{{ addslashes($p->descricaoProduto) }}')" 
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-gray-700/60 text-sm border-b last:border-b-0 border-gray-100 dark:border-gray-700/50 transition-colors duration-150"
                                    >
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $p->descricaoProduto }}</div>
                                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                                            R$ {{ number_format($p->valorProduto, 2, ',', '.') }}
                                        </div>
                                    </button>
                                @empty
                                    <div class="p-3 text-sm text-gray-500 dark:text-gray-400 text-center">Nenhum produto encontrado.</div>
                                @endforelse
                            @else
                                @forelse($servicosDisponiveis as $s)
                                    <button 
                                        type="button"
                                        wire:click="selecionarItem('{{ $s->codigoServico }}', '{{ addslashes($s->descricaoServico) }}')" 
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-gray-700/60 text-sm border-b last:border-b-0 border-gray-100 dark:border-gray-700/50 transition-colors duration-150"
                                    >
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $s->descricaoServico }}</div>
                                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                                            R$ {{ number_format($s->valorServico, 2, ',', '.') }}
                                        </div>
                                    </button>
                                @empty
                                    <div class="p-3 text-sm text-gray-500 dark:text-gray-400 text-center">Nenhum serviço encontrado.</div>
                                @endforelse
                            @endif
                        </div>
                    @endif
                </div>
            </flux:field>           

                <flux:field>
                    <flux:label>Quantidade / Litros</flux:label>
                    <flux:input wire:model="quantidadeItem" type="number" step="0.01" min="0.01" placeholder="Ex: 3.5" />
                </flux:field>

                <flux:button wire:click="adicionarItem" class="mt-6" type="button" variant="primary" icon="plus">
                    Adicionar
                </flux:button>
            </div>

            <div class="overflow-x-auto mt-6">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Descrição</th>
                            <th class="px-4 py-3">Qtd</th>
                            <th class="px-4 py-3">Unitário</th>
                            <th class="px-4 py-3">Subtotal</th>
                            <th class="px-4 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itensAdicionados as $index => $item)
                            @php
                                $badgeVariant = match($item['tipo']) 
                                {
                                    'produto' => 'indigo',
                                    'servico' => 'teal',
                                };
                            @endphp
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="{{ $badgeVariant }}">
                                    {{ ucfirst($item['tipo']) }}
                                </flux:badge>                                
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $item['nome'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ number_format($item['quantidade'], 2, ',', '.') }} {{ $item['unidade'] ?? 'UN' }}</td>
                            <td class="px-4 py-3 font-mono">R$ {{ number_format($item['valor_unitario'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3 font-mono font-bold">R$ {{ number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="removerItem({{ $index }})" variant="danger" size="sm" icon="trash" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                Nenhum item adicionado ao orçamento ainda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6 border-t pt-4 dark:border-gray-700">
                <div class="text-right">
                    <span class="text-sm text-gray-500 block">Valor Total Previsto</span>
                    <span class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">
                        R$ {{ number_format($totalGeral, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <flux:field>
                <flux:label>Relato do Cliente / Diagnóstico Inicial</flux:label>
                <flux:textarea wire:model="observacoesOs" rows="3" placeholder="Descreva os barulhos, falhas relatadas ou serviços a serem executados..." />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button href="{{ route('ordens-servico') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">
                {{ $codigoOs ? 'Atualizar Ordem de Serviço' : 'Salvar e Gerar OS' }}
            </flux:button>
        </div>
    </form>
</div>