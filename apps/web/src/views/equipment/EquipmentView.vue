<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useEquipmentStore } from '@/stores/equipment'

const route = useRoute()
const equipmentStore = useEquipmentStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

onMounted(async () => {
  await equipmentStore.fetchEquipment(siteId.value)
})
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Оборудование объекта</h1>
      </div>

      <RouterLink
        class="button"
        :to="{
          name: 'site.equipment.create',
          params: {
            clientId,
            siteId,
          },
        }"
      >
        Добавить оборудование
      </RouterLink>
    </header>

    <section class="card">
      <p v-if="equipmentStore.loading">Загрузка оборудования...</p>

      <div v-else-if="equipmentStore.equipmentItems.length === 0" class="empty-state">
        <h2>Оборудования пока нет</h2>
        <p>Добавьте первое оборудование для выбранного объекта.</p>

        <RouterLink
          class="button"
          :to="{
            name: 'site.equipment.create',
            params: {
              clientId,
              siteId,
            },
          }"
        >
          Добавить оборудование
        </RouterLink>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="equipment in equipmentStore.equipmentItems"
          :key="equipment.id"
          class="organization-item"
        >
          <div>
            <h2>{{ equipment.name }}</h2>
            <p v-if="equipment.type">Тип: {{ equipment.type }}</p>
            <p v-if="equipment.manufacturer">Производитель: {{ equipment.manufacturer }}</p>
            <p v-if="equipment.model">Модель: {{ equipment.model }}</p>
            <p v-if="equipment.serial_number">Серийный номер: {{ equipment.serial_number }}</p>
            <p v-if="equipment.installed_at">Дата установки: {{ equipment.installed_at }}</p>
            <p v-if="equipment.notes">Заметки: {{ equipment.notes }}</p>
          </div>
        </article>
      </div>
    </section>

    <RouterLink :to="{ name: 'client.sites', params: { clientId } }">
      Назад к объектам клиента
    </RouterLink>
  </main>
</template>
