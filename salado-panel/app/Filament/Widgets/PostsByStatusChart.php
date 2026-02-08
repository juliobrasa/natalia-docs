<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\ChartWidget;

class PostsByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Posts por Estado';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $statuses = ['draft', 'approved', 'scheduled', 'published', 'rejected'];
        $labels = ['Borrador', 'Aprobado', 'Programado', 'Publicado', 'Rechazado'];
        $counts = [];

        foreach ($statuses as $status) {
            $counts[] = Post::where('status', $status)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Posts',
                    'data' => $counts,
                    'backgroundColor' => ['#9ca3af', '#22c55e', '#3b82f6', '#8b5cf6', '#ef4444'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
