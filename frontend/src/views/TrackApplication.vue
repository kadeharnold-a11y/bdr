<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'
import { api } from '../lib/api'

const route = useRoute()
const router = useRouter()

const searchInput = ref('')
const trackingId = ref('')
const isSearching = ref(false)
const notFound = ref(false)
const tier = ref('')
const eventType = ref('')
const rejected = ref(false)

// PRD's status stages are more granular (Verifying Documents, Awaiting
// Approval as separate steps) than what the backend actually models - see
// shared/api-contract.md's "Application status values" note. Real statuses
// collapse onto the closest matching stage here.
const statusStages = [
  { id: 'submitted', label: 'Application Submitted', description: 'Payment confirmed. Awaiting assignment.' },
  { id: 'review', label: 'Under Review', description: 'Assigned to Registration Officer for initial review.' },
  { id: 'verifying', label: 'Verifying Documents', description: 'Checking supporting documents against source records.' },
  { id: 'approval', label: 'Awaiting Approval', description: 'Application ready for Supervisor sign-off.' },
  { id: 'approved', label: 'Approved', description: 'Application approved. Certificate is being generated.' },
  { id: 'completed', label: 'Completed', description: 'Certificate ready for collection/delivery.' },
]

const STATUS_TO_STAGE = {
  SUBMITTED: 0,
  UNDER_REVIEW: 1,
  CORRECTIONS_REQUIRED: 1,
  AWAITING_APPROVAL: 3,
  APPROVED: 4,
  COMPLETED: 5,
}

const currentStageIndex = ref(-1)

function handleSearch() {
  const query = searchInput.value.trim().toUpperCase()
  if (!query) return

  router.push({ name: 'track', query: { id: query } })
}

async function loadTrackingInfo(id) {
  if (!id) {
    trackingId.value = ''
    currentStageIndex.value = -1
    return
  }

  trackingId.value = id
  searchInput.value = id
  isSearching.value = true
  notFound.value = false
  rejected.value = false

  try {
    const { data } = await api.get(`/tracking/${encodeURIComponent(id)}`)
    tier.value = data.tier
    eventType.value = data.eventType
    if (data.status === 'REJECTED') {
      rejected.value = true
      currentStageIndex.value = -1
    } else {
      currentStageIndex.value = STATUS_TO_STAGE[data.status] ?? 0
    }
  } catch (err) {
    if (err.response?.status === 404) {
      notFound.value = true
    }
    currentStageIndex.value = -1
  } finally {
    isSearching.value = false
  }
}

// React to route query changes
watch(
  () => route.query.id,
  (newId) => loadTrackingInfo(newId)
)

onMounted(() => {
  if (route.query.id) {
    loadTrackingInfo(route.query.id)
  }
})
</script>

<template>
  <CitizenLayout>
    <div class="page-header">
      <h1 class="page-title">Track Application</h1>
      <p class="page-subtitle">Enter your Tracking ID to view real-time status and milestones.</p>
    </div>

    <div class="search-card card">
      <form class="search-form" @submit.prevent="handleSearch">
        <label for="trackingId" class="sr-only">Tracking ID</label>
        <div class="search-input-wrapper">
          <span class="search-icon">
            <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="m21 20-5.6-5.6a7 7 0 1 0-1.4 1.4L20 21zM5 10a5 5 0 1 1 5 5 5 5 0 0 1-5-5Z"/></svg>
          </span>
          <input
            id="trackingId"
            v-model="searchInput"
            class="search-input"
            type="text"
            placeholder="e.g. BDR-2026-EB-004821"
          />
        </div>
        <button type="submit" class="btn-primary">Track Status</button>
      </form>
    </div>

    <div v-if="isSearching" class="loading-state">
      <div class="spinner"></div>
      <p>Locating application...</p>
    </div>

    <div v-else-if="trackingId && notFound" class="results-card card">
      <p>No application found for tracking ID <strong>{{ trackingId }}</strong>. Double-check the ID and try again.</p>
    </div>

    <div v-else-if="trackingId && rejected" class="results-card card">
      <div class="results-header">
        <div>
          <h2 class="results-title">Application Status</h2>
          <p class="tracking-badge">{{ trackingId }}</p>
        </div>
        <div class="tier-badge">{{ tier === 'express' ? 'Express Service' : 'Standard Service' }}</div>
      </div>
      <p>This application was rejected. Log in to your dashboard for details and next steps.</p>
    </div>

    <div v-else-if="trackingId && currentStageIndex >= 0" class="results-card card">
      <div class="results-header">
        <div>
          <h2 class="results-title">Application Status</h2>
          <p class="tracking-badge">{{ trackingId }}</p>
        </div>
        <div class="tier-badge">{{ tier === 'express' ? 'Express Service' : 'Standard Service' }}</div>
      </div>

      <div class="timeline">
        <div 
          v-for="(stage, index) in statusStages" 
          :key="stage.id"
          class="timeline-item"
          :class="{
            'is-completed': index < currentStageIndex,
            'is-current': index === currentStageIndex,
            'is-pending': index > currentStageIndex
          }"
        >
          <div class="timeline-indicator">
            <div class="timeline-node">
              <svg v-if="index < currentStageIndex" viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
              <div v-else-if="index === currentStageIndex" class="current-dot"></div>
            </div>
            <div v-if="index < statusStages.length - 1" class="timeline-line"></div>
          </div>
          <div class="timeline-content">
            <h3 class="stage-label">{{ stage.label }}</h3>
            <p class="stage-desc">{{ stage.description }}</p>
          </div>
        </div>
      </div>
      
      <div class="help-section">
        <p>Need help with this application? <a href="#" class="text-link">Contact Support</a></p>
      </div>
    </div>
  </CitizenLayout>
</template>

<style scoped>
.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 8px;
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin: 0;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 32px;
  margin-bottom: 24px;
}

.search-form {
  display: flex;
  gap: 16px;
}

@media (max-width: 600px) {
  .search-form {
    flex-direction: column;
  }
}

.search-input-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--background);
  padding: 4px 12px;
}

.search-input-wrapper:focus-within {
  border-color: var(--brand-green);
  box-shadow: 0 0 0 3px rgba(0, 107, 63, 0.12);
}

.search-icon {
  color: var(--text-muted);
  display: inline-flex;
  margin-right: 8px;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  padding: 10px 0;
  font-size: 16px;
  font-family: monospace;
}

.btn-primary {
  background: var(--brand-green);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 150ms ease;
  white-space: nowrap;
}

.btn-primary:hover {
  background: var(--brand-green-dark);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

.loading-state {
  text-align: center;
  padding: 48px;
  color: var(--text-muted);
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--border);
  border-top-color: var(--brand-green);
  border-radius: 50%;
  margin: 0 auto 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.results-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--border);
}

.results-title {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 8px;
}

.tracking-badge {
  display: inline-block;
  background: var(--background);
  border: 1px solid var(--border);
  padding: 4px 12px;
  border-radius: var(--radius);
  font-family: monospace;
  font-size: 14px;
  color: var(--text-primary);
  margin: 0;
}

.tier-badge {
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.timeline {
  position: relative;
  max-width: 600px;
}

.timeline-item {
  display: flex;
  gap: 20px;
  padding-bottom: 32px;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.timeline-node {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 2;
}

.timeline-line {
  width: 2px;
  flex: 1;
  margin-top: 4px;
}

/* Completed Stage */
.is-completed .timeline-node {
  background: var(--brand-green);
  color: #fff;
}

.is-completed .timeline-line {
  background: var(--brand-green);
}

.is-completed .stage-label {
  color: var(--text-primary);
}

/* Current Stage */
.is-current .timeline-node {
  background: #fff;
  border: 2px solid var(--brand-green);
}

.is-current .current-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--brand-green);
}

.is-current .timeline-line {
  background: var(--border);
}

.is-current .stage-label {
  color: var(--brand-green);
  font-weight: 700;
}

/* Pending Stage */
.is-pending .timeline-node {
  background: #fff;
  border: 2px solid var(--border);
}

.is-pending .timeline-line {
  background: var(--border);
}

.is-pending .stage-label {
  color: var(--text-muted);
}

.is-pending .stage-desc {
  color: var(--border); /* very light text for pending */
}

.timeline-content {
  flex: 1;
  padding-top: 2px;
}

.stage-label {
  font-family: var(--font-body);
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px;
}

.stage-desc {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.5;
}

.help-section {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  font-size: 14px;
  color: var(--text-muted);
}

.text-link {
  color: var(--brand-green);
  font-weight: 600;
  text-decoration: none;
}

.text-link:hover {
  text-decoration: underline;
}
</style>
