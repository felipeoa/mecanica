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

    public function mount($codigoOs)
    {
        $this->codigoOs = $codigoOs;
    }

    public function render()
    {
        $os = Os::with(['veiculo', 'itens_os'])->findOrFail($this->codigoOs);

        $veiculo = Veiculo::with('cliente')->find($os->codigoVeiculo);
        
        return view('pages.ordens-servico.imprimir', [
            'os' => $os,
            'veiculo' => $veiculo,
        ]);
    }
};
?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: white;
            color: black;
        }
    }
</style>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
   
<div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('ordens-servico') }}" class="px-4 py-2 bg-gray-600 rounded hover:bg-gray-700 text-sm font-medium">
            ← Voltar para o Pátio
        </a>
        <a href="{{ route('ordens-servico.pdf', $os->codigoOs) }}" target="_blank" class="px-4 py-2 bg-indigo-600 rounded hover:bg-indigo-700 text-sm font-medium flex items-center gap-2">
            🖨️ Imprimir / Salvar PDF
        </a>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 border border-gray-200 rounded-lg shadow-sm print:shadow-none print:border-none">
        
        <div class="flex justify-between items-center border-b pb-6 mb-6">
            <div>
                <h1 class="text-2xl font-bold uppercase tracking-wide">Sua Oficina Mecânica</h1>
                <p class="text-xs text-gray-500">Rua da Oficina, 123 - Centro - Fone: (00) 99999-9999</p>
                <p class="text-xs text-gray-500">CNPJ: 00.000.000/0001-00</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-gray-100 rounded text-xs font-bold uppercase mb-1">
                    {{ $os->statusOs }}
                </span>
                <h2 class="text-xl font-mono font-bold text-gray-800">OS #{{ str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) }}</h2>
                <p class="text-xs text-gray-500">Data: {{ $os->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6 text-sm border-b pb-6">
            <div>
                <h3 class="font-bold text-gray-700 uppercase mb-2 text-xs border-b pb-1">Dados do Cliente</h3>
                <p><strong>Nome:</strong> {{ $veiculo->cliente->nomeCliente ?? 'N/A' }}</p>
                <p><strong>CPF:</strong> {{ $veiculo->cliente->cpfCliente ?? 'N/A' }}</p>
                <p><strong>Telefone:</strong> {{ $veiculo->cliente->telefoneCliente ?? 'N/A' }}</p>
            </div>
            <div>
                <h3 class="font-bold text-gray-700 uppercase mb-2 text-xs border-b pb-1">Dados do Veículo</h3>
                <p><strong>Modelo:</strong> {{ $os->veiculo->marcaVeiculo ?? '' }} {{ $os->veiculo->modeloVeiculo  ?? 'N/A' }}</p>
                <p><strong>Placa:</strong> <span class="font-mono uppercase font-bold">{{ $os->veiculo->placaVeiculo  ?? 'N/A' }}</span></p>
                <p><strong>Ano/Cor:</strong> {{ $os->veiculo->anoVeiculo  ?? '-' }} / {{ $os->veiculo->corVeiculo  ?? '-' }}</p>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="font-bold text-gray-700 uppercase mb-3 text-xs">Discriminação de Peças e Serviços</h3>
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50 text-xs uppercase text-gray-600">
                        <th class="py-2 px-3">Tipo</th>
                        <th class="py-2 px-3">Descrição</th>
                        <th class="py-2 px-3 text-center">Qtd</th>
                        <th class="py-2 px-3 text-right">Valor Unit.</th>
                        <th class="py-2 px-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($os->itens_os as $item)
                    <tr class="border-b text-xs">
                        <td class="py-2 px-3 uppercase text-gray-500">{{ $item->tipoItem }}</td>
                        <td class="py-2 px-3 font-medium">
                            @if($item->tipoItem === 'produto')
                                {{ \App\Models\Estoque::find($item->codigoItem)?->descricaoProduto ?? 'Insumo/Peça' }}
                            @else
                                {{ \App\Models\Servico::find($item->codigoItem)?->descricaoServico ?? 'Mão de Obra' }}
                            @endif
                        </td>
                        <td class="py-2 px-3 text-center font-mono">{{ $item->quantidadeItem }}</td>
                        <td class="py-2 px-3 text-right font-mono">R$ {{ number_format($item->valorItem, 2, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">R$ {{ number_format($item->quantidadeItem * $item->valorItem, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-400">Nenhum item adicionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-2 gap-6 items-start mb-12">
            <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded border">
                <strong>Observações / Defeito Relatado:</strong>
                <p class="mt-1 whitespace-pre-line">{{ $os->observacoesOs ?? 'Sem observações adicionais.' }}</p>
            </div>
            <div class="text-right">
                <span class="text-xs uppercase text-gray-500 font-bold block">Valor Total do Orçamento</span>
                <span class="text-3xl font-extrabold text-gray-900">
                    R$ {{ number_format($os->totalOs, 2, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-12 pt-8 border-t text-center text-xs mt-12">
            <div>
                <div class="border-b border-gray-400 mb-2"></div>
                <p class="font-bold">{{ $veiculo->cliente->nomeCliente ?? 'Cliente' }}</p>
                <p class="text-gray-500">Assinatura do Cliente</p>
            </div>
            <div>
                <div class="border-b border-gray-400 mb-2"></div>
                <p class="font-bold">Sua Oficina Mecânica</p>
                <p class="text-gray-500">Técnico / Responsável</p>
            </div>
        </div>
    </div>
</div>