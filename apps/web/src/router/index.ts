import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import CreateOrganizationView from '@/views/organizations/CreateOrganizationView.vue'
import OrganizationsView from '@/views/organizations/OrganizationsView.vue'
import ClientsView from '@/views/clients/ClientsView.vue'
import CreateClientView from '@/views/clients/CreateClientView.vue'

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
