<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import OtpInput from '../components/OtpInput.vue'
import { api } from '../lib/api'
import { saveSession } from '../lib/auth'

const router = useRouter()

// "24 000 0000" grouped display of the entered phone number.
const maskedPhone = computed(() => {
  const p = form.phone
  if (!p) return ''
  return `+233 ${p.replace(/(\d{2})(\d{3})(\d{0,4}).*/, '$1 $2 $3').trim()}`
})

const slides = [
  {
    heading: 'Birth & Death Registry',
    intro: 'Official online portal for registering births, deaths, adoptions and surrogacy events across the Republic of Ghana.',
  },
  {
    heading: 'How It Works',
    intro: 'Three simple steps to get started',
    steps: [
      'Create your account & verify identity',
      'Fill your event form & upload documents',
      'Pay fees & track your application',
    ],
  },
  {
    heading: 'About the Registry',
    intro: 'Serving Ghana since 1888, providing secure civil registration for all citizens.',
    cards: [
      { icon: '🛡️', title: 'Secure & Encrypted', text: '256-bit SSL. Your data is protected.' },
      { icon: '🕐', title: 'Real-time Tracking', text: 'Monitor your application status live.' },
      { icon: '🏠', title: 'Nationwide Coverage', text: 'Accessible from all 16 regions of Ghana.' },
    ],
  },
]

const activeSlide = ref(0)
let slideTimer = null

const reduceMotion =
  typeof window !== 'undefined' &&
  window.matchMedia('(prefers-reduced-motion: reduce)').matches

function startAutoplay() {
  if (reduceMotion || slideTimer) return
  slideTimer = setInterval(() => {
    activeSlide.value = (activeSlide.value + 1) % slides.length
  }, 5000)
}

function stopAutoplay() {
  clearInterval(slideTimer)
  slideTimer = null
}

function goToSlide(index) {
  activeSlide.value = index
  // Reset the timer so the user gets a full interval on the slide they chose.
  stopAutoplay()
  startAutoplay()
}

onMounted(startAutoplay)
onBeforeUnmount(stopAutoplay)

// Registration is a single route with staged steps (matches the reference site).
const step = ref('contact') // 'contact' | 'otp'

const form = reactive({
  phone: '',
  email: '',
})

// Where the verification code gets delivered - independent of the account's
// phone number, which is always required as the login identifier.
const channel = ref('phone') // 'phone' | 'email'

const errors = reactive({
  phone: '',
  email: '',
  form: '',
})

const submitting = ref(false)
const registrationToken = ref('')
const profileToken = ref('')

function extractApiError(err) {
  return err?.response?.data?.error?.message || 'Something went wrong. Please try again.'
}

function validate() {
  errors.phone = ''
  errors.email = ''
  errors.form = ''

  if (!/^\d{9,10}$/.test(form.phone)) {
    errors.phone = 'Enter a valid Ghana mobile number (digits only, no +233 or 0 prefix).'
  }
  if (channel.value === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Enter a valid email address to receive your code.'
  } else if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Enter a valid email address.'
  }
  return !errors.phone && !errors.email
}

async function onSubmit() {
  if (!validate()) return
  submitting.value = true
  try {
    const { data } = await api.post('/auth/register/send-otp', {
      phone: form.phone,
      email: form.email || undefined,
      channel: channel.value,
    })
    registrationToken.value = data.registrationToken
    goToOtp()
  } catch (err) {
    const code = err.response?.data?.error?.code
    if (code === 'PHONE_ALREADY_REGISTERED') {
      router.push({ name: 'login' })
    } else if (code === 'INVALID_PHONE') {
      errors.phone = extractApiError(err)
    } else if (code === 'INVALID_EMAIL') {
      errors.email = extractApiError(err)
    } else {
      errors.form = extractApiError(err)
    }
  } finally {
    submitting.value = false
  }
}

// --- OTP verification step ---
const otp = ref('')
const otpError = ref('')
const verifying = ref(false)
const resendSeconds = ref(0)
let resendTimer = null

function startResendCountdown() {
  resendSeconds.value = 60
  clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendSeconds.value -= 1
    if (resendSeconds.value <= 0) clearInterval(resendTimer)
  }, 1000)
}

function goToOtp() {
  step.value = 'otp'
  otp.value = ''
  otpError.value = ''
  startResendCountdown()
}

function backToContact() {
  step.value = 'contact'
  clearInterval(resendTimer)
}

async function resendCode() {
  if (resendSeconds.value > 0) return
  otpError.value = ''
  try {
    const { data } = await api.post('/auth/register/send-otp', {
      phone: form.phone,
      email: form.email || undefined,
      channel: channel.value,
    })
    registrationToken.value = data.registrationToken
    otp.value = ''
    startResendCountdown()
  } catch (err) {
    otpError.value = extractApiError(err)
  }
}

async function verifyOtp() {
  if (otp.value.length !== 6) {
    otpError.value = 'Enter the 6-digit code sent to your phone.'
    return
  }
  verifying.value = true
  otpError.value = ''
  try {
    const { data } = await api.post('/auth/register/verify-otp', {
      registrationToken: registrationToken.value,
      otp: otp.value,
    })
    profileToken.value = data.profileToken
    clearInterval(resendTimer)
    step.value = 'profile'
  } catch (err) {
    const code = err.response?.data?.error?.code
    otpError.value = code === 'OTP_EXPIRED'
      ? 'That code has expired. Request a new one.'
      : code === 'OTP_INCORRECT'
        ? 'Incorrect code. Please try again.'
        : extractApiError(err)
  } finally {
    verifying.value = false
  }
}

// --- Profile setup step (PRD 4.1 step 5) ---
const profile = reactive({
  fullName: '',
  ghanaCard: '',
})
const profileErrors = reactive({
  fullName: '',
  ghanaCard: '',
})
const savingProfile = ref(false)

// Auto-format Ghana Card as GHA-XXXXXXXXX-X while typing.
function formatGhanaCard(event) {
  const digits = event.target.value.toUpperCase().replace(/[^0-9]/g, '').slice(0, 10)
  let out = 'GHA-'
  if (digits.length) out += digits.slice(0, 9)
  if (digits.length > 9) out += '-' + digits.slice(9, 10)
  profile.ghanaCard = digits ? out : ''
}

function validateProfile() {
  profileErrors.fullName = ''
  profileErrors.ghanaCard = ''
  if (profile.fullName.trim().length < 2) {
    profileErrors.fullName = 'Enter your full name as shown on your Ghana Card.'
  }
  if (!/^GHA-\d{9}-\d$/.test(profile.ghanaCard)) {
    profileErrors.ghanaCard = 'Enter a valid Ghana Card number (GHA-XXXXXXXXX-X).'
  }
  return !profileErrors.fullName && !profileErrors.ghanaCard
}

const profileFormError = ref('')

async function submitProfile() {
  if (!validateProfile()) return
  savingProfile.value = true
  profileFormError.value = ''
  try {
    // NIA verification result isn't shown here - per the PRD design note,
    // an UNAVAILABLE/unverified card never blocks registration, so there's
    // nothing actionable to surface to the citizen at this step.
    await api.post('/auth/register/profile', {
      profileToken: profileToken.value,
      fullName: profile.fullName,
      ghanaCardNumber: profile.ghanaCard,
    })
    step.value = 'pin'
  } catch (err) {
    const code = err.response?.data?.error?.code
    if (code === 'INVALID_NAME') profileErrors.fullName = extractApiError(err)
    else if (code === 'INVALID_GHANA_CARD') profileErrors.ghanaCard = extractApiError(err)
    else profileFormError.value = extractApiError(err)
  } finally {
    savingProfile.value = false
  }
}

// --- PIN creation step (PRD 4.1 step 6) ---
const pin = ref('')
const confirmPin = ref('')
const pinError = ref('')
const creatingPin = ref(false)

async function submitPin() {
  pinError.value = ''
  if (pin.value.length !== 6) {
    pinError.value = 'Choose a 6-digit PIN.'
    return
  }
  if (confirmPin.value !== pin.value) {
    pinError.value = 'The two PINs do not match.'
    return
  }
  creatingPin.value = true
  try {
    const { data } = await api.post('/auth/register/pin', {
      profileToken: profileToken.value,
      pin: pin.value,
    })
    saveSession({
      citizenId: data.citizenId,
      accessToken: data.accessToken,
      refreshToken: data.refreshToken,
      fullName: profile.fullName,
    })
    // Account created (PRD 4.1 step 7) — land on the citizen dashboard.
    router.push({ name: 'dashboard' })
  } catch (err) {
    pinError.value = extractApiError(err)
  } finally {
    creatingPin.value = false
  }
}

onBeforeUnmount(() => clearInterval(resendTimer))
</script>

<template>
  <div class="page-shell">
    <div class="flag-topbar" aria-hidden="true"></div>
    <div class="auth-page">
    <div class="auth-panel">
      <header class="brand">
        <img class="brand-logo" src="/coat-of-arms.png" alt="Republic of Ghana coat of arms" />
        <div>
          <h1 class="brand-title">Republic of Ghana</h1>
          <p class="brand-subtitle">Birth &amp; Death Registry Portal</p>
        </div>
      </header>

      <div class="form-wrap">
        <!-- Step 1: contact details -->
        <template v-if="step === 'contact'">
          <h2 class="form-title">Create an Account</h2>
          <p class="form-help">
            Enter your Ghana mobile number, then choose whether your 6-digit verification code goes to your phone
            or your email.
          </p>

          <form @submit.prevent="onSubmit" novalidate>
            <label class="field-label" for="phone">Phone Number</label>
            <div class="phone-input" :class="{ 'has-error': errors.phone }">
              <span class="phone-prefix">
                <span class="flag">🇬🇭</span> +233
              </span>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                placeholder="24 000 0000"
                inputmode="numeric"
                maxlength="10"
                @input="form.phone = form.phone.replace(/\D/g, '')"
              />
            </div>
            <p class="field-hint" v-if="!errors.phone">
              Enter digits only — e.g. <strong>24 000 0000</strong> (no +233 or leading 0)
            </p>
            <p class="field-error" v-else>{{ errors.phone }}</p>

            <label class="field-label" for="email">Email Address</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="text-input"
              :class="{ 'has-error': errors.email }"
              placeholder="Enter your email address"
            />
            <p class="field-hint" v-if="!errors.email">
              {{ channel === 'email' ? 'Your verification code will be sent here.' : 'Optional — used for receipts and account recovery.' }}
            </p>
            <p class="field-error" v-else>{{ errors.email }}</p>

            <label class="field-label">Send my code by</label>
            <div class="channel-toggle" role="radiogroup" aria-label="Verification code delivery method">
              <button
                type="button"
                class="channel-option"
                :class="{ active: channel === 'phone' }"
                role="radio"
                :aria-checked="channel === 'phone'"
                @click="channel = 'phone'"
              >
                📱 Text message
              </button>
              <button
                type="button"
                class="channel-option"
                :class="{ active: channel === 'email' }"
                role="radio"
                :aria-checked="channel === 'email'"
                @click="channel = 'email'"
              >
                ✉️ Email
              </button>
            </div>

            <p class="field-error form-error" v-if="errors.form">{{ errors.form }}</p>

            <button type="submit" class="btn-primary" :disabled="submitting">
              <span v-if="!submitting">Send verification code</span>
              <span v-else>Sending…</span>
            </button>
          </form>

          <p class="switch-auth">
            Already have an Account?
            <router-link to="/login">Sign in Now</router-link>
          </p>
        </template>

        <!-- Step 2: OTP verification -->
        <template v-else-if="step === 'otp'">
          <button type="button" class="back-link" @click="backToContact">← Change number</button>
          <h2 class="form-title">Verify your {{ channel === 'email' ? 'email' : 'number' }}</h2>
          <p class="form-help">
            Enter the 6-digit code we sent by {{ channel === 'email' ? 'email to' : 'SMS to' }}
            <strong>{{ channel === 'email' ? form.email : maskedPhone }}</strong>. The code is valid for 10 minutes.
          </p>

          <form @submit.prevent="verifyOtp" novalidate>
            <label class="field-label">Verification Code</label>
            <OtpInput v-model="otp" :has-error="!!otpError" @complete="verifyOtp" />
            <p class="field-error" v-if="otpError">{{ otpError }}</p>

            <button type="submit" class="btn-primary" :disabled="verifying">
              <span v-if="!verifying">Verify &amp; Continue</span>
              <span v-else>Verifying…</span>
            </button>
          </form>

          <p class="switch-auth">
            Didn't get the code?
            <button
              type="button"
              class="link-button"
              :disabled="resendSeconds > 0"
              @click="resendCode"
            >
              <span v-if="resendSeconds > 0">Resend in {{ resendSeconds }}s</span>
              <span v-else>Resend code</span>
            </button>
          </p>
        </template>

        <!-- Step 3: profile setup -->
        <template v-else-if="step === 'profile'">
          <h2 class="form-title">Complete your profile</h2>
          <p class="form-help">
            Enter your details exactly as they appear on your Ghana Card. Your date of birth and gender are read
            from the card automatically.
          </p>

          <form @submit.prevent="submitProfile" novalidate>
            <label class="field-label" for="fullName">Full Name</label>
            <input
              id="fullName"
              v-model="profile.fullName"
              type="text"
              class="text-input"
              :class="{ 'has-error': profileErrors.fullName }"
              placeholder="e.g. Ama Serwaa Mensah"
            />
            <p class="field-error" v-if="profileErrors.fullName">{{ profileErrors.fullName }}</p>

            <label class="field-label" for="ghanaCard">Ghana Card Number</label>
            <input
              id="ghanaCard"
              :value="profile.ghanaCard"
              type="text"
              class="text-input"
              :class="{ 'has-error': profileErrors.ghanaCard }"
              placeholder="GHA-XXXXXXXXX-X"
              @input="formatGhanaCard"
            />
            <p class="field-hint" v-if="!profileErrors.ghanaCard">
              Validated against the NIA database.
            </p>
            <p class="field-error" v-else>{{ profileErrors.ghanaCard }}</p>

            <p class="field-error form-error" v-if="profileFormError">{{ profileFormError }}</p>

            <button type="submit" class="btn-primary" :disabled="savingProfile">
              <span v-if="!savingProfile">Continue</span>
              <span v-else>Saving…</span>
            </button>
          </form>
        </template>

        <!-- Step 4: PIN creation -->
        <template v-else-if="step === 'pin'">
          <h2 class="form-title">Create your PIN</h2>
          <p class="form-help">
            Choose a 6-digit PIN to secure your account. You'll use it to sign in.
          </p>

          <form @submit.prevent="submitPin" novalidate>
            <label class="field-label">New PIN</label>
            <OtpInput v-model="pin" mask :has-error="!!pinError" />

            <label class="field-label">Confirm PIN</label>
            <OtpInput v-model="confirmPin" mask :has-error="!!pinError" @complete="submitPin" />
            <p class="field-error" v-if="pinError">{{ pinError }}</p>

            <button type="submit" class="btn-primary" :disabled="creatingPin">
              <span v-if="!creatingPin">Create account</span>
              <span v-else>Creating…</span>
            </button>
          </form>
        </template>
      </div>
    </div>

    <aside class="info-panel" @mouseenter="stopAutoplay" @mouseleave="startAutoplay">
      <div class="info-panel-inner">
        <div class="seal">
          <img src="/coat-of-arms.png" alt="Ghana Coat of Arms" />
        </div>

        <transition :name="reduceMotion ? '' : 'slide-fade'" mode="out-in">
          <div :key="activeSlide" class="slide">
            <h2>{{ slides[activeSlide].heading }}</h2>
            <p class="info-intro">{{ slides[activeSlide].intro }}</p>

            <div v-if="slides[activeSlide].steps" class="steps">
              <div v-for="(step, i) in slides[activeSlide].steps" :key="step" class="step">
                <span class="step-number">{{ i + 1 }}</span>
                <span>{{ step }}</span>
              </div>
            </div>

            <div v-if="slides[activeSlide].cards">
              <div v-for="card in slides[activeSlide].cards" :key="card.title" class="info-card">
                <span class="info-icon">{{ card.icon }}</span>
                <div>
                  <strong>{{ card.title }}</strong>
                  <p>{{ card.text }}</p>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <div class="flag-stripe" aria-hidden="true"></div>
        <div class="dots">
          <button
            v-for="(slide, i) in slides"
            :key="slide.heading"
            class="dot"
            :class="{ active: i === activeSlide }"
            :aria-label="`Go to slide ${i + 1}`"
            @click="goToSlide(i)"
          ></button>
        </div>
      </div>
    </aside>
    </div>
  </div>
</template>

<style scoped>
.page-shell {
  min-height: 100vh;
  background-color: #f3f4f2;
  background-image: radial-gradient(var(--border) 1px, transparent 1px);
  background-size: 28px 28px;
  display: flex;
  flex-direction: column;
}

.flag-topbar {
  height: 6px;
  width: 100%;
  background: linear-gradient(
    to right,
    var(--brand-red) 0%,
    var(--brand-red) 33.33%,
    var(--brand-gold) 33.33%,
    var(--brand-gold) 66.66%,
    var(--brand-green) 66.66%,
    var(--brand-green) 100%
  );
}

.auth-page {
  flex: 1;
  margin: 32px auto;
  width: min(1200px, calc(100% - 48px));
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.18);
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

@media (max-width: 900px) {
  .auth-page {
    grid-template-columns: 1fr;
  }
}

.auth-panel {
  background: var(--surface);
  display: flex;
  flex-direction: column;
  padding: 48px 64px;
}

@media (max-width: 600px) {
  .auth-panel {
    padding: 32px 24px;
  }
}

.brand {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 48px;
}

.brand-logo {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: 1px solid var(--border);
  object-fit: cover;
}

.brand-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
}

.brand-subtitle {
  margin: 2px 0 0;
  font-size: 13px;
  color: var(--text-muted);
}

.form-wrap {
  max-width: 440px;
  width: 100%;
  margin: 0 auto;
}

.form-title {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
}

.form-help {
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 32px;
}

.field-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
  margin-top: 20px;
}

.field-label:first-of-type {
  margin-top: 0;
}

.phone-input {
  display: flex;
  align-items: center;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--surface);
}

.phone-input:focus-within {
  border-color: var(--brand-green);
  box-shadow: 0 0 0 3px rgba(0, 107, 63, 0.12);
}

.phone-input.has-error {
  border-color: var(--danger);
}

.phone-prefix {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px 14px;
  border-right: 1px solid var(--border);
  color: var(--text-primary);
  font-size: 14px;
  white-space: nowrap;
}

.phone-input input {
  border: none;
  outline: none;
  padding: 12px 14px;
  font-size: 14px;
  width: 100%;
}

.text-input {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  font-size: 14px;
  outline: none;
}

.text-input:focus {
  border-color: var(--brand-green);
  box-shadow: 0 0 0 3px rgba(0, 107, 63, 0.12);
}

.text-input.has-error {
  border-color: var(--danger);
}

.field-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--text-muted);
}

.field-error {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--danger);
}

.form-error {
  margin-top: 20px;
  text-align: center;
}

.channel-toggle {
  display: flex;
  gap: 8px;
}

.channel-option {
  flex: 1;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  font-size: 14px;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  transition: border-color 150ms ease, color 150ms ease, background 150ms ease;
}

.channel-option.active {
  border-color: var(--brand-green);
  color: var(--brand-green);
  background: rgba(0, 107, 63, 0.08);
}

.btn-primary {
  width: 100%;
  margin-top: 32px;
  background: var(--brand-green);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 14px 16px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 150ms ease;
}

.btn-primary:hover:not(:disabled) {
  background: var(--brand-green-dark);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.switch-auth {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: var(--text-muted);
}

.switch-auth a {
  color: var(--brand-green);
  font-weight: 600;
  text-decoration: none;
}

.link-button {
  background: none;
  border: none;
  padding: 0;
  color: var(--brand-green);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
}

.link-button:disabled {
  color: var(--text-muted);
  cursor: not-allowed;
}

.back-link {
  background: none;
  border: none;
  padding: 0;
  margin-bottom: 16px;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.back-link:hover {
  color: var(--brand-green);
}

.info-panel {
  background: var(--brand-green);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;
  position: relative;
}

@media (max-width: 900px) {
  .info-panel {
    display: none;
  }
}

.info-panel-inner {
  max-width: 420px;
  text-align: center;
}

.seal {
  width: 140px;
  height: 140px;
  margin: 0 auto 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(255, 255, 255, 0.06);
}

.seal img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.info-panel-inner h2 {
  font-size: 24px;
  margin-bottom: 12px;
}

.info-intro {
  color: rgba(255, 255, 255, 0.85);
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 32px;
}

.info-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  text-align: left;
  background: rgba(255, 255, 255, 0.08);
  border-radius: var(--radius);
  padding: 16px;
  margin-bottom: 16px;
}

.info-card strong {
  display: block;
  font-size: 14px;
  margin-bottom: 4px;
}

.info-card p {
  margin: 0;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.75);
}

.info-icon {
  font-size: 20px;
  line-height: 1;
}

.flag-stripe {
  height: 4px;
  border-radius: 4px;
  margin-top: 8px;
  background: linear-gradient(
    to right,
    var(--brand-red) 0%,
    var(--brand-red) 33%,
    var(--brand-gold) 33%,
    var(--brand-gold) 66%,
    var(--brand-green) 66%,
    var(--brand-green) 100%
  );
}

.dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
}

.dot {
  width: 8px;
  height: 8px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: width 200ms ease, border-radius 200ms ease, background 200ms ease;
}

.dot.active {
  width: 20px;
  border-radius: 4px;
  background: #fff;
}

.slide {
  min-height: 220px;
}

.steps {
  display: flex;
  flex-direction: column;
  gap: 12px;
  text-align: left;
}

.step {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: var(--radius);
  padding: 16px;
  font-size: 14px;
  font-weight: 600;
}

.step-number {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 300ms ease, transform 300ms ease;
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
