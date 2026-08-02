<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useEquipmentStore } from '@/stores/equipment'

const route = useRoute()
const equipmentStore = useEquipmentStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

const filters = reactive({
  q: '',
  per_page: 10,
})

const form = reactive({
  name: '',
  type: '',
  manufacturer: '',
  model: '',
  serial_number: '',
  installed_at: '',
  notes: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

onMounted(async () => {
  equipmentStore.clearEquipment()

  await loadFirstPage()
})

async function loadFirstPage(): Promise<void> {
  await equipmentStore.fetchEquipment(siteId.value, {
    page: 1,
    per_page: filters.per_page,
  })
}

async function applyFilters(): Promise<void> {
  clearErrors()

  try {
    await equipmentStore.fetchEquipment(siteId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page: 1,
    })
  } catch {
    error.value = 'Не удалось загрузить оборудование. Попробуйте позже.'
  }
}

async function resetFilters(): Promise<void> {
  filters.q = ''
  filters.per_page = 10

  await applyFilters()
}

async function goToPage(page: number): Promise<void> {
  clearErrors()

  try {
    await equipmentStore.fetchEquipment(siteId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page,
    })
  } catch {
    error.value = 'Не удалось загрузить страницу оборудования. Попробуйте позже.'
  }
}

async function submit(): Promise<void> {
  clearErrors()

  try {
    await equipmentStore.createEquipment(siteId.value, {
      name: form.name,
      type: form.type || null,
      manufacturer: form.manufacturer || null,
      model: form.model || null,
      serial_number: form.serial_number || null,
      installed_at: form.installed_at || null,
      notes: form.notes || null,
    })

    form.name = ''
    form.type = ''
    form.manufacturer = ''
    form.model = ''
    form.serial_number = ''
    form.installed_at = ''
    form.notes = ''

    await applyFilters()
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось создать оборудование. Попробуйте позже.'
  }
}

function clearErrors(): void {
  error.value = null
  validationErrors.value = []
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Оборудование</h1>
      </div>

      <RouterLink :to="{ name: 'client.sites', params: { clientId } }">
        Назад к объектам
      </RouterLink>
    </header>

    <section v-if="error" class="card">
      <div class="error">
        <p>{{ error }}</p>

        <ul v-if="validationErrors.length">
          <li v-for="validationError in validationErrors" :key="validationError">
            {{ validationError }}
          </li>
        </ul>
      </div>
    </section>

    <section class="card">
      <h2>Добавить оборудование</h2>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: Main HVAC Unit" />
        </label>

        <label>
          Тип
          <input v-model="form.type" type="text" placeholder="Например: conditioner" />
        </label>

        <label>
          Производитель
          <input v-model="form.manufacturer" type="text" placeholder="Например: Demo Systems" />
        </label>

        <label>
          Модель
          <input v-model="form.model" type="text" placeholder="Например: HVAC-500" />
        </label>

        <label>
          Серийный номер
          <input v-model="form.serial_number" type="text" placeholder="Например: DEMO-HVAC-001" />
        </label>

        <label>
          Дата установки
          <input v-model="form.installed_at" type="date" />
        </label>

        <label>
          Заметки
          <textarea
            v-model="form.notes"
            rows="4"
            placeholder="Любая внутренняя информация об оборудовании"
          />
        </label>

        <button type="submit" :disabled="equipmentStore.loading">
          {{ equipmentStore.loading ? 'Создание...' : 'Добавить оборудование' }}
        </button>
      </form>
    </section>

    <section class="card">
      <h2>Поиск и фильтры</h2>

      <form class="form compact-form" @submit.prevent="applyFilters">
        <label>
          Поиск
          <input
            v-model="filters.q"
            type="search"
            placeholder="Название, тип, производитель, модель, серийный номер"
          />
        </label>

        <label>
          На странице
          <select v-model.number="filters.per_page">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </label>

        <div class="organization-actions">
          <button type="submit" :disabled="equipmentStore.loading">Применить</button>

          <button type="button" :disabled="equipmentStore.loading" @click="resetFilters">
            Сбросить
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Список оборудования</h2>

      <p v-if="equipmentStore.loading">Загрузка оборудования...</p>

      <div v-else-if="equipmentStore.equipment.length === 0" class="empty-state">
        <p>Оборудование пока не добавлено.</p>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="equipmentItem in equipmentStore.equipment"
          :key="equipmentItem.id"
          class="organization-item"
        >
          <div>
            <h3>{{ equipmentItem.name }}</h3>

            <p v-if="equipmentItem.type">Тип: {{ equipmentItem.type }}</p>
            <p v-if="equipmentItem.manufacturer">Производитель: {{ equipmentItem.manufacturer }}</p>
            <p v-if="equipmentItem.model">Модель: {{ equipmentItem.model }}</p>
            <p v-if="equipmentItem.serial_number">
              Серийный номер: {{ equipmentItem.serial_number }}
            </p>
            <p v-if="equipmentItem.installed_at">
              Дата установки: {{ equipmentItem.installed_at }}
            </p>
            <p v-if="equipmentItem.notes">{{ equipmentItem.notes }}</p>
          </div>

          <RouterLink :to="{ name: 'site.work-orders', params: { clientId, siteId } }">
            Заявки объекта
          </RouterLink>
        </article>
      </div>

      <div v-if="equipmentStore.pagination" class="pagination">
        <p>
          Показано
          {{ equipmentStore.pagination.from ?? 0 }}–{{ equipmentStore.pagination.to ?? 0 }} из
          {{ equipmentStore.pagination.total }}
        </p>

        <div class="organization-actions">
          <button
            type="button"
            :disabled="equipmentStore.loading || equipmentStore.pagination.current_page <= 1"
            @click="goToPage(equipmentStore.pagination.current_page - 1)"
          >
            Назад
          </button>

          <span>
            Страница {{ equipmentStore.pagination.current_page }} из
            {{ equipmentStore.pagination.last_page }}
          </span>

          <button
            type="button"
            :disabled="
              equipmentStore.loading ||
              equipmentStore.pagination.current_page >= equipmentStore.pagination.last_page
            "
            @click="goToPage(equipmentStore.pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
