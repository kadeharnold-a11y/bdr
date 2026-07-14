<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import OfficerLayout from '../../layouts/OfficerLayout.vue'

const route = useRoute()
const router = useRouter()
const appId = route.params.id

// Mock application data (in real app, fetched by appId)
const application = {
  id: appId || 'BDR-2026-EB-004821',
  name: 'Kwame Asante',
  event: 'EB',
  eventLabel: 'Early Birth Registration',
  tier: 'Express',
  submittedAt: '2026-07-11',
  slaDeadline: '2026-07-14',
  slaTotal: 3,
  slaUsed: 2.4,
  stage: 'Document Verification',
  stageIndex: 2,
  officer: 'Akua Mensah',
  childDetails: {
    firstName: 'Kofi',
    lastName: 'Asante',
    dob: '2026-06-15',
    gender: 'Male',
  },
  parentDetails: {
    motherFullName: 'Abena Asante',
    motherGhanaCard: 'GHA-123456789-0',
    fatherFullName: 'Kwame Asante',
  },
  niaStatus: 'pass', // 'pass' | 'flag' | 'unavailable'
  documents: [
    { name: 'Clinical Record of Birth', type: 'PDF', size: '2.3 MB', status: 'uploaded' },
    { name: 'Mother Ghana Card', type: 'JPG', size: '1.1 MB', status: 'uploaded' },
  ],
  log: [
    { actor: 'System', action: 'Application submitted and payment confirmed.', time: '2026-07-11 09:14', type: 'system' },
    { actor: 'System', action: 'Application assigned to Akua Mensah.', time: '2026-07-11 09:15', type: 'system' },
    { actor: 'Akua Mensah', action: 'Started initial review. Documents look complete.', time: '2026-07-11 10:02', type: 'officer' },
  ],
}

const workflowStages = [
  'Initial Review',
  'Document Verification',
  'NIA / Source Check',
  'Awaiting Approval',
  'Certificate Generation',
  'Certificate Print & Dispatch',
]

function stageStatus(index) {
  if (index < application.stageIndex) return 'completed'
  if (index === application.stageIndex) return 'current'
  return 'pending'
}

// SLA helpers
function slaPercent() {
  return Math.min(100, Math.round((application.slaUsed / application.slaTotal) * 100))
}

function slaUrgency() {
  const pct = slaPercent()
  if (pct >= 80) return 'critical'
  if (pct >= 50) return 'warning'
  return 'safe'
}

// Action state
const showCorrectionsModal = ref(false)
const showRejectModal = ref(false)
const addingNote = ref(false)
const noteText = ref('')
const correctionReasons = ref({})
const rejectReason = ref('')

const correctableFields = [
  { key: 'childFirstName', label: "Child's First Name" },
  { key: 'childLastName', label: "Child's Last Name" },
  { key: 'dob', label: 'Date of Birth' },
  { key: 'gender', label: 'Gender' },
  { key: 'motherFullName', label: "Mother's Full Name" },
  { key: 'motherGhanaCard', label: "Mother's Ghana Card Number" },
  { key: 'doc_birth_cert', label: 'Clinical Record of Birth (document)' },
]
const selectedCorrections = ref([])

const processingAction = ref('')

function performAction(action) {
  processingAction.value = action
  setTimeout(() => {
    processingAction.value = ''
    if (action === 'approve') {
      alert(`Application ${application.id} approved. Advancing to Certificate Generation.`)
      router.push('/officer/queue')
    } else if (action === 'escalate') {
      alert(`Application ${application.id} escalated to Supervisor.`)
      router.push('/officer/queue')
    } else if (action === 'reject') {
      showRejectModal.value = false
      alert(`Application ${application.id} rejected.`)
      router.push('/officer/queue')
    } else if (action === 'corrections') {
      showCorrectionsModal.value = false
      alert(`Correction request sent to citizen for ${application.id}. SLA clock paused.`)
    }
  }, 1000)
}

function submitNote() {
  if (!noteText.value.trim()) return
  application.log.push({
    actor: 'Akua Mensah',
    action: noteText.value,
    time: new Date().toLocaleString(),
    type: 'officer',
  })
  noteText.value = ''
  addingNote.value = false
}
</script>

<template>
  <OfficerLayout>
    <!-- Back link -->
    <button class="back-link" @click="router.push('/officer/queue')">← Back to Queue</button>

    <!-- Application Header Strip -->
    <div class="app-header">
      <div class="app-header-left">
        <h1 class="app-tracking-id">{{ application.id }}</h1>
        <div class="app-header-badges">
          <span class="event-badge">{{ application.eventLabel }}</span>
          <span :class="['tier-badge', application.tier === 'Express' ? 'express' : 'standard']">
            {{ application.tier }}
          </span>
          <span class="stage-badge">{{ application.stage }}</span>
        </div>
        <p class="app-meta">Submitted {{ application.submittedAt }} · Assigned to {{ application.officer }}</p>
      </div>

      <div class="sla-block" :class="`sla-${slaUrgency()}`">
        <p class="sla-label">SLA Deadline</p>
        <p class="sla-value">{{ application.slaDeadline }}</p>
        <div class="sla-bar-wrap">
          <div class="sla-bar" :style="{ width: slaPercent() + '%' }"></div>
        </div>
        <p class="sla-days">{{ (application.slaTotal - application.slaUsed).toFixed(1) }} days remaining</p>
      </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="workspace-grid">

      <!-- Left Column: Application Detail -->
      <div class="detail-col">

        <!-- Citizen Form Data -->
        <div class="card">
          <h2 class="card-title">Child's Details</h2>
          <div class="detail-grid">
            <div class="detail-item">
              <p class="detail-label">First Name</p>
              <p class="detail-value">{{ application.childDetails.firstName }}</p>
            </div>
            <div class="detail-item">
              <p class="detail-label">Last Name</p>
              <p class="detail-value">{{ application.childDetails.lastName }}</p>
            </div>
            <div class="detail-item">
              <p class="detail-label">Date of Birth</p>
              <p class="detail-value">{{ application.childDetails.dob }}</p>
            </div>
            <div class="detail-item">
              <p class="detail-label">Gender</p>
              <p class="detail-value">{{ application.childDetails.gender }}</p>
            </div>
          </div>
        </div>

        <div class="card">
          <h2 class="card-title">Parent Details</h2>
          <div class="detail-grid">
            <div class="detail-item">
              <p class="detail-label">Mother's Name</p>
              <p class="detail-value">{{ application.parentDetails.motherFullName }}</p>
            </div>
            <div class="detail-item">
              <p class="detail-label">Mother's Ghana Card</p>
              <p class="detail-value mono">{{ application.parentDetails.motherGhanaCard }}</p>
            </div>
            <div class="detail-item">
              <p class="detail-label">Father's Name</p>
              <p class="detail-value">{{ application.parentDetails.fatherFullName }}</p>
            </div>
          </div>
        </div>

        <!-- NIA Verification -->
        <div class="card">
          <h2 class="card-title">NIA Verification</h2>
          <div :class="['nia-result', `nia-${application.niaStatus}`]">
            <span class="nia-icon">
              {{ application.niaStatus === 'pass' ? '✓' : application.niaStatus === 'flag' ? '⚠' : '–' }}
            </span>
            <div>
              <strong>{{ application.niaStatus === 'pass' ? 'Verified' : application.niaStatus === 'flag' ? 'Flagged' : 'Unavailable' }}</strong>
              <p>
                {{ application.niaStatus === 'pass'
                  ? 'Ghana Card successfully verified against NIA database.'
                  : application.niaStatus === 'flag'
                    ? 'Card number not found in NIA database. Manual verification required.'
                    : 'NIA API unavailable. Check manually before approving.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="card">
          <h2 class="card-title">Uploaded Documents</h2>
          <div v-for="doc in application.documents" :key="doc.name" class="doc-item">
            <div class="doc-icon">📄</div>
            <div class="doc-info">
              <strong>{{ doc.name }}</strong>
              <p>{{ doc.type }} · {{ doc.size }}</p>
            </div>
            <button class="btn-ghost-sm">Preview</button>
          </div>
        </div>

        <!-- Correspondence Log -->
        <div class="card">
          <div class="card-title-row">
            <h2 class="card-title">Activity Log</h2>
            <button class="btn-ghost-sm" @click="addingNote = !addingNote">+ Add Note</button>
          </div>

          <div v-if="addingNote" class="note-input-wrap">
            <textarea v-model="noteText" class="note-input" placeholder="Write a note for this application..." rows="3"></textarea>
            <div class="note-actions">
              <button class="btn-ghost-sm" @click="addingNote = false">Cancel</button>
              <button class="btn-primary-sm" @click="submitNote">Save Note</button>
            </div>
          </div>

          <div class="log-entries">
            <div v-for="(entry, i) in [...application.log].reverse()" :key="i" :class="['log-entry', `log-${entry.type}`]">
              <div class="log-avatar">{{ entry.type === 'system' ? '⚙' : entry.actor.split(' ').map(n => n[0]).join('') }}</div>
              <div class="log-content">
                <p class="log-action">{{ entry.action }}</p>
                <p class="log-meta">{{ entry.actor }} · {{ entry.time }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Actions + Stage Timeline -->
      <div class="action-col">

        <!-- Action Panel -->
        <div class="card action-panel">
          <h2 class="card-title">Officer Actions</h2>

          <button class="action-btn approve" @click="performAction('approve')" :disabled="!!processingAction">
            <span class="action-icon">✅</span>
            <span>{{ processingAction === 'approve' ? 'Approving…' : 'Approve' }}</span>
          </button>

          <button class="action-btn corrections" @click="showCorrectionsModal = true" :disabled="!!processingAction">
            <span class="action-icon">✏️</span>
            <span>Request Corrections</span>
          </button>

          <button class="action-btn escalate" @click="performAction('escalate')" :disabled="!!processingAction">
            <span class="action-icon">⬆️</span>
            <span>{{ processingAction === 'escalate' ? 'Escalating…' : 'Escalate to Supervisor' }}</span>
          </button>

          <button class="action-btn reject" @click="showRejectModal = true" :disabled="!!processingAction">
            <span class="action-icon">❌</span>
            <span>Reject</span>
          </button>
        </div>

        <!-- Stage Timeline -->
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

    <!-- Corrections Modal -->
    <div v-if="showCorrectionsModal" class="modal-backdrop" @click.self="showCorrectionsModal = false">
      <div class="modal">
        <h2>Request Corrections</h2>
        <p>Select the fields that require correction and provide a reason for each.</p>

        <div v-for="field in correctableFields" :key="field.key" class="correction-field">
          <label class="correction-check">
            <input type="checkbox" :value="field.key" v-model="selectedCorrections" />
            <strong>{{ field.label }}</strong>
          </label>
          <textarea
            v-if="selectedCorrections.includes(field.key)"
            v-model="correctionReasons[field.key]"
            class="correction-reason"
            placeholder="Reason for correction…"
            rows="2"
          ></textarea>
        </div>

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

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="modal-backdrop" @click.self="showRejectModal = false">
      <div class="modal">
        <h2>Reject Application</h2>
        <p>Provide a clear reason for rejection. The citizen will be notified by SMS.</p>
        <textarea
          v-model="rejectReason"
          class="correction-reason"
          placeholder="Reason for rejection…"
          rows="4"
        ></textarea>
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
.back-link {
  background: none;
  border: none;
  color: var(--text-muted);
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-bottom: 20px;
  font-size: 14px;
}
.back-link:hover { color: var(--text-primary); }

/* App Header */
.app-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.app-tracking-id {
  font-family: monospace;
  font-size: 20px;
  margin: 0 0 10px;
  letter-spacing: 0.04em;
}

.app-header-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.event-badge {
  font-size: 12px;
  font-weight: 700;
  background: rgba(26, 37, 55, 0.08);
  color: #1a2537;
  padding: 3px 10px;
  border-radius: 4px;
}

.tier-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.tier-badge.express { background: rgba(252, 209, 22, 0.2); color: #a67c00; }
.tier-badge.standard { background: var(--background); color: var(--text-muted); }

.stage-badge {
  font-size: 12px;
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
  padding: 3px 10px;
  border-radius: 4px;
  font-weight: 600;
}

.app-meta {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

/* SLA Block */
.sla-block {
  min-width: 200px;
  padding: 16px 20px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
}
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

/* Workspace Grid */
.workspace-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  align-items: start;
}

@media (max-width: 1100px) {
  .workspace-grid { grid-template-columns: 1fr; }
}

/* Cards */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 20px;
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 20px;
}

.card-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.card-title-row .card-title { margin: 0; }

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

@media (max-width: 600px) {
  .detail-grid { grid-template-columns: 1fr; }
}

.detail-item {}
.detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin: 0 0 4px; }
.detail-value { font-size: 15px; margin: 0; color: var(--text-primary); }
.detail-value.mono { font-family: monospace; }

/* NIA */
.nia-result {
  display: flex;
  gap: 16px;
  padding: 16px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  align-items: flex-start;
}
.nia-pass { border-color: var(--brand-green); background: rgba(0, 107, 63, 0.05); }
.nia-flag { border-color: var(--brand-gold); background: rgba(252, 209, 22, 0.07); }
.nia-unavailable { border-color: var(--border); background: var(--background); }

.nia-icon {
  font-size: 20px;
  font-weight: 700;
  line-height: 1;
  flex-shrink: 0;
}
.nia-pass .nia-icon { color: var(--brand-green); }
.nia-flag .nia-icon { color: #a67c00; }

.nia-result strong { display: block; margin-bottom: 4px; font-size: 14px; }
.nia-result p { margin: 0; font-size: 13px; color: var(--text-muted); }

/* Docs */
.doc-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}
.doc-item:last-child { border-bottom: none; }
.doc-icon { font-size: 24px; flex-shrink: 0; }
.doc-info { flex: 1; }
.doc-info strong { display: block; font-size: 14px; margin-bottom: 2px; }
.doc-info p { margin: 0; font-size: 12px; color: var(--text-muted); }

/* Log */
.note-input-wrap { margin-bottom: 20px; }
.note-input {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  font-size: 14px;
  font-family: var(--font-body);
  resize: vertical;
  outline: none;
  box-sizing: border-box;
}
.note-input:focus { border-color: var(--brand-green); }
.note-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }

.log-entries { display: flex; flex-direction: column; gap: 16px; }
.log-entry { display: flex; gap: 12px; }
.log-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.log-system .log-avatar { background: var(--background); color: var(--text-muted); border: 1px solid var(--border); }
.log-officer .log-avatar { background: #1a2537; color: #fff; }
.log-action { font-size: 14px; margin: 0 0 4px; }
.log-meta { font-size: 12px; color: var(--text-muted); margin: 0; }

/* Actions */
.action-panel .card-title { margin-bottom: 16px; }
.action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--surface);
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 10px;
  transition: all 120ms;
}
.action-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.action-btn:last-child { margin-bottom: 0; }
.action-icon { font-size: 18px; line-height: 1; }

.action-btn.approve:hover:not(:disabled) { border-color: var(--brand-green); background: rgba(0,107,63,0.06); color: var(--brand-green); }
.action-btn.corrections:hover:not(:disabled) { border-color: var(--brand-gold); background: rgba(252,209,22,0.07); }
.action-btn.escalate:hover:not(:disabled) { border-color: #1a2537; background: rgba(26,37,55,0.06); }
.action-btn.reject:hover:not(:disabled) { border-color: var(--brand-red); background: rgba(206,17,38,0.06); color: var(--brand-red); }

/* Timeline */
.timeline { display: flex; flex-direction: column; }
.tl-item { display: flex; gap: 16px; padding-bottom: 24px; }
.tl-item:last-child { padding-bottom: 0; }
.tl-indicator { display: flex; flex-direction: column; align-items: center; }

.tl-node {
  width: 22px; height: 22px;
  border-radius: 50%;
  border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; background: #fff;
}
.tl-completed .tl-node { background: var(--brand-green); border-color: var(--brand-green); color: #fff; }
.tl-current .tl-node { border-color: var(--brand-green); }
.tl-pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--brand-green); }

.tl-line { width: 2px; flex: 1; background: var(--border); margin-top: 4px; }
.tl-completed .tl-line { background: var(--brand-green); }

.tl-content { flex: 1; padding-top: 2px; }
.tl-label { font-size: 13px; font-weight: 500; margin: 0; }
.tl-completed .tl-label { color: var(--text-primary); }
.tl-current .tl-label { color: var(--brand-green); font-weight: 700; }
.tl-pending .tl-label { color: var(--text-muted); }

/* Modals */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  z-index: 100; display: flex; align-items: center; justify-content: center; padding: 24px;
}
.modal {
  background: #fff; border-radius: var(--radius-lg); padding: 32px;
  max-width: 540px; width: 100%; max-height: 80vh; overflow-y: auto;
}
.modal h2 { font-size: 20px; margin: 0 0 8px; }
.modal > p { font-size: 14px; color: var(--text-muted); margin: 0 0 24px; }

.correction-field { margin-bottom: 16px; }
.correction-check { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; margin-bottom: 6px; }
.correction-reason {
  width: 100%; border: 1px solid var(--border); border-radius: var(--radius);
  padding: 8px 12px; font-size: 13px; font-family: var(--font-body);
  resize: vertical; outline: none; box-sizing: border-box;
}
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }

/* Small buttons */
.btn-ghost-sm {
  background: transparent; border: 1px solid var(--border);
  padding: 7px 14px; border-radius: var(--radius);
  font-size: 13px; font-weight: 600; cursor: pointer;
  color: var(--text-muted);
}
.btn-primary-sm {
  background: var(--brand-green); color: #fff; border: none;
  padding: 7px 14px; border-radius: var(--radius);
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-primary-sm:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-reject-sm {
  background: var(--brand-red); color: #fff; border: none;
  padding: 7px 14px; border-radius: var(--radius);
  font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-reject-sm:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
