<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub - About Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #fff;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            min-height: 100vh;
        }

        .header {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-content {
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 26px;
            color: rgb(116, 146, 115);;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
           font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.3s ease;
            color:white;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .btn:hover {
             background-color: rgba(167, 178, 139, 0.7);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .hero-section {
            text-align: center;
            padding: 60px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            margin-bottom: 40px;
            backdrop-filter: blur(15px);
        }

        .hero-section h1 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(45deg, rgb(116, 146, 115), rgb(168, 240, 165));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            font-size: 20px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            color: rgba(255, 255, 255, 0.9);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 60px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(116, 146, 115, 0.3);
            transition: transform 0.3s;
            margin:10px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgb(116, 146, 115);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: rgb(168, 240, 165);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .content-section h2 {
            font-size: 32px;
            margin-bottom: 20px;
            color: rgb(168, 240, 165);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .content-section p {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, rgb(116, 146, 115), rgb(168, 240, 165));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 40px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 8px;
            width: 16px;
            height: 16px;
            background: rgb(168, 240, 165);
            border-radius: 50%;
            border: 3px solid rgba(0, 0, 0, 0.3);
        }

        .timeline-year {
            font-size: 20px;
            font-weight: bold;
            color: rgb(116, 146, 115);
            margin-bottom: 5px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .contact-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .social-links {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .social-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
        }

        .social-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }

        .social-item.instagram:hover {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .social-item.facebook:hover {
            background: #1877f2;
        }

        .social-item.whatsapp:hover {
            background: #25d366;
        }

        .social-item.email:hover {
            background: #ea4335;
        }

        .social-item i {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .contact-item i {
            font-size: 24px;
            color: rgb(168, 240, 165);
            width: 30px;
        }

        .full-width-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .value-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .value-card i {
            font-size: 48px;
            color: rgb(168, 240, 165);
            margin-bottom: 20px;
        }

        .value-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: rgb(116, 146, 115);
        }

        @media (max-width: 768px) {
            .content-grid,
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .hero-section h1 {
                font-size: 32px;
            }

            .hero-section p {
                font-size: 18px;
            }

            .content-section,
            .contact-info,
            .social-links {
                padding: 20px;
            }
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            color: rgba(116, 146, 115, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; right: 15%; animation-delay: 2s; }
        .floating-element:nth-child(3) { bottom: 30%; left: 20%; animation-delay: 4s; }
        .floating-element:nth-child(4) { top: 40%; right: 25%; animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <i class="fas fa-dumbbell floating-element" style="font-size: 60px;"></i>
        <i class="fas fa-heartbeat floating-element" style="font-size: 50px;"></i>
        <i class="fas fa-running floating-element" style="font-size: 55px;"></i>
        <i class="fas fa-medal floating-element" style="font-size: 45px;"></i>
    </div>

    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-info-circle"></i>
                About FitHub
            </h1>
            <div class="nav-buttons">
                    <a href="home.php" class="btn">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="profile.php" class="btn">
                        <i class="fas fa-user"></i>
                    </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="hero-section">
            <h1>🏋️ Welcome to FitHub</h1>
            <p>Your premier fitness destination where strength meets community. We've been transforming lives and building stronger bodies since our foundation.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">2019</div>
                    <div class="stat-label">Established</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Happy Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Expert Trainers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Access</div>
                </div>
            </div>
        </div>
        <div class="content-grid">
            <!-- Our Story -->
            <div class="content-section">
                <h2>
                    <i class="fas fa-book-open"></i>
                    Our Story
                </h2>
                <p>FitHub was born from a simple yet powerful vision: to create a fitness community where everyone feels welcome, supported, and motivated to achieve their personal best.</p>
                
                <p>Founded in 2019 by fitness enthusiasts who believed that working out should be more than just exercise – it should be a lifestyle, a community, and a journey of self-discovery.</p>
                
                <p>What started as a small neighborhood gym has grown into a thriving fitness hub that serves hundreds of members from all walks of life, united by their commitment to health and wellness.</p>
            </div>

            <!-- Our History -->
            <div class="content-section">
                <h2>
                    <i class="fas fa-history"></i>
                    Our Journey
                </h2>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2019</div>
                        <p>FitHub opens its doors with basic equipment and big dreams</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2020</div>
                        <p>Expanded with state-of-the-art cardio and strength training equipment</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2021</div>
                        <p>Introduced group fitness classes and personal training programs</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2022</div>
                        <p>Launched our mobile app and digital fitness tracking system</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <p>Added specialized zones for functional training and rehabilitation</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <p>Reached 1000+ active members and opened 24/7 access</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="full-width-section">
            <h2 style="font-size: 36px; margin-bottom: 20px; color: rgb(168, 240, 165);">
                <i class="fas fa-heart"></i>
                Our Values
            </h2>
            <p style="font-size: 18px; margin-bottom: 40px;">The principles that guide everything we do</p>
            
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-users"></i>
                    <h3>Community First</h3>
                    <p>Building connections and supporting each other's fitness journey</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-trophy"></i>
                    <h3>Excellence</h3>
                    <p>Providing top-quality equipment, training, and member experience</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-handshake"></i>
                    <h3>Inclusivity</h3>
                    <p>Welcoming fitness enthusiasts of all levels and backgrounds</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Innovation</h3>
                    <p>Constantly evolving with the latest fitness trends and technology</p>
                </div>
            </div>
        </div>

        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2 style="color: rgb(168, 240, 165); margin-bottom: 30px;">
                    <i class="fas fa-phone"></i>
                    Get In Touch
                </h2>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <strong>Email Us</strong><br>
                        <a href="mailto:taimakizel18@gmail.com" style="color: rgb(168, 240, 165); text-decoration: none;">
                            info@fithub.co.il
                        </a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Operating Hours</strong><br>
                        24/7 Access for Members<br>
                        Staff: Mon-Fri 6AM-10PM
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <strong>Visit Us</strong><br>
                        123 Fitness Street<br>
                        Wellness District, City
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-headset"></i>
                    <div>
                        <strong>Support</strong><br>
                        We're here to help with any questions<br>
                        about memberships, classes, or equipment
                    </div>
                </div>
            </div>

            <!-- Social Media Links -->
            <div class="social-links">
                <h2 style="color: rgb(168, 240, 165); margin-bottom: 30px;">
                    <i class="fas fa-share-alt"></i>
                    Connect With Us
                </h2>
                <p style="margin-bottom: 30px;">Follow our journey and stay updated with the latest fitness tips, member achievements, and gym updates!</p>
                
                <div class="social-grid">
                    <a href="https://www.instagram.com/fithubnamal/?hl=he" target="_blank" class="social-item instagram">
                        <i class="fab fa-instagram"></i>
                        <div>Instagram</div>
                        <small>@fithub</small>
                    </a>
                    
                    <a href="https://www.facebook.com/CrossFitNamal/?locale=he_IL" target="_blank" class="social-item facebook">
                        <i class="fab fa-facebook-f"></i>
                        <div>Facebook</div>
                        <small>/FitHubGym</small>
                    </a>
                    
                    <a href="https://api.whatsapp.com/send?phone=972524445050&text=%D7%90%D7%A0%D7%90%20%D7%97%D7%96%D7%A8%D7%95%20%D7%90%D7%9C%D7%99%20" target="_blank" class="social-item whatsapp">
                        <i class="fab fa-whatsapp"></i>
                        <div>WhatsApp</div>
                        <small>Chat with us</small>
                    </a>
                    
                    <a href="mailto:taimakizel18@gmail.com" class="social-item email">
                        <i class="fas fa-envelope"></i>
                        <div>Email</div>
                        <small>Contact us</small>
                    </a>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        document.querySelectorAll('.content-section, .stat-card, .value-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        document.querySelectorAll('.social-item').forEach(item => {
            item.addEventListener('click', function() {
                const platform = this.classList.contains('instagram') ? 'Instagram' : 
                               this.classList.contains('facebook') ? 'Facebook' : 
                               this.classList.contains('whatsapp') ? 'WhatsApp' : 'Email';
                console.log(`Clicked on ${platform}`);
            });
        });

        function animateStats() {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const target = parseInt(stat.textContent);
                if (!isNaN(target)) {
                    let current = 0;
                    const increment = target / 100;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            stat.textContent = target;
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.floor(current);
                        }
                    }, 20);
                }
            });
        }
        window.addEventListener('load', () => {
            setTimeout(animateStats, 500);
        });
    </script>
</body>
</html>