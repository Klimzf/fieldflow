import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { WorkOrderFile } from '@/shared/types/work-order-file'

export const useWorkOrderFilesStore = defineStore('work-order-files', () => {
  const files = ref<WorkOrderFile[]>([])
  const loading = ref(false)

  async function fetchFiles(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<WorkOrderFile>>(
        `/api/work-orders/${workOrderId}/files`,
      )

      files.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function uploadFile(workOrderId: number, file: File): Promise<WorkOrderFile> {
    loading.value = true

    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await http.post<ApiResource<WorkOrderFile>>(
        `/api/work-orders/${workOrderId}/files`,
        formData,
      )

      const uploadedFile = response.data.data

      files.value.unshift(uploadedFile)

      return uploadedFile
    } finally {
      loading.value = false
    }
  }

  async function deleteFile(fileId: number): Promise<void> {
    loading.value = true

    try {
      await http.delete(`/api/work-order-files/${fileId}`)

      files.value = files.value.filter((file) => file.id !== fileId)
    } finally {
      loading.value = false
    }
  }

  async function downloadFile(file: WorkOrderFile): Promise<void> {
    const response = await http.get(file.download_url, {
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')

    link.href = url
    link.download = file.original_name
    document.body.appendChild(link)
    link.click()
    link.remove()

    window.URL.revokeObjectURL(url)
  }

  return {
    files,
    loading,
    fetchFiles,
    uploadFile,
    downloadFile,
    deleteFile,
  }
})
