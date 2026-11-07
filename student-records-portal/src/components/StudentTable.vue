
<script setup>
defineProps({
  students: {
    type: Array,
    required: true,
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['edit', 'delete'])
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student Number</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Course</th>
          <th>Year</th>
          <th v-if="isAdmin">Actions</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="student in students" :key="student.id">
          <td class="student-number">{{ student.student_number }}</td>
          <td>{{ student.first_name }} {{ student.last_name }}</td>
          <td>{{ student.email }}</td>
          <td>{{ student.course }}</td>
          <td>Year {{ student.year_level }}</td>

          <td v-if="isAdmin" class="actions">
            <button class="edit-btn" @click="emit('edit', student)">
              Edit
            </button>
            <button class="delete-btn" @click="emit('delete', student)">
              Delete
            </button>
          </td>
        </tr>

        <tr v-if="!loading && students.length === 0">
          <td :colspan="isAdmin ? 6 : 5" class="empty">
            No student records found.
          </td>
        </tr>
      </tbody>
    </table>

    <p v-if="loading" class="loading">
      Loading student records...
    </p>
  </div>
</template>
