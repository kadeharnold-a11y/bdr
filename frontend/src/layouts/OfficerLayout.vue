<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { clearStaffSession, getStaffSession } from '../lib/staffAuth'
import { staffApi } from '../lib/staffApi'

const router = useRouter()
const session = getStaffSession()

const officer = computed(() => {
  const name = session?.fullName || 'Officer'
  return {
    name,
    role: session?.role?.replace(/_/g, ' ') || 'Staff',
    initials: name.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase(),
  }
})

const sidebarOpen = ref(false)

const icons = {
  queue: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 4h18v2H3zm0 7h18v2H3zm0 7h18v2H3z"/></svg>',
  myApps: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M6 2h9l5 5v15H6zm8 1v4h4M8 12h8v2H8zm0 4h8v2H8z"/></svg>',
  reports: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 3h18v18H3zm2 2v14h14V5zm2 10h2v2H7zm0-4h4v2H7zm0-4h6v2H7zm8 8h2v2h-2zm0-4h2v2h-2z"/></svg>',
  settings: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm7-3a1 1 0 0 1-1 1h-.5a5.5 5.5 0 0 1-.4 1l.3.3a1 1 0 0 1 0 1.4l-1.4 1.4a1 1 0 0 1-1.4 0l-.3-.3a5.5 5.5 0 0 1-1 .4V18a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-.5a5.5 5.5 0 0 1-1-.4l-.3.3a1 1 0 0 1-1.4 0L5.3 16a1 1 0 0 1 0-1.4l.3-.3a5.5 5.5 0 0 1-.4-1H4.8a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1h.4a5.5 5.5 0 0 1 .5-1l-.3-.3a1 1 0 0 1 0-1.4L6.8 6.2a1 1 0 0 1 1.4 0l.3.3a5.5 5.5 0 0 1 1-.4V5.8a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v.4a5.5 5.5 0 0 1 1 .5l.3-.3a1 1 0 0 1 1.4 0l1.4 1.4a1 1 0 0 1 0 1.4l-.3.3a5.5 5.5 0 0 1 .4 1H18a1 1 0 0 1 1 1z"/></svg>',
  logout: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M16 17v-2h-6v-6h6V7l5 5zM4 4h8v2H4v12h8v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>',
  bell: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5-6.7V4a2 2 0 1 0-4 0v.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/></svg>',
}

const nav = [
  { label: 'Application Queue', to: '/officer/queue', icon: 'queue' },
]

async function signOut() {
  try {
    await staffApi.post('/staff/logout')
  } catch {
    // Clear local session even if API call fails
  }
  clearStaffSession()
  router.push({ name: 'officer-login' })
}
</script>

<template>
  <div class="officer-shell">
    <!-- Top Bar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="icon-btn hamburger" aria-label="Toggle menu" @click="sidebarOpen = !sidebarOpen">
          <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
        </button>
        <RouterLink to="/officer/queue" class="brand">
          <img src="/coat-of-arms.png" alt="Republic of Ghana" class="brand-logo" />
          <div class="brand-text">
            <span class="brand-title">Births and Deaths Registry</span>
            <span class="brand-subtitle">BACK-OFFICE PORTAL</span>
          </div>
        </RouterLink>
      </div>

      <div class="topbar-right">
        <div class="officer-role-badge">{{ officer.role }}</div>
        <button class="icon-btn" aria-label="Notifications">
          <span v-html="icons.bell"></span>
        </button>
        <div class="avatar-wrap">
          <span class="avatar">{{ officer.initials }}</span>
          <span class="officer-name">{{ officer.name }}</span>
        </div>
      </div>
    </header>

    <!-- Sidebar backdrop -->
    <div v-if="sidebarOpen" class="backdrop" @click="sidebarOpen = false"></div>
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in nav"
          :key="item.to + item.label"
          :to="item.to"
          class="nav-link"
          active-class="active"
          @click="sidebarOpen = false"
        >
          <span class="nav-icon" v-html="icons[item.icon]"></span>
          {{ item.label }}
        </RouterLink>
        <div class="nav-divider"></div>
        <button type="button" class="nav-link logout-link" @click="signOut">
          <span class="nav-icon" v-html="icons.logout"></span>
          Sign Out
        </button>
        <RouterLink to="/dashboard" class="nav-link logout-link">
          <span class="nav-icon" v-html="icons.logout"></span>
          Back to Citizen Portal
        </RouterLink>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.officer-shell {
  min-height: 100vh;
  background: #f0f2f5;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  background: #1a2537;
  border-bottom: 1px solid #2d3a52;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: var(--radius);
  color: rgba(255,255,255,0.7);
  cursor: pointer;
}

.icon-btn:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}

.brand-logo {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid rgba(255,255,255,0.2);
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.brand-title {
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 15px;
  color: #fff;
}

.brand-subtitle {
  font-size: 10px;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.5);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.officer-role-badge {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--brand-gold);
  background: rgba(252, 209, 22, 0.15);
  padding: 4px 10px;
  border-radius: 9999px;
  border: 1px solid rgba(252, 209, 22, 0.3);
}

.avatar-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--brand-green);
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.officer-name {
  font-size: 14px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
}

@media (max-width: 700px) {
  .officer-name, .officer-role-badge { display: none; }
}

.backdrop {
  position: fixed;
  inset: 0;
  top: 64px;
  background: rgba(0,0,0,0.5);
  z-index: 25;
}

.sidebar {
  position: fixed;
  top: 64px;
  left: 0;
  bottom: 0;
  width: 260px;
  background: #fff;
  border-right: 1px solid var(--border);
  transform: translateX(-100%);
  transition: transform 220ms ease;
  z-index: 26;
  overflow-y: auto;
}

.sidebar.open {
  transform: translateX(0);
}

.sidebar-nav {
  padding: 16px 12px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 12px;
  border-radius: var(--radius);
  color: var(--text-primary);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
  transition: background 100ms;
}

.nav-link:hover {
  background: var(--background);
}

.nav-link.active {
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
  font-weight: 600;
}

.nav-icon {
  display: inline-flex;
  color: currentColor;
  opacity: 0.7;
}

.nav-divider {
  border-top: 1px solid var(--border);
  margin: 12px 0;
}

.logout-link {
  color: var(--text-muted);
}

.content {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}
</style>
