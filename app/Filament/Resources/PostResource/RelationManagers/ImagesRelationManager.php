<?php

namespace App\Filament\Resources\PostResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';
    protected static ?string $title = 'Imagenes del Post';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('image_url')
                ->label('URL de Imagen')
                ->required()
                ->url()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('image_path')
                ->label('Nombre del archivo')
                ->required(),
            Forms\Components\TextInput::make('alt_text')
                ->label('Texto alternativo'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Orden')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Preview')
                    ->height(80),
                Tables\Columns\TextColumn::make('image_path')
                    ->label('Archivo'),
                Tables\Columns\TextColumn::make('alt_text')
                    ->label('Descripcion')
                    ->limit(40),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('sort_order');
    }
}
