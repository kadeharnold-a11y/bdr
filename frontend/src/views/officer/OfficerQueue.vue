<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import OfficerLayout from '../../layouts/OfficerLayout.vue'
import { staffApi } from '../../lib/staffApi'
import { getStaffSession } from '../../lib/staffAuth'
import {
  EVENT_TYPE_CODES,
  EVENT_TYPE_LABELS,
  STATUS_LABELS,
  formatTier,
  slaUrgencyFromRemaining,
} from '../../lib/eventLabels'

const router = useRouter()
const staff = getStaffSession()

const queueScope = ref('mine')
const filterEventType = ref('')
const filterSlaUrgency = ref('')
const applications = ref([])
const loading = ref(true)
const loadError = ref('')

async function loadQueue() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await staffApi.get('/staff/queue', {
      params: { mine: queueScope.value === 'mine' ? 'true' : 'false' },
    })
    applications.value = data.map((app) => ({
      ...app,
      eventCode: EVENT_TYPE_CODES[app.eventType] || app.eventType,
      eventLabel: EVENT_TYPE_LABELS[app.eventType] || app.eventType,
      tierLabel: formatTier(app.tier),
      stage: STATUS_LABELS[app.status] || app.status,
      citizenName: app.trackingId, // queue API doesn't include name — show tracking ID
      slaUrgency: slaUrgencyFromRemaining(app.slaPercentRemaining),
    }))
  } catch (err) {
    if (err.response?.status === 401) {
      router.push({ name: 'officer-login' })
      return
    }
    loadError.value = err.response?.data?.error?.message || 'Could not load the queue.'
  } finally {
    loading.value = false
  }
}

onMounted(loadQueue)
watch(queueScope, loadQueue)

const eventTypeOptions = [
  { value: '', label: 'All Event Types' },
  ...Object.entries(EVENT_TYPE_CODES).map(([type, code]) => ({
    value: code,
    label: EVENT_TYPE_LABELS[type],
  })),
]

const slaUrgencyOptions = [
  { value: '', label: 'All Urgency' },
  { value: 'critical', label: '🔴 SLA Critical' },
  { value: 'warning', label: '🟡 SLA Warning' },
  { value: 'safe', label: '🟢 On Track' },
]

const filteredApplications = computed(() =>
  applications.value.filter((app) => {
    const eventFilter = filterEventType.value ? app.eventCode === filterEventType.value : true
    const urgencyFilter = filterSlaUrgency.value ? app.slaUrgency === filterSlaUrgency.value : true
    return eventFilter && urgencyFilter
  }),
)

const expressQueue = computed(() => filteredApplications.value.filter((a) => a.tier === 'express'))
const standardQueue = computed(() => filteredApplications.value.filter((a) => a.tier === 'standard'))

function openApplication(id) {
  router.push({ name: 'officer-application', params: { id } })
}

function slaPercent(app) {
  return app.slaPercentRemaining ?? 100
}
</script>

<template>
  <OfficerLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Application Queue</h1>
        <p class="page-subtitle">
          {{ filteredApplications.length }} application{{ filteredApplications.length !== 1 ? 's' : '' }}
          — {{ expressQueue.length }} Express · {{ standardQueue.length }} Standard
        </p>
      </div>
      <div class="scope-toggle">
        <button :class="['toggle-btn', { active: queueScope === 'mine' }]" @click="queueScope = 'mine'">My Queue</button>
        <button :class="['toggle-btn', { active: queueScope === 'all' }]" @click="queueScope = 'all'">All Queue</button>
      </div>
    </div>

    <div class="filters">
      <select v-model="filterEventType" class="filter-select">
        <option v-for="opt in eventTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <select v-model="filterSlaUrgency" class="filter-select">
        <option v-for="opt in slaUrgencyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </div>

    <p v-if="loading" class="status-msg">Loading queue…</p>
    <p v-else-if="loadError" class="status-msg error">{{ loadError }}</p>
    <p v-else-if="!filteredApplications.length" class="status-msg">No applications in this queue.</p>

    <template v-else>
      <section v-if="expressQueue.length" class="queue-section">
        <h2 class="queue-heading express-heading">⚡ Express Queue</h2>
        <div class="app-grid">
          <article v-for="app in expressQueue" :key="app.id" class="app-card" @click="openApplication(app.id)">
            <div class="app-card-top">
              <span class="tracking-id">{{ app.trackingId }}</span>
              <span :class="['sla-pill', `sla-${app.slaUrgency}`]">{{ slaPercent(app) }}% SLA left</span>
            </div>
            <p class="app-event">{{ app.eventLabel }} · {{ app.tierLabel }}</p>
            <p class="app-stage">{{ app.stage }}</p>
          </article>
        </div>
      </section>

      <section v-if="standardQueue.length" class="queue-section">
        <h2 class="queue-heading">Standard Queue</h2>
        <div class="app-grid">
          <article v-for="app in standardQueue" :key="app.id" class="app-card" @click="openApplication(app.id)">
            <div class="app-card-top">
              <span class="tracking-id">{{ app.trackingId }}</span>
              <span :class="['sla-pill', `sla-${app.slaUrgency}`]">{{ slaPercent(app) }}% SLA left</span>
            </div>
            <p class="app-event">{{ app.eventLabel }} · {{ app.tierLabel }}</p>
            <p class="app-stage">{{ app.stage }}</p>
          </article>
        </div>
      </section>
    </template>
  </OfficerLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; flex-wrap: wrap; }
.page-title { font-size: 28px; font-weight: 700; margin: 0 0 4px; }
.page-subtitle { color: var(--text-muted); margin: 0; font-size: 15px; }
.scope-toggle { display: flex; background: var(--background); border-radius: var(--radius); padding: 4px; }
.toggle-btn { border: none; background: transparent; padding: 8px 16px; border-radius: var(--radius); font-weight: 600; cursor: pointer; font-size: 14px; }
.toggle-btn.active { background: var(--surface); box-shadow: 0 1px 3px rgba(0,0,0,0.08); color: var(--brand-green); }
.filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.filter-select { padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); font-size: 14px; }
.status-msg { text-align: center; padding: 48px; color: var(--text-muted); }
.status-msg.error { color: var(--danger); }
.queue-section { margin-bottom: 32px; }
.queue-heading { font-size: 18px; margin: 0 0 16px; }
.express-heading { color: #a67c00; }
.app-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.app-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; cursor: pointer; transition: border-color 150ms; }
.app-card:hover { border-color: var(--brand-green); }
.app-card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.tracking-id { font-family: monospace; font-weight: 600; font-size: 14px; }
.sla-pill { font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 9999px; }
.sla-safe { background: rgba(0,107,63,0.1); color: var(--brand-green); }
.sla-warning { background: rgba(252,209,22,0.2); color: #a67c00; }
.sla-critical { background: rgba(239,68,68,0.1); color: var(--danger); }
.app-event { font-size: 14px; color: var(--text-muted); margin: 0 0 4px; }
.app-stage { font-size: 13px; font-weight: 600; margin: 0; color: var(--text-primary); }
</style>
