import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import CreateOrganizationView from '@/views/organizations/CreateOrganizationView.vue'
import OrganizationsView from '@/views/organizations/OrganizationsView.vue'
import ClientsView from '@/views/clients/ClientsView.vue'
import CreateClientView from '@/views/clients/CreateClientView.vue'
import CreateSiteView from '@/views/sites/CreateSiteView.vue'
import SitesView from '@/views/sites/SitesView.vue'
import CreateEquipmentView from '@/views/equipment/CreateEquipmentView.vue'
import EquipmentView from '@/views/equipment/EquipmentView.vue'
import CreateWorkOrderView from '@/views/work-orders/CreateWorkOrderView.vue'
import WorkOrdersView from '@/views/work-orders/WorkOrdersView.vue'
import WorkOrderDetailView from '@/views/work-orders/WorkOrderDetailView.vue'
import OrganizationMembersView from '@/views/organization-members/OrganizationMembersView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: {
        guestOnly: true,
      },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: {
        guestOnly: true,
      },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/organizations',
      name: 'organizations',
      component: OrganizationsView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/organizations/new',
      name: 'organizations.create',
      component: CreateOrganizationView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/organizations/:organizationId/clients',
      name: 'organization.clients',
      component: ClientsView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/organizations/:organizationId/clients/new',
      name: 'organization.clients.create',
      component: CreateClientView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites',
      name: 'client.sites',
      component: SitesView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/new',
      name: 'client.sites.create',
      component: CreateSiteView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/:siteId/equipment',
      name: 'site.equipment',
      component: EquipmentView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/:siteId/equipment/new',
      name: 'site.equipment.create',
      component: CreateEquipmentView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/:siteId/work-orders',
      name: 'site.work-orders',
      component: WorkOrdersView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/:siteId/work-orders/new',
      name: 'site.work-orders.create',
      component: CreateWorkOrderView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/clients/:clientId/sites/:siteId/work-orders/:workOrderId',
      name: 'site.work-orders.show',
      component: WorkOrderDetailView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/organizations/:organizationId/members',
      name: 'organization.members',
      component: OrganizationMembersView,
      meta: {
        requiresAuth: true,
      },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return {
      name: 'dashboard',
    }
  }
})

export default router
