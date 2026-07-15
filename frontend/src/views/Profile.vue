<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'
import { api } from '../lib/api'
import { saveSession, getSession } from '../lib/auth'

const router = useRouter()
const profile = reactive({
  full_name: '',
  phone: '',
  email: '',
  ghana_card_number: '',
  nia_status: '',
})
const saving = ref(false)
const message = ref('')
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/citizens/me')
    Object.assign(profile, data)
  } catch (err) {
    if (err.response?.status === 401) router.push({ name: 'login' })
    else error.value = 'Could not load profile.'
  }
})

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    const { data } = await api.patch('/citizens/me', { email: profile.email })
    Object.assign(profile, data)
    const session = getSession()
    if (session) saveSession({ ...session, fullName: data.full_name })
    message.value = 'Profile updated.'
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Update failed.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <CitizenLayout>
    <h1 class="page-title">My Profile</h1>
    <p class="page-sub">Your registered citizen account details.</p>

    <form v-if="profile.full_name" class="card" @submit.prevent="save">
      <label class="field-label">Full Name</label>
      <input class="text-input" :value="profile.full_name" disabled />

      <label class="field-label">Phone</label>
      <input class="text-input" :value="profile.phone" disabled />

      <label class="field-label">Ghana Card</label>
      <input class="text-input" :value="profile.ghana_card_number || '—'" disabled />

      <label class="field-label">NIA Status</label>
      <input class="text-input" :value="profile.nia_status || '—'" disabled />

      <label class="field-label" for="email">Email</label>
      <input id="email" v-model="profile.email" type="email" class="text-input" />

      <p v-if="message" class="success">{{ message }}</p>
      <p v-if="error" class="field-error">{{ error }}</p>

      <button type="submit" class="btn-primary" :disabled="saving">
        {{ saving ? 'Saving…' : 'Save Changes' }}
      </button>
    </form>
    <p v-else-if="error" class="field-error">{{ error }}</p>
  </CitizenLayout>
</template>

<style scoped>
.page-title { font-size: 28px; margin: 0 0 4px; }
.page-sub { color: var(--text-muted); margin: 0 0 24px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px; max-width: 480px; }
.field-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 16px 0 8px; }
.text-input { width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; box-sizing: border-box; }
.text-input:disabled { background: var(--background); color: var(--text-muted); }
.btn-primary { margin-top: 20px; background: var(--brand-green); color: #fff; border: none; border-radius: var(--radius); padding: 14px 24px; font-weight: 600; cursor: pointer; }
.field-error { color: var(--danger); font-size: 13px; margin-top: 12px; }
.success { color: var(--brand-green); font-size: 13px; margin-top: 12px; }
</style>
