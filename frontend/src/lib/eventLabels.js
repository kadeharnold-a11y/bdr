export const EVENT_TYPE_LABELS = {
  early_birth: 'Early Birth',
  late_birth: 'Late Birth',
  death: 'Death Registration',
  foetal_death: 'Foetal Death',
  adoption: 'Adoption',
  surrogacy: 'Surrogacy',
}

export const EVENT_TYPE_CODES = {
  early_birth: 'EB',
  late_birth: 'LB',
  death: 'DR',
  foetal_death: 'FD',
  adoption: 'AD',
  surrogacy: 'SR',
}

export const STATUS_LABELS = {
  SUBMITTED: 'Initial Review',
  UNDER_REVIEW: 'Document Verification',
  CORRECTIONS_REQUIRED: 'Corrections Required',
  AWAITING_APPROVAL: 'Awaiting Approval',
  APPROVED: 'Certificate Generation',
  COMPLETED: 'Completed',
  REJECTED: 'Rejected',
  PAYMENT_PENDING: 'Payment Pending',
  DRAFT: 'Draft',
}

export function formatTier(tier) {
  if (!tier) return ''
  return tier.charAt(0).toUpperCase() + tier.slice(1)
}

export function slaUrgencyFromRemaining(percentRemaining) {
  if (percentRemaining == null) return 'safe'
  if (percentRemaining <= 20) return 'critical'
  if (percentRemaining <= 50) return 'warning'
  return 'safe'
}
