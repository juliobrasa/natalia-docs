<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPostsWidget extends BaseWidget
{
    protected static ?string $heading = 'Proximos Posts';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Post::query()->where('status', 'draft')->orderBy('day_number')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('day_number')->label('Dia'),
                Tables\Columns\ImageColumn::make('images.image_url')
                    ->label('Imagen')
                    ->circular()
                    ->stacked()
                    ->limit(1),
                Tables\Columns\TextColumn::make('title')->label('Titulo')->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors(['gray' => 'draft', 'success' => 'approved']),
                Tables\Columns\TextColumn::make('platform')->label('Plataforma')->badge()->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Post $record) => $record->update(['status' => 'approved'])),
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->url(fn (Post $record) => route('filament.admin.resources.posts.edit', $record)),
            ])
            ->paginated(false);
    }
}
