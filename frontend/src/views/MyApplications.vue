<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const router = useRouter()
const activeTab = ref('active')
const filterEvent = ref('')

const tabs = [
  { key: 'active', label: 'Active', count: 2 },
  { key: 'drafts', label: 'Drafts', count: 1 },
  { key: 'completed', label: 'Completed', count: 3 },
]

const eventTypeOptions = [
  { value: '', label: 'All Event Types' },
  { value: 'EB', label: 'Early Birth' },
  { value: 'LB', label: 'Late Birth' },
  { value: 'DR', label: 'Death Registration' },
  { value: 'FD', label: 'Foetal Death' },
  { value: 'AD', label: 'Adoption' },
  { value: 'SR', label: 'Surrogacy' },
]

const activeApps = [
  {
    id: 'BDR-2026-EB-004821',
    eventCode: 'EB',
    eventLabel: 'Early Birth Registration',
    tier: 'Express',
    stage: 'Document Verification',
    submittedAt: '2026-07-11',
    slaDeadline: '2026-07-14',
    slaTotal: 3,
    slaUsed: 2.4,
    subject: 'Kofi Asante (child)',
  },
  {
    id: 'BDR-2026-DR-001002',
    eventCode: 'DR',
    eventLabel: 'Death Registration',
    tier: 'Standard',
    stage: 'Corrections Required',
    submittedAt: '2026-07-05',
    slaDeadline: '2026-07-26',
    slaTotal: 15,
    slaUsed: 13.5,
    subject: 'Yaw Amponsah (deceased)',
  },
]

const draftApps = [
  {
    id: 'DRAFT-001',
    eventCode: 'LB',
    eventLabel: 'Late Birth Registration',
    tier: 'Standard',
    stepLabel: 'Step 3 of 6 — Parent Details',
    lastSaved: '2026-07-13',
    expiresAt: '2026-10-11',
    subject: 'Ama Boateng (child)',
  },
]

const completedApps = [
  {
    id: 'BDR-2025-EB-002341',
    eventCode: 'EB',
    eventLabel: 'Early Birth Registration',
    tier: 'Standard',
    completedAt: '2025-09-04',
    certSerial: 'CERT-2025-EB-002341',
    subject: 'Abena Asante (child)',
  },
  {
    id: 'BDR-2024-DR-000512',
    eventCode: 'DR',
    eventLabel: 'Death Registration',
    tier: 'Express',
    completedAt: '2024-11-17',
    certSerial: 'CERT-2024-DR-000512',
    subject: 'Kofi Mensah (deceased)',
  },
  {
    id: 'BDR-2024-EB-001088',
    eventCode: 'EB',
    eventLabel: 'Early Birth Registration',
    tier: 'Standard',
    completedAt: '2024-06-22',
    certSerial: 'CERT-2024-EB-001088',
    subject: 'Kwame Boateng (child)',
  },
]

function slaPercent(app) {
  return Math.min(100, Math.round((app.slaUsed / app.slaTotal) * 100))
}

function slaUrgency(app) {
  const pct = slaPercent(app)
  if (pct >= 80) return 'critical'
  if (pct >= 50) return 'warning'
  return 'safe'
}

function slaRemain(app) {
  return Math.max(0, (app.slaTotal - app.slaUsed)).toFixed(1)
}

const filteredActive = computed(() =>
  activeApps.filter(a => !filterEvent.value || a.eventCode === filterEvent.value)
)
const filteredDrafts = computed(() =>
  draftApps.filter(a => !filterEvent.value || a.eventCode === filterEvent.value)
)
const filteredCompleted = computed(() =>
  completedApps.filter(a => !filterEvent.value || a.eventCode === filterEvent.value)
)

const stageColors = {
  'Corrections Required': 'stage-warn',
  'Document Verification': 'stage-active',
  'NIA / Source Check': 'stage-active',
  'Awaiting Approval': 'stage-active',
  'Under Review': 'stage-active',
  'Approved': 'stage-ok',
}
</script>

<template>
  <CitizenLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">My Applications</h1>
        <p class="page-subtitle">Track and manage all your vital registration applications.</p>
      </div>
      <button class="btn-primary" @click="router.push('/new-application')">
        + New Application
      </button>
    </div>

    <!-- Tabs + Filter Row -->
    <div class="toolbar">
      <div class="tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="['tab-btn', { active: activeTab === tab.key }]"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span class="tab-count">{{ tab.count }}</span>
        </button>
      </div>
      <select v-model="filterEvent" class="filter-select">
        <option v-for="o in eventTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
    </div>

    <!-- ACTIVE TAB -->
    <div v-if="activeTab === 'active'">
      <div v-if="filteredActive.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <p>No active applications.</p>
        <button class="btn-primary" @click="router.push('/new-application')">Start a New Application</button>
      </div>
      <div v-else class="app-list">
        <div
          v-for="app in filteredActive"
          :key="app.id"
          class="app-card"
          @click="router.push({ name: 'track', query: { id: app.id } })"
        >
          <div class="app-card-header">
            <div class="app-id-row">
              <span class="app-id">{{ app.id }}</span>
              <span :class="['tier-badge', app.tier === 'Express' ? 'express' : 'standard']">
                {{ app.tier }}
              </span>
            </div>
            <span :class="['stage-badge', stageColors[app.stage] || 'stage-active']">
              {{ app.stage }}
            </span>
          </div>

          <div class="app-card-body">
            <div class="app-meta-row">
              <span class="event-chip">{{ app.eventLabel }}</span>
              <span class="subject-text">{{ app.subject }}</span>
            </div>
            <p class="app-dates">Submitted {{ app.submittedAt }} · SLA Deadline {{ app.slaDeadline }}</p>
          </div>

          <div class="sla-section">
            <div class="sla-bar-wrap">
              <div
                class="sla-bar"
                :class="`sla-bar-${slaUrgency(app)}`"
                :style="{ width: slaPercent(app) + '%' }"
              ></div>
            </div>
            <span :class="['sla-label', `sla-${slaUrgency(app)}`]">
              {{ slaRemain(app) }} days remaining
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- DRAFTS TAB -->
    <div v-if="activeTab === 'drafts'">
      <div v-if="filteredDrafts.length === 0" class="empty-state">
        <div class="empty-icon">✏️</div>
        <p>No saved drafts.</p>
      </div>
      <div v-else class="app-list">
        <div
          v-for="draft in filteredDrafts"
          :key="draft.id"
          class="app-card draft-card"
        >
          <div class="app-card-header">
            <div class="app-id-row">
              <span class="app-id draft-id">{{ draft.id }}</span>
              <span class="draft-badge">Draft</span>
            </div>
            <span class="step-badge">{{ draft.stepLabel }}</span>
          </div>

          <div class="app-card-body">
            <div class="app-meta-row">
              <span class="event-chip">{{ draft.eventLabel }}</span>
              <span class="subject-text">{{ draft.subject }}</span>
            </div>
            <p class="app-dates">Last saved {{ draft.lastSaved }} · Expires {{ draft.expiresAt }}</p>
          </div>

          <div class="draft-actions">
            <button class="btn-primary btn-sm" @click="router.push('/new-application')">
              Resume Application
            </button>
            <button class="btn-ghost btn-sm">Delete Draft</button>
          </div>
        </div>
      </div>
    </div>

    <!-- COMPLETED TAB -->
    <div v-if="activeTab === 'completed'">
      <div v-if="filteredCompleted.length === 0" class="empty-state">
        <div class="empty-icon">✅</div>
        <p>No completed applications yet.</p>
      </div>
      <div v-else class="app-list">
        <div
          v-for="app in filteredCompleted"
          :key="app.id"
          class="app-card completed-card"
        >
          <div class="app-card-header">
            <div class="app-id-row">
              <span class="app-id">{{ app.id }}</span>
              <span :class="['tier-badge', app.tier === 'Express' ? 'express' : 'standard']">
                {{ app.tier }}
              </span>
            </div>
            <span class="completed-badge">Completed</span>
          </div>

          <div class="app-card-body">
            <div class="app-meta-row">
              <span class="event-chip">{{ app.eventLabel }}</span>
              <span class="subject-text">{{ app.subject }}</span>
            </div>
            <p class="app-dates">Completed {{ app.completedAt }} · Cert. {{ app.certSerial }}</p>
          </div>

          <div class="cert-actions">
            <button class="btn-ghost btn-sm" @click="router.push('/certificates')">
              View Certificate
            </button>
            <button class="btn-ghost btn-sm">Download PDF</button>
          </div>
        </div>
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
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin: 0;
}

.btn-primary {
  background: var(--brand-green);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 150ms;
}

.btn-primary:hover { background: var(--brand-green-dark); }

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.tabs {
  display: flex;
  gap: 4px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 4px;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  padding: 8px 16px;
  border-radius: calc(var(--radius) - 2px);
  font-size: 14px;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 120ms;
}

.tab-btn.active {
  background: var(--brand-green);
  color: #fff;
}

.tab-count {
  background: rgba(0,0,0,0.12);
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 6px;
}

.tab-btn.active .tab-count { background: rgba(255,255,255,0.25); }

.filter-select {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  font-size: 14px;
  outline: none;
  cursor: pointer;
  color: var(--text-primary);
}

/* Application List */
.app-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.app-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  cursor: pointer;
  transition: border-color 150ms;
}

.app-card:hover { border-color: var(--brand-green); }

.draft-card,
.completed-card {
  cursor: default;
}

.draft-card:hover,
.completed-card:hover { border-color: var(--border); }

.app-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}

.app-id-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.app-id {
  font-family: monospace;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: 0.04em;
}

.draft-id { color: var(--text-muted); }

.tier-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tier-badge.express { background: rgba(252, 209, 22, 0.2); color: #a67c00; }
.tier-badge.standard { background: var(--background); color: var(--text-muted); border: 1px solid var(--border); }

.stage-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 4px;
}

.stage-active { background: rgba(0, 107, 63, 0.1); color: var(--brand-green); }
.stage-warn { background: rgba(206, 17, 38, 0.08); color: var(--brand-red); }
.stage-ok { background: rgba(0, 107, 63, 0.15); color: var(--brand-green); }

.draft-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 4px;
  background: rgba(252, 209, 22, 0.15);
  color: #a67c00;
}

.step-badge {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 500;
}

.completed-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 4px;
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
}

.app-card-body { margin-bottom: 14px; }

.app-meta-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.event-chip {
  font-size: 13px;
  font-weight: 600;
  color: #1a2537;
  background: rgba(26, 37, 55, 0.07);
  padding: 2px 8px;
  border-radius: 4px;
}

.subject-text {
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 500;
}

.app-dates {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

/* SLA Section */
.sla-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.sla-bar-wrap {
  flex: 1;
  height: 4px;
  background: var(--border);
  border-radius: 9999px;
  overflow: hidden;
}

.sla-bar {
  height: 100%;
  border-radius: 9999px;
  transition: width 400ms ease;
}

.sla-bar-safe { background: var(--brand-green); }
.sla-bar-warning { background: var(--brand-gold); }
.sla-bar-critical { background: var(--brand-red); }

.sla-label {
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.sla-safe { color: var(--brand-green); }
.sla-warning { color: #a67c00; }
.sla-critical { color: var(--brand-red); }

/* Action Buttons */
.draft-actions,
.cert-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 13px;
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  color: var(--text-muted);
  transition: border-color 150ms, color 150ms;
}

.btn-ghost:hover {
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

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state p {
  font-size: 16px;
  margin: 0 0 20px;
}
</style>
