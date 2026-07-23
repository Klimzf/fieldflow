<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOrganizationsStore } from '@/stores/organizations'

const router = useRouter()
const auth = useAuthStore()
const organizationsStore = useOrganizationsStore()

onMounted(async () => {
  await organizationsStore.fetchOrganizations()
})

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <main class="dashboard">
    <header class="dashboard-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Панель управления</h1>
      </div>

      <button type="button" @click="logout">Выйти</button>
    </header>

    <section class="dashboard-card">
      <h2>Добро пожаловать, {{ auth.user?.name }}</h2>
      <p>Email: {{ auth.user?.email }}</p>

      <div v-if="organizationsStore.loading">
        <p>Загружаем организации...</p>
      </div>

      <div v-else-if="organizationsStore.organizations.length === 0">
        <p>У вас пока нет организаций.</p>
        <RouterLink class="button" to="/organizations/new">Создать первую организацию</RouterLink>
      </div>

      <div v-else>
        <p>
          Активная организация:
          <strong>{{ organizationsStore.activeOrganization?.name }}</strong>
        </p>

        <RouterLink class="button" to="/organizations">Управлять организациями</RouterLink>
        <RouterLink
          v-if="organizationsStore.activeOrganization"
          class="button"
          :to="{
            name: 'organization.clients',
            params: { organizationId: organizationsStore.activeOrganization.id },
          }"
        >
          Клиенты организации
        </RouterLink>
        <RouterLink
          v-if="organizationsStore.activeOrganizationId !== null"
          class="button"
          :to="{
            name: 'organization.members',
            params: {
              organizationId: organizationsStore.activeOrganizationId,
            },
          }"
        >
          Участники организации
        </RouterLink>
      </div>
    </section>
  </main>
</template>
