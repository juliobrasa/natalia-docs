<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PostStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', Post::count())
                ->description('Todos los posts')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make('Borradores', Post::where('status', 'draft')->count())
                ->description('Pendientes de aprobacion')
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
            Stat::make('Aprobados', Post::where('status', 'approved')->count())
                ->description('Listos para publicar')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Publicados', Post::where('status', 'published')->count())
                ->description('Ya en redes sociales')
                ->icon('heroicon-o-globe-alt')
                ->color('info'),
        ];
    }
}
