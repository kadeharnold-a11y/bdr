import axios from 'axios'
import { getStaffAccessToken } from './staffAuth'

export const staffApi = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:4000/api',
})

staffApi.interceptors.request.use((config) => {
  const token = getStaffAccessToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
