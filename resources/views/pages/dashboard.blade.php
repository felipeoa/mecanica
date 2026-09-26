<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Os;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public function render()
    {
        $faturamento = Pagamento::whereMonth('created_at', now()->month)->sum('valorPagamento');
        $os_abertas = Os::whereIn('statusOs', ['Aberta', 'Aprovada', 'Em andamento'])->count();
        $produtos_criticos = DB::table('estoque')->whereRaw('quantidadeProduto <= estoqueMinimoProduto')->whereNull('deleted_at')->count();

        $statusCounts = Os::select('statusOs', DB::raw('count(*) as total'))
            ->groupBy('statusOs')
            ->pluck('total', 'statusOs')
            ->toArray();

        $faturamentoSemanal = Pagamento::select(
            DB::raw('WEEK(created_at) as semana'),
            DB::raw('SUM(valorPagamento) as total')
        )
            ->whereMonth('created_at', now()->month)
            ->groupBy('semana')
            ->orderBy('semana')
            ->pluck('total')
            ->toArray();

        return view('pages.dashboard', [
            'faturamento' => $faturamento,
            'os_abertas' => $os_abertas,
            'produtos_criticos' => $produtos_criticos,
            'statusData' => array_values($statusCounts),
            'statusLabels' => array_keys($statusCounts),
            'faturamentoSemanal' => array_values($faturamentoSemanal)
        ]);
    }
}
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 w-auto rounded shadow-sm mr-4">
            <div>
                <h1 class="text-2xl font-bold">Pierre Cotrim</h1>
                <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Gestão para Oficina Mecânica</p>
            </div>
        </div>
        <div class="text-right">
            <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20">Operação Ativa</span>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Caixa Mensal</p>
            <p class="text-2xl font-semibold text-green-600">R$ {{ number_format($faturamento, 2, ',', '.') }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Ordens em Execução</p>
            <p class="text-2xl font-semibold text-yellow-600">{{ $os_abertas }} <span class="text-xs font-normal text-gray-400">no pátio</span></p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Estoque Crítico</p>
            <p class="text-2xl font-semibold text-red-600">{{ $produtos_criticos }} <span class="text-xs font-normal text-gray-400">peças abaixo do mínimo</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-medium mb-4">Evolução do Faturamento Semanal</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="chartFaturamento"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-medium mb-4">Status da Oficina</h3>
            <div class="relative flex items-center justify-center" style="height: 300px;">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('livewire:navigated', () => {
            const ctxFaturamento = document.getElementById('chartFaturamento');
            if (ctxFaturamento) {
                new Chart(ctxFaturamento, {
                    type: 'bar',
                    data: {
                        labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
                        datasets: [{
                            label: 'Faturamento Semanal (R$)',
                            data: @json($faturamentoSemanal),
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            const ctxStatus = document.getElementById('chartStatus');
            if (ctxStatus) {
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: @json($statusLabels),
                        datasets: [{
                            data: @json($statusData),
                            backgroundColor: ['#6b7280', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            }
        });
    </script>
</div>