<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit" icon="heroicon-o-sparkles">
                Generar con IA
            </x-filament::button>
        </div>
    </form>

    @if($this->resultado)
        <div class="mt-6">
            <x-filament::section heading="Resultado Generado">
                <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-sm">
                    {!! nl2br(e($this->resultado)) !!}
                </div>

                <div class="mt-4 flex gap-3">
                    <x-filament::button wire:click="saveAsDraft" color="success" icon="heroicon-o-document-plus">
                        Guardar como Borrador
                    </x-filament::button>
                    <x-filament::button wire:click="$set('resultado', '')" color="gray" icon="heroicon-o-trash">
                        Descartar
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
