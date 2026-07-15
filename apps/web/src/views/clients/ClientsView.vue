<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useClientsStore } from '@/stores/clients'

const route = useRoute()
const clientsStore = useClientsStore()

const organizationId = computed(() => Number(route.params.organizationId))

onMounted(async () => {
  await clientsStore.fetchClients(organizationId.value)
})
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Клиенты</h1>
      </div>

      <RouterLink
        class="button"
        :to="{ name: 'organization.clients.create', params: { organizationId } }"
      >
        Создать клиента
      </RouterLink>
    </header>

    <section class="card">
      <p v-if="clientsStore.loading">Загрузка клиентов...</p>

      <div v-else-if="clientsStore.clients.length === 0" class="empty-state">
        <h2>Клиентов пока нет</h2>
        <p>Создайте первого клиента для выбранной организации.</p>

        <RouterLink
          class="button"
          :to="{ name: 'organization.clients.create', params: { organizationId } }"
        >
          Создать клиента
        </RouterLink>
      </div>

      <div v-else class="organization-list">
        <article v-for="client in clientsStore.clients" :key="client.id" class="organization-item">
          <div>
            <h2>{{ client.name }}</h2>
            <p v-if="client.email">Email: {{ client.email }}</p>
            <p v-if="client.phone">Телефон: {{ client.phone }}</p>
            <p v-if="client.address">Адрес: {{ client.address }}</p>
            <p v-if="client.notes">Заметки: {{ client.notes }}</p>
          </div>

          <div class="organization-actions">
            <RouterLink
              class="button"
              :to="{ name: 'client.sites', params: { clientId: client.id } }"
            >
              Объекты
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <RouterLink to="/organizations">Назад к организациям</RouterLink>
  </main>
</template>
