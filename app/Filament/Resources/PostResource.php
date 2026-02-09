<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Posts';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Contenido del Post')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titulo / Tema')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('caption')
                    ->label('Caption')
                    ->required()
                    ->rows(10)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('hashtags')
                    ->label('Hashtags')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Configuracion')->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('post_type')
                        ->label('Tipo de Post')
                        ->options([
                            'imagen_unica' => 'Imagen unica',
                            'carrusel' => 'Carrusel',
                            'story' => 'Story',
                            'reel' => 'Reel',
                        ])
                        ->required(),
                    Forms\Components\Select::make('platform')
                        ->label('Plataforma')
                        ->options([
                            'instagram' => 'Instagram',
                            'facebook' => 'Facebook',
                            'ambos' => 'Ambos',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'draft' => 'Borrador',
                            'approved' => 'Aprobado',
                            'scheduled' => 'Programado',
                            'published' => 'Publicado',
                            'rejected' => 'Rechazado',
                        ])
                        ->required()
                        ->default('draft'),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make("project_id")
                        ->label("Proyecto")
                        ->relationship("project", "name")
                        ->required()
                        ->default(1),
                    Forms\Components\Select::make('campaign_id')
                        ->label('Campana')
                        ->relationship('campaign', 'name')
                        ->nullable(),
                    Forms\Components\TextInput::make('day_number')
                        ->label('Dia #')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Fecha programada')
                        ->nullable(),
                ]),
            ]),
            Forms\Components\Section::make('Notas')->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('Notas internas')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('ai_generated')
                    ->label('Generado por IA')
                    ->disabled(),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("project.name")
                    ->label("Proyecto")
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_number')
                    ->label('Dia')
                    ->sortable()
                    ->alignCenter()
                    ->width('60px'),
                Tables\Columns\ImageColumn::make('images.image_url')
                    ->label('Imagen')
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->width('80px'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'approved',
                        'info' => 'scheduled',
                        'primary' => 'published',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'approved' => 'Aprobado',
                        'scheduled' => 'Programado',
                        'published' => 'Publicado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('post_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'imagen_unica' => 'Imagen',
                        'carrusel' => 'Carrusel',
                        'story' => 'Story',
                        'reel' => 'Reel',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Plataforma')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'instagram' => 'IG',
                        'facebook' => 'FB',
                        'ambos' => 'IG + FB',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campana')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Programado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Sin programar'),
                Tables\Columns\IconColumn::make('ai_generated')
                    ->label('IA')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('day_number')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'approved' => 'Aprobado',
                        'scheduled' => 'Programado',
                        'published' => 'Publicado',
                        'rejected' => 'Rechazado',
                    ]),
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Plataforma')
                    ->options([
                        'instagram' => 'Instagram',
                        'facebook' => 'Facebook',
                        'ambos' => 'Ambos',
                    ]),
                Tables\Filters\SelectFilter::make("project_id")
                    ->label("Proyecto")
                    ->relationship("project", "name"),
                Tables\Filters\SelectFilter::make('campaign_id')
                    ->label('Campana')
                    ->relationship('campaign', 'name'),
                Tables\Filters\TernaryFilter::make('ai_generated')
                    ->label('Generado por IA'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Post $record) => $record->status === 'draft')
                    ->action(fn (Post $record) => $record->update(['status' => 'approved'])),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Post $record) => in_array($record->status, ['draft', 'approved']))
                    ->action(fn (Post $record) => $record->update(['status' => 'rejected'])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Aprobar seleccionados')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'approved'])),
                Tables\Actions\BulkAction::make('reject_selected')
                    ->label('Rechazar seleccionados')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'rejected'])),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PostResource\RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
            'view' => Pages\ViewPost::route('/{record}'),
        ];
    }
}
