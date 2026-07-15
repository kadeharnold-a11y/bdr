<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'
import { api } from '../lib/api'

const router = useRouter()
const notifications = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/citizens/me/notifications')
    notifications.value = data
  } catch (err) {
    if (err.response?.status === 401) router.push({ name: 'login' })
    else error.value = 'Could not load notifications.'
  } finally {
    loading.value = false
  }
})

async function markRead(n) {
  if (n.read) return
  try {
    await api.post(`/citizens/me/notifications/${n.id}/read`)
    n.read = true
  } catch {
    // Non-fatal
  }
}
</script>

<template>
  <CitizenLayout>
    <h1 class="page-title">Notifications</h1>
    <p class="page-sub">Updates about your applications.</p>

    <p v-if="loading" class="status">Loading…</p>
    <p v-else-if="error" class="status error">{{ error }}</p>
    <p v-else-if="!notifications.length" class="status">No notifications yet.</p>

    <div v-else class="list">
      <article
        v-for="n in notifications"
        :key="n.id"
        :class="['row', { unread: !n.read }]"
        @click="markRead(n)"
      >
        <div>
          <p class="title">{{ n.title }}</p>
          <p class="body">{{ n.body }}</p>
          <p class="time">{{ new Date(n.createdAt).toLocaleString() }}</p>
        </div>
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
.row { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; cursor: pointer; }
.row.unread { border-left: 4px solid var(--brand-green); }
.title { font-weight: 700; margin: 0 0 4px; }
.body { font-size: 14px; color: var(--text-muted); margin: 0 0 8px; }
.time { font-size: 12px; color: var(--text-muted); margin: 0; }
</style>
