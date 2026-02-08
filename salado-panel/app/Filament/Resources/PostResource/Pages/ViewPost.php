<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Preview del Post')->schema([
                Infolists\Components\Grid::make(2)->schema([
                    Infolists\Components\Group::make([
                        Infolists\Components\TextEntry::make('title')->label('Titulo'),
                        Infolists\Components\TextEntry::make('caption')
                            ->label('Caption')
                            ->markdown()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('hashtags')->label('Hashtags'),
                    ]),
                    Infolists\Components\Group::make([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'approved' => 'success',
                                'scheduled' => 'info',
                                'published' => 'primary',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('post_type')->label('Tipo'),
                        Infolists\Components\TextEntry::make('platform')->label('Plataforma'),
                        Infolists\Components\TextEntry::make('campaign.name')->label('Campana'),
                        Infolists\Components\TextEntry::make('day_number')->label('Dia'),
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('Programado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin programar'),
                        Infolists\Components\IconEntry::make('ai_generated')
                            ->label('Generado por IA')
                            ->boolean(),
                    ]),
                ]),
            ]),
            Infolists\Components\Section::make('Imagenes')->schema([
                Infolists\Components\RepeatableEntry::make('images')->schema([
                    Infolists\Components\ImageEntry::make('image_url')
                        ->label('')
                        ->height(200),
                    Infolists\Components\TextEntry::make('alt_text')->label('Descripcion'),
                ])->columns(3),
            ]),
            Infolists\Components\Section::make('Notas')->schema([
                Infolists\Components\TextEntry::make('notes')
                    ->label('')
                    ->placeholder('Sin notas'),
            ])->collapsed(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft')
                ->action(fn () => $this->record->update(['status' => 'approved'])),
        ];
    }
}
