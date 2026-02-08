<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\Seeder;

class ContentCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = Campaign::create([
            'name' => 'Lanzamiento 30 dias',
            'description' => 'Calendario de contenido inicial de 30 dias para Instagram y Facebook de Salado Golf & Beach Resort',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $imgBase = 'https://natalia.soporteclientes.net/images';

        $posts = [
            [
                'day' => 1, 'title' => 'Presentacion del proyecto', 'type' => 'carrusel',
                'images' => ['salado-exterior-1.png', 'salado-exterior-2.png', 'salado-exterior-7.png'],
                'caption' => "Bienvenidos a Salado Golf & Beach Resort, donde el lujo se encuentra con el Caribe.\n\nUbicado dentro del exclusivo complejo White Sands en Punta Cana, nuestro resort combina:\n\n⛳ Campo de golf de 9 hoyos\n🏖️ Acceso directo a playa\n🏊 Piscina con bar integrado\n🏢 3 bloques residenciales de solo 3 niveles\n\nApartamentos desde €165,000 hasta €375,000.\nDesarrollado por Arena Gorda, con mas de 30 anos de experiencia y 250+ proyectos completados.\n\n¿Listo para invertir en el paraiso?",
                'hashtags' => '#SaladoResort #PuntaCana #GolfAndBeach #WhiteSands #InvierteEnElCaribe #LuxuryLiving #BienesRaicesRD #PuntaCanaRealEstate',
                'platform' => 'ambos',
            ],
            [
                'day' => 2, 'title' => 'Playa - Estilo de vida', 'type' => 'imagen_unica',
                'images' => ['salado-playa-1.jpg'],
                'caption' => "Tu oficina de los lunes podria verse asi.\n\nSalado Golf & Beach Resort te da acceso directo a las playas de Bavaro, consideradas entre las mejores del mundo.\n\nVivir en Punta Cana no es un sueno, es una decision.\n\n📍 Ubicado en White Sands, Bavaro, Punta Cana\n💰 Apartamentos desde €165,000",
                'hashtags' => '#PlayaBavaro #PuntaCana #VidaEnLaPlaya #SaladoResort #CaribbeanLife #BeachLife #InversionCaribe',
                'platform' => 'ambos',
            ],
            [
                'day' => 3, 'title' => 'Apartamento Tipo A - Premium', 'type' => 'carrusel',
                'images' => ['salado-plano-tipo-a-planta.png', 'salado-plano-tipo-a-ubicacion.png'],
                'caption' => "TIPOLOGIA A — El apartamento mas exclusivo del proyecto.\n\n📐 103.75 m² totales\n🛌 2 Habitaciones amplias\n🛁 2 Banos completos\n🌿 Terraza de 23 m²\n🍳 Cocina equipada con cuarzo blanco\n\n¿Para quien es ideal?\n→ Familias que buscan espacio y confort\n→ Inversion premium con alta demanda de renta\n\nDesliza para ver la planta y la ubicacion dentro del resort.",
                'hashtags' => '#SaladoResort #ApartamentoLujo #PuntaCana #TipoA #2Habitaciones #InversionInmobiliaria',
                'platform' => 'ambos',
            ],
            [
                'day' => 4, 'title' => 'Amenidades - Piscina', 'type' => 'imagen_unica',
                'images' => ['salado-amenidad-1.jpg'],
                'caption' => "Imagina terminar tu dia asi: piscina infinita, coctel en mano y el sol del Caribe despidiendose.\n\nLa piscina principal de Salado Golf & Beach incluye:\n• Bar integrado en la piscina\n• Terraza con tumbonas\n• Areas de descanso\n• Rodeada de jardines tropicales\n\nY esto es solo una de las amenidades.",
                'hashtags' => '#Piscina #PoolLife #SaladoResort #PuntaCana #AmenidadesLujo #ResortLife',
                'platform' => 'ambos',
            ],
            [
                'day' => 5, 'title' => 'Golf lifestyle', 'type' => 'imagen_unica',
                'images' => ['salado-golf-1.jpg'],
                'caption' => "9 hoyos a pasos de tu apartamento.\n\nSalado Golf & Beach Resort esta dentro de White Sands, un complejo que incluye campo de golf propio y campo de practicas disponible 24/7.\n\nPara el golfista que quiere vivir donde juega.\n\n⛳ Campo de 9 hoyos\n🏌️ Campo de practicas 24/7\n🏠 Apartamentos con vista al green",
                'hashtags' => '#Golf #PuntaCanaGolf #SaladoResort #GolfLife #GolfAndBeach #CaribbeanGolf',
                'platform' => 'ambos',
            ],
            [
                'day' => 6, 'title' => 'Inversion - Datos duros', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-4.png'],
                'caption' => "📊 ¿Por que invertir en Punta Cana en 2026?\n\n✅ Republica Dominicana: destino turistico #1 del Caribe\n✅ Punta Cana recibe +8 millones de turistas al ano\n✅ Ocupacion hotelera promedio superior al 80%\n✅ Ley Confotur: exencion de impuestos por 15 anos\n✅ Plusvalia en constante crecimiento\n\nSalado Golf & Beach Resort:\n💰 Desde €165,000\n🏢 Solo 15 unidades disponibles\n🌴 Dentro de White Sands, Bavaro\n\nLa ventana de oportunidad se cierra.",
                'hashtags' => '#Inversion #RealEstate #PuntaCana #SaladoResort #Confotur #InversionInteligente #ROI',
                'platform' => 'ambos',
            ],
            [
                'day' => 7, 'title' => 'Playa - Domingo relax', 'type' => 'carrusel',
                'images' => ['salado-playa-2.jpg', 'salado-playa-3.jpg', 'salado-playa-4.jpg'],
                'caption' => "Domingos en Salado Golf & Beach.\n\nNada de trafico, nada de estres. Solo tu, la arena blanca y el mar turquesa del Caribe.\n\nAcceso directo a playa desde el resort. Cada dia puede ser asi.\n\n📍 White Sands, Bavaro, Punta Cana",
                'hashtags' => '#DomingoEnLaPlaya #PuntaCana #SaladoResort #BeachSunday #CaribbeanVibes #VidaEnElCaribe',
                'platform' => 'ambos',
            ],
            [
                'day' => 8, 'title' => 'Apartamento Tipo B - Inversion', 'type' => 'carrusel',
                'images' => ['salado-plano-tipo-b-planta.png', 'salado-plano-tipo-b-ubicacion.png'],
                'caption' => "TIPOLOGIA B — La inversion inteligente.\n\n📐 59-62 m² totales\n🛌 1 Habitacion\n🛁 1 Bano completo + W.C\n🌿 Terraza privada\n🍳 Cocina con meseta de cuarzo blanco\n\n¿Por que elegir el Tipo B?\n→ Precio de entrada mas accesible\n→ Alta demanda en renta vacacional (Airbnb)\n→ Facil de mantener\n→ Retorno de inversion rapido\n\nEl apartamento compacto perfecto para generar ingresos pasivos en el Caribe.",
                'hashtags' => '#SaladoResort #ApartamentoInversion #PuntaCana #Airbnb #RentaVacacional #TipoB',
                'platform' => 'ambos',
            ],
            [
                'day' => 9, 'title' => 'Fachada arquitectonica', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-5.png'],
                'caption' => "Arquitectura moderna tropical.\n\nDisenado por ROJE Arquitectos, Salado Golf & Beach combina lineas contemporaneas con el entorno natural del Caribe.\n\n• Edificios de solo 3 niveles (baja densidad)\n• Terrazas amplias en cada apartamento\n• Acabados premium\n• Ascensor en todos los bloques\n• Azotea con jacuzzi\n\nMenos apartamentos = Mas exclusividad.",
                'hashtags' => '#Arquitectura #DisenoModerno #SaladoResort #PuntaCana #TropicalDesign #ROJE',
                'platform' => 'ambos',
            ],
            [
                'day' => 10, 'title' => 'Amenidades - Pool bar', 'type' => 'imagen_unica',
                'images' => ['salado-amenidad-2.jpg'],
                'caption' => "Bar de piscina: porque tus vacaciones no deberian terminar nunca.\n\nEn Salado Golf & Beach el bar esta integrado directamente en la piscina. Pide tu bebida sin salir del agua.\n\n🍹 Bar en la piscina\n☀️ Terraza con tumbonas\n🌴 Ambiente tropical\n\nVivir aqui es estar de vacaciones permanentes.",
                'hashtags' => '#PoolBar #SaladoResort #PuntaCana #ResortLife #BarDePiscina #LuxuryPool',
                'platform' => 'ambos',
            ],
            [
                'day' => 11, 'title' => 'Ubicacion estrategica', 'type' => 'carrusel',
                'images' => ['salado-ubicacion-1.jpg', 'salado-ubicacion-2.jpg'],
                'caption' => "📍 Ubicacion: lo que hace la diferencia.\n\nSalado Golf & Beach esta dentro de White Sands, en el corazon de Bavaro, Punta Cana.\n\n¿Que tienes cerca?\n• Aeropuerto Internacional de Punta Cana: 15 min\n• Playas de Bavaro: acceso directo\n• Downtown Punta Cana: 10 min\n• Restaurantes y entretenimiento\n• Supermercados y servicios\n\nNo estas aislado, estas en la mejor zona.",
                'hashtags' => '#Ubicacion #Bavaro #PuntaCana #SaladoResort #WhiteSands #CercaDeTodo',
                'platform' => 'ambos',
            ],
            [
                'day' => 12, 'title' => 'Apartamento Tipo C', 'type' => 'carrusel',
                'images' => ['salado-plano-tipo-c-planta.png', 'salado-plano-tipo-c-ubicacion.png'],
                'caption' => "TIPOLOGIA C — El equilibrio perfecto.\n\n📐 69-71 m² totales\n🛌 1 Habitacion amplia\n🛁 1 Bano completo + W.C\n🌿 Terraza generosa\n🍳 Cocina equipada\n\nEl Tipo C ofrece el mayor metraje de los apartamentos de 1 habitacion.\n\nMas espacio para vivir, misma eficiencia para rentar.\n\nIdeal si buscas algo entre lo compacto del Tipo B y lo amplio del Tipo A.",
                'hashtags' => '#SaladoResort #TipoC #ApartamentoPuntaCana #1Habitacion #InversionRD',
                'platform' => 'ambos',
            ],
            [
                'day' => 13, 'title' => 'Amenidades deportivas', 'type' => 'imagen_unica',
                'images' => ['salado-amenidad-3.jpg'],
                'caption' => "Mas que un resort, un estilo de vida activo.\n\nWhite Sands te ofrece:\n\n🎾 2 pistas de tenis\n🏓 4 pistas de padel\n🏌️ Campo de golf de 9 hoyos + driving range 24/7\n🏊 Piscina semi-olimpica\n🏃 Pista de running\n💪 Gimnasio equipado\n\nTodo incluido en tu comunidad. Sin salir de casa.",
                'hashtags' => '#Deportes #VidaActiva #SaladoResort #PuntaCana #Padel #Tenis #Golf #Gym',
                'platform' => 'ambos',
            ],
            [
                'day' => 14, 'title' => 'Exterior noche/atardecer', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-8.png'],
                'caption' => "Cuando el sol se pone en Punta Cana, la magia comienza.\n\nSalado Golf & Beach Resort de noche es otra experiencia. Iluminacion ambiental, la brisa del Caribe y la tranquilidad de un resort de baja densidad.\n\nSolo 3 bloques. Solo 3 niveles. Maxima exclusividad.",
                'hashtags' => '#Atardecer #PuntaCana #SaladoResort #SunsetCaribe #LuxuryResort',
                'platform' => 'ambos',
            ],
            [
                'day' => 15, 'title' => 'Masterplan general', 'type' => 'imagen_unica',
                'images' => ['salado-masterplan.png'],
                'caption' => "Asi se ve el paraiso desde arriba.\n\nEl masterplan de Salado Golf & Beach Resort muestra un diseno pensado en cada detalle:\n\n🏢 3 bloques residenciales (A, B, C)\n🏊 Piscina central con bar\n🌿 Jardines y areas verdes\n🚗 Acceso vehicular ordenado\n🚶 Circulaciones peatonales\n\n¿Te imaginas viviendo aqui?",
                'hashtags' => '#Masterplan #SaladoResort #PuntaCana #DisenoUrbano #Resort #PlanoGeneral',
                'platform' => 'ambos',
            ],
            [
                'day' => 16, 'title' => 'Apartamento Tipo D', 'type' => 'carrusel',
                'images' => ['salado-plano-tipo-d-planta.png', 'salado-plano-tipo-d-ubicacion.png'],
                'caption' => "TIPOLOGIA D — Funcional y accesible.\n\n📐 62.68 m² totales\n🛌 1 Habitacion\n🛁 1 Bano completo + W.C\n🌿 Terraza de 13.89 m²\n🍳 Cocina equipada\n♻️ Area de lavado\n\nEl Tipo D es la puerta de entrada al Caribe. Compacto, funcional, con todos los espacios que necesitas.\n\nPerfecto para tu primera inversion en Punta Cana.",
                'hashtags' => '#SaladoResort #TipoD #InversionAccesible #PuntaCana #PrimerApartamento',
                'platform' => 'ambos',
            ],
            [
                'day' => 17, 'title' => 'Playa - Lifestyle', 'type' => 'carrusel',
                'images' => ['salado-playa-5.jpg', 'salado-playa-6.jpg'],
                'caption' => "Esto no es una postal. Es tu patio trasero.\n\nLas playas de Bavaro estan reconocidas internacionalmente como unas de las mejores del mundo. Y estan a pasos de tu apartamento en Salado.\n\n☀️ Arena blanca\n🌊 Aguas turquesas\n🌴 Palmeras naturales\n🚶 Acceso directo desde el resort",
                'hashtags' => '#Playa #Bavaro #PuntaCana #SaladoResort #BeachLife #ArenaBlanca #CaribeMagico',
                'platform' => 'ambos',
            ],
            [
                'day' => 18, 'title' => 'Jacuzzi rooftop', 'type' => 'imagen_unica',
                'images' => ['salado-amenidad-4.jpg'],
                'caption' => "Jacuzzi privado en la azotea. Con vista al Caribe.\n\nCada bloque de Salado tiene su propia terraza rooftop con jacuzzi, disponible para los residentes.\n\nImagina terminar el dia aqui arriba, con una copa de vino y la brisa tropical.\n\n🛁 Jacuzzi en azotea\n🌅 Vistas panoramicas\n🌴 Pergola para sombra",
                'hashtags' => '#Jacuzzi #Rooftop #SaladoResort #PuntaCana #LuxuryAmenities #VidaDeResort',
                'platform' => 'ambos',
            ],
            [
                'day' => 19, 'title' => 'Comparacion tipologias', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-3.jpg'],
                'caption' => "📊 GUIA RAPIDA: ¿Cual apartamento es para ti?\n\n🔵 TIPO B (59-62 m²) → 1 hab | Inversion Airbnb\n🟢 TIPO D (62.68 m²) → 1 hab | Primera inversion\n🟡 TIPO C (69-71 m²) → 1 hab | Mas espacio\n🟠 TIPO E (99.63 m²) → 2 hab | Familiar\n🔴 TIPO A (103.75 m²) → 2 hab | Premium\n\nTodos incluyen: terraza, cocina equipada, area de lavado, ascensor, acceso a piscina, golf y playa.\n\nPrecios desde €165,000.\n\n¿Cual te interesa? Escribenos.",
                'hashtags' => '#SaladoResort #Tipologias #GuiaDeCompra #PuntaCana #InversionRD #ApartamentoCaribe',
                'platform' => 'ambos',
            ],
            [
                'day' => 20, 'title' => 'Gimnasio y coworking', 'type' => 'imagen_unica',
                'images' => ['salado-amenidad-5.jpg'],
                'caption' => "Trabaja. Entrena. Vive.\n\nEl Bloque B de Salado incluye en planta baja:\n\n💪 Gimnasio completamente equipado\n💻 Espacio de co-working moderno\n\nNo necesitas salir del resort para mantenerte activo ni productivo.\n\nIdeal para nomadas digitales y profesionales remotos que eligen vivir donde otros vacacionan.",
                'hashtags' => '#Coworking #Gimnasio #NomadaDigital #SaladoResort #PuntaCana #TrabajoRemoto #GymLife',
                'platform' => 'ambos',
            ],
            [
                'day' => 21, 'title' => 'Exterior hero shot', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-9.png'],
                'caption' => "Esto no es un hotel de 5 estrellas. Es tu proximo hogar.\n\nSalado Golf & Beach Resort ofrece la experiencia de resort con la comodidad de tu propio apartamento.\n\nServicios de hotel. Privacidad de hogar. Retorno de inversion.\n\nLo mejor de tres mundos.\n\n💰 Desde €165,000\n📩 Escribenos para mas info",
                'hashtags' => '#SaladoResort #PuntaCana #ResortLiving #HomeSweetHome #InversionLujo',
                'platform' => 'ambos',
            ],
            [
                'day' => 22, 'title' => 'Apartamento Tipo E', 'type' => 'carrusel',
                'images' => ['salado-plano-tipo-e-planta.png', 'salado-plano-tipo-e-ubicacion.png'],
                'caption' => "TIPOLOGIA E — Espacio familiar con todo incluido.\n\n📐 99.63 m² totales\n🛌 2 Habitaciones\n🛁 2 Banos completos\n🌿 Terraza de 18.49 m²\n🍳 Cocina con cuarzo blanco\n♻️ Area de lavado\n\nEl Tipo E es la opcion familiar por excelencia. Amplio, comodo, con 2 habitaciones y 2 banos.\n\nPara quienes quieren vivir en grande en el Caribe.",
                'hashtags' => '#SaladoResort #TipoE #ApartamentoFamiliar #PuntaCana #2Habitaciones #VidaEnFamilia',
                'platform' => 'ambos',
            ],
            [
                'day' => 23, 'title' => 'Elevacion arquitectonica', 'type' => 'carrusel',
                'images' => ['salado-exterior-11.png', 'salado-exterior-12.png', 'salado-edificio-1.jpg'],
                'caption' => "Cada bloque, una obra de arte.\n\nSalado Golf & Beach fue disenado por ROJE Arquitectos, firma reconocida en Republica Dominicana.\n\nCaracteristicas del diseno:\n• 3 niveles + azotea (baja densidad)\n• Balcones y terrazas amplias\n• Ventilacion natural cruzada\n• Acabados de primera calidad\n• Ascensor en cada bloque\n\nArquitectura que respeta el entorno tropical.",
                'hashtags' => '#Arquitectura #ROJE #SaladoResort #DisenoTropical #PuntaCana #ModernDesign',
                'platform' => 'ambos',
            ],
            [
                'day' => 24, 'title' => 'Urgencia - Disponibilidad limitada', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-14.png'],
                'caption' => "⚠️ SOLO QUEDAN 15 APARTAMENTOS.\n\nSalado Golf & Beach Resort es un proyecto exclusivo y limitado:\n\n🏢 3 bloques residenciales\n🛏 3 niveles por bloque\n🔑 15 unidades disponibles\n\nDistribucion actual:\n• Bloque Bavaro: 5 unidades\n• Bloque Punta Cana: 10 unidades\n\n💰 Desde €165,000 | Hasta €375,000\n\nCuando se acaben, se acaban. No habra fase 2.\n\n📩 Contactanos hoy.",
                'hashtags' => '#UltimasUnidades #SaladoResort #PuntaCana #OportunidadUnica #InversionAhora',
                'platform' => 'ambos',
            ],
            [
                'day' => 25, 'title' => 'Amenidades completas', 'type' => 'carrusel',
                'images' => ['salado-amenidad-6.jpg', 'salado-piscina-7.jpg', 'salado-piscina-8.jpg'],
                'caption' => "Todo lo que necesitas, a pasos de tu puerta.\n\nAmenidades de Salado Golf & Beach y White Sands:\n\n🏊 Piscina con bar integrado\n🛁 Jacuzzi en azotea\n💪 Gimnasio\n💻 Co-working\n⛳ Golf 9 hoyos\n🎾 Tenis y padel\n🏃 Pista de running\n🚁 Helipuerto\n🛍 Minimarket\n☕ Cafeteria\n🐶 Parque de mascotas\n🎨 Club social\n\nMas que un apartamento, un estilo de vida completo.",
                'hashtags' => '#Amenidades #SaladoResort #PuntaCana #TodoIncluido #WhiteSands #LuxuryLiving',
                'platform' => 'ambos',
            ],
            [
                'day' => 26, 'title' => 'Confotur - Beneficio fiscal', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-6.jpg'],
                'caption' => "💰 ¿Sabias que tu inversion en Salado puede estar EXENTA de impuestos?\n\nLey Confotur (158-01) de Republica Dominicana:\n\n✅ 0% impuesto de transferencia inmobiliaria\n✅ 0% impuesto sobre la propiedad (IPI) por 15 anos\n✅ 0% impuesto sobre la renta de alquiler\n\nEsto significa:\n→ No pagas impuestos al comprar\n→ No pagas impuestos anuales por 15 anos\n→ Mayor retorno neto en tu inversion\n\nPunta Cana + Confotur = Inversion inteligente.",
                'hashtags' => '#Confotur #BeneficioFiscal #SaladoResort #InversionRD #PuntaCana #0Impuestos',
                'platform' => 'ambos',
            ],
            [
                'day' => 27, 'title' => 'Playa premium', 'type' => 'imagen_unica',
                'images' => ['salado-playa-1.jpg'],
                'caption' => "Bavaro: una de las 10 mejores playas del mundo.\n\nY esta literalmente en tu patio trasero.\n\nSalado Golf & Beach Resort te da acceso directo a las playas virgenes de Bavaro. Sin intermediarios, sin costos extra.\n\nCada manana, cada atardecer, cada vez que quieras.\n\n🌊 Tu playa. Tu resort. Tu vida.",
                'hashtags' => '#Bavaro #MejoresPlayas #PuntaCana #SaladoResort #PlayaPrivada #CaribbeanDream',
                'platform' => 'ambos',
            ],
            [
                'day' => 28, 'title' => 'Arena Gorda - Desarrolladora', 'type' => 'imagen_unica',
                'images' => ['salado-exterior-2.png'],
                'caption' => "Respaldado por la experiencia.\n\nSalado Golf & Beach Resort es desarrollado por Arena Gorda:\n\n📼 +30 anos en el mercado dominicano\n🏗 +250 proyectos completados\n✅ Entregas a tiempo\n✅ Calidad comprobada\n\nCuando inviertes en Salado, inviertes con una empresa que ha construido el Caribe que conoces.\n\nTu tranquilidad esta respaldada.",
                'hashtags' => '#ArenaGorda #Desarrolladora #SaladoResort #PuntaCana #Confianza #30Anos',
                'platform' => 'ambos',
            ],
            [
                'day' => 29, 'title' => 'Terraza y exteriores', 'type' => 'carrusel',
                'images' => ['salado-exterior-10.jpg', 'salado-amenidad-1.jpg'],
                'caption' => "Cada apartamento tiene su propia terraza privada.\n\nDesde 13 m² hasta 23 m² de terraza, dependiendo de la tipologia.\n\nTu espacio al aire libre para:\n• Desayunar con vista al verde\n• Trabajar con brisa tropical\n• Cenar bajo las estrellas\n• Simplemente respirar\n\nDisenados para vivir hacia afuera.",
                'hashtags' => '#Terraza #VidaAlAireLibre #SaladoResort #PuntaCana #OutdoorLiving #BalconCaribe',
                'platform' => 'ambos',
            ],
            [
                'day' => 30, 'title' => 'Call to Action - Cierre del mes', 'type' => 'carrusel',
                'images' => ['salado-exterior-7.png', 'salado-amenidad-2.jpg', 'salado-playa-2.jpg'],
                'caption' => "🌴 Tu momento es AHORA.\n\nResumen de lo que te espera en Salado Golf & Beach Resort:\n\n✅ Apartamentos de 1 y 2 habitaciones\n✅ Desde €165,000\n✅ Campo de golf 9 hoyos\n✅ Acceso directo a playa\n✅ Piscina con bar\n✅ Gimnasio + Co-working\n✅ Jacuzzi en azotea\n✅ Exencion fiscal (Confotur)\n✅ Desarrollado por Arena Gorda (+30 anos)\n✅ Solo 15 unidades disponibles\n\nNo dejes que otro tome tu lugar en el paraiso.\n\n📩 Escribenos hoy: el Caribe te esta esperando.",
                'hashtags' => '#SaladoResort #PuntaCana #GolfAndBeach #InvierteAhora #UltimasUnidades #ParaisoReal #WhiteSands',
                'platform' => 'ambos',
            ],
        ];

        foreach ($posts as $data) {
            $post = Post::create([
                'campaign_id' => $campaign->id,
                'day_number' => $data['day'],
                'title' => $data['title'],
                'caption' => $data['caption'],
                'hashtags' => $data['hashtags'],
                'post_type' => $data['type'],
                'platform' => $data['platform'],
                'status' => 'draft',
                'ai_generated' => false,
            ]);

            foreach ($data['images'] as $i => $img) {
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $img,
                    'image_url' => "{$imgBase}/{$img}",
                    'sort_order' => $i,
                    'alt_text' => "{$data['title']} - Imagen " . ($i + 1),
                ]);
            }
        }
    }
}
