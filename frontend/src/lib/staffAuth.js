const STORAGE_KEY = 'hbdrp_staff_session'

export function saveStaffSession({ staffId, role, fullName, accessToken }) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ staffId, role, fullName, accessToken }))
}

export function getStaffSession() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
  } catch {
    return null
  }
}

export function getStaffAccessToken() {
  return getStaffSession()?.accessToken || null
}

export function clearStaffSession() {
  localStorage.removeItem(STORAGE_KEY)
}
