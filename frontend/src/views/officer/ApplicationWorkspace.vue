<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import OfficerLayout from '../../layouts/OfficerLayout.vue'
import { staffApi } from '../../lib/staffApi'
import {
  EVENT_TYPE_CODES,
  EVENT_TYPE_LABELS,
  STATUS_LABELS,
  formatTier,
  slaUrgencyFromRemaining,
} from '../../lib/eventLabels'

const route = useRoute()
const router = useRouter()
const appId = route.params.id

const loading = ref(true)
const loadError = ref('')
const raw = ref(null)

const workflowStages = [
  'Initial Review',
  'Document Verification',
  'NIA / Source Check',
  'Awaiting Approval',
  'Certificate Generation',
  'Certificate Print & Dispatch',
]

const STATUS_STAGE_INDEX = {
  SUBMITTED: 0,
  UNDER_REVIEW: 1,
  CORRECTIONS_REQUIRED: 1,
  AWAITING_APPROVAL: 3,
  APPROVED: 4,
  COMPLETED: 5,
  REJECTED: 0,
}

const showCorrectionsModal = ref(false)
const showRejectModal = ref(false)
const correctionNotes = ref('')
const selectedCorrections = ref([])
const rejectReason = ref('')
const processingAction = ref('')
const actionError = ref('')

const application = computed(() => {
  if (!raw.value) return null
  const d = raw.value
  return {
    id: d.id,
    trackingId: d.trackingId,
    eventCode: EVENT_TYPE_CODES[d.eventType] || d.eventType,
    eventLabel: EVENT_TYPE_LABELS[d.eventType] || d.eventType,
    tier: formatTier(d.tier),
    tierRaw: d.tier,
    status: d.status,
    stage: STATUS_LABELS[d.status] || d.status,
    stageIndex: STATUS_STAGE_INDEX[d.status] ?? 0,
    submittedAt: d.submittedAt ? new Date(d.submittedAt).toLocaleDateString() : '—',
    slaDeadline: d.slaDeadline ? new Date(d.slaDeadline).toLocaleDateString() : '—',
    slaPercentRemaining: d.slaPercentRemaining ?? 100,
    citizenName: d.citizen?.full_name || 'Citizen',
    niaStatus: d.citizen?.nia_status || 'unavailable',
    formData: d.formData || {},
    documents: (d.documents || []).map((doc) => ({
      id: doc.id,
      name: doc.original_name || doc.field_name,
      type: (doc.mime_type || '').split('/').pop()?.toUpperCase() || 'FILE',
      size: formatBytes(doc.size_bytes),
    })),
  }
})

const formFields = computed(() => {
  const data = application.value?.formData || {}
  return Object.entries(data).map(([key, value]) => ({
    key,
    label: humanizeKey(key),
    value: String(value ?? ''),
  }))
})

const correctableFields = computed(() => formFields.value)

const canClaim = computed(() => application.value?.status === 'SUBMITTED')
const canReview = computed(() =>
  ['UNDER_REVIEW', 'AWAITING_APPROVAL'].includes(application.value?.status),
)
const canComplete = computed(() => application.value?.status === 'APPROVED')

function humanizeKey(key) {
  return key.replace(/([A-Z])/g, ' $1').replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
}

function formatBytes(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}

function stageStatus(index) {
  const app = application.value
  if (!app) return 'pending'
  if (index < app.stageIndex) return 'completed'
  if (index === app.stageIndex) return 'current'
  return 'pending'
}

function slaPercent() {
  return application.value?.slaPercentRemaining ?? 100
}

function slaUrgency() {
  return slaUrgencyFromRemaining(application.value?.slaPercentRemaining)
}

async function loadApplication() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await staffApi.get(`/staff/applications/${appId}`)
    raw.value = data
  } catch (err) {
    if (err.response?.status === 401) {
      router.push({ name: 'officer-login' })
      return
    }
    loadError.value = err.response?.data?.error?.message || 'Application not found.'
  } finally {
    loading.value = false
  }
}

onMounted(loadApplication)

async function previewDocument(doc) {
  try {
    const { data } = await staffApi.get(`/staff/applications/${appId}/documents/${doc.id}`, {
      responseType: 'blob',
    })
    window.open(URL.createObjectURL(data), '_blank')
  } catch {
    actionError.value = 'Could not open document.'
  }
}

async function performAction(action) {
  actionError.value = ''
  processingAction.value = action
  try {
    if (action === 'claim') {
      const { data } = await staffApi.post(`/staff/applications/${appId}/claim`)
      raw.value = { ...raw.value, ...data }
    } else if (action === 'approve') {
      await staffApi.post(`/staff/applications/${appId}/approve`)
      router.push({ name: 'officer-queue' })
      return
    } else if (action === 'complete') {
      await staffApi.post(`/staff/applications/${appId}/complete`)
      router.push({ name: 'officer-queue' })
      return
    } else if (action === 'reject') {
      await staffApi.post(`/staff/applications/${appId}/reject`, { reason: rejectReason.value })
      showRejectModal.value = false
      router.push({ name: 'officer-queue' })
      return
    } else if (action === 'corrections') {
      await staffApi.post(`/staff/applications/${appId}/request-corrections`, {
        fields: selectedCorrections.value,
        notes: correctionNotes.value,
      })
      showCorrectionsModal.value = false
      router.push({ name: 'officer-queue' })
      return
    }
    await loadApplication()
  } catch (err) {
    actionError.value = err.response?.data?.error?.message || 'Action failed.'
  } finally {
    processingAction.value = ''
  }
}
</script>

<template>
  <OfficerLayout>
    <button class="back-link" @click="router.push('/officer/queue')">← Back to Queue</button>

    <p v-if="loading" class="status-msg">Loading application…</p>
    <p v-else-if="loadError" class="status-msg error">{{ loadError }}</p>

    <template v-else-if="application">
      <div class="app-header">
        <div class="app-header-left">
          <h1 class="app-tracking-id">{{ application.trackingId }}</h1>
          <div class="app-header-badges">
            <span class="event-badge">{{ application.eventLabel }}</span>
            <span :class="['tier-badge', application.tierRaw === 'express' ? 'express' : 'standard']">
              {{ application.tier }}
            </span>
            <span class="stage-badge">{{ application.stage }}</span>
          </div>
          <p class="app-meta">Submitted {{ application.submittedAt }} · {{ application.citizenName }}</p>
        </div>

        <div class="sla-block" :class="`sla-${slaUrgency()}`">
          <p class="sla-label">SLA Deadline</p>
          <p class="sla-value">{{ application.slaDeadline }}</p>
          <div class="sla-bar-wrap">
            <div class="sla-bar" :style="{ width: slaPercent() + '%' }"></div>
          </div>
          <p class="sla-days">{{ slaPercent() }}% SLA remaining</p>
        </div>
      </div>

      <p v-if="actionError" class="action-error">{{ actionError }}</p>

      <div class="workspace-grid">
        <div class="detail-col">
          <div class="card">
            <h2 class="card-title">Application Data</h2>
            <div v-if="formFields.length" class="detail-grid">
              <div v-for="field in formFields" :key="field.key" class="detail-item">
                <p class="detail-label">{{ field.label }}</p>
                <p class="detail-value">{{ field.value }}</p>
              </div>
            </div>
            <p v-else class="empty-msg">No form data submitted yet.</p>
          </div>

          <div class="card">
            <h2 class="card-title">NIA Verification</h2>
            <div :class="['nia-result', `nia-${application.niaStatus}`]">
              <span class="nia-icon">
                {{ application.niaStatus === 'verified' ? '✓' : application.niaStatus === 'flagged' ? '⚠' : '–' }}
              </span>
              <div>
                <strong>{{ application.niaStatus === 'verified' ? 'Verified' : application.niaStatus === 'flagged' ? 'Flagged' : 'Unavailable' }}</strong>
                <p>Citizen Ghana Card status from registration.</p>
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="card-title">Uploaded Documents</h2>
            <div v-for="doc in application.documents" :key="doc.id" class="doc-item">
              <div class="doc-icon">📄</div>
              <div class="doc-info">
                <strong>{{ doc.name }}</strong>
                <p>{{ doc.type }} · {{ doc.size }}</p>
              </div>
              <button class="btn-ghost-sm" @click="previewDocument(doc)">Preview</button>
            </div>
            <p v-if="!application.documents.length" class="empty-msg">No documents uploaded.</p>
          </div>
        </div>

        <div class="action-col">
          <div class="card action-panel">
            <h2 class="card-title">Officer Actions</h2>

            <button
              v-if="canClaim"
              class="action-btn claim"
              @click="performAction('claim')"
              :disabled="!!processingAction"
            >
              <span class="action-icon">📋</span>
              <span>{{ processingAction === 'claim' ? 'Claiming…' : 'Claim Application' }}</span>
            </button>

            <button
              v-if="canReview"
              class="action-btn approve"
              @click="performAction('approve')"
              :disabled="!!processingAction"
            >
              <span class="action-icon">✅</span>
              <span>{{ processingAction === 'approve' ? 'Approving…' : 'Approve' }}</span>
            </button>

            <button
              v-if="canReview"
              class="action-btn corrections"
              @click="showCorrectionsModal = true"
              :disabled="!!processingAction"
            >
              <span class="action-icon">✏️</span>
              <span>Request Corrections</span>
            </button>

            <button
              v-if="canComplete"
              class="action-btn approve"
              @click="performAction('complete')"
              :disabled="!!processingAction"
            >
              <span class="action-icon">🎓</span>
              <span>{{ processingAction === 'complete' ? 'Completing…' : 'Complete & Issue Certificate' }}</span>
            </button>

            <button
              v-if="canReview"
              class="action-btn reject"
              @click="showRejectModal = true"
              :disabled="!!processingAction"
            >
              <span class="action-icon">❌</span>
              <span>Reject</span>
            </button>
          </div>

          <div class="card">
            <h2 class="card-title">Workflow Stages</h2>
            <div class="timeline">
              <div
                v-for="(stage, i) in workflowStages"
                :key="stage"
                :class="['tl-item', `tl-${stageStatus(i)}`]"
              >
                <div class="tl-indicator">
                  <div class="tl-node">
                    <svg v-if="stageStatus(i) === 'completed'" viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                    <div v-else-if="stageStatus(i) === 'current'" class="tl-pulse"></div>
                  </div>
                  <div v-if="i < workflowStages.length - 1" class="tl-line"></div>
                </div>
                <div class="tl-content">
                  <p class="tl-label">{{ stage }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div v-if="showCorrectionsModal" class="modal-backdrop" @click.self="showCorrectionsModal = false">
      <div class="modal">
        <h2>Request Corrections</h2>
        <p>Select fields that need correction and add notes for the citizen.</p>
        <div v-for="field in correctableFields" :key="field.key" class="correction-field">
          <label class="correction-check">
            <input type="checkbox" :value="field.key" v-model="selectedCorrections" />
            <strong>{{ field.label }}</strong>
          </label>
        </div>
        <textarea v-model="correctionNotes" class="correction-reason" placeholder="Notes for the citizen…" rows="3"></textarea>
        <div class="modal-actions">
          <button class="btn-ghost-sm" @click="showCorrectionsModal = false">Cancel</button>
          <button
            class="btn-primary-sm"
            :disabled="selectedCorrections.length === 0 || processingAction === 'corrections'"
            @click="performAction('corrections')"
          >
            {{ processingAction === 'corrections' ? 'Sending…' : 'Send Correction Request' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showRejectModal" class="modal-backdrop" @click.self="showRejectModal = false">
      <div class="modal">
        <h2>Reject Application</h2>
        <p>Provide a clear reason. The citizen will be notified.</p>
        <textarea v-model="rejectReason" class="correction-reason" placeholder="Reason for rejection…" rows="4"></textarea>
        <div class="modal-actions">
          <button class="btn-ghost-sm" @click="showRejectModal = false">Cancel</button>
          <button
            class="btn-reject-sm"
            :disabled="!rejectReason.trim() || processingAction === 'reject'"
            @click="performAction('reject')"
          >
            {{ processingAction === 'reject' ? 'Rejecting…' : 'Confirm Rejection' }}
          </button>
        </div>
      </div>
    </div>
  </OfficerLayout>
</template>

<style scoped>
.back-link { background: none; border: none; color: var(--text-muted); font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 20px; font-size: 14px; }
.back-link:hover { color: var(--text-primary); }
.status-msg { text-align: center; padding: 48px; color: var(--text-muted); }
.status-msg.error { color: var(--danger); }
.action-error { color: var(--danger); margin-bottom: 16px; font-size: 14px; }
.empty-msg { color: var(--text-muted); font-size: 14px; margin: 0; }
.app-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 24px; flex-wrap: wrap; }
.app-tracking-id { font-family: monospace; font-size: 20px; margin: 0 0 10px; letter-spacing: 0.04em; }
.app-header-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.event-badge { font-size: 12px; font-weight: 700; background: rgba(26, 37, 55, 0.08); color: #1a2537; padding: 3px 10px; border-radius: 4px; }
.tier-badge { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
.tier-badge.express { background: rgba(252, 209, 22, 0.2); color: #a67c00; }
.tier-badge.standard { background: var(--background); color: var(--text-muted); }
.stage-badge { font-size: 12px; background: rgba(0, 107, 63, 0.1); color: var(--brand-green); padding: 3px 10px; border-radius: 4px; font-weight: 600; }
.app-meta { font-size: 13px; color: var(--text-muted); margin: 0; }
.sla-block { min-width: 200px; padding: 16px 20px; border-radius: var(--radius); border: 1px solid var(--border); }
.sla-block.sla-safe { border-color: var(--brand-green); background: rgba(0, 107, 63, 0.05); }
.sla-block.sla-warning { border-color: var(--brand-gold); background: rgba(252, 209, 22, 0.07); }
.sla-block.sla-critical { border-color: var(--brand-red); background: rgba(206, 17, 38, 0.05); }
.sla-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin: 0 0 4px; }
.sla-value { font-size: 18px; font-weight: 700; margin: 0 0 10px; }
.sla-bar-wrap { height: 6px; background: var(--border); border-radius: 9999px; overflow: hidden; margin-bottom: 6px; }
.sla-bar { height: 100%; border-radius: 9999px; }
.sla-safe .sla-bar { background: var(--brand-green); }
.sla-warning .sla-bar { background: var(--brand-gold); }
.sla-critical .sla-bar { background: var(--brand-red); }
.sla-days { font-size: 12px; color: var(--text-muted); margin: 0; }
.workspace-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
@media (max-width: 1100px) { .workspace-grid { grid-template-columns: 1fr; } }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 20px; }
.card-title { font-size: 16px; font-weight: 700; margin: 0 0 20px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
.detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 0 0 4px; }
.detail-value { font-size: 15px; margin: 0; font-weight: 500; }
.nia-result { display: flex; gap: 16px; padding: 16px; border-radius: var(--radius); }
.nia-verified, .nia-pass { background: rgba(0, 107, 63, 0.08); }
.nia-flagged, .nia-flag { background: rgba(252, 209, 22, 0.1); }
.nia-unavailable { background: var(--background); }
.nia-icon { font-size: 24px; }
.doc-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.doc-item:last-child { border-bottom: none; }
.doc-info { flex: 1; }
.doc-info p { font-size: 12px; color: var(--text-muted); margin: 2px 0 0; }
.btn-ghost-sm { background: none; border: 1px solid var(--border); border-radius: var(--radius); padding: 6px 12px; font-size: 13px; cursor: pointer; }
.action-panel { position: sticky; top: 88px; }
.action-btn { display: flex; align-items: center; gap: 12px; width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 10px; text-align: left; }
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.action-btn.approve { border-color: var(--brand-green); color: var(--brand-green); }
.action-btn.claim { border-color: #1a2537; color: #1a2537; }
.action-btn.corrections { border-color: var(--brand-gold); color: #a67c00; }
.action-btn.reject { border-color: var(--brand-red); color: var(--brand-red); }
.timeline { display: flex; flex-direction: column; }
.tl-item { display: flex; gap: 12px; }
.tl-indicator { display: flex; flex-direction: column; align-items: center; }
.tl-node { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tl-completed .tl-node { background: var(--brand-green); border-color: var(--brand-green); color: #fff; }
.tl-current .tl-node { border-color: var(--brand-green); }
.tl-pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--brand-green); animation: pulse 1.5s infinite; }
.tl-line { width: 2px; flex: 1; min-height: 24px; background: var(--border); margin: 4px 0; }
.tl-completed .tl-line { background: var(--brand-green); }
.tl-content { padding-bottom: 20px; }
.tl-label { font-size: 13px; font-weight: 600; margin: 2px 0 0; }
.tl-pending .tl-label { color: var(--text-muted); font-weight: 400; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 24px; }
.modal { background: var(--surface); border-radius: var(--radius-lg); padding: 28px; max-width: 480px; width: 100%; max-height: 80vh; overflow-y: auto; }
.modal h2 { margin: 0 0 8px; font-size: 20px; }
.modal p { color: var(--text-muted); font-size: 14px; margin: 0 0 16px; }
.correction-field { margin-bottom: 12px; }
.correction-check { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.correction-reason { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; box-sizing: border-box; margin-top: 8px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.btn-primary-sm { background: var(--brand-green); color: #fff; border: none; border-radius: var(--radius); padding: 8px 16px; font-weight: 600; cursor: pointer; }
.btn-reject-sm { background: var(--brand-red); color: #fff; border: none; border-radius: var(--radius); padding: 8px 16px; font-weight: 600; cursor: pointer; }
</style>
