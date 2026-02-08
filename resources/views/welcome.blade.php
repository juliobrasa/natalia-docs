<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salado Golf & Beach Resort | Punta Cana</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1a1a1a; }

        .hero {
            position: relative;
            height: 100vh;
            min-height: 700px;
            background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.25) 100%),
                        url('https://natalia.soporteclientes.net/images/salado-render-fachada.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .hero-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 300;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }
        .hero h1 strong { font-weight: 700; }
        .hero p.sub {
            font-size: clamp(1rem, 2vw, 1.3rem);
            opacity: 0.9;
            margin-bottom: 2.5rem;
            max-width: 600px;
        }
        .hero-buttons { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
        .btn {
            padding: 0.9rem 2.2rem;
            border-radius: 50px;
            font-size: 1rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        .btn-primary {
            background: #16a34a;
            color: white;
            border: 2px solid #16a34a;
        }
        .btn-primary:hover { background: #15803d; border-color: #15803d; }
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.6);
        }
        .btn-outline:hover { background: rgba(255,255,255,0.15); border-color: white; }

        .stats {
            background: #f8faf8;
            padding: 3rem 2rem;
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }
        .stat { text-align: center; }
        .stat-num { font-size: 2.5rem; font-weight: 700; color: #16a34a; }
        .stat-label { font-size: 0.9rem; color: #666; margin-top: 0.3rem; }

        .section { padding: 5rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-title h2 {
            font-size: 2.2rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
        }
        .section-title h2 strong { font-weight: 700; }
        .section-title p { color: #666; font-size: 1.05rem; }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        .card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            background: white;
        }
        .card:hover { transform: translateY(-4px); }
        .card img { width: 100%; height: 220px; object-fit: cover; }
        .card-body { padding: 1.5rem; }
        .card-body h3 { font-size: 1.2rem; margin-bottom: 0.5rem; }
        .card-body p { color: #555; font-size: 0.95rem; line-height: 1.6; }

        .tipos {
            background: #f0fdf4;
            padding: 5rem 2rem;
        }
        .tipos-inner { max-width: 1200px; margin: 0 auto; }
        .tipo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
            margin-top: 2rem;
        }
        .tipo-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .tipo-letter {
            width: 50px; height: 50px;
            background: #16a34a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        .tipo-card h4 { margin-bottom: 0.3rem; }
        .tipo-card .area { font-size: 1.4rem; font-weight: 700; color: #16a34a; }
        .tipo-card .detail { font-size: 0.85rem; color: #888; margin-top: 0.3rem; }

        .amenidades {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .amen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .amen-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 10px;
        }
        .amen-icon { font-size: 1.5rem; width: 40px; text-align: center; }
        .amen-text { font-size: 0.95rem; }

        .cta {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.3)),
                        url('https://natalia.soporteclientes.net/images/salado-playa-1.jpg') center/cover no-repeat;
            color: white;
            text-align: center;
            padding: 5rem 2rem;
        }
        .cta h2 { font-size: 2.5rem; font-weight: 300; margin-bottom: 1rem; }
        .cta h2 strong { font-weight: 700; }
        .cta p { font-size: 1.1rem; opacity: 0.9; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto; }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.5rem;
            padding: 0;
        }
        .gallery img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .gallery img:hover { transform: scale(1.03); }

        footer {
            background: #111;
            color: #999;
            text-align: center;
            padding: 2rem;
            font-size: 0.85rem;
        }
        footer a { color: #16a34a; text-decoration: none; }

        @media (max-width: 600px) {
            .stats { gap: 1.5rem; }
            .hero-buttons { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

<section class="hero">
    <div class="hero-badge">White Sands, Bavaro, Punta Cana</div>
    <h1>Salado <strong>Golf & Beach</strong></h1>
    <p class="sub">Tu apartamento de lujo frente al golf y la playa en el corazon del Caribe</p>
    <div class="hero-buttons">
        <a href="https://wa.me/34685805924" class="btn btn-primary">Contactar por WhatsApp</a>
        <a href="#tipologias" class="btn btn-outline">Ver Apartamentos</a>
    </div>
</section>

<section class="stats">
    <div class="stat"><div class="stat-num">15</div><div class="stat-label">Apartamentos disponibles</div></div>
    <div class="stat"><div class="stat-num">5</div><div class="stat-label">Tipologias</div></div>
    <div class="stat"><div class="stat-num">9</div><div class="stat-label">Hoyos de golf</div></div>
    <div class="stat"><div class="stat-num">30+</div><div class="stat-label">Anos de experiencia</div></div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Vive en el <strong>Paraiso</strong></h2>
        <p>Desarrollado por Arena Gorda, con mas de 250 proyectos completados en Republica Dominicana</p>
    </div>
    <div class="grid-3">
        <div class="card">
            <img src="https://natalia.soporteclientes.net/images/salado-exterior-1.png" alt="Exterior Salado Resort" loading="lazy">
            <div class="card-body">
                <h3>Arquitectura Moderna Tropical</h3>
                <p>3 bloques residenciales de solo 3 niveles + azotea con jacuzzi. Disenado por ROJE Arquitectos para maxima exclusividad y baja densidad.</p>
            </div>
        </div>
        <div class="card">
            <img src="https://natalia.soporteclientes.net/images/salado-amenidad-1.jpg" alt="Piscina Salado" loading="lazy">
            <div class="card-body">
                <h3>Amenidades de Resort</h3>
                <p>Piscina con bar integrado, gimnasio, co-working, jacuzzi en azotea, tenis, padel, pista de running y acceso directo a playa.</p>
            </div>
        </div>
        <div class="card">
            <img src="https://natalia.soporteclientes.net/images/salado-golf-1.jpg" alt="Golf Punta Cana" loading="lazy">
            <div class="card-body">
                <h3>Golf & Beach Lifestyle</h3>
                <p>Campo de golf de 9 hoyos y driving range 24/7 dentro del complejo White Sands, con acceso directo a las playas de Bavaro.</p>
            </div>
        </div>
    </div>
</section>

<section class="tipos" id="tipologias">
    <div class="tipos-inner">
        <div class="section-title">
            <h2>Nuestras <strong>Tipologias</strong></h2>
            <p>Apartamentos de 1 y 2 habitaciones desde 59 m2 hasta 103 m2</p>
        </div>
        <div class="tipo-grid">
            <div class="tipo-card">
                <div class="tipo-letter">A</div>
                <div class="area">103.75 m2</div>
                <h4>2 Habitaciones</h4>
                <div class="detail">2 banos | Terraza 23 m2</div>
                <div class="detail">El mas exclusivo</div>
            </div>
            <div class="tipo-card">
                <div class="tipo-letter">B</div>
                <div class="area">59-62 m2</div>
                <h4>1 Habitacion</h4>
                <div class="detail">1 bano + W.C</div>
                <div class="detail">Ideal inversion Airbnb</div>
            </div>
            <div class="tipo-card">
                <div class="tipo-letter">C</div>
                <div class="area">69-71 m2</div>
                <h4>1 Habitacion</h4>
                <div class="detail">1 bano + W.C</div>
                <div class="detail">El mas amplio de 1 hab</div>
            </div>
            <div class="tipo-card">
                <div class="tipo-letter">D</div>
                <div class="area">62.68 m2</div>
                <h4>1 Habitacion</h4>
                <div class="detail">1 bano + W.C</div>
                <div class="detail">Compacto y funcional</div>
            </div>
            <div class="tipo-card">
                <div class="tipo-letter">E</div>
                <div class="area">99.63 m2</div>
                <h4>2 Habitaciones</h4>
                <div class="detail">2 banos | Terraza 18.5 m2</div>
                <div class="detail">Familiar premium</div>
            </div>
        </div>
    </div>
</section>

<section class="gallery">
    <img src="https://natalia.soporteclientes.net/images/salado-exterior-2.png" alt="Salado Resort" loading="lazy">
    <img src="https://natalia.soporteclientes.net/images/salado-amenidad-2.jpg" alt="Amenidades" loading="lazy">
    <img src="https://natalia.soporteclientes.net/images/salado-playa-2.jpg" alt="Playa Bavaro" loading="lazy">
    <img src="https://natalia.soporteclientes.net/images/salado-exterior-5.png" alt="Fachada" loading="lazy">
    <img src="https://natalia.soporteclientes.net/images/salado-amenidad-4.jpg" alt="Rooftop" loading="lazy">
    <img src="https://natalia.soporteclientes.net/images/salado-render-fachada-noche.png" alt="Vista general" loading="lazy">
</section>

<section class="amenidades">
    <div class="section-title">
        <h2>Todo lo que <strong>Necesitas</strong></h2>
        <p>Amenidades del complejo White Sands y Salado Golf & Beach</p>
    </div>
    <div class="amen-grid">
        <div class="amen-item"><div class="amen-icon">&#9971;</div><div class="amen-text">Campo de golf 9 hoyos</div></div>
        <div class="amen-item"><div class="amen-icon">&#127946;</div><div class="amen-text">Piscina con bar integrado</div></div>
        <div class="amen-item"><div class="amen-icon">&#127947;</div><div class="amen-text">Gimnasio equipado</div></div>
        <div class="amen-item"><div class="amen-icon">&#128187;</div><div class="amen-text">Espacio co-working</div></div>
        <div class="amen-item"><div class="amen-icon">&#127934;</div><div class="amen-text">2 pistas de tenis</div></div>
        <div class="amen-item"><div class="amen-icon">&#127955;</div><div class="amen-text">4 pistas de padel</div></div>
        <div class="amen-item"><div class="amen-icon">&#128705;</div><div class="amen-text">Acceso directo a playa</div></div>
        <div class="amen-item"><div class="amen-icon">&#128705;</div><div class="amen-text">Jacuzzi en azotea</div></div>
        <div class="amen-item"><div class="amen-icon">&#128003;</div><div class="amen-text">Parque de mascotas</div></div>
        <div class="amen-item"><div class="amen-icon">&#127939;</div><div class="amen-text">Pista de running</div></div>
        <div class="amen-item"><div class="amen-icon">&#128641;</div><div class="amen-text">Helipuerto</div></div>
        <div class="amen-item"><div class="amen-icon">&#9749;</div><div class="amen-text">Cafeteria y minimarket</div></div>
    </div>
</section>

<section class="cta">
    <h2>Desde <strong>&euro;165,000</strong></h2>
    <p>Exencion fiscal de 15 anos con Ley Confotur. Solo quedan 15 apartamentos. No habra fase 2.</p>
    <div class="hero-buttons">
        <a href="https://wa.me/34685805924" class="btn btn-primary">Reserva Tu Apartamento</a>
        <a href="https://earth.google.com/earth/d/1qyWrIORbx57b3O9KCpJmsecglHzibXbz?usp=sharing" target="_blank" class="btn btn-outline" style="margin-left:12px;">Ver Ubicaci&#243;n A&#233;rea</a>
    </div>
</section>

<footer>
    <p>Salado Golf & Beach Resort &mdash; White Sands, Bavaro, Punta Cana, Republica Dominicana</p>
    <p style="margin-top:0.5rem">Desarrollado por Arena Gorda | <a href="/admin">Panel Admin</a></p>
</footer>

<!-- Boton flotante WhatsApp Natalia --><a href="https://wa.me/34685805924?text=Hola%20Natalia%2C%20me%20interesa%20Salado%20Golf%20%26%20Beach" target="_blank" class="whatsapp-float" title="Habla con Natalia">    <svg viewBox="0 0 24 24" width="32" height="32" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>    <span class="whatsapp-label">Habla con Natalia</span></a><style>.whatsapp-float {    position: fixed;    bottom: 24px;    right: 24px;    background: #25D366;    color: white;    border-radius: 50px;    padding: 14px 22px;    display: flex;    align-items: center;    gap: 10px;    text-decoration: none;    font-weight: 600;    font-size: 0.95rem;    box-shadow: 0 4px 20px rgba(37,211,102,0.4);    z-index: 9999;    transition: all 0.3s;}.whatsapp-float:hover { transform: scale(1.05); box-shadow: 0 6px 28px rgba(37,211,102,0.5); }.whatsapp-float svg { flex-shrink: 0; }@media (max-width: 600px) {    .whatsapp-label { display: none; }    .whatsapp-float { border-radius: 50%; padding: 16px; }}</style>
</body>
</html>
