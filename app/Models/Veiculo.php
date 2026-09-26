<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('veiculos', key: 'codigoVeiculo')]
#[Fillable(['codigoCliente', 'marcaVeiculo', 'modeloVeiculo', 'anoVeiculo', 'corVeiculo', 'placaVeiculo'])]
class Veiculo extends Model
{
    use SoftDeletes;

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'codigoCliente');
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(Os::class, 'codigoVeiculo');
    }
}