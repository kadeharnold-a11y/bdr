<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'
import { api } from '../lib/api'
import { EVENT_TYPE_LABELS } from '../lib/eventLabels'

const router = useRouter()
const certificates = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/applications', { params: { status: 'COMPLETED' } })
    certificates.value = data.filter((a) => a.trackingId)
  } catch (err) {
    if (err.response?.status === 401) router.push({ name: 'login' })
    else error.value = 'Could not load certificates.'
  } finally {
    loading.value = false
  }
})

async function download(app) {
  try {
    const { data } = await api.get(`/applications/${app.id}/certificate`, { responseType: 'blob' })
    const url = URL.createObjectURL(data)
    const a = document.createElement('a')
    a.href = url
    a.download = `${app.trackingId}-certificate.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    error.value = 'Certificate not available yet.'
  }
}
</script>

<template>
  <CitizenLayout>
    <h1 class="page-title">Certificates</h1>
    <p class="page-sub">Download certificates for completed applications.</p>

    <p v-if="loading" class="status">Loading…</p>
    <p v-else-if="error" class="status error">{{ error }}</p>
    <p v-else-if="!certificates.length" class="status">No certificates yet.</p>

    <div v-else class="list">
      <article v-for="app in certificates" :key="app.id" class="row">
        <div>
          <p class="tracking">{{ app.trackingId }}</p>
          <p class="meta">{{ EVENT_TYPE_LABELS[app.eventType] || app.eventType }}</p>
        </div>
        <button class="btn" @click="download(app)">Download PDF</button>
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
