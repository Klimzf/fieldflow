<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useOrganizationsStore } from '@/stores/organizations'

const organizationsStore = useOrganizationsStore()

onMounted(async () => {
  await organizationsStore.fetchOrganizations()
})
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Организации</h1>
      </div>

      <RouterLink class="button" to="/organizations/new">Создать организацию</RouterLink>
    </header>

    <section class="card">
      <p v-if="organizationsStore.loading">Загрузка...</p>

      <div v-else-if="organizationsStore.organizations.length === 0" class="empty-state">
        <h2>Организаций пока нет</h2>
        <p>Создайте первую организацию, чтобы начать работу с клиентами, объектами и заявками.</p>
        <RouterLink class="button" to="/organizations/new">Создать организацию</RouterLink>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="organization in organizationsStore.organizations"
          :key="organization.id"
          class="organization-item"
        >
          <div>
            <h2>{{ organization.name }}</h2>
            <p>Slug: {{ organization.slug }}</p>
            <p>Роль: {{ organization.role }}</p>
          </div>

          <div class="organization-actions">
            <RouterLink
              class="button"
              :to="{ name: 'organization.clients', params: { organizationId: organization.id } }"
            >
              Клиенты
            </RouterLink>

            <button
              type="button"
              @click="organizationsStore.setActiveOrganization(organization.id)"
            >
              {{
                organizationsStore.activeOrganizationId === organization.id ? 'Активная' : 'Выбрать'
              }}
            </button>
          </div>
        </article>
      </div>
    </section>

    <RouterLink to="/dashboard">Вернуться в dashboard</RouterLink>
  </main>
</template>
