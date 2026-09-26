<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('estoque', key: 'codigoEstoque')]
#[Fillable(['codigoProduto', 'descricaoProduto', 'categoriaProduto', 'unidadeMedidaProduto', 'quantidadeProduto', 'estoqueMinimoProduto', 'valorProduto'])]
class Estoque extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantidadeProduto' => 'integer',
            'estoqueMinimoProduto' => 'integer',
            'valorProduto' => 'decimal:2'
        ];
    }

    public function isEstoqueBaixo(): bool
    {
        return $this->quantidadeProduto <= $this->estoqueMinimoProduto;
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'codigoFornecedor');
    }

    public function fornecedores(): BelongsToMany
    {
        return $this->belongsToMany(
            Fornecedor::class,
            'estoque_fornecedor',
            'codigoEstoque',      // Chave estrangeira deste model na tabela pivô
            'codigoFornecedor'    // Chave estrangeira do model relacionado na tabela pivô
        );
    }
}
