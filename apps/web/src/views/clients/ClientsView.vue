<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useClientsStore } from '@/stores/clients'

const route = useRoute()
const clientsStore = useClientsStore()

const organizationId = computed(() => Number(route.params.organizationId))

const filters = reactive({
  q: '',
  per_page: 10,
})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  notes: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

onMounted(async () => {
  clientsStore.clearClients()

  await loadFirstPage()
})

async function loadFirstPage(): Promise<void> {
  await clientsStore.fetchClients(organizationId.value, {
    page: 1,
    per_page: filters.per_page,
  })
}

async function applyFilters(): Promise<void> {
  clearErrors()

  try {
    await clientsStore.fetchClients(organizationId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page: 1,
    })
  } catch {
    error.value = 'Не удалось загрузить клиентов. Попробуйте позже.'
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
    await clientsStore.fetchClients(organizationId.value, {
      q: filters.q,
      per_page: filters.per_page,
      page,
    })
  } catch {
    error.value = 'Не удалось загрузить страницу клиентов. Попробуйте позже.'
  }
}

async function submit(): Promise<void> {
  clearErrors()

  try {
    await clientsStore.createClient(organizationId.value, {
      name: form.name,
      email: form.email || null,
      phone: form.phone || null,
      address: form.address || null,
      notes: form.notes || null,
    })

    form.name = ''
    form.email = ''
    form.phone = ''
    form.address = ''
    form.notes = ''

    await applyFilters()
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось создать клиента. Попробуйте позже.'
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
        <h1>Клиенты</h1>
      </div>

      <RouterLink :to="{ name: 'organizations' }"> Назад к организациям </RouterLink>
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
      <h2>Создать клиента</h2>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Например: ACME Manufacturing"
          />
        </label>

        <label>
          Email
          <input v-model="form.email" type="email" placeholder="facilities@example.com" />
        </label>

        <label>
          Телефон
          <input v-model="form.phone" type="text" placeholder="+1 555 0100" />
        </label>

        <label>
          Адрес
          <input v-model="form.address" type="text" placeholder="100 Industrial Ave" />
        </label>

        <label>
          Заметки
          <textarea
            v-model="form.notes"
            rows="4"
            placeholder="Любая внутренняя информация о клиенте"
          />
        </label>

        <button type="submit" :disabled="clientsStore.loading">
          {{ clientsStore.loading ? 'Создание...' : 'Создать клиента' }}
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
            placeholder="Название, email, телефон или адрес"
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
          <button type="submit" :disabled="clientsStore.loading">Применить</button>

          <button type="button" :disabled="clientsStore.loading" @click="resetFilters">
            Сбросить
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Список клиентов</h2>

      <p v-if="clientsStore.loading">Загрузка клиентов...</p>

      <div v-else-if="clientsStore.clients.length === 0" class="empty-state">
        <p>Клиентов пока нет.</p>
      </div>

      <div v-else class="organization-list">
        <article v-for="client in clientsStore.clients" :key="client.id" class="organization-item">
          <div>
            <h3>{{ client.name }}</h3>

            <p v-if="client.email">Email: {{ client.email }}</p>
            <p v-if="client.phone">Телефон: {{ client.phone }}</p>
            <p v-if="client.address">Адрес: {{ client.address }}</p>
            <p v-if="client.notes">{{ client.notes }}</p>
          </div>

          <RouterLink :to="{ name: 'client.sites', params: { clientId: client.id } }">
            Объекты
          </RouterLink>
        </article>
      </div>

      <div v-if="clientsStore.pagination" class="pagination">
        <p>
          Показано
          {{ clientsStore.pagination.from ?? 0 }}–{{ clientsStore.pagination.to ?? 0 }} из
          {{ clientsStore.pagination.total }}
        </p>

        <div class="organization-actions">
          <button
            type="button"
            :disabled="clientsStore.loading || clientsStore.pagination.current_page <= 1"
            @click="goToPage(clientsStore.pagination.current_page - 1)"
          >
            Назад
          </button>

          <span>
            Страница {{ clientsStore.pagination.current_page }} из
            {{ clientsStore.pagination.last_page }}
          </span>

          <button
            type="button"
            :disabled="
              clientsStore.loading ||
              clientsStore.pagination.current_page >= clientsStore.pagination.last_page
            "
            @click="goToPage(clientsStore.pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
