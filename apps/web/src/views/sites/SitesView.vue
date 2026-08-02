<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useSitesStore } from '@/stores/sites'

const route = useRoute()
const router = useRouter()
const sitesStore = useSitesStore()

const clientId = computed(() => Number(route.params.clientId))

const filters = reactive({
  q: '',
  per_page: 10,
})

const form = reactive({
  name: '',
  address: '',
  contact_name: '',
  contact_phone: '',
  notes: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

onMounted(async () => {
  sitesStore.clearSites()

  await loadFirstPage()
})

async function loadFirstPage(): Promise<void> {
  await sitesStore.fetchSites(clientId.value, {
    page: 1,
    per_page: filters.per_page,
  })
}

async function applyFilters(): Promise<void> {
  clearErrors()

  try {
    await sitesStore.fetchSites(clientId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page: 1,
    })
  } catch {
    error.value = 'Не удалось загрузить объекты. Попробуйте позже.'
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
    await sitesStore.fetchSites(clientId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page,
    })
  } catch {
    error.value = 'Не удалось загрузить страницу объектов. Попробуйте позже.'
  }
}

async function submit(): Promise<void> {
  clearErrors()

  try {
    await sitesStore.createSite(clientId.value, {
      name: form.name,
      address: form.address || null,
      contact_name: form.contact_name || null,
      contact_phone: form.contact_phone || null,
      notes: form.notes || null,
    })

    form.name = ''
    form.address = ''
    form.contact_name = ''
    form.contact_phone = ''
    form.notes = ''

    await applyFilters()
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось создать объект. Попробуйте позже.'
  }
}

function clearErrors(): void {
  error.value = null
  validationErrors.value = []
}

function back(): void {
  router.back()
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Объекты</h1>
      </div>

      <button type="button" @click="back">Назад</button>
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
      <h2>Создать объект</h2>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: ACME Main Plant" />
        </label>

        <label>
          Адрес
          <input v-model="form.address" type="text" placeholder="100 Industrial Ave, Building A" />
        </label>

        <label>
          Контактное лицо
          <input v-model="form.contact_name" type="text" placeholder="John Facility" />
        </label>

        <label>
          Телефон контакта
          <input v-model="form.contact_phone" type="text" placeholder="+1 555 0101" />
        </label>

        <label>
          Заметки
          <textarea
            v-model="form.notes"
            rows="4"
            placeholder="Любая внутренняя информация об объекте"
          />
        </label>

        <button type="submit" :disabled="sitesStore.loading">
          {{ sitesStore.loading ? 'Создание...' : 'Создать объект' }}
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
            placeholder="Название, адрес, контакт или заметки"
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
          <button type="submit" :disabled="sitesStore.loading">Применить</button>

          <button type="button" :disabled="sitesStore.loading" @click="resetFilters">
            Сбросить
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Список объектов</h2>

      <p v-if="sitesStore.loading">Загрузка объектов...</p>

      <div v-else-if="sitesStore.sites.length === 0" class="empty-state">
        <p>Объектов пока нет.</p>
      </div>

      <div v-else class="organization-list">
        <article v-for="site in sitesStore.sites" :key="site.id" class="organization-item">
          <div>
            <h3>{{ site.name }}</h3>

            <p v-if="site.address">Адрес: {{ site.address }}</p>
            <p v-if="site.contact_name">Контакт: {{ site.contact_name }}</p>
            <p v-if="site.contact_phone">Телефон: {{ site.contact_phone }}</p>
            <p v-if="site.notes">{{ site.notes }}</p>
          </div>

          <div class="organization-actions">
            <RouterLink :to="{ name: 'site.equipment', params: { clientId, siteId: site.id } }">
              Оборудование
            </RouterLink>

            <RouterLink :to="{ name: 'site.work-orders', params: { clientId, siteId: site.id } }">
              Заявки
            </RouterLink>
          </div>
        </article>
      </div>

      <div v-if="sitesStore.pagination" class="pagination">
        <p>
          Показано {{ sitesStore.pagination.from ?? 0 }}–{{ sitesStore.pagination.to ?? 0 }} из
          {{ sitesStore.pagination.total }}
        </p>

        <div class="organization-actions">
          <button
            type="button"
            :disabled="sitesStore.loading || sitesStore.pagination.current_page <= 1"
            @click="goToPage(sitesStore.pagination.current_page - 1)"
          >
            Назад
          </button>

          <span>
            Страница {{ sitesStore.pagination.current_page }} из
            {{ sitesStore.pagination.last_page }}
          </span>

          <button
            type="button"
            :disabled="
              sitesStore.loading ||
              sitesStore.pagination.current_page >= sitesStore.pagination.last_page
            "
            @click="goToPage(sitesStore.pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
