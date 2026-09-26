<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Table('empresas', key: 'codigoEmpresa')]
#[Fillable(['razaoSocialEmpresa', 'nomeFantasiaEmpresa', 'cnpjEmpresa', 'telefoneEmpresa', 'emailEmpresa', 'enderecoEmpresa', 'cidadeEmpresa', 'ufEmpresa', 'cepEmpresa', 'logoPathEmpresa'])]
class Empresa extends Model
{
    //
}
