
<script setup>
import { ref, computed, onMounted } from 'vue'
import api from './services/api'
import StudentTable from './components/StudentTable.vue'

const students = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')

const token = ref(sessionStorage.getItem('student_portal_token') || '')

function storedUser() {
  try {
    return JSON.parse(sessionStorage.getItem('student_portal_user') || 'null')
  } catch {
    return null
  }
}

const user = ref(storedUser())
const email = ref('')
const password = ref('')
const editingId = ref(null)

function emptyForm() {
  return {
    student_number: '',
    first_name: '',
    last_name: '',
    email: '',
    course: '',
    year_level: 1,
  }
}

const form = ref(emptyForm())

const isAdmin = computed(() => user.value?.role === 'admin')

const filteredStudents = computed(() => {
  const term = search.value.trim().toLowerCase()

  return students.value.filter((student) =>
    [
      student.student_number,
      student.first_name,
      student.last_name,
      student.email,
      student.course,
      student.year_level,
    ].some((value) =>
      String(value ?? '').toLowerCase().includes(term)
    )
  )
})

function handleError(err) {
  if (err.response?.status === 422) {
    const errors = err.response.data.errors || {}
    error.value = Object.values(errors).flat().join(' ')
  } else if (err.response?.status === 403) {
    error.value = 'You do not have permission to perform this action.'
  } else if (err.response?.status === 401) {
    error.value = 'Authentication failed. Please sign in again.'
  } else {
    error.value =
      err.response?.data?.message ||
      'Unable to complete the request.'
  }
}

async function login() {
  error.value = ''
  success.value = ''
  loading.value = true

  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value,
    })

    token.value = response.data.token
    user.value = response.data.user

    sessionStorage.setItem('student_portal_token', token.value)
    sessionStorage.setItem(
      'student_portal_user',
      JSON.stringify(user.value)
    )

    password.value = ''
    await loadStudents()
  } catch (err) {
    handleError(err)
  } finally {
    loading.value = false
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
  }

  sessionStorage.removeItem('student_portal_token')
  sessionStorage.removeItem('student_portal_user')

  token.value = ''
  user.value = null
  students.value = []
  editingId.value = null
  form.value = emptyForm()
  error.value = ''
  success.value = ''
}

async function loadStudents() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/students')
    students.value = response.data
  } catch (err) {
    handleError(err)
  } finally {
    loading.value = false
  }
}

function editStudent(student) {
  editingId.value = student.id

  form.value = {
    student_number: student.student_number,
    first_name: student.first_name,
    last_name: student.last_name,
    email: student.email,
    course: student.course,
    year_level: student.year_level,
  }

  error.value = ''
  success.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() {
  editingId.value = null
  form.value = emptyForm()
}

async function saveStudent() {
  if (!isAdmin.value) return

  saving.value = true
  error.value = ''
  success.value = ''

  try {
    if (editingId.value !== null) {
      await api.put(`/students/${editingId.value}`, form.value)
      cancelEdit()
      await loadStudents()
      success.value = 'Student updated successfully.'
    } else {
      await api.post('/students', form.value)
      cancelEdit()
      await loadStudents()
      success.value = 'Student added successfully.'
    }
  } catch (err) {
    handleError(err)
  } finally {
    saving.value = false
  }
}

async function deleteStudent(student) {
  if (!isAdmin.value) return

  if (!window.confirm(`Delete ${student.first_name} ${student.last_name}?`)) {
    return
  }

  error.value = ''
  success.value = ''

  try {
    await api.delete(`/students/${student.id}`)
    await loadStudents()
    success.value = 'Student deleted successfully.'
  } catch (err) {
    handleError(err)
  }
}

onMounted(() => {
  if (token.value) loadStudents()
})
</script>

<template>
  <div class="app">
    <header class="header">
      <h2>Student Records System</h2>
      <button v-if="token" class="logout" @click="logout">
        Logout
      </button>
    </header>

    <main v-if="!token" class="login-page">
      <div class="login-box">
        <h2>Login</h2>
        <p>Enter your account details</p>

        <form @submit.prevent="login">
          <label>Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="Email address"
            required
          />

          <label>Password</label>
          <input
            v-model="password"
            type="password"
            placeholder="Password"
            required
          />

          <p v-if="error" class="error">{{ error }}</p>

          <button class="primary" type="submit" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </form>
      </div>
    </main>

    <main v-else class="container">
      <div class="page-title">
        <h2>Student Management</h2>
        <p>Logged in as: {{ user?.name || user?.email }}</p>
        <p>Role: {{ isAdmin ? 'Admin' : 'Regular User' }}</p>
      </div>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>

      <section v-if="isAdmin" class="box">
        <h3>{{ editingId !== null ? 'Edit Student' : 'Add Student' }}</h3>

        <form class="student-form" @submit.prevent="saveStudent">
          <div>
            <label>Student Number</label>
            <input v-model="form.student_number" required />
          </div>

          <div>
            <label>First Name</label>
            <input v-model="form.first_name" required />
          </div>

          <div>
            <label>Last Name</label>
            <input v-model="form.last_name" required />
          </div>

          <div>
            <label>Email</label>
            <input v-model="form.email" type="email" required />
          </div>

          <div>
            <label>Course</label>
            <input v-model="form.course" required />
          </div>

          <div>
            <label>Year Level</label>
            <select v-model.number="form.year_level">
              <option v-for="year in 5" :key="year" :value="year">
                Year {{ year }}
              </option>
            </select>
          </div>

          <div class="form-buttons">
            <button class="primary" type="submit" :disabled="saving">
              {{ saving ? 'Saving...' : editingId !== null ? 'Update Student' : 'Add Student' }}
            </button>

            <button
              v-if="editingId !== null"
              type="button"
              class="secondary"
              @click="cancelEdit"
            >
              Cancel
            </button>
          </div>
        </form>
      </section>

      <section class="box">
        <div class="table-heading">
          <h3>Student List ({{ students.length }})</h3>
          <button class="secondary" @click="loadStudents">
            Refresh
          </button>
        </div>

        <input
          v-model="search"
          class="search"
          placeholder="Search students..."
        />

        <StudentTable
          :students="filteredStudents"
          :is-admin="isAdmin"
          :loading="loading"
          @edit="editStudent"
          @delete="deleteStudent"
        />
      </section>
    </main>
  </div>
</template>

<style>
:root {
  font-family: Arial, sans-serif;
  color: #222;
  background: #f5f5f5;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
}

button, input, select {
  font: inherit;
}

.header {
  background: #ffffff;
  border-bottom: 1px solid #ddd;
  padding: 16px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h2 {
  margin: 0;
  font-size: 20px;
}

.login-page {
  min-height: 75vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.login-box {
  width: 100%;
  max-width: 380px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 30px;
}

.login-box h2 {
  text-align: center;
  margin-top: 0;
}

.login-box p {
  text-align: center;
  color: #666;
}

label {
  display: block;
  margin: 14px 0 6px;
  font-size: 14px;
}

input, select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: white;
}

.primary {
  background: #2563eb;
  color: white;
  border: none;
  padding: 11px 18px;
  border-radius: 4px;
  cursor: pointer;
}

.primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login-box .primary {
  width: 100%;
  margin-top: 20px;
}

.logout, .secondary {
  background: white;
  border: 1px solid #aaa;
  border-radius: 4px;
  padding: 9px 15px;
  cursor: pointer;
}

.container {
  max-width: 1100px;
  margin: 30px auto;
  padding: 0 20px;
}

.page-title {
  margin-bottom: 25px;
}

.page-title h2 {
  margin-bottom: 8px;
}

.page-title p {
  margin: 5px 0;
  color: #666;
}

.box {
  background: white;
  border: 1px solid #ddd;
  border-radius: 5px;
  padding: 22px;
  margin-bottom: 25px;
}

.box h3 {
  margin-top: 0;
}

.student-form {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px 20px;
}

.form-buttons {
  grid-column: 1 / -1;
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.table-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.search {
  max-width: 350px;
  margin: 10px 0 20px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th, td {
  border: 1px solid #ddd;
  padding: 12px;
  font-size: 14px;
}

th {
  background: #f2f2f2;
}

.edit-btn, .delete-btn {
  padding: 7px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin: 3px;
  cursor: pointer;
}

.edit-btn {
  background: #e7f0ff;
}

.delete-btn {
  background: #ffe8e8;
}

.student-number {
  font-weight: bold;
}

.empty, .loading {
  text-align: center;
  color: #666;
  padding: 20px;
}

.error {
  color: #b91c1c;
  padding: 10px;
  background: #fee2e2;
}

.success {
  color: #166534;
  padding: 10px;
  background: #dcfce7;
}

@media (max-width: 650px) {
  .student-form {
    grid-template-columns: 1fr;
  }

  .header {
    padding: 15px;
  }

  .table-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
