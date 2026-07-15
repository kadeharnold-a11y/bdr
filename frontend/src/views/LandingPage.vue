<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuOpen = ref(false)

const services = [
  { icon: '👶', title: 'Early Birth', sub: 'Within 12 months', route: '/signup' },
  { icon: '📋', title: 'Late Birth', sub: 'Over 12 months', route: '/signup' },
  { icon: '⚰️', title: 'Death Registration', sub: 'Standard & foetal', route: '/signup' },
  { icon: '💔', title: 'Foetal Death', sub: '28+ weeks gestation', route: '/signup' },
  { icon: '🏠', title: 'Adoption', sub: 'Court order required', route: '/signup' },
  { icon: '🧬', title: 'Surrogacy Birth', sub: 'ART / surrogate', route: '/signup' },
]

const stats = [
  { value: '2M+', label: 'Certificates Issued' },
  { value: '16', label: 'Regions Covered' },
  { value: '48h', label: 'Express Processing' },
  { value: '100%', label: 'Secure & Official' },
]

const steps = [
  {
    number: '01',
    icon: '📱',
    title: 'Create account',
    desc: 'Sign up with your phone number — no email required',
  },
  {
    number: '02',
    icon: '📝',
    title: 'Choose event',
    desc: 'Select from 6 registration event types',
  },
  {
    number: '03',
    icon: '💳',
    title: 'Fill & pay',
    desc: 'Complete your form and pay via Mobile Money or card',
  },
  {
    number: '04',
    icon: '📄',
    title: 'Track & receive',
    desc: 'Follow your application and receive your certificate',
  },
]

// Close menu on resize
function onResize() {
  if (window.innerWidth > 768) menuOpen.value = false
}
onMounted(() => window.addEventListener('resize', onResize))
onBeforeUnmount(() => window.removeEventListener('resize', onResize))

function scrollTo(id) {
  menuOpen.value = false
  const el = document.getElementById(id)
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
  <div class="lp-shell">
    <!-- ─── Ghana flag stripe ─── -->
    <div class="flag-topbar" aria-hidden="true"></div>

    <!-- ─── Navbar ─── -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
      <div class="nav-inner">
        <a class="nav-brand" href="#" aria-label="BDR home">
          <img src="/coat-of-arms.png" alt="Ghana coat of arms" class="nav-logo" />
          <div class="nav-brand-text">
            <span class="nav-brand-primary">Republic of Ghana</span>
            <span class="nav-brand-secondary">Ministry of Local Government, Chieftaincy &amp; Religious Affairs</span>
          </div>
        </a>

        <div class="nav-links" :class="{ open: menuOpen }">
          <button class="nav-link" @click="scrollTo('how-it-works')">How it works</button>
          <button class="nav-link" @click="scrollTo('services')">Services</button>
          <div class="nav-actions">
            <router-link to="/track" class="btn-track">Track Application</router-link>
            <button class="btn-lang" aria-label="Language selector">
              🌐 EN ▾
            </button>
          </div>
        </div>

        <button
          class="hamburger"
          :aria-expanded="menuOpen"
          aria-controls="nav-links"
          @click="menuOpen = !menuOpen"
        >
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>

    <!-- ─── Hero ─── -->
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero-inner">
        <div class="hero-badge">
          <span class="hero-badge-dot"></span>
          Official Government Portal · hbdrp.bdr.gov.gh
        </div>
        <h1 id="hero-title" class="hero-title">Birth &amp; Death Registry</h1>
        <p class="hero-subtitle">Republic of Ghana · Ministry of Local Government</p>
        <div class="hero-cta">
          <router-link to="/signup" class="btn-apply">Apply here</router-link>
          <router-link to="/login" class="btn-login">Login</router-link>
        </div>
      </div>

      <!-- Decorative Ghana flag colours circle -->
      <div class="hero-deco" aria-hidden="true">
        <div class="hero-deco-ring"></div>
        <img src="/coat-of-arms.png" alt="" class="hero-deco-seal" />
      </div>
    </section>

    <!-- ─── How it works ─── -->
    <section id="how-it-works" class="section" aria-labelledby="how-title">
      <div class="section-inner">
        <div class="section-header">
          <p class="section-eyebrow">Process</p>
          <h2 id="how-title" class="section-title">How it works</h2>
          <p class="section-lead">Four simple steps</p>
        </div>

        <div class="steps-grid">
          <article v-for="step in steps" :key="step.number" class="step-card">
            <div class="step-num">{{ step.number }}</div>
            <div class="step-icon">{{ step.icon }}</div>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
          </article>
        </div>
      </div>
    </section>

    <!-- ─── Metrics bar ─── -->
    <div class="metrics-bar" aria-label="Key statistics">
      <div class="metrics-inner">
        <div v-for="stat in stats" :key="stat.label" class="metric-item">
          <span class="metric-value">{{ stat.value }}</span>
          <span class="metric-label">{{ stat.label }}</span>
        </div>
      </div>
    </div>

    <!-- ─── Services ─── -->
    <section id="services" class="section section--alt" aria-labelledby="services-title">
      <div class="section-inner">
        <div class="section-header">
          <p class="section-eyebrow">Services</p>
          <h2 id="services-title" class="section-title">Registration services</h2>
          <p class="section-lead">What would you like to register?</p>
        </div>

        <div class="services-grid">
          <button
            v-for="svc in services"
            :key="svc.title"
            class="event-card"
            role="button"
            :aria-label="`Register: ${svc.title}`"
            @click="router.push(svc.route)"
          >
            <span class="event-icon">{{ svc.icon }}</span>
            <h3 class="event-title">{{ svc.title }}</h3>
            <p class="event-sub">{{ svc.sub }}</p>
            <span class="event-arrow">→</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ─── CTA banner ─── -->
    <section class="cta-section" aria-labelledby="cta-title">
      <div class="cta-inner">
        <p class="cta-eyebrow">Get started</p>
        <h2 id="cta-title" class="cta-title">Get Started Today</h2>
        <p class="cta-lead">Ready to register?</p>
        <p class="cta-desc">
          Create your free account and begin your civil registration in minutes.
          Available 24/7 from anywhere in Ghana.
        </p>
        <div class="cta-actions">
          <router-link to="/signup" class="btn-apply">Create Account</router-link>
          <router-link to="/track" class="btn-track-outline">Track Existing Application</router-link>
        </div>
      </div>
    </section>

    <!-- ─── Footer ─── -->
    <footer class="footer" role="contentinfo">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo-row">
            <img src="/coat-of-arms.png" alt="Ghana coat of arms" class="footer-logo" />
            <div>
              <p class="footer-brand-primary">Republic of Ghana</p>
              <p class="footer-brand-secondary">Births &amp; Deaths Registry</p>
            </div>
          </div>
          <p class="footer-brand-desc">
            Ghana's official civil registration portal for births, deaths, and related vital
            events — available 24/7 from anywhere in Ghana.
          </p>
          <div class="footer-flag" aria-hidden="true"></div>
        </div>

        <div class="footer-col">
          <p class="footer-col-head">Quick Links</p>
          <ul class="footer-links">
            <li><button class="footer-link" @click="scrollTo('how-it-works')">How it Works</button></li>
            <li><button class="footer-link" @click="scrollTo('services')">Services</button></li>
            <li><router-link to="/track" class="footer-link">Track Application</router-link></li>
            <li><router-link to="/signup" class="footer-link">Create Account</router-link></li>
            <li><router-link to="/login" class="footer-link">Login</router-link></li>
          </ul>
        </div>

        <div class="footer-col">
          <p class="footer-col-head">Legal</p>
          <ul class="footer-links">
            <li><a href="#" class="footer-link">Privacy Policy</a></li>
            <li><a href="#" class="footer-link">Terms of Service</a></li>
            <li><a href="#" class="footer-link">Accessibility</a></li>
            <li><a href="#" class="footer-link">About BDR</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <p class="footer-col-head">Contact Us</p>
          <address class="footer-address">
            <p>BDR Head Office, Ministries, Accra, Ghana</p>
            <p><a href="tel:+233302666651" class="footer-link">+233 30 266 6651</a></p>
            <p><a href="mailto:info@bdr.gov.gh" class="footer-link">info@bdr.gov.gh</a></p>
            <p><a href="https://hbdrp.bdr.gov.gh" class="footer-link" target="_blank" rel="noopener">hbdrp.bdr.gov.gh</a></p>
          </address>
        </div>
      </div>

      <div class="footer-bottom">
        <p>© 2026 Republic of Ghana · Births &amp; Deaths Registry. All rights reserved.</p>
        <p>Powered by HBDRP Digital Services</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* ── Shell ── */
.lp-shell {
  min-height: 100vh;
  font-family: var(--font-body);
  color: var(--text-primary);
  background: var(--surface);
}

/* ── Ghana flag stripe ── */
.flag-topbar {
  height: 6px;
  width: 100%;
  background: linear-gradient(
    to right,
    var(--brand-red) 0% 33.33%,
    var(--brand-gold) 33.33% 66.66%,
    var(--brand-green) 66.66% 100%
  );
}

/* ── Navbar ── */
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}

.nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  height: 68px;
  display: flex;
  align-items: center;
  gap: 24px;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

.nav-logo {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.nav-brand-text {
  display: flex;
  flex-direction: column;
}

.nav-brand-primary {
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

.nav-brand-secondary {
  font-size: 10px;
  color: var(--text-muted);
  line-height: 1.3;
  max-width: 200px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}

.nav-link {
  background: none;
  border: none;
  padding: 8px 12px;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
  cursor: pointer;
  border-radius: var(--radius);
  transition: color 150ms, background 150ms;
}

.nav-link:hover {
  color: var(--brand-green);
  background: rgba(0, 107, 63, 0.06);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 8px;
}

.btn-track {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  border: 1.5px solid var(--brand-green);
  border-radius: var(--radius);
  color: var(--brand-green);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: background 150ms, color 150ms;
  white-space: nowrap;
}

.btn-track:hover {
  background: var(--brand-green);
  color: #fff;
}

.btn-lang {
  background: none;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary);
  cursor: pointer;
  transition: border-color 150ms;
}

.btn-lang:hover {
  border-color: var(--brand-green);
}

.hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  margin-left: auto;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.hamburger span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--text-primary);
  border-radius: 2px;
  transition: transform 200ms;
}

/* ── Hero ── */
.hero {
  background: linear-gradient(135deg, #00532f 0%, #006b3f 45%, #007f4a 100%);
  color: #fff;
  padding: 96px 24px 80px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
  background-size: 28px 28px;
  pointer-events: none;
}

.hero-inner {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 680px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 100px;
  padding: 6px 16px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.03em;
  margin-bottom: 24px;
}

.hero-badge-dot {
  width: 7px;
  height: 7px;
  background: var(--brand-gold);
  border-radius: 50%;
  flex-shrink: 0;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(0.85); }
}

.hero-title {
  font-family: var(--font-heading);
  font-size: clamp(36px, 6vw, 64px);
  font-weight: 800;
  line-height: 1.1;
  margin: 0 0 12px;
}

.hero-subtitle {
  font-size: 16px;
  color: rgba(255,255,255,0.75);
  margin: 0 0 40px;
  font-weight: 500;
}

.hero-cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-apply {
  display: inline-flex;
  align-items: center;
  padding: 14px 32px;
  background: var(--brand-gold);
  color: #1a2537;
  font-size: 15px;
  font-weight: 700;
  border-radius: var(--radius);
  text-decoration: none;
  transition: background 150ms, transform 150ms, box-shadow 150ms;
  box-shadow: 0 4px 16px rgba(252, 209, 22, 0.4);
}

.btn-apply:hover {
  background: #e8be0e;
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(252, 209, 22, 0.45);
}

.btn-login {
  display: inline-flex;
  align-items: center;
  padding: 14px 32px;
  background: rgba(255,255,255,0.12);
  border: 1.5px solid rgba(255,255,255,0.4);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  border-radius: var(--radius);
  text-decoration: none;
  transition: background 150ms, border-color 150ms;
}

.btn-login:hover {
  background: rgba(255,255,255,0.22);
  border-color: rgba(255,255,255,0.7);
}

/* Hero deco */
.hero-deco {
  position: absolute;
  right: -40px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  z-index: 0;
  opacity: 0.12;
}

.hero-deco-ring {
  width: 360px;
  height: 360px;
  border: 40px solid rgba(255,255,255,0.5);
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.hero-deco-seal {
  width: 240px;
  height: 240px;
  object-fit: contain;
}

/* ── Section commons ── */
.section {
  padding: 80px 24px;
}

.section--alt {
  background: var(--background);
}

.section-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 56px;
}

.section-eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--brand-green);
  margin: 0 0 8px;
}

.section-title {
  font-family: var(--font-heading);
  font-size: clamp(28px, 4vw, 40px);
  font-weight: 800;
  margin: 0 0 12px;
}

.section-lead {
  font-size: 16px;
  color: var(--text-muted);
  margin: 0;
}

/* ── Steps ── */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 24px;
}

.step-card {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 32px 24px;
  position: relative;
  transition: box-shadow 200ms, transform 200ms;
}

.step-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}

.step-num {
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--brand-green);
  background: rgba(0, 107, 63, 0.08);
  display: inline-block;
  padding: 4px 10px;
  border-radius: 100px;
  margin-bottom: 16px;
}

.step-icon {
  font-size: 32px;
  margin-bottom: 12px;
}

.step-title {
  font-family: var(--font-heading);
  font-size: 17px;
  font-weight: 700;
  margin: 0 0 8px;
}

.step-desc {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.5;
}

/* ── Metrics bar ── */
.metrics-bar {
  background: var(--brand-green);
  padding: 32px 24px;
}

.metrics-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 24px;
  text-align: center;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.metric-value {
  font-family: var(--font-heading);
  font-size: 36px;
  font-weight: 800;
  color: var(--brand-gold);
  line-height: 1;
}

.metric-label {
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,0.8);
}

/* ── Services ── */
.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.event-card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 20px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  text-align: left;
  cursor: pointer;
  transition: border-color 200ms, box-shadow 200ms, transform 200ms;
  position: relative;
}

.event-card:hover {
  border-color: var(--brand-green);
  box-shadow: 0 8px 24px rgba(0, 107, 63, 0.12);
  transform: translateY(-2px);
}

.event-icon {
  font-size: 28px;
  margin-bottom: 4px;
}

.event-title {
  font-family: var(--font-heading);
  font-size: 16px;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.event-sub {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

.event-arrow {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 18px;
  color: var(--brand-green);
  opacity: 0;
  transform: translateX(-4px);
  transition: opacity 200ms, transform 200ms;
}

.event-card:hover .event-arrow {
  opacity: 1;
  transform: translateX(0);
}

/* ── CTA section ── */
.cta-section {
  background: linear-gradient(135deg, #00532f 0%, #006b3f 100%);
  padding: 96px 24px;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
}

.cta-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 28px 28px;
  pointer-events: none;
}

.cta-inner {
  position: relative;
  z-index: 1;
  max-width: 600px;
  margin: 0 auto;
}

.cta-eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--brand-gold);
  margin: 0 0 8px;
}

.cta-title {
  font-family: var(--font-heading);
  font-size: clamp(28px, 4vw, 44px);
  font-weight: 800;
  margin: 0 0 8px;
}

.cta-lead {
  font-size: 18px;
  color: rgba(255,255,255,0.8);
  margin: 0 0 16px;
}

.cta-desc {
  font-size: 15px;
  color: rgba(255,255,255,0.65);
  margin: 0 0 40px;
  line-height: 1.6;
}

.cta-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-track-outline {
  display: inline-flex;
  align-items: center;
  padding: 14px 28px;
  border: 1.5px solid rgba(255,255,255,0.5);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  border-radius: var(--radius);
  text-decoration: none;
  transition: background 150ms, border-color 150ms;
}

.btn-track-outline:hover {
  background: rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.8);
}

/* ── Footer ── */
.footer {
  background: #0d1f14;
  color: rgba(255,255,255,0.75);
  padding: 64px 24px 0;
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 48px;
  padding-bottom: 48px;
}

.footer-logo-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.footer-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
  opacity: 0.9;
}

.footer-brand-primary {
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 2px;
}

.footer-brand-secondary {
  font-size: 11px;
  color: rgba(255,255,255,0.5);
  margin: 0;
}

.footer-brand-desc {
  font-size: 13px;
  line-height: 1.6;
  color: rgba(255,255,255,0.55);
  margin: 0 0 20px;
  max-width: 280px;
}

.footer-flag {
  height: 4px;
  width: 60px;
  border-radius: 4px;
  background: linear-gradient(
    to right,
    var(--brand-red) 0% 33.33%,
    var(--brand-gold) 33.33% 66.66%,
    var(--brand-green) 66.66% 100%
  );
}

.footer-col-head {
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.9);
  margin: 0 0 16px;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 13px;
  color: rgba(255,255,255,0.55);
  text-decoration: none;
  cursor: pointer;
  transition: color 150ms;
  text-align: left;
  font-family: var(--font-body);
}

.footer-link:hover {
  color: rgba(255,255,255,0.9);
}

.footer-address {
  font-style: normal;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-address p {
  margin: 0;
  font-size: 13px;
  color: rgba(255,255,255,0.55);
}

.footer-bottom {
  border-top: 1px solid rgba(255,255,255,0.08);
  padding: 24px 0;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.footer-bottom p {
  margin: 0;
  font-size: 12px;
  color: rgba(255,255,255,0.4);
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .footer-inner {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .nav-brand-secondary {
    display: none;
  }

  .nav-links {
    display: none;
    position: absolute;
    top: 68px;
    left: 0;
    right: 0;
    background: #fff;
    border-bottom: 1px solid var(--border);
    flex-direction: column;
    align-items: flex-start;
    padding: 16px 24px;
    gap: 4px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }

  .nav-links.open {
    display: flex;
  }

  .nav-actions {
    margin-left: 0;
    margin-top: 8px;
    width: 100%;
    flex-wrap: wrap;
  }

  .hamburger {
    display: flex;
  }

  .hero-deco {
    display: none;
  }

  .footer-inner {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .hero {
    padding: 72px 20px 64px;
  }

  .section {
    padding: 60px 20px;
  }
}
</style>
