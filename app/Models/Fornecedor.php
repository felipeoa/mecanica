<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('fornecedores', key: 'codigoFornecedor')]
#[Fillable(['nomeFantasiaFornecedor', 'razaoSocialFornecedor', 'cnpjFornecedor', 'telefoneFornecedor', 'emailFornecedor', 'vendedorFornecedor'])]
class Fornecedor extends Model
{
    use SoftDeletes;

    /*public function produtos(): HasMany
    {
        return $this->hasMany(Estoque::class, 'codigoFornecedor');
    }*/

    public function produtos(): BelongsToMany
    {
       return $this->belongsToMany(
            Estoque::class,
            'estoque_fornecedor',
            'codigoFornecedor',   // Chave estrangeira deste model na tabela pivô
            'codigoEstoque'       // Chave estrangeira do model relacionado na tabela pivô
        );
    }
}
