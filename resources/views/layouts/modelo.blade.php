<!----APP---->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficina Mecânica - Pierre Cotrim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .wrapper { display: flex; min-height: 100vh; }
        #sidebar { min-width: 250px; background: #212529; color: #fff; }
        #sidebar .nav-link { color: #adb5bd; padding: 15px 20px; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { color: #fff; background: #343a40; }
        #content { flex: 1; padding: 30px; }
    </style>
    @livewireStyles
</head>
<body>
    <div class="wrapper">
        <nav id="sidebar" class="d-flex flex-column p-3">
            <h4>Oficina Mecânica</h4>
            <small class="text-muted mb-4">{{ Auth::user()->name }} ({{ Auth::user()->perfil }})</small>
            <ul class="nav nav-pills flex-column mb-auto">
                <li><a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a></li>
                @if(in_array(Auth::user()->perfil, ['Administrador', 'Atendente']))
                    <li><a href="{{ route('clientes.index') }}" class="nav-link">Clientes</a></li>
                    <li><a href="{{ route('veiculos.index') }}" class="nav-link">Veículos</a></li>
                    <li><a href="{{ route('estoque.index') }}" class="nav-link">Estoque</a></li>
                    <li><a href="{{ route('fornecedores.index') }}" class="nav-link">Fornecedores</a></li>
                    <li><a href="{{ route('orcamento.novo') }}" class="nav-link">Novo Orçamento</a></li>
                @endif
                <li><a href="{{ route('os.index') }}" class="nav-link">Ordens de Serviço</a></li>
            </ul>
        </nav>
        <div id="content">
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!----DASHBOARD---->
<div class="p-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0">
            <img src="{{ asset('images/logo-pierre-cotrim.jpg') }}" alt="Logo Pierre Cotrim" class="h-16 w-auto rounded shadow-sm mr-4">
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