<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zya - Soluciones Financieras y Tecnológicas</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Estilos personalizados -->
    <style>
        :root {
            --zya-primary: #6a0dad;
            --zya-secondary: #9c27b0;
            --zya-light: #f3e5f5;
            --zya-dark: #4a148c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .bg-zya-primary {
            background-color: var(--zya-primary);
        }

        .bg-zya-secondary {
            background-color: var(--zya-secondary);
        }

        .bg-success {
            background-color: var(--zya-light);
        }

        .text-zya-primary {
            color: var(--zya-primary);
        }

        .btn-zya-primary {
            background-color: var(--zya-primary);
            color: white;
        }

        .btn-zya-primary:hover {
            background-color: var(--zya-dark);
            color: white;
        }

        .navbar {
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background-color: rgba(37, 211, 102, 0.5);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .hero-section {
            background:rgba(37, 211, 102, 0.5);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.2;
            z-index: 0;
        }

        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(106, 13, 173, 0.2);
        }

        .service-icon {
            font-size: 2.5rem;
            color: var(--zya-primary);
            margin-bottom: 1rem;
        }

        .whatsapp-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            background: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            text-align: center;
            font-size: 30px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: scale(1.1);
            color: white;
        }

        .feature-box {
            padding: 30px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(106, 13, 173, 0.15);
        }

        .phone-img {
            max-height: 200px;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .phone-img:hover {
            transform: scale(1.05);
        }

        .contact-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        footer {
            background: var(--zya-dark);
            color: white;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }

            100% {
                transform: translateY(0px);
            }
        }
    </style>
</head>

<body>
    <!-- Botón de WhatsApp -->
    <a href="https://wa.me/528261356159" class="whatsapp-btn animate__animated animate__bounceIn" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold animate__animated animate__fadeInLeft" href="#">
                <img src="{{ asset('assets/images/logow.png') }}" class="img-fluid rounded-normal  light-logo"
                    style="width: 190px;" alt="logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto animate__animated animate__fadeInRight">
                    <li class="nav-item">
                        <a class="nav-link" href="#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#servicios">Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#smartphones">Smartphones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tandas">Tandas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#accesorios">Accesorios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">Contacto</a>
                    </li>
                    <li class="nav-item">

                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                    class="nav-link">Panel</a>
                            @else
                                <a href="{{ route('login') }}" class="nav-link">Iniciar Sesión</a>
                            @endauth
                        @endif
                    </li>




                </ul>
            </div>
        </div>
    </nav>

    <!-- Sección Hero -->
    <section id="inicio" class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 animate__animated animate__fadeInLeft">
                    <h1 class="display-4 fw-bold mb-4">Soluciones Financieras y Tecnológicas</h1>
                    <p class="lead mb-4 text-dark"><strong> Zya te ofrecemos las mejores opciones en préstamos, smartphones y más, con
                        atención personalizada y las mejores condiciones del mercado.</strong></p>
                    <a href="#contacto" class="btn btn-light btn-lg me-2">Contáctanos</a>
                    <a href="#servicios" class="btn btn-outline-light btn-lg">Nuestros Servicios</a>
                </div>
                <div class="col-lg-6 animate__animated animate__fadeInRight">
                    <img src="{{ asset('assets/images/expa.jpeg') }}"
                        alt="Finanzas y tecnología" class="img-fluid rounded-4 floating w-75">
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Servicios -->
    <section id="servicios" class="py-5  bg-success-light">
        <div class="container">
            <div class="text-center mb-5 animate__animated animate__fadeInDown">
                <h2 class="fw-bold text-zya-primary">Nuestros Servicios</h2>
                <p class="lead">Ofrecemos soluciones adaptadas a tus necesidades</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4 animate__animated animate__fadeInUp" data-wow-delay="0.1s">
                    <div class="service-card card h-100">
                        <div class="card-body text-center p-4">
                            <div class="service-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <h4 class="card-title fw-bold">Préstamos Personales</h4>
                            <p class="card-text">Obtén el dinero que necesitas con tasas competitivas y plazos
                                flexibles. Sin complicaciones y con aprobación rápida.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 animate__animated animate__fadeInUp" data-wow-delay="0.2s">
                    <div class="service-card card h-100">
                        <div class="card-body text-center p-4">
                            <div class="service-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4 class="card-title fw-bold">Smartphones a Crédito</h4>
                            <p class="card-text">Los mejores equipos con financiamiento a través de Payjoy y Krediya.
                                Llévate el smartphone que quieres hoy y paga después.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 animate__animated animate__fadeInUp" data-wow-delay="0.3s">
                    <div class="service-card card h-100">
                        <div class="card-body text-center p-4">
                            <div class="service-icon">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <h4 class="card-title fw-bold">Tandas</h4>
                            <p class="card-text">Participa en nuestras tandas seguras y administradas profesionalmente.
                                Una forma tradicional de ahorro con seguridad moderna.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Smartphones -->
    <section id="smartphones" class="py-5">
        <div class="container">
            <div class="text-center mb-5 animate__animated animate__fadeInDown">
                <h2 class="fw-bold text-zya-primary">Smartphones</h2>
                <p class="lead">Los mejores equipos al mejor precio</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3 animate__animated animate__fadeIn" data-wow-delay="0.1s">
                    <div class="feature-box">
                        <img src="https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1367&q=80"
                            alt="iPhone" class="img-fluid phone-img mb-3">
                        <h4 class="fw-bold">iPhone </h4>
                        <p>El último iPhone con tecnología avanzada y cámara profesional.</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-success">Disponible</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 animate__animated animate__fadeIn" data-wow-delay="0.2s">
                    <div class="feature-box">
                        <img src="https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1528&q=80"
                            alt="Samsung Galaxy" class="img-fluid phone-img mb-3">
                        <h4 class="fw-bold">Samsung</h4>
                        <p>Potencia y elegancia en un solo dispositivo con pantalla AMOLED.</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-success">Disponible</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 animate__animated animate__fadeIn" data-wow-delay="0.3s">
                    <div class="feature-box">
                        <img src="https://images.unsplash.com/photo-1598327105666-5b89351aff97?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80"
                            alt="Xiaomi" class="img-fluid phone-img mb-3">
                        <h4 class="fw-bold">Xiaomi</h4>
                        <p>Excelente relación calidad-precio con batería de larga duración.</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-success">Disponible</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 animate__animated animate__fadeIn" data-wow-delay="0.4s">
                    <div class="feature-box">
                        <img src="https://images.unsplash.com/photo-1595941069915-4ebc5197c14a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1488&q=80"
                            alt="Oppo" class="img-fluid phone-img mb-3">
                        <h4 class="fw-bold">Oppo</h4>
                        <p>Diseño elegante y cámara profesional para fotos increíbles.</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-success">Disponible</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5 animate__animated animate__fadeInUp">
                <h4 class="fw-bold text-zya-primary">Financiamiento disponible con:</h4>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <img src="https://cdn.prod.website-files.com/674450e27e5d54e8286f6929/674d9979c472debeaf7b4278_logo.svg"
                        alt="PayJoy" class="img-fluid" style="max-height: 100px;">
                    <img src="https://www.krediya.com/hs-fs/hubfs/krdya_new_color_full.png?width=270&height=75&name=krdya_new_color_full.png"
                        alt="Krediya" class="img-fluid" style="max-height: 100px;">
                </div>
                <p class="mt-3">Aprobación rápida y sin complicaciones</p>
            </div>
        </div>
    </section>

    <!-- Sección de Tandas -->
    <section id="tandas" class="py-5 bg-success-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 animate__animated animate__fadeInLeft">
                    <img src="https://images.unsplash.com/photo-1579621970795-87facc2f976d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80"
                        alt="Tandas" class="img-fluid rounded-4">
                </div>
                <div class="col-lg-6 animate__animated animate__fadeInRight">
                    <h2 class="fw-bold text-zya-primary mb-4">Tandas Seguras</h2>
                    <p class="lead">Una forma tradicional de ahorro con todas las garantías</p>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-check-circle text-zya-primary me-2"></i> Administración
                            profesional y transparente</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-zya-primary me-2"></i> Grupos pequeños
                            para mayor control</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-zya-primary me-2"></i> Pagos seguros y
                            seguimiento en línea</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-zya-primary me-2"></i> Flexibilidad en
                            montos y plazos</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-zya-primary me-2"></i> Historial de
                            participación disponible</li>
                    </ul>
                    <p class="mt-4">Participa en nuestras tandas y alcanza tus metas de ahorro con la confianza que
                        solo Zya te puede ofrecer.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Accesorios -->
    <section id="accesorios" class="py-5">
        <div class="container">
            <div class="text-center mb-5 animate__animated animate__fadeInDown">
                <h2 class="fw-bold text-zya-primary">Accesorios</h2>
                <p class="lead">Complementa tu dispositivo con nuestros accesorios de calidad</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4 animate__animated animate__fadeIn" data-wow-delay="0.1s">
                    <div class="card h-100 border-0">
                        <img src="https://images.unsplash.com/photo-1592507595940-edcec54727e2?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTJ8fGF1ZGlmb25vcyUyMGluYWxhbWJyaWNvc3xlbnwwfHwwfHx8MA%3D%3D"
                            class="card-img-top" alt="Auriculares">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Auriculares Inalámbricos</h5>
                            <p class="card-text">Sonido de alta calidad con cancelación de ruido y hasta 20 horas de
                                batería.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 animate__animated animate__fadeIn" data-wow-delay="0.2s">
                    <div class="card h-100 border-0">
                        <img src="https://plus.unsplash.com/premium_photo-1669261149433-febd56c05327?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NXx8cGhvbmUlMjBjaGFyZ2VyfGVufDB8fDB8fHww"
                            class="card-img-top" alt="Cargador">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Cargador Rápido</h5>
                            <p class="card-text">Carga tu dispositivo al 50% en solo 30 minutos con tecnología Quick
                                Charge.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 animate__animated animate__fadeIn" data-wow-delay="0.3s">
                    <div class="card h-100 border-0">
                        <img src="https://images.unsplash.com/photo-1623393945964-8f5d573f9358?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8cGhvbmUlMjBjYXNlfGVufDB8fDB8fHww"
                            class="card-img-top" alt="Fundas">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Fundas Protectoras</h5>
                            <p class="card-text">Protege tu smartphone con estilo. Varios modelos y colores
                                disponibles.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5 animate__animated animate__fadeInUp">
                <p class="lead">Y muchos más accesorios disponibles en tienda</p>
                <p>Visítanos y descubre nuestra amplia gama de productos para tu dispositivo.</p>
            </div>
        </div>
    </section>

    <!-- Sección de Contacto -->
    <section id="contacto" class="py-5 bg-success text-white">
        <div class="container">
            <div class="text-center mb-5 animate__animated animate__fadeInDown">
                <h2 class="fw-bold">Contáctanos</h2>
                <p class="lead">Estamos aquí para ayudarte</p>
            </div>

            <div class="row">
                <div class="col-lg-12 animate__animated animate__fadeInLeft">
                    <div class="mb-4">
                        <h4 class="fw-bold mb-3"><i class="fas fa-map-marker-alt me-2"></i>Dirección Allende</h4>
                        <p>📍Allende N.L. : Calle Lerdo de Tejada #306 local C , entre calles Hidalgo y Morelos, Centro.
                        </p>
                        <p>+52 826 135 6159</p>

                    </div>

                    <div class="mb-4">
                        <h4 class="fw-bold mb-3"><i class="fas  fa-map-marker-alt me-2"></i> Dirección Montemorelos
                        </h4>
                        <p>📍 Montemorelos N.L.: Calle Cuahutémoc #412, entre calles Degollado y 5 de Mayo, Centro </p>

                        <p>+52 826 261 5418</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="fw-bold mb-3"><i class="fas fa-envelope me-2"></i> Correo Electrónico</h4>
                        <p>zyaallende@gmail.com</p>
                        <p>zyamontemorelos@gmail.com</p>

                    </div>

                    <div class="social-links mt-4">
                        <a href="https://www.facebook.com/share/16uRofdEZ5/?mibextid=wwXIfr" target="_blank"
                            class="text-white me-3"><i class="fab fa-facebook-f fa-2x"></i> Zya , Celulares, Accesorios y Reparaciones</a>

                    </div>
                </div>


            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2025 Zya. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white me-3"></a>
                    <a href="#" class="text-white"></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts personalizados -->
    <script>
        // Efecto de navbar al hacer scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animaciones al hacer scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate__animated');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const animation = entry.target.getAttribute('data-animation');
                        entry.target.classList.add('animate__fadeInUp');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            animateElements.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>

</html>
