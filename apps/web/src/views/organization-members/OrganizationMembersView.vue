<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useOrganizationMembersStore } from '@/stores/organization-members'
import type {
  ManageableOrganizationMemberRole,
  OrganizationMember,
  OrganizationMemberRole,
} from '@/shared/types/organization-member'

const route = useRoute()
const membersStore = useOrganizationMembersStore()

const organizationId = computed(() => Number(route.params.organizationId))

const roleLabels: Record<OrganizationMemberRole, string> = {
  owner: 'Владелец',
  admin: 'Администратор',
  technician: 'Техник',
}

const manageableRoles: Array<{ value: ManageableOrganizationMemberRole; label: string }> = [
  { value: 'admin', label: 'Администратор' },
  { value: 'technician', label: 'Техник' },
]

const form = reactive({
  email: '',
  role: 'technician' as ManageableOrganizationMemberRole,
})

const selectedRoles = reactive<Record<number, ManageableOrganizationMemberRole>>({})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

onMounted(async () => {
  await loadMembers()
})

async function loadMembers(): Promise<void> {
  await membersStore.fetchMembers(organizationId.value)

  for (const member of membersStore.members) {
    if (member.role !== 'owner') {
      selectedRoles[member.id] = member.role
    }
  }
}

async function submit(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    const member = await membersStore.addMember(organizationId.value, {
      email: form.email,
      role: form.role,
    })

    if (member.role !== 'owner') {
      selectedRoles[member.id] = member.role
    }

    form.email = ''
    form.role = 'technician'
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось добавить участника. Попробуйте позже.')
  }
}

async function updateRole(member: OrganizationMember): Promise<void> {
  if (member.role === 'owner') {
    return
  }

  const selectedRole = selectedRoles[member.id]

  if (selectedRole === undefined || selectedRole === member.role) {
    return
  }

  error.value = null
  validationErrors.value = []

  try {
    const updatedMember = await membersStore.updateMemberRole(organizationId.value, member.id, {
      role: selectedRole,
    })

    if (updatedMember.role !== 'owner') {
      selectedRoles[updatedMember.id] = updatedMember.role
    }
  } catch (exception: unknown) {
    selectedRoles[member.id] = member.role
    handleError(exception, 'Не удалось изменить роль участника. Попробуйте позже.')
  }
}

async function removeMember(member: OrganizationMember): Promise<void> {
  if (member.role === 'owner') {
    return
  }

  error.value = null
  validationErrors.value = []

  try {
    await membersStore.removeMember(organizationId.value, member.id)
    delete selectedRoles[member.id]
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось удалить участника. Попробуйте позже.')
  }
}

function handleError(exception: unknown, fallbackMessage: string): void {
  const validationError = getValidationError(exception)

  if (validationError !== null) {
    error.value = validationError.message
    validationErrors.value = validationError.errors

    return
  }

  error.value = fallbackMessage
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Участники организации</h1>
      </div>

      <RouterLink :to="{ name: 'organizations' }"> Назад к организациям </RouterLink>
    </header>

    <section class="card">
      <h2>Добавить участника</h2>

      <p class="description">
        Добавить можно только пользователя, который уже зарегистрирован в FieldFlow.
      </p>

      <form class="form" @submit.prevent="submit">
        <label>
          Email пользователя
          <input v-model="form.email" type="email" required placeholder="user@example.com" />
        </label>

        <label>
          Роль
          <select v-model="form.role">
            <option v-for="role in manageableRoles" :key="role.value" :value="role.value">
              {{ role.label }}
            </option>
          </select>
        </label>

        <div v-if="error" class="error">
          <p>{{ error }}</p>

          <ul v-if="validationErrors.length">
            <li v-for="validationError in validationErrors" :key="validationError">
              {{ validationError }}
            </li>
          </ul>
        </div>

        <button type="submit" :disabled="membersStore.loading">
          {{ membersStore.loading ? 'Добавление...' : 'Добавить участника' }}
        </button>
      </form>
    </section>

    <section class="card">
      <h2>Список участников</h2>

      <p v-if="membersStore.loading">Загрузка участников...</p>

      <div v-else-if="membersStore.members.length === 0" class="empty-state">
        <p>Участников пока нет.</p>
      </div>

      <div v-else class="organization-list">
        <article v-for="member in membersStore.members" :key="member.id" class="organization-item">
          <div>
            <h3>{{ member.name }}</h3>
            <p>{{ member.email }}</p>
            <p>Роль: {{ roleLabels[member.role] }}</p>
            <p v-if="member.joined_at">Добавлен: {{ member.joined_at }}</p>
          </div>

          <div v-if="member.role !== 'owner'" class="organization-actions">
            <select v-model="selectedRoles[member.id]">
              <option v-for="role in manageableRoles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>

            <button
              type="button"
              :disabled="membersStore.loading || selectedRoles[member.id] === member.role"
              @click="updateRole(member)"
            >
              Сохранить роль
            </button>

            <button type="button" :disabled="membersStore.loading" @click="removeMember(member)">
              Удалить
            </button>
          </div>

          <p v-else class="description">Владельца нельзя изменить или удалить.</p>
        </article>
      </div>
    </section>
  </main>
</template>
