<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('pagamentos', key: 'codigoPagamento')]
#[Fillable(['codigoOs', 'formaPagamento', 'valorPagamento', 'parcelasPagamento', 'observacaoPagamento'])]
class Pagamento extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'valorPagamento' => 'decimal:2',
        ];
    }

    public function os(): BelongsTo
    {
        return $this->belongsTo(Os::class, 'codigoOs');
    }
}
