<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Proyectos';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationGroup = 'Configuracion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\Textarea::make('description')
                ->label('Descripcion')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'active' => 'Activo',
                    'planning' => 'En diseno',
                    'coming_soon' => 'Proximamente',
                    'completed' => 'Completado',
                ])
                ->default('active'),
            Forms\Components\ColorPicker::make('color')
                ->label('Color')
                ->default('#16a34a'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')->label(''),
                Tables\Columns\TextColumn::make('name')->label('Proyecto')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->badge()->color('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'planning',
                        'info' => 'coming_soon',
                        'gray' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'planning' => 'En diseno',
                        'coming_soon' => 'Proximamente',
                        'completed' => 'Completado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('posts_count')->counts('posts')->label('Posts'),
                Tables\Columns\TextColumn::make('campaigns_count')->counts('campaigns')->label('Campanas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
