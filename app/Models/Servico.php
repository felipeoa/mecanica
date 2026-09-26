<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('servicos', key: 'codigoServico')]
#[Fillable(['descricaoServico', 'valorServico'])]
class Servico extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'valorServico' => 'decimal:2'
        ];
    }
}
