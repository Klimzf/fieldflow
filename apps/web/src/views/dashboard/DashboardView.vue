<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  WORK_ORDER_PRIORITY_LABELS,
  WORK_ORDER_STATUS_LABELS,
} from '@/shared/constants/work-orders'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import { useOrganizationsStore } from '@/stores/organizations'
import type { WorkOrder, WorkOrderStatus } from '@/shared/types/work-order'

const authStore = useAuthStore()
const organizationsStore = useOrganizationsStore()
const dashboardStore = useDashboardStore()

const selectedOrganizationId = ref('')

const dashboard = computed(() => dashboardStore.dashboard)

onMounted(async () => {
  await organizationsStore.fetchOrganizations()

  const activeOrganizationId = organizationsStore.activeOrganizationId
  const firstOrganization = organizationsStore.organizations.at(0)

  if (activeOrganizationId !== null) {
    selectedOrganizationId.value = String(activeOrganizationId)
  } else if (firstOrganization !== undefined) {
    selectedOrganizationId.value = String(firstOrganization.id)
  }

  await loadDashboard()
})

async function loadDashboard(): Promise<void> {
  if (selectedOrganizationId.value === '') {
    dashboardStore.clearDashboard()

    return
  }

  await dashboardStore.fetchDashboard(Number(selectedOrganizationId.value))
}

function formatStatus(status: string): string {
  return WORK_ORDER_STATUS_LABELS[status as WorkOrderStatus] ?? status
}

function workOrderLink(workOrder: WorkOrder) {
  return {
    name: 'site.work-orders.show',
    params: {
      clientId: workOrder.client_id,
      siteId: workOrder.site_id,
      workOrderId: workOrder.id,
    },
  }
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Панель управления</h1>
        <p v-if="authStore.user">Здравствуйте, {{ authStore.user.name }}</p>
      </div>

      <RouterLink :to="{ name: 'organizations' }"> Организации </RouterLink>
    </header>

    <section v-if="organizationsStore.organizations.length === 0" class="card">
      <h2>Пока нет организаций</h2>
      <p>Создайте первую организацию, чтобы увидеть dashboard.</p>

      <RouterLink class="button" :to="{ name: 'organizations.create' }">
        Создать организацию
      </RouterLink>
    </section>

    <template v-else>
      <section class="card">
        <h2>Организация</h2>

        <label>
          Выберите организацию
          <select v-model="selectedOrganizationId" @change="loadDashboard">
            <option
              v-for="organization in organizationsStore.organizations"
              :key="organization.id"
              :value="String(organization.id)"
            >
              {{ organization.name }}
            </option>
          </select>
        </label>
      </section>

      <section class="card">
        <p v-if="dashboardStore.loading">Загрузка dashboard...</p>

        <template v-else-if="dashboard">
          <h2>Сводка</h2>

          <div class="stats-grid">
            <article class="stat-card">
              <span>Клиенты</span>
              <strong>{{ dashboard.clients_count }}</strong>
            </article>

            <article class="stat-card">
              <span>Объекты</span>
              <strong>{{ dashboard.sites_count }}</strong>
            </article>

            <article class="stat-card">
              <span>Оборудование</span>
              <strong>{{ dashboard.equipment_count }}</strong>
            </article>

            <article class="stat-card">
              <span>Заявки</span>
              <strong>{{ dashboard.work_orders_count }}</strong>
            </article>

            <article class="stat-card">
              <span>Срочные заявки</span>
              <strong>{{ dashboard.urgent_work_orders_count }}</strong>
            </article>

            <article class="stat-card">
              <span>Назначены мне</span>
              <strong>{{ dashboard.assigned_to_me_count }}</strong>
            </article>
          </div>
        </template>
      </section>

      <section v-if="dashboard" class="dashboard-grid">
        <article class="card">
          <h2>Заявки по статусам</h2>

          <div class="status-grid">
            <div
              v-for="(count, status) in dashboard.work_orders_by_status"
              :key="status"
              class="status-row"
            >
              <span>{{ formatStatus(status) }}</span>
              <strong>{{ count }}</strong>
            </div>
          </div>
        </article>

        <article class="card">
          <h2>Быстрые действия</h2>

          <div class="organization-actions">
            <RouterLink
              class="button"
              :to="{
                name: 'organization.clients',
                params: {
                  organizationId: selectedOrganizationId,
                },
              }"
            >
              Клиенты
            </RouterLink>

            <RouterLink
              class="button"
              :to="{
                name: 'organization.members',
                params: {
                  organizationId: selectedOrganizationId,
                },
              }"
            >
              Участники
            </RouterLink>
          </div>
        </article>
      </section>

      <section v-if="dashboard" class="dashboard-grid">
        <article class="card">
          <h2>Последние заявки</h2>

          <div v-if="dashboard.latest_work_orders.length === 0" class="empty-state">
            <p>Заявок пока нет.</p>
          </div>

          <div v-else class="work-order-list">
            <RouterLink
              v-for="workOrder in dashboard.latest_work_orders"
              :key="workOrder.id"
              class="work-order-row"
              :to="workOrderLink(workOrder)"
            >
              <div>
                <strong>{{ workOrder.title }}</strong>
                <p>
                  {{ formatStatus(workOrder.status) }} ·
                  {{ WORK_ORDER_PRIORITY_LABELS[workOrder.priority] }}
                </p>
              </div>

              <span>#{{ workOrder.id }}</span>
            </RouterLink>
          </div>
        </article>

        <article class="card">
          <h2>Назначены мне</h2>

          <div v-if="dashboard.assigned_to_me_work_orders.length === 0" class="empty-state">
            <p>На вас пока нет назначенных заявок.</p>
          </div>

          <div v-else class="work-order-list">
            <RouterLink
              v-for="workOrder in dashboard.assigned_to_me_work_orders"
              :key="workOrder.id"
              class="work-order-row"
              :to="workOrderLink(workOrder)"
            >
              <div>
                <strong>{{ workOrder.title }}</strong>
                <p>
                  {{ formatStatus(workOrder.status) }} ·
                  {{ WORK_ORDER_PRIORITY_LABELS[workOrder.priority] }}
                </p>
              </div>

              <span>#{{ workOrder.id }}</span>
            </RouterLink>
          </div>
        </article>
      </section>
    </template>
  </main>
</template>
