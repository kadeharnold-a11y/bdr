<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import OfficerLayout from '../../layouts/OfficerLayout.vue'

const router = useRouter()

// Queue filter state
const queueScope = ref('mine') // 'mine' | 'all'
const filterEventType = ref('')
const filterSlaUrgency = ref('')

// Mock application data based on PRD status stages
const allApplications = [
  {
    id: 'BDR-2026-EB-004821', name: 'Kwame Asante', event: 'EB', eventLabel: 'Early Birth',
    tier: 'Express', stage: 'Document Verification', submittedAt: '2026-07-11',
    slaTotal: 3, slaUsed: 2.4, officer: 'AM', officerName: 'Akua Mensah',
  },
  {
    id: 'BDR-2026-DR-001047', name: 'Abena Osei', event: 'DR', eventLabel: 'Death Reg.',
    tier: 'Express', stage: 'NIA / Source Check', submittedAt: '2026-07-12',
    slaTotal: 3, slaUsed: 0.5, officer: 'KD', officerName: 'Kojo Darko',
  },
  {
    id: 'BDR-2026-LB-000312', name: 'Ama Serwaa', event: 'LB', eventLabel: 'Late Birth',
    tier: 'Express', stage: 'Awaiting Approval', submittedAt: '2026-07-10',
    slaTotal: 3, slaUsed: 2.9, officer: 'AM', officerName: 'Akua Mensah',
  },
  {
    id: 'BDR-2026-EB-004720', name: 'Kofi Boateng', event: 'EB', eventLabel: 'Early Birth',
    tier: 'Standard', stage: 'Initial Review', submittedAt: '2026-07-08',
    slaTotal: 15, slaUsed: 4, officer: 'AM', officerName: 'Akua Mensah',
  },
  {
    id: 'BDR-2026-AD-000034', name: 'Efua Nyarko', event: 'AD', eventLabel: 'Adoption',
    tier: 'Standard', stage: 'Document Verification', submittedAt: '2026-07-07',
    slaTotal: 15, slaUsed: 8, officer: 'KD', officerName: 'Kojo Darko',
  },
  {
    id: 'BDR-2026-DR-001002', name: 'Yaw Amponsah', event: 'DR', eventLabel: 'Death Reg.',
    tier: 'Standard', stage: 'Corrections Required', submittedAt: '2026-07-05',
    slaTotal: 15, slaUsed: 13.5, officer: 'AM', officerName: 'Akua Mensah',
  },
  {
    id: 'BDR-2026-FD-000089', name: 'Akosua Frimpong', event: 'FD', eventLabel: 'Foetal Death',
    tier: 'Standard', stage: 'NIA / Source Check', submittedAt: '2026-07-09',
    slaTotal: 15, slaUsed: 6, officer: 'PA', officerName: 'Paa Adjei',
  },
  {
    id: 'BDR-2026-SR-000011', name: 'Nana Boateng', event: 'SR', eventLabel: 'Surrogacy',
    tier: 'Standard', stage: 'Initial Review', submittedAt: '2026-07-12',
    slaTotal: 15, slaUsed: 1, officer: 'KD', officerName: 'Kojo Darko',
  },
]

function slaPercent(app) {
  return Math.min(100, Math.round((app.slaUsed / app.slaTotal) * 100))
}

function slaRemainDays(app) {
  return Math.max(0, (app.slaTotal - app.slaUsed)).toFixed(1)
}

function slaUrgency(app) {
  const pct = slaPercent(app)
  if (pct >= 80) return 'critical'  // <20% remaining
  if (pct >= 50) return 'warning'   // 20-50% remaining
  return 'safe'                      // >50% remaining
}

const eventTypeOptions = [
  { value: '', label: 'All Event Types' },
  { value: 'EB', label: 'Early Birth' },
  { value: 'LB', label: 'Late Birth' },
  { value: 'DR', label: 'Death Registration' },
  { value: 'FD', label: 'Foetal Death' },
  { value: 'AD', label: 'Adoption' },
  { value: 'SR', label: 'Surrogacy' },
]

const slaUrgencyOptions = [
  { value: '', label: 'All Urgency' },
  { value: 'critical', label: '🔴 SLA Critical' },
  { value: 'warning', label: '🟡 SLA Warning' },
  { value: 'safe', label: '🟢 On Track' },
]

const filteredApplications = computed(() => {
  return allApplications.filter(app => {
    const myFilter = queueScope.value === 'mine' ? app.officer === 'AM' : true
    const eventFilter = filterEventType.value ? app.event === filterEventType.value : true
    const urgencyFilter = filterSlaUrgency.value ? slaUrgency(app) === filterSlaUrgency.value : true
    return myFilter && eventFilter && urgencyFilter
  })
})

const expressQueue = computed(() => filteredApplications.value.filter(a => a.tier === 'Express'))
const standardQueue = computed(() => filteredApplications.value.filter(a => a.tier === 'Standard'))

function openApplication(id) {
  router.push(`/officer/application/${id}`)
}
</script>

<template>
  <OfficerLayout>
    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Application Queue</h1>
        <p class="page-subtitle">
          {{ filteredApplications.length }} application{{ filteredApplications.length !== 1 ? 's' : '' }}
          — {{ expressQueue.length }} Express · {{ standardQueue.length }} Standard
        </p>
      </div>

      <!-- My Queue / All Queue Toggle -->
      <div class="scope-toggle">
        <button :class="['toggle-btn', { active: queueScope === 'mine' }]" @click="queueScope = 'mine'">My Queue</button>
        <button :class="['toggle-btn', { active: queueScope === 'all' }]" @click="queueScope = 'all'">All Queue</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <select v-model="filterEventType" class="filter-select">
        <option v-for="o in eventTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <select v-model="filterSlaUrgency" class="filter-select">
        <option v-for="o in slaUrgencyOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
    </div>

    <!-- Dual Lane Grid -->
    <div class="dual-lane">

      <!-- EXPRESS QUEUE -->
      <section class="lane lane-express">
        <div class="lane-header">
          <div class="lane-title-wrap">
            <span class="lane-dot express-dot"></span>
            <h2>Express Queue</h2>
          </div>
          <span class="lane-count express-count">{{ expressQueue.length }}</span>
        </div>

        <div v-if="expressQueue.length === 0" class="empty-state">
          No Express applications in queue.
        </div>

        <div
          v-for="app in expressQueue"
          :key="app.id"
          class="queue-card express-card"
          @click="openApplication(app.id)"
        >
          <div class="card-top">
            <span class="tracking-id">{{ app.id }}</span>
            <span :class="['sla-badge', `sla-${slaUrgency(app)}`]">
              {{ slaRemainDays(app) }}d remaining
            </span>
          </div>

          <div class="card-mid">
            <strong>{{ app.name }}</strong>
            <div class="card-meta">
              <span class="event-chip">{{ app.eventLabel }}</span>
              <span class="stage-text">{{ app.stage }}</span>
            </div>
          </div>

          <div class="card-bottom">
            <div class="sla-bar-wrap">
              <div
                class="sla-bar"
                :class="`sla-bar-${slaUrgency(app)}`"
                :style="{ width: slaPercent(app) + '%' }"
              ></div>
            </div>
            <div class="card-footer-meta">
              <span class="submitted-date">Submitted {{ app.submittedAt }}</span>
              <span class="officer-chip">{{ app.officer }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- STANDARD QUEUE -->
      <section class="lane lane-standard">
        <div class="lane-header">
          <div class="lane-title-wrap">
            <span class="lane-dot standard-dot"></span>
            <h2>Standard Queue</h2>
          </div>
          <span class="lane-count standard-count">{{ standardQueue.length }}</span>
        </div>

        <div v-if="standardQueue.length === 0" class="empty-state">
          No Standard applications in queue.
        </div>

        <div
          v-for="app in standardQueue"
          :key="app.id"
          class="queue-card standard-card"
          @click="openApplication(app.id)"
        >
          <div class="card-top">
            <span class="tracking-id">{{ app.id }}</span>
            <span :class="['sla-badge', `sla-${slaUrgency(app)}`]">
              {{ slaRemainDays(app) }}d remaining
            </span>
          </div>

          <div class="card-mid">
            <strong>{{ app.name }}</strong>
            <div class="card-meta">
              <span class="event-chip">{{ app.eventLabel }}</span>
              <span class="stage-text">{{ app.stage }}</span>
            </div>
          </div>

          <div class="card-bottom">
            <div class="sla-bar-wrap">
              <div
                class="sla-bar"
                :class="`sla-bar-${slaUrgency(app)}`"
                :style="{ width: slaPercent(app) + '%' }"
              ></div>
            </div>
            <div class="card-footer-meta">
              <span class="submitted-date">Submitted {{ app.submittedAt }}</span>
              <span class="officer-chip">{{ app.officer }}</span>
            </div>
          </div>
        </div>
      </section>

    </div>
  </OfficerLayout>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px;
  color: var(--text-primary);
}

.page-subtitle {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
}

.scope-toggle {
  display: flex;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.toggle-btn {
  border: none;
  background: transparent;
  padding: 8px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  color: var(--text-muted);
  transition: all 120ms;
}

.toggle-btn.active {
  background: #1a2537;
  color: #fff;
}

.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  font-size: 14px;
  outline: none;
  cursor: pointer;
}

/* Dual Lane */
.dual-lane {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: start;
}

@media (max-width: 900px) {
  .dual-lane {
    grid-template-columns: 1fr;
  }
}

.lane {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.lane-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}

.lane-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.lane-title-wrap h2 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}

.lane-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.express-dot { background: var(--brand-gold); }
.standard-dot { background: var(--text-muted); }

.lane-count {
  font-size: 13px;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 9999px;
}

.express-count {
  background: rgba(252, 209, 22, 0.2);
  color: #a67c00;
}

.standard-count {
  background: var(--background);
  color: var(--text-muted);
}

.lane-express .lane-header {
  background: #fffdf0;
}

.empty-state {
  padding: 40px;
  text-align: center;
  font-size: 14px;
  color: var(--text-muted);
}

/* Queue Cards */
.queue-card {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background 120ms;
}

.queue-card:last-child {
  border-bottom: none;
}

.express-card:hover {
  background: #fffdf0;
}

.standard-card:hover {
  background: var(--background);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.tracking-id {
  font-family: monospace;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 0.03em;
}

.sla-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 9999px;
}

.sla-safe {
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
}

.sla-warning {
  background: rgba(252, 209, 22, 0.2);
  color: #a67c00;
}

.sla-critical {
  background: rgba(206, 17, 38, 0.1);
  color: var(--brand-red);
}

.card-mid {
  margin-bottom: 12px;
}

.card-mid strong {
  font-size: 15px;
  color: var(--text-primary);
  display: block;
  margin-bottom: 6px;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.event-chip {
  font-size: 11px;
  font-weight: 700;
  background: rgba(26, 37, 55, 0.08);
  color: #1a2537;
  padding: 2px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stage-text {
  font-size: 13px;
  color: var(--text-muted);
}

.card-bottom {
  margin-top: 4px;
}

.sla-bar-wrap {
  height: 4px;
  background: var(--border);
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 8px;
}

.sla-bar {
  height: 100%;
  border-radius: 9999px;
  transition: width 400ms ease;
}

.sla-bar-safe { background: var(--brand-green); }
.sla-bar-warning { background: var(--brand-gold); }
.sla-bar-critical { background: var(--brand-red); }

.card-footer-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.submitted-date {
  font-size: 12px;
  color: var(--text-muted);
}

.officer-chip {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #1a2537;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>
