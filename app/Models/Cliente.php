<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('clientes', key: 'codigoCliente')]
#[Fillable(['nomeCliente', 'cpfCliente', 'dataNascimentoCliente', 'telefoneCliente', 'enderecoCliente'])]
class Cliente extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'dataNascimentoCliente' => 'date',
        ];
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class, 'codigoCliente');
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(Os::class, 'codigoCliente');
    }
}
