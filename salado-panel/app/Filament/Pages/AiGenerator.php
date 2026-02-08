<?php

namespace App\Filament\Pages;

use App\Models\Post;
use App\Models\PostImage;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class AiGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Generar con IA';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.ai-generator';

    public ?string $tema = '';
    public ?string $tono = 'profesional';
    public ?int $cantidad = 1;
    public ?string $campaign_id = null;
    public ?array $imagenes = [];
    public ?string $resultado = '';
    public bool $generating = false;

    protected function getFormSchema(): array
    {
        $imageFiles = [];
        $imgDir = '/var/www/salado-images';
        if (is_dir($imgDir)) {
            foreach (scandir($imgDir) as $file) {
                if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                    $imageFiles[$file] = $file;
                }
            }
        }

        return [
            Forms\Components\Section::make('Generador de Contenido con IA')->schema([
                Forms\Components\TextInput::make('tema')
                    ->label('Tema del post')
                    ->placeholder('Ej: Beneficios de invertir en Punta Cana, Amenidades del resort...')
                    ->required(),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('tono')
                        ->label('Tono')
                        ->options([
                            'profesional' => 'Profesional',
                            'casual' => 'Casual / Cercano',
                            'urgente' => 'Urgencia / FOMO',
                            'inspiracional' => 'Inspiracional',
                            'informativo' => 'Informativo / Datos',
                        ])
                        ->default('profesional'),
                    Forms\Components\TextInput::make('cantidad')
                        ->label('Cantidad de posts')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->default(1),
                    Forms\Components\Select::make('campaign_id')
                        ->label('Campana')
                        ->options(Campaign::pluck('name', 'id'))
                        ->nullable()
                        ->placeholder('Sin campana'),
                ]),
                Forms\Components\Select::make('imagenes')
                    ->label('Imagenes a usar')
                    ->multiple()
                    ->options($imageFiles)
                    ->searchable()
                    ->helperText('Selecciona las imagenes que quieres incluir en el post'),
            ]),
        ];
    }

    public function generate(): void
    {
        $this->validate();

        $imageList = '';
        if (!empty($this->imagenes)) {
            $imageList = "\nImagenes disponibles: " . implode(', ', $this->imagenes);
        }

        $prompt = "Genera {$this->cantidad} post(s) para Instagram/Facebook de Salado Golf & Beach Resort en Punta Cana.

DATOS DEL PROYECTO:
- Salado Golf & Beach Resort, dentro de White Sands, Bavaro, Punta Cana
- 5 tipologias de apartamentos: A (103.75m², 2hab), B (59-62m², 1hab), C (69-71m², 1hab), D (62.68m², 1hab), E (99.63m², 2hab)
- 3 bloques residenciales (A, B, C), 3 niveles + azotea con jacuzzi
- Precios: €165,000 - €375,000
- 15 apartamentos disponibles
- Amenidades: piscina con bar, golf 9 hoyos, gimnasio, co-working, tenis, padel, playa directa
- Desarrollador: Arena Gorda (30+ anos, 250+ proyectos)
- Ley Confotur: exencion fiscal 15 anos

TEMA: {$this->tema}
TONO: {$this->tono}
{$imageList}

Para CADA post genera:
1. TITULO: (tema corto, 5-8 palabras)
2. CAPTION: (texto del post, 150-300 palabras, con emojis, formato Instagram)
3. HASHTAGS: (8-12 hashtags relevantes)
4. TIPO: (imagen_unica o carrusel)

Responde SOLO con el contenido de los posts, sin explicaciones adicionales. Separa cada post con ---";

        try {
            $deepseekKey = env('DEEPSEEK_API_KEY', '');
            $deepseekUrl = env('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');

            if (empty($deepseekKey)) {
                // Try local bridge
                $response = Http::timeout(60)->post('http://localhost:18790/api/chat', [
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 2000,
                ]);

                if ($response->successful()) {
                    $this->resultado = $response->json('choices.0.message.content', 'Sin respuesta');
                } else {
                    throw new \Exception('Bridge error: ' . $response->status());
                }
            } else {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$deepseekKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($deepseekUrl, [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un experto en marketing inmobiliario y redes sociales para proyectos de lujo en el Caribe.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.8,
                ]);

                if ($response->successful()) {
                    $this->resultado = $response->json('choices.0.message.content', 'Sin respuesta');
                } else {
                    throw new \Exception('DeepSeek error: ' . $response->status());
                }
            }

            Notification::make()
                ->title('Contenido generado')
                ->body("Se genero contenido para {$this->cantidad} post(s)")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->resultado = "Error al generar: " . $e->getMessage();
            Notification::make()
                ->title('Error al generar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveAsDraft(): void
    {
        if (empty($this->resultado)) {
            Notification::make()->title('No hay contenido para guardar')->warning()->send();
            return;
        }

        $posts = array_filter(explode('---', $this->resultado));
        $imgBase = 'https://natalia.soporteclientes.net/images';
        $saved = 0;

        foreach ($posts as $postText) {
            $postText = trim($postText);
            if (empty($postText)) continue;

            // Parse basic structure
            $title = 'Post generado por IA';
            $caption = $postText;
            $hashtags = '';
            $postType = 'imagen_unica';

            // Try to extract title
            if (preg_match('/TITULO:\s*(.+)/i', $postText, $m)) {
                $title = trim($m[1]);
            }
            // Try to extract caption
            if (preg_match('/CAPTION:\s*(.+?)(?=HASHTAGS:|TIPO:|$)/is', $postText, $m)) {
                $caption = trim($m[1]);
            }
            // Try to extract hashtags
            if (preg_match('/HASHTAGS:\s*(.+?)(?=TIPO:|$)/is', $postText, $m)) {
                $hashtags = trim($m[1]);
            }
            // Try to extract type
            if (preg_match('/TIPO:\s*(carrusel|imagen_unica|story|reel)/i', $postText, $m)) {
                $postType = strtolower(trim($m[1]));
            }

            $post = Post::create([
                'campaign_id' => $this->campaign_id ?: null,
                'title' => $title,
                'caption' => $caption,
                'hashtags' => $hashtags,
                'post_type' => $postType,
                'platform' => 'ambos',
                'status' => 'draft',
                'ai_generated' => true,
            ]);

            // Attach selected images
            foreach ($this->imagenes ?? [] as $i => $img) {
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $img,
                    'image_url' => "{$imgBase}/{$img}",
                    'sort_order' => $i,
                    'alt_text' => "{$title} - Imagen " . ($i + 1),
                ]);
            }

            $saved++;
        }

        Notification::make()
            ->title("{$saved} post(s) guardados como borrador")
            ->success()
            ->send();

        $this->resultado = '';
    }
}
