<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuOpen = ref(false)

const services = [
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`,
    title: 'Early Birth',
    sub: 'Within 12 months',
  },
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>`,
    title: 'Late Birth',
    sub: 'Over 12 months',
  },
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>`,
    title: 'Death Registration',
    sub: 'Standard & foetal',
  },
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
    title: 'Foetal Death',
    sub: '28+ weeks gestation',
  },
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
    title: 'Adoption',
    sub: 'Court order required',
  },
  {
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
    title: 'Surrogacy Birth',
    sub: 'ART / surrogate',
  },
]

const stats = [
  { value: '2M+', label: 'Certificates Issued' },
  { value: '16', label: 'Regions Covered' },
  { value: '48h', label: 'Express Processing' },
  { value: '100%', label: 'Secure & Official' },
]

const steps = [
  {
    title: 'Create account',
    desc: 'Sign up with your phone number — no email required',
  },
  {
    title: 'Choose event',
    desc: 'Select from 6 registration event types',
  },
  {
    title: 'Fill & pay',
    desc: 'Complete your form and pay via Mobile Money or card',
  },
  {
    title: 'Track & receive',
    desc: 'Follow your application and receive your certificate',
  },
]

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
    <nav class="navbar" aria-label="Main navigation">
      <div class="nav-inner">
        <a class="nav-brand" href="#" aria-label="BDR home">
          <img src="/coat-of-arms.png" alt="Ghana coat of arms" class="nav-logo" width="40" height="40" />
          <div class="nav-brand-text">
            <span class="nav-brand-primary">Republic of Ghana</span>
            <span class="nav-brand-secondary">Ministry of Local Government, Chieftaincy &amp; Religious Affairs</span>
          </div>
        </a>

        <div class="nav-links" :class="{ open: menuOpen }" id="nav-links">
          <button class="nav-link" @click="scrollTo('how-it-works')">How it works</button>
          <button class="nav-link" @click="scrollTo('services')">Services</button>
          <div class="nav-actions">
            <router-link to="/track" class="btn-track">Track Application</router-link>
            <button class="btn-lang" aria-label="Select language: English">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              EN
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
          </div>
        </div>

        <button
          class="hamburger"
          :aria-expanded="String(menuOpen)"
          aria-controls="nav-links"
          aria-label="Toggle navigation menu"
          @click="menuOpen = !menuOpen"
        >
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>

    <!-- ─── Hero ─── -->
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero-inner">
        <div class="hero-badge" role="note">
          <span class="hero-badge-dot" aria-hidden="true"></span>
          Official Government Portal &thinsp;·&thinsp; hbdrp.bdr.gov.gh
        </div>
        <h1 id="hero-title" class="hero-title">Birth &amp; Death<br>Registry</h1>
        <p class="hero-subtitle">Republic of Ghana · Ministry of Local Government</p>
        <div class="hero-cta">
          <router-link to="/signup" class="btn-apply">Apply here</router-link>
          <router-link to="/login" class="btn-login">Login</router-link>
        </div>
      </div>

      <!-- Decorative large seal -->
      <div class="hero-deco" aria-hidden="true">
        <div class="hero-deco-ring hero-deco-ring--outer"></div>
        <div class="hero-deco-ring hero-deco-ring--inner"></div>
        <img src="/coat-of-arms.png" alt="" class="hero-deco-seal" />
      </div>
    </section>

    <!-- ─── How it works ─── -->
    <section id="how-it-works" class="section how-section" aria-labelledby="how-title">
      <div class="section-inner">
        <div class="section-header">
          <h2 id="how-title" class="section-title">How it works</h2>
          <p class="section-lead">Four simple steps to register from anywhere in Ghana.</p>
        </div>

        <ol class="steps-list">
          <li
            v-for="(step, i) in steps"
            :key="step.title"
            class="step-item"
          >
            <div class="step-number" aria-hidden="true">{{ String(i + 1).padStart(2, '0') }}</div>
            <div class="step-body">
              <h3 class="step-title">{{ step.title }}</h3>
              <p class="step-desc">{{ step.desc }}</p>
            </div>
            <div v-if="i < steps.length - 1" class="step-connector" aria-hidden="true"></div>
          </li>
        </ol>
      </div>
    </section>

    <!-- ─── Metrics bar ─── -->
    <div class="metrics-bar" role="region" aria-label="Key statistics">
      <div class="metrics-inner">
        <div v-for="stat in stats" :key="stat.label" class="metric-item">
          <span class="metric-value" aria-label="`${stat.value} ${stat.label}`">{{ stat.value }}</span>
          <span class="metric-label">{{ stat.label }}</span>
        </div>
      </div>
    </div>

    <!-- ─── Services ─── -->
    <section id="services" class="section section--alt" aria-labelledby="services-title">
      <div class="section-inner">
        <div class="section-header">
          <h2 id="services-title" class="section-title">Registration services</h2>
          <p class="section-lead">What would you like to register?</p>
        </div>

        <div class="services-grid">
          <button
            v-for="svc in services"
            :key="svc.title"
            class="event-card"
            :aria-label="`Register: ${svc.title} — ${svc.sub}`"
            @click="router.push('/signup')"
          >
            <span class="event-icon" aria-hidden="true" v-html="svc.icon"></span>
            <h3 class="event-title">{{ svc.title }}</h3>
            <p class="event-sub">{{ svc.sub }}</p>
            <span class="event-arrow" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </span>
          </button>
        </div>
      </div>
    </section>

    <!-- ─── CTA banner ─── -->
    <section class="cta-section" aria-labelledby="cta-title">
      <div class="cta-inner">
        <h2 id="cta-title" class="cta-title">Ready to register?</h2>
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
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo-row">
            <img src="/coat-of-arms.png" alt="Ghana coat of arms" class="footer-logo" width="36" height="36" />
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

        <nav class="footer-col" aria-label="Quick links">
          <p class="footer-col-head">Quick Links</p>
          <ul class="footer-links">
            <li><button class="footer-link" @click="scrollTo('how-it-works')">How it Works</button></li>
            <li><button class="footer-link" @click="scrollTo('services')">Services</button></li>
            <li><router-link to="/track" class="footer-link">Track Application</router-link></li>
            <li><router-link to="/signup" class="footer-link">Create Account</router-link></li>
            <li><router-link to="/login" class="footer-link">Login</router-link></li>
          </ul>
        </nav>

        <nav class="footer-col" aria-label="Legal">
          <p class="footer-col-head">Legal</p>
          <ul class="footer-links">
            <li><a href="#" class="footer-link">Privacy Policy</a></li>
            <li><a href="#" class="footer-link">Terms of Service</a></li>
            <li><a href="#" class="footer-link">Accessibility</a></li>
            <li><a href="#" class="footer-link">About BDR</a></li>
          </ul>
        </nav>

        <div class="footer-col">
          <p class="footer-col-head">Contact Us</p>
          <address class="footer-address">
            <p>BDR Head Office, Ministries, Accra, Ghana</p>
            <p><a href="tel:+233302666651" class="footer-link">+233 30 266 6651</a></p>
            <p><a href="mailto:info@bdr.gov.gh" class="footer-link">info@bdr.gov.gh</a></p>
            <p><a href="https://hbdrp.bdr.gov.gh" class="footer-link" target="_blank" rel="noopener noreferrer">hbdrp.bdr.gov.gh</a></p>
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
/* ── Design tokens (local aliases) ── */
:root {
  --lp-green: var(--brand-green, #006b3f);
  --lp-green-dark: var(--brand-green-dark, #00532f);
  --lp-gold: var(--brand-gold, #fcd116);
  --lp-red: var(--brand-red, #ce1126);
  --lp-ink: var(--text-primary, #1a2537);
  --lp-muted: var(--text-muted, #6b7280);
  --lp-surface: var(--surface, #ffffff);
  --lp-bg: var(--background, #f7f8f9);
  --lp-border: var(--border, #e5e7eb);
  --lp-radius: var(--radius, 8px);
  --lp-radius-lg: var(--radius-lg, 14px);
  --lp-font-head: var(--font-heading, 'Public Sans', sans-serif);
  --lp-font-body: var(--font-body, 'Manrope', sans-serif);

  /* semantic z-index scale */
  --z-sticky: 50;
  --z-dropdown: 60;
  --z-modal-backdrop: 70;
  --z-modal: 80;
}

/* ── Shell ── */
.lp-shell {
  min-height: 100vh;
  font-family: var(--lp-font-body);
  color: var(--lp-ink);
  background: var(--lp-surface);
}

/* ── Ghana flag stripe ── */
.flag-topbar {
  height: 6px;
  width: 100%;
  background: linear-gradient(
    to right,
    var(--lp-red) 0% 33.33%,
    var(--lp-gold) 33.33% 66.66%,
    var(--lp-green) 66.66% 100%
  );
}

/* ── Navbar ── */
.navbar {
  position: sticky;
  top: 0;
  z-index: var(--z-sticky);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--lp-border);
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
  border-radius: var(--lp-radius);
  outline-offset: 4px;
}

.nav-brand:focus-visible {
  outline: 2px solid var(--lp-green);
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
  font-family: var(--lp-font-head);
  font-size: 14px;
  font-weight: 700;
  color: var(--lp-ink);
  line-height: 1.2;
}

.nav-brand-secondary {
  font-size: 10px;
  color: var(--lp-muted);
  line-height: 1.3;
  max-width: 220px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-left: auto;
}

.nav-link {
  background: none;
  border: none;
  padding: 8px 14px;
  font-size: 14px;
  font-weight: 500;
  font-family: var(--lp-font-body);
  color: var(--lp-ink);
  cursor: pointer;
  border-radius: var(--lp-radius);
  transition: color 150ms ease, background 150ms ease;
}

.nav-link:hover {
  color: var(--lp-green);
  background: rgba(0, 107, 63, 0.07);
}

.nav-link:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 2px;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 12px;
}

.btn-track {
  display: inline-flex;
  align-items: center;
  padding: 8px 18px;
  border: 1.5px solid var(--lp-green);
  border-radius: var(--lp-radius);
  color: var(--lp-green);
  font-size: 13px;
  font-weight: 600;
  font-family: var(--lp-font-body);
  text-decoration: none;
  transition: background 150ms ease, color 150ms ease;
  white-space: nowrap;
}

.btn-track:hover {
  background: var(--lp-green);
  color: #fff;
}

.btn-track:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 2px;
}

.btn-lang {
  background: none;
  border: 1.5px solid var(--lp-border);
  border-radius: var(--lp-radius);
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 500;
  font-family: var(--lp-font-body);
  color: var(--lp-ink);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: border-color 150ms ease;
}

.btn-lang:hover {
  border-color: var(--lp-green);
}

.btn-lang:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 2px;
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
  padding: 8px;
  border-radius: var(--lp-radius);
}

.hamburger:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 2px;
}

.hamburger span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--lp-ink);
  border-radius: 2px;
  transition: transform 220ms ease, opacity 220ms ease;
}

/* ── Hero ── */
.hero {
  background: linear-gradient(140deg, #00532f 0%, #006b3f 50%, #007f4a 100%);
  color: #fff;
  padding: 104px 24px 88px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 520px;
}

.hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
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
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 100px;
  padding: 7px 18px;
  font-size: 11.5px;
  font-weight: 600;
  letter-spacing: 0.04em;
  margin-bottom: 28px;
  color: rgba(255, 255, 255, 0.92);
}

.hero-badge-dot {
  width: 7px;
  height: 7px;
  background: var(--lp-gold);
  border-radius: 50%;
  flex-shrink: 0;
}

@media (prefers-reduced-motion: no-preference) {
  .hero-badge-dot {
    animation: pulse 2.4s ease-in-out infinite;
  }
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.55; transform: scale(0.8); }
}

.hero-title {
  font-family: var(--lp-font-head);
  font-size: clamp(40px, 6vw, 72px);
  font-weight: 800;
  line-height: 1.08;
  letter-spacing: -0.03em;
  margin: 0 0 14px;
  text-wrap: balance;
}

.hero-subtitle {
  font-size: 15px;
  /* Sufficient contrast: white 100% against dark green */
  color: rgba(255, 255, 255, 0.88);
  margin: 0 0 44px;
  font-weight: 500;
  letter-spacing: 0.01em;
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
  justify-content: center;
  padding: 14px 34px;
  background: var(--lp-gold);
  color: #1a2537;
  font-size: 15px;
  font-weight: 700;
  font-family: var(--lp-font-body);
  border-radius: var(--lp-radius);
  text-decoration: none;
  transition: background 150ms ease, transform 150ms ease, box-shadow 150ms ease;
  box-shadow: 0 4px 20px rgba(252, 209, 22, 0.38);
}

.btn-apply:hover {
  background: #e8be0e;
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(252, 209, 22, 0.48);
}

.btn-apply:active {
  transform: translateY(0);
}

.btn-apply:focus-visible {
  outline: 3px solid #fff;
  outline-offset: 3px;
}

.btn-login {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 14px 34px;
  background: rgba(255, 255, 255, 0.1);
  border: 1.5px solid rgba(255, 255, 255, 0.45);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  font-family: var(--lp-font-body);
  border-radius: var(--lp-radius);
  text-decoration: none;
  transition: background 150ms ease, border-color 150ms ease;
}

.btn-login:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.75);
}

.btn-login:focus-visible {
  outline: 3px solid #fff;
  outline-offset: 3px;
}

/* Hero decorative seal */
.hero-deco {
  position: absolute;
  right: clamp(-80px, -4vw, -20px);
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  z-index: 0;
  width: 380px;
  height: 380px;
}

.hero-deco-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.hero-deco-ring--outer {
  width: 380px;
  height: 380px;
}

.hero-deco-ring--inner {
  width: 260px;
  height: 260px;
  border-color: rgba(255, 255, 255, 0.08);
  border-width: 40px;
}

.hero-deco-seal {
  width: 200px;
  height: 200px;
  object-fit: contain;
  opacity: 0.1;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

/* ── Section commons ── */
.section {
  padding: 88px 24px;
}

.section--alt {
  background: var(--lp-bg);
}

.section-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 64px;
}

.section-title {
  font-family: var(--lp-font-head);
  font-size: clamp(28px, 4vw, 44px);
  font-weight: 800;
  margin: 0 0 14px;
  letter-spacing: -0.02em;
  text-wrap: balance;
}

.section-lead {
  font-size: 17px;
  color: var(--lp-muted);
  margin: 0;
  max-width: 52ch;
  margin-inline: auto;
  line-height: 1.6;
  text-wrap: pretty;
}

/* ── How it works (horizontal numbered flow) ── */
.how-section {
  background: var(--lp-surface);
}

.steps-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  position: relative;
}

/* Connecting line behind all steps */
.steps-list::before {
  content: '';
  position: absolute;
  top: 28px;
  left: calc(12.5% + 28px);
  right: calc(12.5% + 28px);
  height: 1px;
  background: var(--lp-border);
  z-index: 0;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 0 20px;
  position: relative;
  z-index: 1;
}

.step-number {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--lp-green);
  color: #fff;
  font-family: var(--lp-font-head);
  font-size: 18px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  flex-shrink: 0;
  box-shadow: 0 0 0 6px var(--lp-surface), 0 0 0 7px var(--lp-border);
  letter-spacing: -0.02em;
}

.step-body {
  max-width: 200px;
}

.step-title {
  font-family: var(--lp-font-head);
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 8px;
  text-wrap: balance;
}

.step-desc {
  font-size: 13.5px;
  color: var(--lp-muted);
  margin: 0;
  line-height: 1.55;
  text-wrap: pretty;
}

/* ── Metrics bar ── */
.metrics-bar {
  background: var(--lp-green);
  padding: 40px 24px;
}

.metrics-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
  text-align: center;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.metric-value {
  font-family: var(--lp-font-head);
  font-size: 40px;
  font-weight: 800;
  color: var(--lp-gold);
  line-height: 1;
  letter-spacing: -0.03em;
}

.metric-label {
  font-size: 13px;
  font-weight: 500;
  /* White text on brand-green: ~7:1 contrast — WCAG AAA */
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 0.02em;
}

/* ── Services ── */
.services-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.event-card {
  background: var(--lp-surface);
  border: 1.5px solid var(--lp-border);
  border-radius: var(--lp-radius-lg);
  padding: 28px 24px 24px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  text-align: left;
  cursor: pointer;
  transition: border-color 200ms ease, box-shadow 200ms ease, transform 200ms ease;
  position: relative;
  font-family: var(--lp-font-body);
}

.event-card:hover {
  border-color: var(--lp-green);
  box-shadow: 0 8px 28px rgba(0, 107, 63, 0.11);
  transform: translateY(-3px);
}

.event-card:active {
  transform: translateY(-1px);
}

.event-card:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 2px;
}

.event-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 107, 63, 0.08);
  border-radius: 10px;
  color: var(--lp-green);
  margin-bottom: 4px;
  flex-shrink: 0;
}

.event-icon svg {
  width: 20px;
  height: 20px;
}

.event-title {
  font-family: var(--lp-font-head);
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  color: var(--lp-ink);
  text-wrap: balance;
}

.event-sub {
  font-size: 12.5px;
  color: var(--lp-muted);
  margin: 0;
  line-height: 1.4;
}

.event-arrow {
  position: absolute;
  bottom: 24px;
  right: 24px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--lp-bg);
  color: var(--lp-green);
  opacity: 0;
  transform: translateX(-4px);
  transition: opacity 200ms ease, transform 200ms ease;
}

.event-card:hover .event-arrow {
  opacity: 1;
  transform: translateX(0);
}

/* ── CTA section ── */
.cta-section {
  background: linear-gradient(140deg, #00532f 0%, #006b3f 100%);
  padding: 100px 24px;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
}

.cta-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
  background-size: 32px 32px;
  pointer-events: none;
}

/* Subtle decorative shapes */
.cta-section::after {
  content: '';
  position: absolute;
  bottom: -80px;
  right: -80px;
  width: 360px;
  height: 360px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.08);
  pointer-events: none;
}

.cta-inner {
  position: relative;
  z-index: 1;
  max-width: 560px;
  margin: 0 auto;
}

.cta-title {
  font-family: var(--lp-font-head);
  font-size: clamp(30px, 4vw, 48px);
  font-weight: 800;
  margin: 0 0 16px;
  letter-spacing: -0.02em;
  text-wrap: balance;
}

.cta-desc {
  font-size: 16px;
  /* White at 84%: ~5.4:1 on dark green — passes AA */
  color: rgba(255, 255, 255, 0.84);
  margin: 0 0 44px;
  line-height: 1.65;
  max-width: 48ch;
  margin-inline: auto;
  margin-bottom: 44px;
  text-wrap: pretty;
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
  justify-content: center;
  padding: 14px 28px;
  border: 1.5px solid rgba(255, 255, 255, 0.45);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  font-family: var(--lp-font-body);
  border-radius: var(--lp-radius);
  text-decoration: none;
  transition: background 150ms ease, border-color 150ms ease;
}

.btn-track-outline:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.8);
}

.btn-track-outline:focus-visible {
  outline: 3px solid #fff;
  outline-offset: 3px;
}

/* ── Footer ── */
.footer {
  background: #0c1a10;
  color: rgba(255, 255, 255, 0.6);
  padding: 64px 24px 0;
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 48px;
  padding-bottom: 56px;
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
  opacity: 0.85;
}

.footer-brand-primary {
  font-family: var(--lp-font-head);
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 2px;
}

.footer-brand-secondary {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
  margin: 0;
}

.footer-brand-desc {
  font-size: 13px;
  line-height: 1.65;
  color: rgba(255, 255, 255, 0.5);
  margin: 0 0 20px;
  max-width: 280px;
  text-wrap: pretty;
}

.footer-flag {
  height: 3px;
  width: 56px;
  border-radius: 3px;
  background: linear-gradient(
    to right,
    var(--lp-red) 0% 33.33%,
    var(--lp-gold) 33.33% 66.66%,
    var(--lp-green) 66.66% 100%
  );
}

.footer-col-head {
  font-family: var(--lp-font-head);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  /* White at 88%: ~6.8:1 on footer dark — passes AAA */
  color: rgba(255, 255, 255, 0.88);
  margin: 0 0 16px;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.footer-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
  text-decoration: none;
  cursor: pointer;
  transition: color 150ms ease;
  text-align: left;
  font-family: var(--lp-font-body);
  border-radius: 2px;
}

.footer-link:hover {
  color: rgba(255, 255, 255, 0.92);
}

.footer-link:focus-visible {
  outline: 2px solid var(--lp-green);
  outline-offset: 3px;
}

.footer-address {
  font-style: normal;
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.footer-address p {
  margin: 0;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.07);
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
  color: rgba(255, 255, 255, 0.35);
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .footer-inner {
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }

  .services-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .steps-list {
    grid-template-columns: repeat(2, 1fr);
    gap: 40px 48px;
  }

  .steps-list::before {
    display: none;
  }

  .step-item {
    align-items: flex-start;
    text-align: left;
    flex-direction: row;
    gap: 20px;
    padding: 0;
  }

  .step-number {
    margin-bottom: 0;
    flex-shrink: 0;
    box-shadow: 0 0 0 6px var(--lp-surface), 0 0 0 7px var(--lp-border);
  }

  .metrics-inner {
    grid-template-columns: repeat(2, 1fr);
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
    border-bottom: 1px solid var(--lp-border);
    flex-direction: column;
    align-items: flex-start;
    padding: 16px 20px 20px;
    gap: 4px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.07);
    z-index: var(--z-dropdown);
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

  .hero {
    min-height: auto;
    padding: 80px 20px 72px;
  }

  .steps-list {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .footer-inner {
    grid-template-columns: 1fr;
    gap: 36px;
  }

  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}

@media (max-width: 600px) {
  .services-grid {
    grid-template-columns: 1fr;
  }

  .metrics-inner {
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
  }

  .section {
    padding: 64px 20px;
  }

  .cta-section {
    padding: 80px 20px;
  }
}

/* ── Reduced motion ── */
@media (prefers-reduced-motion: reduce) {
  .btn-apply,
  .btn-login,
  .btn-track,
  .btn-track-outline,
  .event-card,
  .step-card,
  .event-arrow,
  .footer-link,
  .nav-link {
    transition: none;
  }

  .event-card:hover {
    transform: none;
  }

  .btn-apply:hover {
    transform: none;
  }

  @keyframes pulse {
    from, to { opacity: 1; transform: none; }
  }
}
</style>
