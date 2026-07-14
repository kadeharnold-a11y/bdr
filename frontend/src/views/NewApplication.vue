<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const router = useRouter()

// Application State
const currentStep = ref('event-catalog') // event-catalog | tier-selection | form-subject | form-parent | document-upload | review | payment | success
const saving = ref(false)

const application = reactive({
  eventType: '',
  tier: '',
  childFirstName: '',
  childLastName: '',
  dob: '',
  gender: '',
  motherFullName: '',
  motherGhanaCard: '',
  fatherFullName: '',
  documentsUploaded: false,
  declarationAccepted: false,
  trackingId: '',
})

const errors = reactive({})

// Event Catalog
const events = [
  { id: 'EB', title: 'Early Birth Registration', desc: 'Within 12 months of birth', icon: '👶' },
  { id: 'LB', title: 'Late Birth Registration', desc: 'Over 12 months from birth', icon: '📝' },
  { id: 'DR', title: 'Death Registration', desc: 'Register a death before burial', icon: '🕊️' },
  { id: 'FD', title: 'Foetal Death', desc: 'Stillbirth at or after 28 weeks', icon: '🏥' },
  { id: 'AD', title: 'Adoption', desc: 'Register an adopted child', icon: '👨‍👩‍👧' },
  { id: 'SR', title: 'Surrogacy', desc: 'Assisted reproductive technology', icon: '🤝' },
]

function selectEvent(id) {
  application.eventType = id
  currentStep.value = 'tier-selection'
}

// Tier Selection
function selectTier(tier) {
  application.tier = tier
  currentStep.value = 'form-subject'
}

// Navigation
function nextStep(target) {
  // Clear previous errors
  for (let key in errors) errors[key] = ''

  // Validate current step
  if (currentStep.value === 'form-subject') {
    if (!application.childFirstName) errors.childFirstName = 'Required'
    if (!application.childLastName) errors.childLastName = 'Required'
    if (!application.dob) errors.dob = 'Required'
    if (!application.gender) errors.gender = 'Required'
    if (Object.keys(errors).some(k => errors[k])) return
  } else if (currentStep.value === 'form-parent') {
    if (!application.motherFullName) errors.motherFullName = 'Required'
    if (!/^GHA-\d{9}-\d$/.test(application.motherGhanaCard)) errors.motherGhanaCard = 'Valid Ghana Card required (GHA-XXXXXXXXX-X)'
    if (Object.keys(errors).some(k => errors[k])) return
  } else if (currentStep.value === 'document-upload') {
    if (!application.documentsUploaded) {
      alert('Please upload the required documents.')
      return
    }
  } else if (currentStep.value === 'review') {
    if (!application.declarationAccepted) {
      alert('You must accept the declaration to proceed.')
      return
    }
  }

  currentStep.value = target
  window.scrollTo(0, 0)
}

function prevStep(target) {
  currentStep.value = target
  window.scrollTo(0, 0)
}

function saveForLater() {
  saving.value = true
  setTimeout(() => {
    saving.value = false
    alert('Draft saved successfully! You can resume from your dashboard.')
    router.push('/dashboard')
  }, 1000)
}

// Mock Upload
const uploading = ref(false)
function mockUpload() {
  uploading.value = true
  setTimeout(() => {
    uploading.value = false
    application.documentsUploaded = true
  }, 1500)
}

// Payment & Submission
const processingPayment = ref(false)
function submitPayment() {
  processingPayment.value = true
  setTimeout(() => {
    processingPayment.value = false
    application.trackingId = `BDR-2026-${application.eventType}-00${Math.floor(Math.random() * 10000)}`
    currentStep.value = 'success'
  }, 2000)
}

// Helpers
const fee = computed(() => application.tier === 'Express' ? '150.00' : '50.00')

</script>

<template>
  <CitizenLayout>
    <div class="wizard-container">
      
      <!-- STEP 0: Event Catalog -->
      <div v-if="currentStep === 'event-catalog'" class="step-view">
        <h1 class="page-title">New Registration</h1>
        <p class="page-subtitle">Select the type of vital event you want to register.</p>

        <div class="event-grid">
          <button 
            v-for="evt in events" 
            :key="evt.id" 
            class="event-card"
            @click="selectEvent(evt.id)"
          >
            <span class="event-icon">{{ evt.icon }}</span>
            <div class="event-info">
              <h3>{{ evt.title }}</h3>
              <p>{{ evt.desc }}</p>
            </div>
            <span class="chevron">›</span>
          </button>
        </div>
      </div>

      <!-- STEP 1: Tier Selection -->
      <div v-else-if="currentStep === 'tier-selection'" class="step-view">
        <button class="back-link" @click="prevStep('event-catalog')">← Back to Events</button>
        <h1 class="page-title">Choose Service Tier</h1>
        <p class="page-subtitle">Select how quickly you need the certificate processed.</p>

        <div class="tier-grid">
          <div class="tier-card standard">
            <div class="tier-header">
              <h2>Standard Service</h2>
              <p class="price">GHS 50.00</p>
            </div>
            <ul class="tier-features">
              <li><span class="check">✓</span> 15 working days processing</li>
              <li><span class="check">✓</span> Standard queue priority</li>
              <li><span class="check">✓</span> SMS status updates</li>
              <li><span class="check">✓</span> Collection at BDR office</li>
            </ul>
            <button class="btn-outline" @click="selectTier('Standard')">Select Standard</button>
          </div>

          <div class="tier-card express">
            <div class="tier-header">
              <div class="badge-express">Priority</div>
              <h2>Express Service</h2>
              <p class="price">GHS 150.00</p>
            </div>
            <ul class="tier-features">
              <li><span class="check">✓</span> 3 working days processing (Guaranteed)</li>
              <li><span class="check">✓</span> Dedicated Express queue</li>
              <li><span class="check">✓</span> Real-time dashboard + SMS</li>
              <li><span class="check">✓</span> Priority doorstep delivery included</li>
            </ul>
            <button class="btn-primary" @click="selectTier('Express')">Select Express</button>
          </div>
        </div>
      </div>

      <!-- STEP 2: Subject Details -->
      <div v-else-if="currentStep === 'form-subject'" class="step-view form-layout">
        <div class="form-header">
          <button class="back-link" @click="prevStep('tier-selection')">← Change Tier</button>
          <div class="progress-indicator">Step 1 of 4 — Child's Information</div>
        </div>

        <div class="card">
          <h2 class="card-title">Child's Details</h2>
          <div class="form-grid">
            <div class="field-group">
              <label>First Name</label>
              <input v-model="application.childFirstName" type="text" class="input" :class="{'has-error': errors.childFirstName}" />
              <span class="error-msg" v-if="errors.childFirstName">{{ errors.childFirstName }}</span>
            </div>
            <div class="field-group">
              <label>Last Name / Surname</label>
              <input v-model="application.childLastName" type="text" class="input" :class="{'has-error': errors.childLastName}" />
              <span class="error-msg" v-if="errors.childLastName">{{ errors.childLastName }}</span>
            </div>
            <div class="field-group">
              <label>Date of Birth</label>
              <input v-model="application.dob" type="date" class="input" :class="{'has-error': errors.dob}" />
              <span class="error-msg" v-if="errors.dob">{{ errors.dob }}</span>
            </div>
            <div class="field-group">
              <label>Gender</label>
              <select v-model="application.gender" class="input" :class="{'has-error': errors.gender}">
                <option value="" disabled>Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
              <span class="error-msg" v-if="errors.gender">{{ errors.gender }}</span>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-ghost" @click="saveForLater" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save for Later' }}
          </button>
          <button class="btn-primary" @click="nextStep('form-parent')">Continue</button>
        </div>
      </div>

      <!-- STEP 3: Parent Details -->
      <div v-else-if="currentStep === 'form-parent'" class="step-view form-layout">
        <div class="form-header">
          <button class="back-link" @click="prevStep('form-subject')">← Back to Child Info</button>
          <div class="progress-indicator">Step 2 of 4 — Parents' Information</div>
        </div>

        <div class="card">
          <h2 class="card-title">Mother's Details</h2>
          <div class="form-grid">
            <div class="field-group">
              <label>Full Name</label>
              <input v-model="application.motherFullName" type="text" class="input" :class="{'has-error': errors.motherFullName}" />
              <span class="error-msg" v-if="errors.motherFullName">{{ errors.motherFullName }}</span>
            </div>
            <div class="field-group">
              <label>Ghana Card Number</label>
              <input v-model="application.motherGhanaCard" type="text" placeholder="GHA-XXXXXXXXX-X" class="input" :class="{'has-error': errors.motherGhanaCard}" />
              <span class="error-msg" v-if="errors.motherGhanaCard">{{ errors.motherGhanaCard }}</span>
            </div>
          </div>

          <hr class="divider" />

          <h2 class="card-title">Father's Details (Optional)</h2>
          <div class="form-grid">
            <div class="field-group">
              <label>Full Name</label>
              <input v-model="application.fatherFullName" type="text" class="input" />
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-ghost" @click="saveForLater" :disabled="saving">Save for Later</button>
          <button class="btn-primary" @click="nextStep('document-upload')">Continue</button>
        </div>
      </div>

      <!-- STEP 4: Document Upload -->
      <div v-else-if="currentStep === 'document-upload'" class="step-view form-layout">
        <div class="form-header">
          <button class="back-link" @click="prevStep('form-parent')">← Back to Parent Info</button>
          <div class="progress-indicator">Step 3 of 4 — Supporting Documents</div>
        </div>

        <div class="card">
          <h2 class="card-title">Upload Documents</h2>
          <p class="text-muted">Accepted formats: PDF, JPG, PNG (Max 5MB each).</p>
          
          <div class="upload-zone" :class="{'is-uploaded': application.documentsUploaded}">
            <template v-if="!application.documentsUploaded">
              <div v-if="uploading" class="spinner"></div>
              <div v-else>
                <div class="upload-icon">📄</div>
                <p><strong>Clinical Record of Birth</strong></p>
                <button class="btn-outline" style="margin-top: 16px;" @click="mockUpload">Select File</button>
              </div>
            </template>
            <template v-else>
              <div class="success-icon">✓</div>
              <p><strong>Clinical_Record.pdf</strong> uploaded successfully.</p>
              <button class="btn-ghost" @click="application.documentsUploaded = false">Remove</button>
            </template>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-ghost" @click="saveForLater" :disabled="saving">Save for Later</button>
          <button class="btn-primary" @click="nextStep('review')">Review Application</button>
        </div>
      </div>

      <!-- STEP 5: Review -->
      <div v-else-if="currentStep === 'review'" class="step-view form-layout">
        <div class="form-header">
          <button class="back-link" @click="prevStep('document-upload')">← Back to Uploads</button>
          <div class="progress-indicator">Step 4 of 4 — Review & Submit</div>
        </div>

        <div class="card review-card">
          <div class="review-header">
            <h2 class="card-title">Application Summary</h2>
            <span class="badge">{{ application.tier }} Tier</span>
          </div>

          <div class="review-section">
            <h3>Child Details <button class="link-btn" @click="prevStep('form-subject')">Edit</button></h3>
            <p><strong>Name:</strong> {{ application.childFirstName }} {{ application.childLastName }}</p>
            <p><strong>DOB:</strong> {{ application.dob }}</p>
            <p><strong>Gender:</strong> {{ application.gender }}</p>
          </div>

          <div class="review-section">
            <h3>Parent Details <button class="link-btn" @click="prevStep('form-parent')">Edit</button></h3>
            <p><strong>Mother:</strong> {{ application.motherFullName }} ({{ application.motherGhanaCard }})</p>
            <p v-if="application.fatherFullName"><strong>Father:</strong> {{ application.fatherFullName }}</p>
          </div>

          <div class="declaration">
            <label class="checkbox-label">
              <input type="checkbox" v-model="application.declarationAccepted" />
              <span>I confirm the information provided is true and accurate to the best of my knowledge. I understand that providing false information is an offense under Act 1027 (2020).</span>
            </label>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-ghost" @click="saveForLater" :disabled="saving">Save for Later</button>
          <button class="btn-primary" @click="nextStep('payment')" :disabled="!application.declarationAccepted">Proceed to Payment</button>
        </div>
      </div>

      <!-- STEP 6: Payment -->
      <div v-else-if="currentStep === 'payment'" class="step-view form-layout">
        <div class="form-header">
          <button class="back-link" @click="prevStep('review')">← Back to Review</button>
        </div>

        <div class="payment-card card">
          <div class="payment-header">
            <h2>Complete Payment</h2>
            <div class="secure-badge">🔒 Secure Npontu Pay</div>
          </div>

          <div class="fee-summary">
            <div class="fee-row">
              <span>{{ application.tier }} Service Fee</span>
              <span>GHS {{ fee }}</span>
            </div>
            <div class="fee-row total">
              <span>Total to Pay</span>
              <span>GHS {{ fee }}</span>
            </div>
          </div>

          <div class="payment-methods">
            <label class="method-option">
              <input type="radio" name="pay-method" checked />
              <div class="method-content">
                <strong>Mobile Money</strong>
                <p>MTN, Vodafone, AirtelTigo</p>
              </div>
            </label>
            <label class="method-option">
              <input type="radio" name="pay-method" />
              <div class="method-content">
                <strong>Credit/Debit Card</strong>
                <p>Visa, Mastercard</p>
              </div>
            </label>
          </div>

          <button class="btn-primary payment-btn" @click="submitPayment" :disabled="processingPayment">
            <span v-if="!processingPayment">Pay GHS {{ fee }}</span>
            <span v-else>Processing securely...</span>
          </button>
        </div>
      </div>

      <!-- STEP 7: Success -->
      <div v-else-if="currentStep === 'success'" class="step-view success-layout">
        <div class="card text-center">
          <div class="success-circle">✓</div>
          <h1 class="card-title">Application Submitted Successfully!</h1>
          <p class="text-muted">Your payment was confirmed and your application has entered the BDR processing queue.</p>
          
          <div class="tracking-box">
            <p>Your Tracking ID</p>
            <h3>{{ application.trackingId }}</h3>
          </div>

          <p class="text-sm">An SMS receipt and confirmation have been sent to your registered phone number. You can track this application from your dashboard.</p>
          
          <div class="success-actions">
            <RouterLink to="/dashboard" class="btn-primary">Go to Dashboard</RouterLink>
          </div>
        </div>
      </div>

    </div>
  </CitizenLayout>
</template>

<style scoped>
.wizard-container {
  max-width: 800px;
  margin: 0 auto;
}

.page-title {
  font-family: var(--font-heading);
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 8px;
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin: 0 0 32px;
}

.back-link {
  background: none;
  border: none;
  color: var(--text-muted);
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-bottom: 24px;
}
.back-link:hover {
  color: var(--brand-green);
}

/* Event Grid */
.event-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.event-card {
  display: flex;
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  text-align: left;
  cursor: pointer;
  transition: all 150ms ease;
}

.event-card:hover {
  border-color: var(--brand-green);
  box-shadow: 0 4px 12px rgba(0, 107, 63, 0.08);
}

.event-icon {
  font-size: 32px;
  margin-right: 16px;
}

.event-info h3 {
  font-size: 16px;
  margin: 0 0 4px;
  font-weight: 600;
  color: var(--text-primary);
}

.event-info p {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

.chevron {
  margin-left: auto;
  font-size: 24px;
  color: var(--text-muted);
}

/* Tier Grid */
.tier-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

@media (max-width: 600px) {
  .tier-grid { grid-template-columns: 1fr; }
}

.tier-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 32px;
  display: flex;
  flex-direction: column;
}

.tier-card.express {
  border-color: var(--brand-gold);
  background: #fffcf0;
  position: relative;
}

.badge-express {
  position: absolute;
  top: -12px;
  left: 32px;
  background: var(--brand-gold);
  color: #4a3800;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 4px 12px;
  border-radius: 9999px;
}

.tier-header h2 {
  font-size: 20px;
  margin: 0 0 8px;
}

.price {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 24px;
  font-family: var(--font-heading);
}

.tier-features {
  list-style: none;
  padding: 0;
  margin: 0 0 32px;
  flex: 1;
}

.tier-features li {
  margin-bottom: 12px;
  font-size: 14px;
  color: var(--text-muted);
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.check {
  color: var(--brand-green);
  font-weight: 700;
}

/* Form Layout */
.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.progress-indicator {
  font-size: 13px;
  font-weight: 600;
  color: var(--brand-green);
  background: rgba(0, 107, 63, 0.1);
  padding: 4px 12px;
  border-radius: 9999px;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 32px;
  margin-bottom: 24px;
}

.card-title {
  font-size: 20px;
  margin: 0 0 24px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
}

.input {
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 15px;
  background: var(--background);
  outline: none;
}
.input:focus {
  border-color: var(--brand-green);
}
.input.has-error {
  border-color: var(--danger);
}
.error-msg {
  color: var(--danger);
  font-size: 12px;
}

.divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 32px 0;
}

.form-actions {
  display: flex;
  justify-content: space-between;
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
}
.btn-primary:disabled { opacity: 0.7; }
.btn-primary:hover:not(:disabled) { background: var(--brand-green-dark); }

.btn-outline {
  background: transparent;
  color: var(--text-primary);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.btn-ghost {
  background: transparent;
  color: var(--text-muted);
  border: none;
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

/* Document Upload */
.text-muted { color: var(--text-muted); font-size: 14px; }
.upload-zone {
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  padding: 48px;
  text-align: center;
  margin-top: 16px;
  background: var(--background);
  transition: all 150ms ease;
}
.upload-zone.is-uploaded {
  border-color: var(--brand-green);
  border-style: solid;
  background: rgba(0, 107, 63, 0.05);
}
.upload-icon { font-size: 40px; margin-bottom: 16px; }
.success-icon { font-size: 32px; color: var(--brand-green); margin-bottom: 12px; font-weight: bold; }
.spinner { width: 32px; height: 32px; border: 3px solid var(--border); border-top-color: var(--brand-green); border-radius: 50%; margin: 0 auto; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Review */
.review-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 24px; }
.badge { background: var(--brand-green); color: #fff; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.review-section { margin-bottom: 24px; }
.review-section h3 { font-size: 16px; margin: 0 0 12px; display: flex; justify-content: space-between; }
.link-btn { background: none; border: none; color: var(--brand-green); font-weight: 600; cursor: pointer; }
.review-section p { margin: 0 0 8px; font-size: 14px; }
.declaration { background: var(--background); padding: 16px; border-radius: var(--radius); margin-top: 32px; }
.checkbox-label { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; cursor: pointer; line-height: 1.5; }

/* Payment */
.payment-card { max-width: 500px; margin: 0 auto; }
.payment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
.payment-header h2 { margin: 0; font-size: 20px; }
.secure-badge { font-size: 12px; color: var(--brand-green); font-weight: 600; background: rgba(0,107,63,0.1); padding: 4px 8px; border-radius: 4px; }
.fee-summary { background: var(--background); padding: 24px; border-radius: var(--radius); margin-bottom: 24px; }
.fee-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: var(--text-muted); }
.fee-row.total { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 0; border-top: 1px solid var(--border); padding-top: 12px; }
.payment-methods { display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px; }
.method-option { display: flex; align-items: center; gap: 16px; border: 1px solid var(--border); padding: 16px; border-radius: var(--radius); cursor: pointer; }
.method-option:has(input:checked) { border-color: var(--brand-green); background: rgba(0,107,63,0.05); }
.method-content strong { display: block; font-size: 15px; margin-bottom: 4px; }
.method-content p { margin: 0; font-size: 13px; color: var(--text-muted); }
.payment-btn { width: 100%; padding: 16px; font-size: 16px; }

/* Success */
.text-center { text-align: center; padding: 48px 32px; }
.success-circle { width: 64px; height: 64px; background: var(--brand-green); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; }
.tracking-box { background: var(--background); padding: 24px; border-radius: var(--radius); border: 1px dashed var(--border); margin: 32px 0; }
.tracking-box p { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); }
.tracking-box h3 { margin: 0; font-size: 24px; font-family: monospace; letter-spacing: 0.05em; }
.text-sm { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 32px; }
</style>
