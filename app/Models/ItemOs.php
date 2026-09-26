<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('itens_os', key: 'codigoItemOs')]
#[Fillable(['codigoOs', 'codigoItem', 'tipoItem', 'quantidadeItem', 'valorItem'])]
class ItemOs extends Model
{
    // Sem SoftDeletes pois depende diretamente do ciclo de vida da OS pai
    protected function casts(): array
    {
        return [
            'quantidadeItem' => 'integer',
            'valorItem' => 'decimal:2'
        ];
    }

    public function os(): BelongsTo
    {
        return $this->belongsTo(Os::class, 'codigoOs');
    }

    /**
     * Resgata dinamicamente a entidade real (Produto ou Serviço)
     */
    public function getEntidadeItemAttribute()
    {
        return $this->tipo === 'produto'
            ? Estoque::find($this->codigoItem)
            : Servico::find($this->codigoItem);
    }
}
