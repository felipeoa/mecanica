<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('os', key: 'codigoOs')]
#[Fillable(['codigoVeiculo', 'statusOs', 'totalOs', 'observacoesOs'])]
class Os extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'totalOs' => 'decimal:2'
        ];
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'codigoVeiculo');
    }

    public function itens_os(): HasMany
    {
        return $this->hasMany(ItemOs::class, 'codigoOs');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'codigoOs');
    }

    public function atualizarTotal(): void
    {
        $this->total = $this->itens()->sum(DB::raw('quantidadeItem * valorItem'));
        $this->save();
    }
}
