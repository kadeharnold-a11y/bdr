// Minimal localStorage-backed session store. No pinia store yet - this is
// intentionally small so it's easy to replace once real state management
// for the citizen session is decided.
const STORAGE_KEY = 'hbdrp_citizen_session'

export function saveSession({ citizenId, accessToken, refreshToken, fullName }) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ citizenId, accessToken, refreshToken, fullName }))
}

export function getSession() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
  } catch {
    return null
  }
}

export function getAccessToken() {
  return getSession()?.accessToken || null
}

export function clearSession() {
  localStorage.removeItem(STORAGE_KEY)
}
