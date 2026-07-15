<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import OtpInput from '../../components/OtpInput.vue'
import { staffApi } from '../../lib/staffApi'
import { saveStaffSession } from '../../lib/staffAuth'

const router = useRouter()

const step = ref('credentials') // credentials | setup-2fa | verify-2fa
const form = reactive({ staffId: '', password: '' })
const totpCode = ref('')
const challengeToken = ref('')
const otpauthUrl = ref('')
const error = ref('')
const submitting = ref(false)

async function onSubmitCredentials() {
  error.value = ''
  submitting.value = true
  try {
    const { data } = await staffApi.post('/staff/login', {
      staffId: form.staffId.trim(),
      password: form.password,
    })
    challengeToken.value = data.challengeToken
    if (data.twoFactorSetupRequired) {
      otpauthUrl.value = data.otpauthUrl
      step.value = 'setup-2fa'
    } else {
      step.value = 'verify-2fa'
    }
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Login failed. Check your Staff ID and password.'
  } finally {
    submitting.value = false
  }
}

async function onVerify2fa() {
  if (totpCode.value.length !== 6) {
    error.value = 'Enter the 6-digit code from your authenticator app.'
    return
  }
  error.value = ''
  submitting.value = true
  try {
    const { data } = await staffApi.post('/staff/login/verify-2fa', {
      challengeToken: challengeToken.value,
      code: totpCode.value,
    })
    saveStaffSession({
      staffId: data.staffId,
      role: data.role,
      fullName: data.fullName,
      accessToken: data.accessToken,
    })
    router.push({ name: 'officer-queue' })
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Incorrect verification code.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page-shell">
    <div class="flag-topbar" aria-hidden="true"></div>
    <div class="login-card card">
      <header class="brand">
        <img class="brand-logo" src="/coat-of-arms.png" alt="Republic of Ghana coat of arms" />
        <div>
          <h1 class="brand-title">Back-Office Portal</h1>
          <p class="brand-subtitle">Birth &amp; Death Registry — Staff Sign In</p>
        </div>
      </header>

      <template v-if="step === 'credentials'">
        <h2 class="form-title">Staff Sign In</h2>
        <form @submit.prevent="onSubmitCredentials">
          <label class="field-label" for="staffId">Staff ID</label>
          <input id="staffId" v-model="form.staffId" class="text-input" placeholder="e.g. OFF-001" />
          <label class="field-label" for="password">Password</label>
          <input id="password" v-model="form.password" type="password" class="text-input" />
          <p class="field-error" v-if="error">{{ error }}</p>
          <button type="submit" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'Signing in…' : 'Continue' }}
          </button>
        </form>
      </template>

      <template v-else>
        <h2 class="form-title">{{ step === 'setup-2fa' ? 'Set Up Two-Factor Auth' : 'Verification Required' }}</h2>
        <p class="form-help" v-if="step === 'setup-2fa'">
          Scan this URL into Google Authenticator or Authy, then enter the 6-digit code.
        </p>
        <p class="form-help" v-else>Enter the 6-digit code from your authenticator app.</p>
        <p class="setup-url" v-if="otpauthUrl"><code>{{ otpauthUrl }}</code></p>
        <OtpInput v-model="totpCode" :has-error="!!error" @complete="onVerify2fa" />
        <p class="field-error" v-if="error">{{ error }}</p>
        <button type="button" class="btn-primary" :disabled="submitting" @click="onVerify2fa">
          {{ submitting ? 'Verifying…' : 'Verify &amp; Enter Portal' }}
        </button>
      </template>
    </div>
  </div>
</template>

<style scoped>
.page-shell { min-height: 100vh; background: #f3f4f2; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; }
.flag-topbar { height: 6px; width: 100%; position: fixed; top: 0; left: 0; background: linear-gradient(to right, var(--brand-red) 33%, var(--brand-gold) 33% 66%, var(--brand-green) 66%); }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 40px; width: min(440px, 100%); }
.brand { display: flex; gap: 16px; align-items: center; margin-bottom: 32px; }
.brand-logo { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; }
.brand-title { font-size: 18px; font-weight: 700; margin: 0; }
.brand-subtitle { margin: 2px 0 0; font-size: 13px; color: var(--text-muted); }
.form-title { font-size: 24px; margin: 0 0 16px; }
.form-help { color: var(--text-muted); font-size: 14px; margin: 0 0 16px; }
.field-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 16px 0 8px; }
.text-input { width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; box-sizing: border-box; }
.field-error { color: var(--danger); font-size: 13px; margin: 12px 0; }
.btn-primary { width: 100%; margin-top: 20px; background: var(--brand-green); color: #fff; border: none; border-radius: var(--radius); padding: 14px; font-weight: 600; cursor: pointer; }
.setup-url { font-size: 11px; word-break: break-all; background: var(--background); padding: 12px; border-radius: var(--radius); margin-bottom: 16px; }
</style>
