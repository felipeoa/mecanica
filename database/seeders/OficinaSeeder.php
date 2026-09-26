<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Veiculo;
use App\Models\Fornecedor;
use App\Models\Estoque;
use App\Models\Servico;
use App\Models\Os;
use App\Models\ItemOs;
use App\Models\Pagamento;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OficinaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Usuários de Teste (Um para cada perfil)
        User::create([
            'name' => 'Admin',
            'email' => 'admin@pierre.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'Administrador',
        ]);

        User::create([
            'name' => 'Carlos Atendente',
            'email' => 'atendente@pierre.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'Atendente',
        ]);

        User::create([
            'name' => 'Marcos Mecânico',
            'email' => 'mecanico@pierre.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'Mecânico',
        ]);

        // 2. Criar Fornecedores
        $forn1 = Fornecedor::create([
            'nomeFantasiaFornecedor' => 'Distribuidora AutoPeças Brasil',
            'cnpjFornecedor' => '12.345.678/0001-99',
            'telefoneFornecedor' => '(11) 98888-7777',
            'emailFornecedor' => 'vendas@autopasbrasil.com',
            'vendedorFornecedor' => 'Roberto Silveira'
        ]);

        $forn2 = Fornecedor::create([
            'nomeFantasiaFornecedor' => 'Zeca Filtros e Lubrificantes',
            'cnpjFornecedor' => '98.765.432/0001-11',
            'telefoneFornecedor' => '(11) 97777-6666',
            'emailFornecedor' => 'contato@zecafiltros.com',
            'vendedorFornecedor' => 'Aline Souza'
        ]);

        // 3. Criar Itens no Estoque
        $oleo = Estoque::create([
            'codigoFornecedor' => $forn2->codigoFornecedor,
            'codigoProduto' => 'LUB-5W30',
            'descricaoProduto' => 'Óleo de Motor 5W30 Sintético 1L',
            'categoriaProduto' => 'Lubrificantes',
            'quantidadeProduto' => 45,
            'estoqueMinimoProduto' => 15,
            'valorProduto' => 59.90
        ]);

        $pastilha = Estoque::create([
            'codigoFornecedor' => $forn1->codigoFornecedor,
            'codigoProduto' => 'FRE-PA01',
            'descricaoProduto' => 'Pastilha de Freio Dianteira - Cobreq',
            'categoriaProduto' => 'Freios',
            'quantidadeProduto' => 3, // Vai disparar alerta de estoque crítico!
            'estoqueMinimoProduto' => 5,
            'valorProduto' => 149.90
        ]);

        $filtro = Estoque::create([
            'codigoFornecedor' => $forn2->codigoFornecedor,
            'codigoProduto' => 'FIL-OB02',
            'descricaoProduto' => 'Filtro de Óleo Automotivo Fram',
            'categoriaProduto' => 'Filtros',
            'quantidadeProduto' => 22,
            'estoqueMinimoProduto' => 8,
            'valorProduto' => 34.50
        ]);

        // 4. Criar Catálogo de Serviços Base
        $srvTrocaOleo = Servico::create(['descricaoServico' => 'Troca de Óleo e Filtros', 'valorServico' => 80.00]);
        $srvAlinhamento = Servico::create(['descricaoServico' => 'Alinhamento e Balanceamento 3D', 'valorServico' => 120.00]);
        $srvMaoObraGeral = Servico::create(['descricaoServico' => 'Mão de Obra de Reparação Geral', 'valorServico' => 250.00]);

        // 5. Criar Clientes e Veículos
        $c1 = Cliente::create([
            'nomeCliente' => 'Ronaldo Nazário', 'cpfCliente' => '111.222.333-44', 'dataNascimentoCliente' => '1982-09-22',
            'telefoneCliente' => '(11) 99111-2222', 'enderecoCliente' => 'Av. Paulista, 1000 - São Paulo/SP'
        ]);
        $v1 = Veiculo::create([
            'codigoCliente' => $c1->codigoCliente, 'marcaVeiculo' => 'Volkswagen', 'modeloVeiculo' => 'Golf TSI', 'anoVeiculo' => 2018, 'corVeiculo' => 'Alemão Cinza', 'placaVeiculo' => 'BRA2E19'
        ]);

        $c2 = Cliente::create([
            'nomeCliente' => 'Ayrton Senna da Silva', 'cpfCliente' => '555.666.777-88', 'dataNascimentoCliente' => '1960-03-21',
            'telefoneCliente' => '(11) 99555-4444', 'enderecoCliente' => 'Rua do Horto, 250 - São Paulo/SP'
        ]);
        $v2 = Veiculo::create([
            'codigoCliente' => $c2->codigoCliente, 'marcaVeiculo' => 'Honda', 'modeloVeiculo' => 'Civic Touring', 'anoVeiculo' => 2021, 'corVeiculo' => 'Preto Cristal', 'placaVeiculo' => 'SEN1010'
        ]);

        // 6. Criar Histórico Financeiro distribuído por semanas (Para o gráfico de barras)
        $hoje = Carbon::now();

        // Semana 1 do mês atual
        $this->criarOsFechada($c1, $v1, $oleo, $srvTrocaOleo, 4, $hoje->copy()->startOfMonth()->addDays(2));
        
        // Semana 2 do mês atual
        $this->criarOsFechada($c2, $v2, $pastilha, $srvMaoObraGeral, 1, $hoje->copy()->startOfMonth()->addDays(10));
        
        // Semana 3 do mês atual
        $this->criarOsFechada($c1, $v1, $filtro, $srvAlinhamento, 2, $hoje->copy()->startOfMonth()->addDays(18));

        // 7. Criar OS em andamento e orçamentos abertos (Para o gráfico de rosca)
        Os::create([
            'codigoVeiculo' => $v1->codigoVeiculo, 'statusOs' => 'Em andamento', 'totalOs' => 350.00
        ]);
        Os::create([
            'codigoVeiculo' => $v2->codigoVeiculo, 'statusOs' => 'Orçamento', 'totalOs' => 1500.00
        ]);
    }

    // Auxiliar para gerar OS finalizada com pagamentos em datas retroativas específicas
    private function criarOsFechada($cliente, $veiculo, $produto, $servico, $qtdProd, $data)
    {
        $totalItem = ($produto->valorProduto * $qtdProd) + $servico->valorServico;

        $os = Os::create([
            'codigoVeiculo' => $veiculo->codigoVeiculo,
            'statusOs' => 'Finalizada',
            'totalOs' => $totalItem,
            'created_at' => $data,
            'updated_at' => $data
        ]);

        ItemOs::create([
            'codigoOs' => $os->codigoOs, 'codigoItem' => $produto->codigoProduto, 'tipoItem' => 'produto', 'quantidadeItem' => $qtdProd, 'valorItem' => $produto->valorProduto, 'created_at' => $data
        ]);

        ItemOs::create([
            'codigoOs' => $os->codigoOs, 'codigoItem' => $servico->codigoServico, 'tipoItem' => 'servico', 'quantidadeItem' => 1, 'valorItem' => $servico->valorServico, 'created_at' => $data
        ]);

        Pagamento::create([
            'codigoOs' => $os->codigoOs,
            'valorPagamento' => $totalItem,
            'formaPagamento' => 'Pix',
            'parcelasPagamento' => 1,
            'created_at' => $data,
            'updated_at' => $data
        ]);
    }
}