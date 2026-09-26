<?php

use Illuminate\Support\Facades\Route;
use App\Models\Os;
use App\Models\Veiculo;
use Barryvdh\DomPDF\Facade\Pdf;

// Rota de Login nativa ou redirecionamento
Route::get('/', function () { return redirect()->route('login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    //Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

Route::middleware(['auth'])->group(function () 
{    
    Route::livewire('clientes', 'pages::clientes')->name('clientes');
    Route::livewire('veiculos', 'pages::veiculos')->name('veiculos');
    Route::livewire('fornecedores', 'pages::fornecedores')->name('fornecedores');
    Route::livewire('estoques', 'pages::estoques')->name('estoques');
    Route::livewire('servicos', 'pages::servicos')->name('servicos');
    Route::livewire('pagamentos', 'pages::pagamentos')->name('pagamentos');
    
    Route::livewire('ordens-servico', 'pages::ordens-servico')->name('ordens-servico');
    Route::livewire('ordens-servico/criar/{codigoOs?}', 'pages::ordens-servico.create')->name('ordens-servico.create');
    Route::livewire('ordens-servico/imprimir/{codigoOs}', 'pages::ordens-servico.imprimir')->name('ordens-servico.imprimir');
    Route::livewire('ordens-servico/pagamentos/{codigoOs}', 'pages::ordens-servico.pagamento')->name('ordens-servico.pagamento');

    //Route::get('ordens-servico/imprimir/{codigoOs}/pdf', 'pages::ordens-servico.pdf')->name('ordens-servico.pdf');


    Route::get('/ordens-servico/imprimir/{codigoOs}/pdf', function ($codigoOs) 
    {
        $os = Os::with(['veiculo', 'itens_os'])->findOrFail($codigoOs);
        $veiculo = Veiculo::with('cliente')->find($os->codigoVeiculo);

        // Carrega a view Blade formatada para o PDF
        $pdf = Pdf::loadView('pages.ordens-servico.pdf', compact('os', 'veiculo'))->setPaper('a4', 'portrait');

        // 'stream' abre o PDF diretamente no navegador (pronto para imprimir/salvar)
        return $pdf->stream('OS-' . str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) . '.pdf');

        //return view('pages.ordens-servico.pdf', compact('os', 'veiculo'));

    })->name('ordens-servico.pdf');


    // Todos os perfis acessam o Dashboard e a listagem de OS
    //Route::livewire('/admin/dashboard', 'pages::admin.dashboard');

    /*Route::get('/ordens-servico', GestaoOs::class)->name('os.index');

    // Restrição para Administrador e Atendente
    Route::middleware(['can:access-gerencial'])->group(function () {
        Route::get('/clientes', ListagemClientes::class)->name('clientes.index');
        Route::get('/estoque', GestaoEstoque::class)->name('estoque.index');
        Route::get('/fornecedores', ListagemFornecedores::class)->name('fornecedores.index');
        Route::get('/orcamentos/novo', CriarOrcamento::class)->name('orcamento.novo');
    }); */
});

require __DIR__.'/settings.php';