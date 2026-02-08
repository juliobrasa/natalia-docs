<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $s1 = Project::create([
            'name' => 'Salado 1',
            'slug' => 'salado-1',
            'description' => 'Primera fase de Universo Salado. Resort con 3 bloques residenciales (A, B, C), 5 tipologias de apartamentos, piscina con bar, gimnasio, co-working, acceso a golf y playa. Dentro de White Sands, Bavaro, Punta Cana.',
            'status' => 'active',
            'color' => '#16a34a',
        ]);

        Project::create([
            'name' => 'Salado 2',
            'slug' => 'salado-2',
            'description' => 'Segunda fase de Universo Salado. En parcela contigua a Salado 1. Actualmente en fase de diseno.',
            'status' => 'planning',
            'color' => '#3b82f6',
        ]);

        Project::create([
            'name' => 'Salado 3',
            'slug' => 'salado-3',
            'description' => 'Tercera fase de Universo Salado. En parcela contigua a Salado 2.',
            'status' => 'active',
            'color' => '#f59e0b',
        ]);

        // Assign all existing posts and campaigns to Salado 1
        DB::table('posts')->whereNull('project_id')->update(['project_id' => $s1->id]);
        DB::table('campaigns')->whereNull('project_id')->update(['project_id' => $s1->id]);
    }
}
