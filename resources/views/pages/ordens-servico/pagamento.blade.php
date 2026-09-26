<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Os;
use App\Models\Pagamento;
use App\Models\Veiculo;

new class extends Component
{
    use WithPagination;

    public $codigoOs;
    public $os;
    public $veiculo;

    // Resumo da OS
    public $subtotal = 0.00;
    public $desconto = 0.00;
    public $totalComDesconto = 0.00;

    // Inputs para adicionar uma nova forma de pagamento
    public $formaPagamentoSelecionada = 'Pix';
    public $valorPagamento = 0.00;
    public $parcelas = 1;
    public $observacaoItem = '';

    // Lista temporária e acumuladores
    public $pagamentosAdicionados = [];
    public $totalPago = 0.00;
    public $saldoRestante = 0.00;

    public function mount($codigoOs)
    {
        $this->codigoOs = $codigoOs;
        $this->os = Os::with(['itens_os', 'pagamentos'])->findOrFail($codigoOs);
        $this->veiculo = Veiculo::with(['cliente'])->findOrFail($this->os->codigoVeiculo);
    
        $this->subtotal = (float) $this->os->totalOs;

        // Carrega pagamentos previamente cadastrados (se houver)
        foreach ($this->os->pagamentos as $pag) {
            $this->pagamentosAdicionados[] = [
                'id' => $pag->codigoPagamento,
                'forma_pagamento' => $pag->formaPagamento,
                'valor' => (float) $pag->valorPagamento,
                'parcelas' => (int) $pag->parcelasPagamento,
                'observacao' => $pag->observacaoPagamento,
            ];
        }

        $this->recalcularTotais();
        $this->valorPagamento = $this->saldoRestante;
    }

    public function updatedDesconto()
    {
        $this->recalcularTotais();
        $this->valorPagamento = $this->saldoRestante;
    }

    public function recalcularTotais()
    {
        $desc = is_numeric($this->desconto) ? (float) $this->desconto : 0.00;
        $this->totalComDesconto = max(0, $this->subtotal - $desc);

        // Soma todas as formas de pagamento inseridas
        $this->totalPago = array_reduce($this->pagamentosAdicionados, function ($acc, $item) {
            return $acc + $item['valor'];
        }, 0.00);

        $this->saldoRestante = max(0, $this->totalComDesconto - $this->totalPago);
    }

    /**
     * Adiciona uma forma de pagamento à lista
     */
    public function adicionarPagamento()
    {
        $valor = (float) $this->valorPagamento;

        if ($valor <= 0) {
            $this->addError('valorPagamento', 'Informe um valor válido.');
            return;
        }

        if ($valor > ($this->saldoRestante + 0.01)) {
            $this->addError('valorPagamento', 'O valor inserido é maior que o saldo restante.');
            return;
        }

        $this->pagamentosAdicionados[] = [
            'forma_pagamento' => $this->formaPagamentoSelecionada,
            'valor' => $valor,
            'parcelas' => $this->formaPagamentoSelecionada === 'Cartão de Crédito' ? (int) $this->parcelas : 1,
            'observacao' => $this->observacaoItem,
        ];

        $this->reset(['valorPagamento', 'observacaoItem', 'parcelas']);
        $this->parcelas = 1;
        $this->recalcularTotais();
        $this->valorPagamento = $this->saldoRestante;
    }

    /**
     * Remove um pagamento da lista temporária
     */
    public function removerPagamento($index)
    {
        if (isset($this->pagamentosAdicionados[$index])) {
            unset($this->pagamentosAdicionados[$index]);
            $this->pagamentosAdicionados = array_values($this->pagamentosAdicionados);
            $this->recalcularTotais();
            $this->valorPagamento = $this->saldoRestante;
        }
    }

    /**
     * Finaliza e salva o recebimento
     */
    public function concluirPagamento()
    {
        if (count($this->pagamentosAdicionados) === 0) {
            $this->addError('pagamentos', 'Adicione pelo menos uma forma de pagamento.');
            return;
        }

        // Salva/Atualiza o desconto e o status da OS
        $this->os->update([
            'status' => ($this->saldoRestante <= 0) ? 'Finalizada' : $this->os->status
        ]);

        // Regrava os pagamentos vinculados
        $this->os->pagamentos()->delete();
        foreach ($this->pagamentosAdicionados as $pag) {
            Pagamento::create([
                'codigoOs' => $this->os->codigoOs ?? $this->os->id,
                'formaPagamento' => $pag['forma_pagamento'],
                'valorPagamento' => $pag['valor'],
                'parcelasPagamento' => $pag['parcelas'],
                'observacaoPagamento' => $pag['observacao'],
            ]);
        }

        session()->flash('success', 'Pagamentos registrados e Ordem de Serviço atualizada!');

        return redirect()->route('ordens-servico');
    }

    public function render()
    {
        return view('pages.ordens-servico.pagamento');
    }    
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Recebimento da OS #{{ str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) }}</flux:heading>
            <flux:subheading>Cliente: {{ $veiculo->cliente->nomeCliente ?? 'N/I' }} | Veículo: {{ $veiculo->modeloVeiculo ?? 'N/I' }} ({{ $veiculo->placaVeiculo ?? 'N/I' }})</flux:subheading>
        </div>

        <flux:button href="{{ route('ordens-servico') }}" wire:navigate variant="ghost" icon="arrow-left">
            Voltar
        </flux:button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Resumo da OS -->
        <div class="md:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
            <div>
                <flux:heading size="lg" class="mb-4">Resumo da OS</flux:heading>

                <div class="space-y-3 text-sm">
                    @foreach($os->itens_os as $item)
                        <div class="flex justify-between border-b pb-2 dark:border-gray-700">
                            <div>
                                <span class="font-medium block text-gray-800 dark:text-gray-200">
                                    @if($item->tipoItem === 'produto')
                                        {{ \App\Models\Estoque::find($item->codigoItem)?->descricaoProduto ?? 'Peça' }}
                                    @else
                                        {{ \App\Models\Servico::find($item->codigoItem)?->descricaoServico ?? 'Serviço' }}
                                    @endif
                                </span>
                                <span class="text-xs text-gray-500">{{ $item->quantidadeItem }}x R$ {{ number_format($item->valorItem, 2, ',', '.') }}</span>
                            </div>
                            <span class="font-mono font-bold">R$ {{ number_format($item->quantidadeItem * $item->valorItem, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 border-t pt-4 dark:border-gray-700 space-y-2 text-sm">
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span class="font-mono font-bold">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-gray-600 dark:text-gray-400 items-center">
                    <span>Desconto (R$)</span>
                    <flux:input wire:model.live="desconto" type="number" step="0.01" min="0" class="w-32 text-right" placeholder="0,00" />
                </div>
                <div class="flex justify-between text-gray-800 dark:text-gray-200 font-bold border-t pt-2">
                    <span>Total com Desconto</span>
                    <span class="font-mono text-indigo-600 dark:text-indigo-400">R$ {{ number_format($totalComDesconto, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-green-600 dark:text-green-400 font-bold">
                    <span>Total Já Pago</span>
                    <span class="font-mono">R$ {{ number_format($totalPago, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-red-600 dark:text-red-400 font-bold text-base border-t pt-2">
                    <span>Saldo Restante</span>
                    <span class="font-mono">R$ {{ number_format($saldoRestante, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Adicionar e Lista de Pagamentos -->
        <div class="md:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-6">
            <div>
                <flux:heading size="lg" class="mb-4">Adicionar Forma de Pagamento</flux:heading>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>Forma de Pagamento</flux:label>
                            <flux:select wire:model.live="formaPagamentoSelecionada">
                                <flux:select.option value="Pix">Pix</flux:select.option>
                                <flux:select.option value="Dinheiro">Dinheiro</flux:select.option>
                                <flux:select.option value="Cartão de Crédito">Cartão de Crédito</flux:select.option>
                                <flux:select.option value="Cartão de Débito">Cartão de Débito</flux:select.option>
                                <flux:select.option value="Boleto">Boleto Bancário</flux:select.option>
                            </flux:select>
                        </flux:field>

                        @if($formaPagamentoSelecionada === 'Cartão de Crédito')
                            <flux:field>
                                <flux:label>Parcelas</flux:label>
                                <flux:select wire:model="parcelas">
                                    <flux:select.option value="1">1x (À Vista)</flux:select.option>
                                    <flux:select.option value="2">2x</flux:select.option>
                                    <flux:select.option value="3">3x</flux:select.option>
                                    <flux:select.option value="4">4x</flux:select.option>
                                    <flux:select.option value="5">5x</flux:select.option>
                                    <flux:select.option value="6">6x</flux:select.option>
                                    <flux:select.option value="10">10x</flux:select.option>
                                    <flux:select.option value="12">12x</flux:select.option>
                                </flux:select>
                            </flux:field>
                        @endif

                        <flux:field class="{{ $formaPagamentoSelecionada !== 'Cartão de Crédito' ? 'md:col-span-2' : '' }}">
                            <flux:label>Valor (R$)</flux:label>
                            <flux:input wire:model="valorPagamento" type="number" step="0.01" min="0" placeholder="0.00" icon="currency-dollar" />
                            <flux:error name="valorPagamento" />
                        </flux:field>
                    </div>

                    <flux:field class="mt-3">
                        <flux:label>Observação do Item</flux:label>
                        <flux:input wire:model="observacaoItem" placeholder="Ex: Comprovante, número da transação, etc." />
                    </flux:field>

                    <div class="flex justify-end mt-3">
                        <flux:button wire:click="adicionarPagamento" variant="filled" icon="plus" class="w-full md:w-auto">
                            Adicionar Pagamento
                        </flux:button>
                    </div>
                </div>
            </div>

            <hr class="dark:border-gray-700" />

            <!-- Pagamentos Inseridos -->
            <div>
                <flux:heading size="md" class="mb-3">Pagamentos Lançados</flux:heading>

                @error('pagamentos')
                    <p class="text-sm text-red-500 mb-2">{{ $message }}</p>
                @enderror

                @if(count($pagamentosAdicionados) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2">Forma</th>
                                    <th class="px-4 py-2">Parcelas</th>
                                    <th class="px-4 py-2">Observação</th>
                                    <th class="px-4 py-2 text-right">Valor</th>
                                    <th class="px-4 py-2 text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pagamentosAdicionados as $index => $pag)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $pag['forma_pagamento'] }}</td>
                                        <td class="px-4 py-2">{{ $pag['parcelas'] }}x</td>
                                        <td class="px-4 py-2">{{ $pag['observacao'] ?: '-' }}</td>
                                        <td class="px-4 py-2 text-right font-mono font-bold">R$ {{ number_format($pag['valor'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <flux:button wire:click="removerPagamento({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        Nenhuma forma de pagamento adicionada até o momento.
                    </div>
                @endif
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-2 pt-4 border-t dark:border-gray-700">
                <flux:button href="{{ route('ordens-servico') }}" wire:navigate variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="concluirPagamento" variant="primary" icon="check-circle" :disabled="$saldoRestante > 0.01">
                    Finalizar Recebimento
                </flux:button>
            </div>
        </div>
    </div>
</div>