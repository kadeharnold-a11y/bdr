<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const router = useRouter()

const notifications = ref([
  {
    id: 1,
    type: 'stage',
    read: false,
    title: 'Application Under Review',
    body: 'Your application BDR-2026-EB-004821 (Early Birth) has been assigned to a Registration Officer and is now under review.',
    time: '2 hours ago',
    date: '2026-07-14',
    link: { name: 'track', query: { id: 'BDR-2026-EB-004821' } },
    icon: '🔍',
  },
  {
    id: 2,
    type: 'correction',
    read: false,
    title: 'Corrections Required',
    body: "Your application BDR-2026-DR-001002 (Death Registration) requires corrections. The officer has flagged Mother's Ghana Card Number and one uploaded document. Please login and resubmit.",
    time: '1 day ago',
    date: '2026-07-13',
    link: { name: 'track', query: { id: 'BDR-2026-DR-001002' } },
    icon: '✏️',
  },
  {
    id: 3,
    type: 'sla',
    read: false,
    title: 'SLA Warning — Action Needed',
    body: 'Your Express application BDR-2026-EB-004821 has less than 20% of its SLA time remaining. The BDR team has been notified and is prioritising your application.',
    time: '3 hours ago',
    date: '2026-07-14',
    link: { name: 'track', query: { id: 'BDR-2026-EB-004821' } },
    icon: '⚠️',
  },
  {
    id: 4,
    type: 'payment',
    read: true,
    title: 'Payment Confirmed',
    body: 'Your payment of GHS 850.00 for application BDR-2026-DR-001002 (Standard Death Registration) has been confirmed. Your application has entered the BDR processing queue. Tracking ID: BDR-2026-DR-001002.',
    time: '9 days ago',
    date: '2026-07-05',
    link: { name: 'track', query: { id: 'BDR-2026-DR-001002' } },
    icon: '💳',
  },
  {
    id: 5,
    type: 'certificate',
    read: true,
    title: 'Certificate Ready for Download',
    body: 'Your Birth Certificate (CERT-2025-EB-002341) for Abena Asante has been issued and is ready for download from your certificates page.',
    time: '10 months ago',
    date: '2025-09-04',
    link: '/certificates',
    icon: '🏛️',
  },
  {
    id: 6,
    type: 'stage',
    read: true,
    title: 'Application Approved',
    body: 'Your application BDR-2025-EB-002341 has been approved. Certificate generation is in progress.',
    time: '10 months ago',
    date: '2025-09-04',
    link: { name: 'track', query: { id: 'BDR-2025-EB-002341' } },
    icon: '✅',
  },
  {
    id: 7,
    type: 'system',
    read: true,
    title: 'Welcome to HBDRP',
    body: 'Your account has been successfully created. You can now submit vital registration applications online, track progress, and download certificates from your dashboard.',
    time: '1 year ago',
    date: '2025-07-01',
    link: null,
    icon: '👋',
  },
])

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

function markAllRead() {
  notifications.value.forEach(n => { n.read = true })
}

function markRead(id) {
  const n = notifications.value.find(n => n.id === id)
  if (n) n.read = true
}

function handleNotificationClick(n) {
  markRead(n.id)
  if (n.link) {
    if (typeof n.link === 'string') {
      router.push(n.link)
    } else {
      router.push(n.link)
    }
  }
}

const typeColors = {
  stage: 'type-stage',
  correction: 'type-correction',
  sla: 'type-sla',
  payment: 'type-payment',
  certificate: 'type-certificate',
  system: 'type-system',
}
</script>

<template>
  <CitizenLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">
          Notifications
          <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
        </h1>
        <p class="page-subtitle">Status updates, alerts, and messages from the Births and Deaths Registry.</p>
      </div>
      <button
        v-if="unreadCount > 0"
        class="btn-ghost"
        @click="markAllRead"
      >
        Mark all as read
      </button>
    </div>

    <!-- All Read State -->
    <div v-if="notifications.length === 0" class="empty-state">
      <div class="empty-icon">🔔</div>
      <p>No notifications yet.</p>
    </div>

    <!-- Notification List -->
    <div v-else class="notif-list">
      <div
        v-for="notif in notifications"
        :key="notif.id"
        :class="['notif-card', { unread: !notif.read }, notif.link ? 'clickable' : '']"
        @click="notif.link ? handleNotificationClick(notif) : null"
      >
        <!-- Unread indicator -->
        <div class="unread-dot-wrap">
          <div v-if="!notif.read" class="unread-dot"></div>
        </div>

        <!-- Icon -->
        <div :class="['notif-icon-wrap', typeColors[notif.type]]">
          <span class="notif-icon">{{ notif.icon }}</span>
        </div>

        <!-- Content -->
        <div class="notif-content">
          <div class="notif-header">
            <h3 class="notif-title">{{ notif.title }}</h3>
            <span class="notif-time">{{ notif.time }}</span>
          </div>
          <p class="notif-body">{{ notif.body }}</p>
          <div v-if="notif.link" class="notif-cta">
            <span class="view-link">View →</span>
          </div>
        </div>

        <!-- Mark read button (unread only) -->
        <button
          v-if="!notif.read"
          class="mark-read-btn"
          title="Mark as read"
          @click.stop="markRead(notif.id)"
        >
          <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
        </button>
      </div>
    </div>
  </CitizenLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 6px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.unread-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  background: var(--brand-red);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  border-radius: 9999px;
  padding: 0 6px;
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin: 0;
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  color: var(--text-muted);
  transition: border-color 150ms;
  white-space: nowrap;
}

.btn-ghost:hover {
  border-color: var(--brand-green);
  color: var(--brand-green);
}

/* Notification List */
.notif-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.notif-card {
  display: flex;
  align-items: flex-start;
  gap: 0;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  position: relative;
  transition: background 120ms;
}

.notif-card:last-child { border-bottom: none; }

.notif-card.unread {
  background: rgba(0, 107, 63, 0.03);
}

.notif-card.clickable { cursor: pointer; }
.notif-card.clickable:hover { background: var(--background); }

/* Unread dot */
.unread-dot-wrap {
  width: 20px;
  padding-top: 4px;
  flex-shrink: 0;
}

.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--brand-green);
}

/* Icon */
.notif-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-right: 16px;
}

.type-stage { background: rgba(0, 107, 63, 0.1); }
.type-correction { background: rgba(206, 17, 38, 0.08); }
.type-sla { background: rgba(252, 209, 22, 0.15); }
.type-payment { background: rgba(99, 102, 241, 0.1); }
.type-certificate { background: rgba(0, 107, 63, 0.12); }
.type-system { background: var(--background); border: 1px solid var(--border); }

.notif-icon { font-size: 20px; line-height: 1; }

/* Content */
.notif-content { flex: 1; min-width: 0; }

.notif-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.notif-title {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.notif-time {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
  flex-shrink: 0;
}

.notif-body {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0 0 8px;
  line-height: 1.6;
}

.notif-cta {
  margin-top: 4px;
}

.view-link {
  font-size: 13px;
  font-weight: 700;
  color: var(--brand-green);
}

/* Mark read button */
.mark-read-btn {
  flex-shrink: 0;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-muted);
  margin-left: 12px;
  transition: border-color 150ms, color 150ms;
}

.mark-read-btn:hover {
  border-color: var(--brand-green);
  color: var(--brand-green);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 64px 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  color: var(--text-muted);
}

.empty-icon { font-size: 48px; margin-bottom: 16px; }
.empty-state p { font-size: 16px; margin: 0; }
</style>
