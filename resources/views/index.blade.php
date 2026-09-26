<?php

use Livewire\Component;

new class extends Component
{

}
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center">
        <flux:heading size="xl">{{ $clienteId ? 'Editar Cliente' : 'Clientes' }}</flux:heading>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-50 rounded-lg dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        </div>
    </form>

    <div class="mb-4">
        <flux:input wire:model.live="busca" icon="magnifying-glass" placeholder="Filtrar por nome ou CPF..." />
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm mb-6 border border-gray-200 dark:border-gray-700">
        <flux:table :paginate="">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'nomeCliente'" :direction="$sortDirection" wire:click="sort('nomeCLiente')">Nome</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'cpfCliente'" :direction="$sortDirection" wire:click="sort('cpfCliente')">CPF</flux:table.column>
                <flux:table.column>Telefone</flux:table.column>
                <flux:table.column align="center">Ações</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->clientes as $cliente)
                    <flux:table.row :key="$cliente->codigoCliente">
                        <flux:table.cell class="flex items-center gap-3">
                            {{ $cliente->nomeCliente }}
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4 whitespace-nowrap">{{ $cliente->cpfCliente }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $cliente->telefoneCliente }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4" align="center">
                            <flux:button wire:click="editar({{ $cliente->codigoCliente }})" variant="primary" color="yellow" size="sm" icon="pencil-square">Editar</flux:button>
                            <flux:button wire:click="confirmarExclusao({{ $cliente->codigoCliente }})" variant="danger" size="sm" icon="trash">Excluir</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="modal-exclusao" class="min-w-[22rem] space-y-6">
        <div>
            <flux:heading size="lg">Excluir Cliente</flux:heading>
            <flux:subheading class="mt-2">
                Tem certeza de que deseja excluir este cliente?
            </flux:subheading>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="primary" color="rose" icon="x-mark">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button wire:click="deletar" variant="primary" icon="trash">Excluir</flux:button>
        </div>
    </flux:modal>
</div>