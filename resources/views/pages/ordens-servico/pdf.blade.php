<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ordem de Serviço #{{ str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .logo-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .os-number {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            font-family: monospace;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #eee;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f4f4f4;
            padding: 5px;
            margin-bottom: 8px;
            border-left: 3px solid #4f46e5;
        }
        .grid-2 {
            width: 100%;
            margin-bottom: 15px;
        }
        .grid-2 td {
            vertical-align: top;
            width: 50%;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-items th {
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
            text-align: left;
        }
        .table-items td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px;
            font-size: 11px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-box {
            float: right;
            text-align: right;
            margin-top: 10px;
        }
        .total-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .total-value {
            font-size: 22px;
            font-weight: bold;
            color: #4f46e5;
        }
        .clear { clear: both; }
        .footer-signatures {
            margin-top: 50px;
            width: 100%;
        }
        .signature-line {
            border-top: 1px solid #aaa;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-title">Sua Oficina Mecânica</div>
                    <div class="company-info">Rua da Oficina, 123 - Centro | Tel: (00) 99999-9999</div>
                    <div class="company-info">CNPJ: 00.000.000/0001-00</div>
                </td>
                <td class="text-right">
                    <span class="badge">{{ $os->statusOs }}</span>
                    <div class="os-number">OS #{{ str_pad($os->codigoOs, 5, '0', STR_PAD_LEFT) }}</div>
                    <div class="company-info">Data: {{ $os->created_at->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid-2">
        <tr>
            <td style="padding-right: 10px;">
                <div class="section-title">Dados do Cliente</div>
                <strong>Nome:</strong> {{ $veiculo->cliente->nomeCliente ?? 'N/I' }}<br>
                <strong>CPF/CNPJ:</strong> {{ $veiculo->cliente->cpfCliente ?? 'N/I' }}<br>
                <strong>Telefone:</strong> {{ $veiculo->cliente->telefoneCliente ?? 'N/I' }}
            </td>
            <td style="padding-left: 10px;">
                <div class="section-title">Dados do Veículo</div>
                <strong>Modelo:</strong> {{ $os->veiculo->marcaVeiculo ?? '' }} {{ $os->veiculo->modeloVeiculo ?? 'N/I' }}<br>
                <strong>Placa:</strong> {{ $os->veiculo->placaVeiculo ?? 'N/I' }}<br>
                <strong>Ano/Cor:</strong> {{ $os->veiculo->anoVeiculo ?? '-' }} / {{ $os->veiculo->corVeiculo ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="section-title">Peças e Serviços Executados</div>
    <table class="table-items">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descrição</th>
                <th class="text-center">Qtd</th>
                <th class="text-right">Valor Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($os->itens_os as $item)
            <tr>
                <td style="text-transform: uppercase; color: #666;">{{ $item->tipoItem }}</td>
                <td>
                    @if($item->tipoItem === 'produto')                         
                        {{ \App\Models\Estoque::find($item->codigoItem)?->descricaoProduto ?? 'Insumo/Peça' }}
                    @else
                        {{ \App\Models\Servico::find($item->codigoItem)?->descricaoServico ?? 'Mão de Obra' }}
                    @endif
                </td>
                <td class="text-center">{{ $item->quantidadeItem }}</td>
                <td class="text-right">R${{ number_format($item->valorItem, 2, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold;">
                    R$ {{ number_format($item->quantidadeItem * $item->valorItem, 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="color: #999;">Nenhum item informado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div>
        <div style="float: left; width: 55%; font-size: 10px; background: #f9f9f9; padding: 8px; border: 1px solid #eee;">
            <strong>Observações / Defeito Relatado:</strong><br>
            {{ $os->observacoesOs ?? 'Nenhuma observação registrada.' }}
        </div>

        <div class="total-box">
            <span class="total-label">Valor Total do Orçamento</span><br>
            <span class="total-value">R${{ number_format($os->totalOs, 2, ',', '.') }}</span>
        </div>
        <div class="clear"></div>
    </div>

    <table class="footer-signatures">
        <tr>
            <td style="width: 45%; text-align: center;">
                <div class="signature-line">
                    {{ $veiculo->cliente->nomeCliente ?? 'Cliente' }}<br>
                    Assinatura do Cliente
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; text-align: center;">
                <div class="signature-line">
                    Sua Oficina Mecânica<br>
                    Responsável Técnico
                </div>
            </td>
        </tr>
    </table>

</body>
</html>