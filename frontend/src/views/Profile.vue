<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const router = useRouter()

// Mock citizen data — replace with auth store in production
const profile = ref({
  fullName: 'Enam Kadeh Arnold',
  phone: '+233 24 123 4567',
  email: 'enam.kadeh@email.com',
  ghanaCard: 'GHA-123456789-0',
  gender: 'Male',
  dob: '1990-04-15',
  district: 'Greater Accra',
  accountCreated: '2025-07-01',
  lastLogin: '2026-07-14 at 09:32 AM',
  niaVerified: true,
})

const editing = ref(false)
const editEmail = ref(profile.value.email)
const editDistrict = ref(profile.value.district)

const showPinModal = ref(false)
const pinStep = ref(1) // 1 = current pin, 2 = new pin, 3 = confirm
const currentPin = ref('')
const newPin = ref('')
const confirmPin = ref('')
const pinError = ref('')

function saveProfile() {
  profile.value.email = editEmail.value
  profile.value.district = editDistrict.value
  editing.value = false
}

function cancelEdit() {
  editEmail.value = profile.value.email
  editDistrict.value = profile.value.district
  editing.value = false
}

function openPinModal() {
  showPinModal.value = true
  pinStep.value = 1
  currentPin.value = ''
  newPin.value = ''
  confirmPin.value = ''
  pinError.value = ''
}

function nextPinStep() {
  pinError.value = ''
  if (pinStep.value === 1) {
    if (currentPin.value.length !== 6) { pinError.value = 'PIN must be 6 digits'; return }
    pinStep.value = 2
  } else if (pinStep.value === 2) {
    if (newPin.value.length !== 6) { pinError.value = 'PIN must be 6 digits'; return }
    pinStep.value = 3
  } else if (pinStep.value === 3) {
    if (confirmPin.value !== newPin.value) { pinError.value = 'PINs do not match'; return }
    showPinModal.value = false
    alert('PIN updated successfully.')
  }
}

function handleLogout() {
  router.push('/login')
}

function maskGhanaCard(card) {
  // Show first 4 and last 2, mask middle
  return card.replace(/GHA-(\d{3})(\d{6})-(\d)/, 'GHA-$1••••••-$3')
}

const districts = [
  'Greater Accra', 'Ashanti Region', 'Western Region', 'Eastern Region',
  'Central Region', 'Northern Region', 'Upper East', 'Upper West',
  'Volta Region', 'Brong-Ahafo', 'Bono East', 'Ahafo', 'Savannah',
  'North East', 'Western North', 'Oti Region',
]
</script>

<template>
  <CitizenLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your account details and security settings.</p>
      </div>
    </div>

    <!-- Profile Hero -->
    <div class="profile-hero">
      <div class="avatar-large">{{ profile.fullName.split(' ').map(n => n[0]).slice(0, 2).join('') }}</div>
      <div class="profile-hero-info">
        <h2 class="profile-name">{{ profile.fullName }}</h2>
        <p class="profile-phone">{{ profile.phone }}</p>
        <div class="profile-badges">
          <span class="badge-nia" v-if="profile.niaVerified">
            <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
            NIA Verified
          </span>
          <span class="badge-citizen">Verified Citizen</span>
        </div>
        <p class="profile-since">Member since {{ profile.accountCreated }} · Last login {{ profile.lastLogin }}</p>
      </div>
      <button v-if="!editing" class="btn-ghost" @click="editing = true">Edit Profile</button>
    </div>

    <!-- Main Grid -->
    <div class="profile-grid">

      <!-- Personal Details -->
      <div class="card">
        <div class="card-title-row">
          <h2 class="card-title">Personal Details</h2>
        </div>

        <div v-if="!editing" class="detail-grid">
          <div class="detail-item">
            <p class="detail-label">Full Name</p>
            <p class="detail-value">{{ profile.fullName }}</p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Gender</p>
            <p class="detail-value">{{ profile.gender }}</p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Date of Birth</p>
            <p class="detail-value">{{ profile.dob }}</p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Phone Number</p>
            <p class="detail-value">{{ profile.phone }}</p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Email Address</p>
            <p class="detail-value">{{ profile.email || 'Not provided' }}</p>
          </div>
          <div class="detail-item">
            <p class="detail-label">District</p>
            <p class="detail-value">{{ profile.district }}</p>
          </div>
        </div>

        <div v-else class="edit-form">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input class="form-input disabled" :value="profile.fullName" disabled />
              <p class="form-hint">Name is linked to your Ghana Card and cannot be changed here.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input class="form-input disabled" :value="profile.phone" disabled />
              <p class="form-hint">Phone number is your login credential and cannot be changed here.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email Address <span class="optional">(Optional)</span></label>
              <input v-model="editEmail" class="form-input" type="email" placeholder="your@email.com" />
            </div>
            <div class="form-group">
              <label class="form-label">District</label>
              <select v-model="editDistrict" class="form-input">
                <option v-for="d in districts" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>
          </div>
          <div class="edit-actions">
            <button class="btn-ghost btn-sm" @click="cancelEdit">Cancel</button>
            <button class="btn-primary btn-sm" @click="saveProfile">Save Changes</button>
          </div>
        </div>
      </div>

      <!-- Ghana Card -->
      <div class="card">
        <h2 class="card-title">Ghana Card & NIA Verification</h2>
        <div class="nia-card">
          <div class="nia-card-left">
            <div class="nia-card-flag">
              <div class="flag-stripe red"></div>
              <div class="flag-stripe gold"></div>
              <div class="flag-stripe green"></div>
            </div>
            <div>
              <p class="nia-card-label">Ghana Card Number</p>
              <p class="nia-card-number">{{ maskGhanaCard(profile.ghanaCard) }}</p>
            </div>
          </div>
          <div :class="['nia-status', profile.niaVerified ? 'nia-ok' : 'nia-pending']">
            <span v-if="profile.niaVerified">
              <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
              Verified
            </span>
            <span v-else>⏳ Pending Verification</span>
          </div>
        </div>
        <p class="nia-note">
          Your Ghana Card was verified against the National Identification Authority (NIA) database during registration.
          Your name, date of birth, and gender are sourced from the NIA record.
        </p>
      </div>

    </div>

    <!-- Security -->
    <div class="card security-card">
      <h2 class="card-title">Security</h2>
      <div class="security-list">
        <div class="security-item">
          <div class="security-item-info">
            <div class="security-icon">🔑</div>
            <div>
              <p class="security-label">6-Digit PIN</p>
              <p class="security-desc">Used for quick access on trusted devices. Change regularly for security.</p>
            </div>
          </div>
          <button class="btn-ghost btn-sm" @click="openPinModal">Change PIN</button>
        </div>
        <div class="security-item">
          <div class="security-item-info">
            <div class="security-icon">📱</div>
            <div>
              <p class="security-label">Two-Factor Authentication</p>
              <p class="security-desc">OTP via SMS to {{ profile.phone }} is active on every login.</p>
            </div>
          </div>
          <span class="badge-active">Active</span>
        </div>
        <div class="security-item">
          <div class="security-item-info">
            <div class="security-icon">🕐</div>
            <div>
              <p class="security-label">Last Login</p>
              <p class="security-desc">{{ profile.lastLogin }} · Greater Accra, Ghana</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Logout -->
    <div class="logout-section">
      <button class="btn-logout" @click="handleLogout">
        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M16 17v-2h-6v-6h6V7l5 5zM4 4h8v2H4v12h8v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
        Sign Out
      </button>
      <p class="logout-note">You will be returned to the login screen.</p>
    </div>

    <!-- PIN Change Modal -->
    <div v-if="showPinModal" class="modal-backdrop" @click.self="showPinModal = false">
      <div class="modal">
        <h2>Change Your PIN</h2>
        <p>Step {{ pinStep }} of 3 — {{ pinStep === 1 ? 'Confirm current PIN' : pinStep === 2 ? 'Enter new PIN' : 'Confirm new PIN' }}</p>

        <div class="pin-input-wrap">
          <input
            v-if="pinStep === 1"
            v-model="currentPin"
            class="pin-input"
            type="password"
            inputmode="numeric"
            maxlength="6"
            placeholder="••••••"
            @keyup.enter="nextPinStep"
          />
          <input
            v-else-if="pinStep === 2"
            v-model="newPin"
            class="pin-input"
            type="password"
            inputmode="numeric"
            maxlength="6"
            placeholder="••••••"
            @keyup.enter="nextPinStep"
          />
          <input
            v-else
            v-model="confirmPin"
            class="pin-input"
            type="password"
            inputmode="numeric"
            maxlength="6"
            placeholder="••••••"
            @keyup.enter="nextPinStep"
          />
          <p v-if="pinError" class="pin-error">{{ pinError }}</p>
        </div>

        <div class="modal-actions">
          <button class="btn-ghost btn-sm" @click="showPinModal = false">Cancel</button>
          <button class="btn-primary btn-sm" @click="nextPinStep">
            {{ pinStep < 3 ? 'Next' : 'Update PIN' }}
          </button>
        </div>
      </div>
    </div>
  </CitizenLayout>
</template>

<style scoped>
.page-header {
  margin-bottom: 28px;
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

/* Profile Hero */
.profile-hero {
  display: flex;
  align-items: center;
  gap: 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--brand-green);
  color: #fff;
  font-family: var(--font-heading);
  font-size: 28px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.profile-hero-info {
  flex: 1;
  min-width: 0;
}

.profile-name {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 4px;
}

.profile-phone {
  font-size: 15px;
  color: var(--text-muted);
  margin: 0 0 10px;
}

.profile-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.badge-nia {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 4px;
}

.badge-citizen {
  display: inline-flex;
  align-items: center;
  background: rgba(26, 37, 55, 0.08);
  color: #1a2537;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 4px;
}

.profile-since {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

/* Card */
.profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

@media (max-width: 900px) {
  .profile-grid { grid-template-columns: 1fr; }
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
}

.card-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 20px;
}

.card-title-row .card-title { margin: 0; }

/* Detail Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 600px) {
  .detail-grid { grid-template-columns: 1fr; }
}

.detail-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin: 0 0 4px;
}

.detail-value {
  font-size: 15px;
  color: var(--text-primary);
  margin: 0;
  font-weight: 500;
}

/* Edit Form */
.edit-form {}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

@media (max-width: 600px) {
  .form-row { grid-template-columns: 1fr; }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
}

.optional {
  font-size: 11px;
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
}

.form-input {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  font-size: 14px;
  font-family: var(--font-body);
  background: var(--background);
  color: var(--text-primary);
  outline: none;
  transition: border-color 150ms;
}

.form-input:focus { border-color: var(--brand-green); }
.form-input.disabled { background: var(--surface); color: var(--text-muted); cursor: not-allowed; }

.form-hint {
  font-size: 11px;
  color: var(--text-muted);
  margin: 0;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

/* Ghana Card */
.nia-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  margin-bottom: 16px;
  gap: 12px;
  flex-wrap: wrap;
}

.nia-card-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.nia-card-flag {
  display: flex;
  flex-direction: column;
  width: 8px;
  height: 32px;
  border-radius: 2px;
  overflow: hidden;
  flex-shrink: 0;
}

.flag-stripe { flex: 1; }
.flag-stripe.red { background: var(--brand-red); }
.flag-stripe.gold { background: var(--brand-gold); }
.flag-stripe.green { background: var(--brand-green); }

.nia-card-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin: 0 0 4px;
}

.nia-card-number {
  font-family: monospace;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: 0.04em;
}

.nia-status {
  font-size: 13px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.nia-ok { background: rgba(0, 107, 63, 0.1); color: var(--brand-green); }
.nia-pending { background: rgba(252, 209, 22, 0.15); color: #a67c00; }

.nia-note {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.6;
}

/* Security */
.security-card {
  margin-bottom: 20px;
}

.security-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.security-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid var(--border);
  gap: 16px;
  flex-wrap: wrap;
}

.security-item:last-child { border-bottom: none; padding-bottom: 0; }
.security-item:first-child { padding-top: 0; }

.security-item-info {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
}

.security-icon {
  font-size: 22px;
  flex-shrink: 0;
}

.security-label {
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px;
  color: var(--text-primary);
}

.security-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

.badge-active {
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 4px;
  background: rgba(0, 107, 63, 0.1);
  color: var(--brand-green);
  white-space: nowrap;
}

/* Logout */
.logout-section {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  flex-wrap: wrap;
}

.btn-logout {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 1px solid var(--brand-red);
  color: var(--brand-red);
  border-radius: var(--radius);
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 150ms;
}

.btn-logout:hover {
  background: rgba(206, 17, 38, 0.06);
}

.logout-note {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

/* Buttons */
.btn-primary {
  background: var(--brand-green);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 150ms;
}

.btn-primary:hover { background: var(--brand-green-dark); }

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  color: var(--text-muted);
  transition: border-color 150ms;
}

.btn-ghost:hover {
  border-color: var(--brand-green);
  color: var(--brand-green);
}

.btn-sm { padding: 8px 16px; font-size: 13px; }

/* PIN Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.modal {
  background: #fff;
  border-radius: var(--radius-lg);
  padding: 32px;
  max-width: 380px;
  width: 100%;
}

.modal h2 {
  font-size: 20px;
  margin: 0 0 8px;
}

.modal > p {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0 0 24px;
}

.pin-input-wrap {
  margin-bottom: 24px;
}

.pin-input {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
  font-size: 24px;
  text-align: center;
  letter-spacing: 0.5em;
  outline: none;
  font-family: monospace;
  box-sizing: border-box;
}

.pin-input:focus { border-color: var(--brand-green); }

.pin-error {
  font-size: 13px;
  color: var(--brand-red);
  margin: 8px 0 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
