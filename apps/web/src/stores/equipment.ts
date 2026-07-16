import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { Equipment, EquipmentPayload } from '@/shared/types/equipment'

export const useEquipmentStore = defineStore('equipment', () => {
  const equipmentItems = ref<Equipment[]>([])
  const loading = ref(false)

  async function fetchEquipment(siteId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<Equipment>>(
        `/api/sites/${siteId}/equipment`,
      )

      equipmentItems.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createEquipment(siteId: number, payload: EquipmentPayload): Promise<Equipment> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Equipment>>(
        `/api/sites/${siteId}/equipment`,
        payload,
      )

      const equipment = response.data.data

      equipmentItems.value.push(equipment)

      return equipment
    } finally {
      loading.value = false
    }
  }

  return {
    equipmentItems,
    loading,
    fetchEquipment,
    createEquipment,
  }
})
