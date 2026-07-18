<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useWorkOrdersStore } from '@/stores/work-orders'
import {
  WORK_ORDER_PRIORITY_LABELS,
  WORK_ORDER_STATUS_LABELS,
} from '@/shared/constants/work-orders'

const route = useRoute()
const workOrdersStore = useWorkOrdersStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

onMounted(async () => {
  await workOrdersStore.fetchWorkOrders(siteId.value)
})
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Заявки объекта</h1>
      </div>

      <RouterLink
        class="button"
        :to="{
          name: 'site.work-orders.create',
          params: {
            clientId,
            siteId,
          },
        }"
      >
        Создать заявку
      </RouterLink>
    </header>

    <section class="card">
      <p v-if="workOrdersStore.loading">Загрузка заявок...</p>

      <div v-else-if="workOrdersStore.workOrders.length === 0" class="empty-state">
        <h2>Заявок пока нет</h2>
        <p>Создайте первую заявку на обслуживание для выбранного объекта.</p>

        <RouterLink
          class="button"
          :to="{
            name: 'site.work-orders.create',
            params: {
              clientId,
              siteId,
            },
          }"
        >
          Создать заявку
        </RouterLink>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="workOrder in workOrdersStore.workOrders"
          :key="workOrder.id"
          class="organization-item"
        >
          <div>
            <h2>{{ workOrder.title }}</h2>
            <p>Статус: {{ WORK_ORDER_STATUS_LABELS[workOrder.status] }}</p>
            <p>Приоритет: {{ WORK_ORDER_PRIORITY_LABELS[workOrder.priority] }}</p>
            <p v-if="workOrder.description">Описание: {{ workOrder.description }}</p>
            <p v-if="workOrder.equipment_id">Оборудование ID: {{ workOrder.equipment_id }}</p>
            <p v-if="workOrder.scheduled_at">Запланировано: {{ workOrder.scheduled_at }}</p>
            <p v-if="workOrder.completed_at">Завершено: {{ workOrder.completed_at }}</p>
          </div>

          <div class="organization-actions">
            <RouterLink
              class="button"
              :to="{
                name: 'site.work-orders.show',
                params: {
                  clientId,
                  siteId,
                  workOrderId: workOrder.id,
                },
              }"
            >
              Открыть
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <RouterLink :to="{ name: 'client.sites', params: { clientId } }">
      Назад к объектам клиента
    </RouterLink>
  </main>
</template>
