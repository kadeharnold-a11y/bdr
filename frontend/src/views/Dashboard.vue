<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const router = useRouter()

// TODO: replace with the authenticated citizen + real figures from the API.
const user = { name: 'Enam Kadeh' }

const trackingId = ref('')

const stats = [
  { key: 'active', value: 0, label: 'Active applications in progress', icon: 'doc', tone: 'green' },
  { key: 'drafts', value: 0, label: 'Saved drafts to finish later', icon: 'edit', tone: 'gold' },
  { key: 'completed', value: 0, label: 'Completed applications & certificates', icon: 'check', tone: 'mint' },
  { key: 'alerts', value: 0, label: 'Unread alerts & messages', icon: 'bell', tone: 'pink' },
]

const statIcons = {
  doc: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M6 2h9l5 5v15H6zm8 1v4h4M8 12h8v2H8zm0 4h8v2H8z"/></svg>',
  edit: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M4 20h4l10-10-4-4L4 16zm14-13 2-2a1.4 1.4 0 0 0 0-2l-2-2a1.4 1.4 0 0 0-2 0l-2 2z"/></svg>',
  check: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
  bell: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5-6.7V4a2 2 0 1 0-4 0v.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/></svg>',
}

function trackStatus() {
  const id = trackingId.value.trim()
  if (!id) return
  router.push({ name: 'track', query: { id } })
}
</script>

<template>
  <CitizenLayout>
    <!-- Welcome hero -->
    <section class="hero">
      <div class="hero-body">
        <div class="hero-avatar">
          <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M6 2h9l5 5v15H6zm8 1v4h4M8 12h8v2H8zm0 4h8v2H8z"/></svg>
        </div>
        <div>
          <h1 class="hero-title">Welcome back, {{ user.name }}</h1>
          <p class="hero-subtitle">Verified Citizen — Births &amp; Deaths Registry, Ghana</p>
        </div>
      </div>

      <form class="track" @submit.prevent="trackStatus">
        <span class="track-search-icon">
          <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="m21 20-5.6-5.6a7 7 0 1 0-1.4 1.4L20 21zM5 10a5 5 0 1 1 5 5 5 5 0 0 1-5-5Z"/></svg>
        </span>
        <input
          v-model="trackingId"
          class="track-input"
          type="text"
          placeholder="Track application (e.g. BDR-2026-EB-004821)"
        />
        <button type="submit" class="track-btn">
          <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 10.4 4 2.3-1 1.7-5-3V7h2Z"/></svg>
          Track Status
        </button>
      </form>

      <div class="hero-illustration" aria-hidden="true">
        <svg viewBox="0 0 200 200" width="220" height="220">
          <circle cx="100" cy="100" r="92" fill="#c7d2fe" />
          <circle cx="100" cy="100" r="70" fill="#a5b4fc" />
          <circle cx="100" cy="100" r="48" fill="#818cf8" />
          <circle cx="100" cy="100" r="26" fill="#6366f1" />
          <circle cx="100" cy="100" r="9" fill="#fde68a" />
          <line x1="118" y1="82" x2="168" y2="40" stroke="#ef4444" stroke-width="6" stroke-linecap="round" />
          <path d="M168 40 l-4 12 l12 -4 z" fill="#16a34a" />
        </svg>
      </div>
    </section>

    <!-- Stat cards -->
    <section class="stats">
      <article v-for="stat in stats" :key="stat.key" class="stat-card">
        <span class="stat-icon" :class="`tone-${stat.tone}`" v-html="statIcons[stat.icon]"></span>
        <div>
          <p class="stat-value">{{ stat.value }}</p>
          <p class="stat-label">{{ stat.label }}</p>
        </div>
      </article>
    </section>
  </CitizenLayout>
</template>

<style scoped>
.hero {
  position: relative;
  overflow: hidden;
  background: var(--brand-green);
  color: #fff;
  border-radius: var(--radius-lg);
  padding: 40px;
  margin-bottom: 24px;
}

.hero-body {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
}

.hero-avatar {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
}

.hero-subtitle {
  margin: 4px 0 0;
  color: rgba(255, 255, 255, 0.85);
  font-size: 15px;
}

.track {
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: 720px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: var(--radius);
  padding: 8px;
}

.track-search-icon {
  display: inline-flex;
  padding-left: 8px;
  color: rgba(255, 255, 255, 0.8);
}

.track-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  color: #fff;
  font-size: 15px;
  padding: 10px 8px;
}

.track-input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.track-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--brand-gold);
  color: #4a3800;
  font-weight: 700;
  font-size: 14px;
  border: none;
  border-radius: var(--radius);
  padding: 12px 20px;
  cursor: pointer;
  white-space: nowrap;
}

.hero-illustration {
  position: absolute;
  right: 40px;
  top: 50%;
  transform: translateY(-50%);
}

@media (max-width: 900px) {
  .hero-illustration {
    display: none;
  }
}

.stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}

@media (max-width: 700px) {
  .stats {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
}

.stat-icon {
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  border-radius: var(--radius);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.tone-green { background: rgba(0, 107, 63, 0.12); color: var(--brand-green); }
.tone-gold { background: rgba(252, 209, 22, 0.18); color: #a67c00; }
.tone-mint { background: rgba(16, 163, 74, 0.12); color: #16a34a; }
.tone-pink { background: rgba(236, 72, 153, 0.12); color: #db2777; }

.stat-value {
  font-family: var(--font-heading);
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.stat-label {
  margin: 2px 0 0;
  font-size: 14px;
  color: var(--text-muted);
}
</style>
