<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { clearSession, getSession } from '../lib/auth'

const router = useRouter()

const session = getSession()
const fullName = session?.fullName || 'Citizen'
const user = {
  name: fullName,
  initials: fullName
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase(),
}

function signOut() {
  clearSession()
  router.push({ name: 'login' })
}

const sidebarOpen = ref(false)

// Inline icon set for the sidebar nav.
const icons = {
  grid: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 0h8v8h-8z"/></svg>',
  clock: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 10.4 4 2.3-1 1.7-5-3V7h2Z"/></svg>',
  plus: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z"/></svg>',
  doc: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M6 2h9l5 5v15H6zm8 1v4h4M8 12h8v2H8zm0 4h8v2H8z"/></svg>',
  certificate: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M4 4h16v12H4zm0 14h16v2H4zM8 8h8v2H8z"/></svg>',
  bell: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5-6.7V4a2 2 0 1 0-4 0v.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/></svg>',
  user: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4 0-8 2-8 6v2h16v-2c0-4-4-6-8-6Z"/></svg>',
  logout: '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M16 17v-2h-6v-6h6V7l5 5zM4 4h8v2H4v12h8v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>',
}

const nav = [
  {
    group: 'Main',
    items: [
      { label: 'Dashboard', to: '/dashboard', icon: 'grid' },
      { label: 'Track Application', to: '/track', icon: 'clock' },
    ],
  },
  {
    group: 'Applications',
    items: [
      { label: 'New Application', to: '/new-application', icon: 'plus' },
      { label: 'My Applications', to: '/applications', icon: 'doc' },
      { label: 'Certificates', to: '/certificates', icon: 'certificate' },
    ],
  },
  {
    group: 'Account',
    items: [
      { label: 'Notifications', to: '/notifications', icon: 'bell' },
      { label: 'My Profile', to: '/profile', icon: 'user' },
      { label: 'Sign Out', to: '/login', icon: 'logout' },
    ],
  },
]
</script>

<template>
  <div class="shell">
    <header class="topbar">
      <div class="topbar-left">
        <button class="icon-btn hamburger" aria-label="Toggle menu" @click="sidebarOpen = !sidebarOpen">
          <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
        </button>
        <RouterLink to="/dashboard" class="brand">
          <img src="/coat-of-arms.png" alt="Republic of Ghana" class="brand-logo" />
          <div class="brand-text">
            <span class="brand-title">Births and Deaths Registry</span>
            <span class="brand-subtitle">REPUBLIC OF GHANA</span>
          </div>
        </RouterLink>
      </div>

      <div class="topbar-right">
        <RouterLink to="/new-application" class="btn-new">
          <span class="plus">+</span> New Application
        </RouterLink>
        <RouterLink to="/notifications" class="icon-btn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5-6.7V4a2 2 0 1 0-4 0v.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/></svg>
        </RouterLink>
        <button class="avatar-btn" aria-label="Account menu">
          <span class="avatar">{{ user.initials }}</span>
          <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="m7 10 5 5 5-5z"/></svg>
        </button>
      </div>
    </header>

    <!-- Off-canvas sidebar -->
    <div v-if="sidebarOpen" class="backdrop" @click="sidebarOpen = false"></div>
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <nav class="sidebar-nav">
        <div v-for="section in nav" :key="section.group" class="nav-section">
          <p class="nav-group">{{ section.group }}</p>
          <RouterLink
            v-for="item in section.items"
            :key="item.to"
            :to="item.to"
            class="nav-link"
            active-class="active"
            @click="item.label === 'Sign Out' ? signOut() : (sidebarOpen = false)"
          >
            <span class="nav-icon" v-html="icons[item.icon]"></span>
            {{ item.label }}
          </RouterLink>
        </div>
      </nav>
    </aside>

    <main class="content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.shell {
  min-height: 100vh;
  background: var(--background);
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  border-radius: var(--radius);
  color: var(--text-muted);
  cursor: pointer;
}

.icon-btn:hover {
  background: var(--background);
  color: var(--brand-green);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}

.brand-logo {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.brand-title {
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 16px;
  color: var(--brand-green);
}

.brand-subtitle {
  font-size: 11px;
  letter-spacing: 0.08em;
  color: var(--text-muted);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-new {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--brand-green);
  color: #fff;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  padding: 10px 18px;
  border-radius: var(--radius);
  transition: background 150ms ease;
}

.btn-new:hover {
  background: var(--brand-green-dark);
}

.btn-new .plus {
  font-size: 18px;
  line-height: 1;
}

.avatar-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--text-muted);
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--brand-green);
  color: #fff;
  font-weight: 700;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.backdrop {
  position: fixed;
  inset: 0;
  top: 72px;
  background: rgba(0, 0, 0, 0.4);
  z-index: 25;
}

.sidebar {
  position: fixed;
  top: 72px;
  left: 0;
  bottom: 0;
  width: 280px;
  background: var(--surface);
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
  padding: 20px 16px;
}

.nav-section {
  margin-bottom: 20px;
}

.nav-group {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin: 0 0 8px 12px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--radius);
  color: var(--text-primary);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
}

.nav-link:hover {
  background: var(--background);
}

.nav-link.active {
  background: var(--brand-green);
  color: #fff;
}

.nav-icon {
  display: inline-flex;
  color: currentColor;
}

.content {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
