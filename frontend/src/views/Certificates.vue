<script setup>
import { ref, computed } from 'vue'
import CitizenLayout from '../layouts/CitizenLayout.vue'

const filterType = ref('')

const certTypeOptions = [
  { value: '', label: 'All Certificate Types' },
  { value: 'EB', label: 'Birth Certificate' },
  { value: 'LB', label: 'Late Birth Certificate' },
  { value: 'DR', label: 'Death Certificate' },
  { value: 'FD', label: 'Foetal Death Certificate' },
  { value: 'AD', label: 'Adoption Certificate' },
  { value: 'SR', label: 'Surrogacy Certificate' },
]

const certificates = [
  {
    serial: 'CERT-2026-EB-004821',
    eventCode: 'EB',
    eventLabel: 'Birth Certificate',
    applicationId: 'BDR-2026-EB-004821',
    subject: 'Kofi Asante',
    dateOfEvent: '2026-06-15',
    dateIssued: '2026-07-14',
    district: 'Greater Accra',
    registrar: 'Mrs. Abena Frimpong',
  },
  {
    serial: 'CERT-2025-EB-002341',
    eventCode: 'EB',
    eventLabel: 'Birth Certificate',
    applicationId: 'BDR-2025-EB-002341',
    subject: 'Abena Asante',
    dateOfEvent: '2025-08-22',
    dateIssued: '2025-09-04',
    district: 'Ashanti Region',
    registrar: 'Mr. Kweku Ansah',
  },
  {
    serial: 'CERT-2024-DR-000512',
    eventCode: 'DR',
    eventLabel: 'Death Certificate',
    applicationId: 'BDR-2024-DR-000512',
    subject: 'Kofi Mensah',
    dateOfEvent: '2024-11-10',
    dateIssued: '2024-11-17',
    district: 'Greater Accra',
    registrar: 'Mrs. Abena Frimpong',
  },
  {
    serial: 'CERT-2024-EB-001088',
    eventCode: 'EB',
    eventLabel: 'Birth Certificate',
    applicationId: 'BDR-2024-EB-001088',
    subject: 'Kwame Boateng',
    dateOfEvent: '2024-06-01',
    dateIssued: '2024-06-22',
    district: 'Eastern Region',
    registrar: 'Mr. Samuel Osei',
  },
]

const filteredCerts = computed(() =>
  certificates.filter(c => !filterType.value || c.eventCode === filterType.value)
)

const certIcons = {
  EB: '👶',
  LB: '📝',
  DR: '🕊️',
  FD: '🏥',
  AD: '👨‍👩‍👧',
  SR: '🤝',
}

const certColors = {
  EB: 'cert-green',
  LB: 'cert-teal',
  DR: 'cert-slate',
  FD: 'cert-slate',
  AD: 'cert-indigo',
  SR: 'cert-indigo',
}
</script>

<template>
  <CitizenLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">My Certificates</h1>
        <p class="page-subtitle">Official registration certificates issued by the Ghana Births and Deaths Registry.</p>
      </div>
    </div>

    <!-- Filter + Count Row -->
    <div class="toolbar">
      <p class="count-label">{{ filteredCerts.length }} certificate{{ filteredCerts.length !== 1 ? 's' : '' }}</p>
      <select v-model="filterType" class="filter-select">
        <option v-for="o in certTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
    </div>

    <!-- Empty State -->
    <div v-if="filteredCerts.length === 0" class="empty-state">
      <div class="empty-icon">🏛️</div>
      <p>No certificates found.</p>
    </div>

    <!-- Certificate Cards -->
    <div v-else class="cert-list">
      <div v-for="cert in filteredCerts" :key="cert.serial" class="cert-card">
        <!-- Left accent stripe -->
        <div :class="['cert-stripe', certColors[cert.eventCode]]"></div>

        <!-- Icon -->
        <div class="cert-icon">{{ certIcons[cert.eventCode] }}</div>

        <!-- Main info -->
        <div class="cert-body">
          <div class="cert-top">
            <div>
              <h2 class="cert-title">{{ cert.eventLabel }}</h2>
              <p class="cert-subject">{{ cert.subject }}</p>
            </div>
            <div class="cert-serial-block">
              <p class="cert-serial-label">Certificate No.</p>
              <p class="cert-serial">{{ cert.serial }}</p>
            </div>
          </div>

          <div class="cert-meta-grid">
            <div class="cert-meta-item">
              <span class="meta-label">Event Date</span>
              <span class="meta-value">{{ cert.dateOfEvent }}</span>
            </div>
            <div class="cert-meta-item">
              <span class="meta-label">Date Issued</span>
              <span class="meta-value">{{ cert.dateIssued }}</span>
            </div>
            <div class="cert-meta-item">
              <span class="meta-label">District</span>
              <span class="meta-value">{{ cert.district }}</span>
            </div>
            <div class="cert-meta-item">
              <span class="meta-label">Application ID</span>
              <span class="meta-value mono">{{ cert.applicationId }}</span>
            </div>
          </div>

          <div class="cert-footer">
            <div class="qr-note">
              <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 3h7v7H3zm1 1v5h5V4zm8-1h7v7h-7zm1 1v5h5V4zM3 14h7v7H3zm1 1v5h5v-5zm11 0h-3v3h3zm2 0v2h-2v-2zm0 3h2v2h-2zm-4 0h2v4h-4v-2h2zm2 0v2h2v-2z"/></svg>
              <span>QR-verified at <strong>verify.bdr.gov.gh</strong></span>
            </div>
            <div class="cert-actions">
              <button class="btn-ghost btn-sm">Share</button>
              <button class="btn-primary btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15"><path fill="currentColor" d="M5 20h14v-2H5m14-9h-4V3H9v6H5l7 7z"/></svg>
                Download PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Verification note -->
    <div class="verify-note">
      <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.58L18 9l-8 8z"/></svg>
      <p>All certificates carry a BDR digital seal and PKI-based Registrar-General signature. Verify authenticity at <strong>verify.bdr.gov.gh</strong> using the certificate QR code.</p>
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

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}

.count-label {
  font-size: 14px;
  color: var(--text-muted);
  margin: 0;
  font-weight: 500;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  font-size: 14px;
  outline: none;
  cursor: pointer;
  color: var(--text-primary);
}

/* Certificate List */
.cert-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 32px;
}

.cert-card {
  display: flex;
  align-items: stretch;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.cert-stripe {
  width: 6px;
  flex-shrink: 0;
}

.cert-green { background: var(--brand-green); }
.cert-teal { background: #0d9488; }
.cert-slate { background: #64748b; }
.cert-indigo { background: #6366f1; }

.cert-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  flex-shrink: 0;
  font-size: 28px;
  background: var(--background);
  border-right: 1px solid var(--border);
}

.cert-body {
  flex: 1;
  padding: 20px 24px;
  min-width: 0;
}

.cert-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.cert-title {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 4px;
}

.cert-subject {
  font-size: 15px;
  color: var(--text-muted);
  margin: 0;
}

.cert-serial-block {
  text-align: right;
}

.cert-serial-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  margin: 0 0 4px;
}

.cert-serial {
  font-family: monospace;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: 0.04em;
}

/* Meta Grid */
.cert-meta-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 16px 0;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  margin-bottom: 16px;
}

@media (max-width: 800px) {
  .cert-meta-grid { grid-template-columns: repeat(2, 1fr); }
}

.cert-meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
}

.meta-value {
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 500;
}

.meta-value.mono { font-family: monospace; font-size: 12px; }

/* Footer */
.cert-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.qr-note {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--text-muted);
}

.cert-actions {
  display: flex;
  gap: 10px;
}

.btn-sm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 13px;
}

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
  padding: 12px 20px;
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

/* Empty State */
.empty-state {
  text-align: center;
  padding: 64px 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  color: var(--text-muted);
  margin-bottom: 32px;
}

.empty-icon { font-size: 48px; margin-bottom: 16px; }
.empty-state p { font-size: 16px; margin: 0; }

/* Verify Note */
.verify-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: rgba(0, 107, 63, 0.06);
  border: 1px solid rgba(0, 107, 63, 0.2);
  border-radius: var(--radius);
  padding: 16px 20px;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
}

.verify-note svg { flex-shrink: 0; color: var(--brand-green); margin-top: 2px; }
.verify-note p { margin: 0; }
</style>
