<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'
import { api } from '../lib/api'
import { EVENT_TYPE_LABELS, STATUS_LABELS, formatTier } from '../lib/eventLabels'

const router = useRouter()
const applications = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/applications')
    applications.value = data.map((app) => ({
      ...app,
      eventLabel: EVENT_TYPE_LABELS[app.eventType] || app.eventType,
      tierLabel: formatTier(app.tier),
      stage: STATUS_LABELS[app.status] || app.status,
    }))
  } catch (err) {
    if (err.response?.status === 401) router.push({ name: 'login' })
    else error.value = 'Could not load applications.'
  } finally {
    loading.value = false
  }
})

function track(id) {
  router.push({ name: 'track', query: { id } })
}
</script>

<template>
  <CitizenLayout>
    <h1 class="page-title">My Applications</h1>
    <p class="page-sub">All your birth and death registry applications.</p>

    <p v-if="loading" class="status">Loading…</p>
    <p v-else-if="error" class="status error">{{ error }}</p>
    <p v-else-if="!applications.length" class="status">No applications yet. <router-link to="/new-application">Start one</router-link>.</p>

    <div v-else class="list">
      <article v-for="app in applications" :key="app.id" class="row">
        <div>
          <p class="tracking">{{ app.trackingId || 'Draft' }}</p>
          <p class="meta">{{ app.eventLabel }} · {{ app.tierLabel }} · {{ app.stage }}</p>
        </div>
        <button v-if="app.trackingId" class="btn" @click="track(app.trackingId)">Track</button>
      </article>
    </div>
  </CitizenLayout>
</template>

<style scoped>
.page-title { font-size: 28px; margin: 0 0 4px; }
.page-sub { color: var(--text-muted); margin: 0 0 24px; }
.status { text-align: center; padding: 48px; color: var(--text-muted); }
.status.error { color: var(--danger); }
.list { display: flex; flex-direction: column; gap: 12px; }
.row { display: flex; justify-content: space-between; align-items: center; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; }
.tracking { font-family: monospace; font-weight: 700; margin: 0 0 4px; }
.meta { font-size: 14px; color: var(--text-muted); margin: 0; }
.btn { background: var(--brand-green); color: #fff; border: none; border-radius: var(--radius); padding: 8px 16px; font-weight: 600; cursor: pointer; }
</style>
