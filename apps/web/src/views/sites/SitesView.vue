<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSitesStore } from '@/stores/sites'

const route = useRoute()
const sitesStore = useSitesStore()

const clientId = computed(() => Number(route.params.clientId))

onMounted(async () => {
  await sitesStore.fetchSites(clientId.value)
})
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Объекты клиента</h1>
      </div>

      <RouterLink class="button" :to="{ name: 'client.sites.create', params: { clientId } }">
        Создать объект
      </RouterLink>
    </header>

    <section class="card">
      <p v-if="sitesStore.loading">Загрузка объектов...</p>

      <div v-else-if="sitesStore.sites.length === 0" class="empty-state">
        <h2>Объектов пока нет</h2>
        <p>Создайте первый объект клиента: офис, склад, магазин или производственный участок.</p>

        <RouterLink class="button" :to="{ name: 'client.sites.create', params: { clientId } }">
          Создать объект
        </RouterLink>
      </div>

      <div v-else class="organization-list">
        <article v-for="site in sitesStore.sites" :key="site.id" class="organization-item">
          <div>
            <h2>{{ site.name }}</h2>
            <p v-if="site.address">Адрес: {{ site.address }}</p>
            <p v-if="site.contact_name">Контакт: {{ site.contact_name }}</p>
            <p v-if="site.contact_phone">Телефон: {{ site.contact_phone }}</p>
            <p v-if="site.notes">Заметки: {{ site.notes }}</p>
          </div>

          <div class="organization-actions">
            <RouterLink
              class="button"
              :to="{
                name: 'site.equipment',
                params: {
                  clientId,
                  siteId: site.id,
                },
              }"
            >
              Оборудование
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <RouterLink to="/organizations">Назад к организациям</RouterLink>
  </main>
</template>
