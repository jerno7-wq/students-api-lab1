
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use(
  (config) => {
    const token = sessionStorage.getItem('student_portal_token')

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error) => Promise.reject(error)
)

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      console.error('Cannot connect to Laravel API:', error.message)
    } else {
      console.error(
        'API Error:',
        error.response.status,
        error.response.data
      )
    }

    return Promise.reject(error)
  }
)

export default api
