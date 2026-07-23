export type OrganizationMemberRole = 'owner' | 'admin' | 'technician'

export type ManageableOrganizationMemberRole = 'admin' | 'technician'

export interface OrganizationMember {
  id: number
  name: string
  email: string
  role: OrganizationMemberRole
  joined_at: string | null
}

export interface AddOrganizationMemberPayload {
  email: string
  role: ManageableOrganizationMemberRole
}

export interface UpdateOrganizationMemberPayload {
  role: ManageableOrganizationMemberRole
}
