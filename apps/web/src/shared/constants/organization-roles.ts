import type { Organization } from '@/shared/types/organization'

export const ORGANIZATION_ROLE_LABELS: Record<Organization['role'], string> = {
  owner: 'Владелец',
  admin: 'Администратор',
  technician: 'Техник',
}

export function canManageRole(role: Organization['role'] | null | undefined): boolean {
  return role === 'owner' || role === 'admin'
}
