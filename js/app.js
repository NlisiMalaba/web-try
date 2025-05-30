// Main App Component
function App() {
    return (
        <div className="app">
            <Header />
            <main>
                <HeroSection />
                <FeaturesSection />
                <HowItWorks />
                <Testimonials />
                <CallToAction />
            </main>
            <Footer />
        </div>
    );
}

// Header Component
function Header() {
    return (
        <header className="header">
            <nav className="navbar">
                <a href="/" className="logo">HealthAssist Pro</a>
                <div className="nav-links">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#testimonials">Testimonials</a>
                    <a href="#contact">Contact</a>
                    <a href="login.php" className="btn">Login</a>
                </div>
            </nav>
        </header>
    );
}

// Hero Section Component
function HeroSection() {
    return (
        <section className="hero">
            <div className="hero-content">
                <h1>Your Personal Healthcare Companion for Chronic Disease Management</h1>
                <p>Take control of your health with our AI-powered platform that helps you manage chronic conditions, track symptoms, and connect with healthcare providers.</p>
                <div className="cta-buttons">
                    <a href="register.php" className="btn">Get Started</a>
                    <a href="#features" className="btn btn-outline">Learn More</a>
                </div>
            </div>
            <div className="hero-image">
                {/* Image will be added via CSS background */}
            </div>
        </section>
    );
}

// Features Section Component
function FeaturesSection() {
    const features = [
        {
            icon: 'chart-line',
            title: 'Symptom Tracking',
            description: 'Log and monitor your symptoms with our intuitive tracking system.'
        },
        {
            icon: 'calendar-check',
            title: 'Appointment Management',
            description: 'Schedule and keep track of all your medical appointments in one place.'
        },
        {
            icon: 'pills',
            title: 'Medication Reminders',
            description: 'Never miss a dose with our smart medication reminder system.'
        },
        {
            icon: 'user-md',
            title: 'Doctor Connect',
            description: 'Connect with healthcare providers for virtual consultations.'
        },
        {
            icon: 'chart-pie',
            title: 'Health Analytics',
            description: 'Get insights into your health trends with detailed analytics.'
        },
        {
            icon: 'users',
            title: 'Support Community',
            description: 'Join a community of people managing similar health conditions.'
        }
    ];

    return (
        <section id="features" className="features">
            <h2 className="section-title">Features That Help You Thrive</h2>
            <div className="features-grid">
                {features.map((feature, index) => (
                    <div key={index} className="feature-card">
                        <div className="feature-icon">
                            <i className={`fas fa-${feature.icon}`}></i>
                        </div>
                        <h3>{feature.title}</h3>
                        <p>{feature.description}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}

// How It Works Component
function HowItWorks() {
    const steps = [
        {
            number: '01',
            title: 'Sign Up',
            description: 'Create your personalized health profile in minutes.'
        },
        {
            number: '02',
            title: 'Set Up Your Profile',
            description: 'Add your health conditions, medications, and preferences.'
        },
        {
            number: '03',
            title: 'Start Managing',
            description: 'Use our tools to track symptoms, medications, and appointments.'
        },
        {
            number: '04',
            title: 'Connect & Improve',
            description: 'Get insights and connect with healthcare professionals.'
        }
    ];

    return (
        <section id="how-it-works" className="how-it-works">
            <div className="container">
                <h2 className="section-title">How It Works</h2>
                <div className="steps-container">
                    {steps.map((step, index) => (
                        <div key={index} className="step">
                            <div className="step-number">{step.number}</div>
                            <h3>{step.title}</h3>
                            <p>{step.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

// Testimonials Component
function Testimonials() {
    const testimonials = [
        {
            quote: "This platform has completely transformed how I manage my diabetes. The medication reminders are a lifesaver!",
            author: "Sarah J.",
            condition: "Type 2 Diabetes"
        },
        {
            quote: "As a caregiver for my father with heart disease, this app has made it so much easier to coordinate his care.",
            author: "Michael T.",
            condition: "Caregiver"
        },
        {
            quote: "The symptom tracking feature helps me identify patterns in my condition that I never noticed before.",
            author: "Emma R.",
            condition: "Rheumatoid Arthritis"
        }
    ];

    return (
        <section id="testimonials" className="testimonials">
            <div className="container">
                <h2 className="section-title">What Our Users Say</h2>
                <div className="testimonials-grid">
                    {testimonials.map((testimonial, index) => (
                        <div key={index} className="testimonial-card">
                            <p className="quote">"{testimonial.quote}"</p>
                            <div className="author">
                                <span className="name">{testimonial.author}</span>
                                <span className="condition">{testimonial.condition}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

// Call to Action Component
function CallToAction() {
    return (
        <section className="cta">
            <div className="container">
                <h2>Ready to Take Control of Your Health Journey?</h2>
                <p>Join thousands of users managing their chronic conditions with confidence.</p>
                <a href="register.php" className="btn btn-large">Start Your Free Trial</a>
            </div>
        </section>
    );
}

// Footer Component
function Footer() {
    return (
        <footer className="footer">
            <div className="container">
                <div className="footer-content">
                    <div className="footer-logo">
                        <a href="/" className="logo">HealthAssist Pro</a>
                        <p>Empowering you to take control of your health journey.</p>
                    </div>
                    <div className="footer-links">
                        <div className="footer-column">
                            <h4>Company</h4>
                            <a href="#">About Us</a>
                            <a href="#">Careers</a>
                            <a href="#">Blog</a>
                            <a href="#">Press</a>
                        </div>
                        <div className="footer-column">
                            <h4>Product</h4>
                            <a href="#">Features</a>
                            <a href="#">Pricing</a>
                            <a href="#">Security</a>
                            <a href="#">FAQ</a>
                        </div>
                        <div className="footer-column">
                            <h4>Resources</h4>
                            <a href="#">Help Center</a>
                            <a href="#">Community</a>
                            <a href="#">Contact Support</a>
                            <a href="#">Privacy Policy</a>
                        </div>
                    </div>
                </div>
                <div className="footer-bottom">
                    <p>&copy; {new Date().getFullYear()} HealthAssist Pro. All rights reserved.</p>
                    <div className="social-links">
                        <a href="#" aria-label="Facebook"><i className="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i className="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i className="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i className="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </footer>
    );
}

// Render the app
ReactDOM.render(<App />, document.getElementById('root'));
