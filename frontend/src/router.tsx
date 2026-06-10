import { lazy, Suspense } from 'react'
import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom'
import { jwtDecode } from 'jwt-decode'
import { useAuth } from '@/contexts/AuthContext'
import AppLayout from '@/layouts/AppLayout'
import PageLoader from '@/components/PageLoader'

// Public
const LoginPage    = lazy(() => import('@/pages/LoginPage'))
const RegisterPage = lazy(() => import('@/pages/RegisterPage'))

// Reporter
const MyTicketsPage    = lazy(() => import('@/pages/reporter/MyTicketsPage'))
const CreateTicketPage = lazy(() => import('@/pages/reporter/CreateTicketPage'))
const TicketDetailPage = lazy(() => import('@/pages/reporter/TicketDetailPage'))

// Agent
const AgentQueuePage      = lazy(() => import('@/pages/agent/QueuePage'))
const MyAssignedPage      = lazy(() => import('@/pages/agent/MyAssignedPage'))
const AgentTicketDetailPage = lazy(() => import('@/pages/agent/TicketDetailPage'))

// Manager
const AllTicketsPage      = lazy(() => import('@/pages/manager/AllTicketsPage'))
const ManagerDashboardPage = lazy(() => import('@/pages/manager/DashboardPage'))
const UsersPage           = lazy(() => import('@/pages/manager/UsersPage'))
const CategoriesPage      = lazy(() => import('@/pages/manager/CategoriesPage'))
const SlaPoliciesPage     = lazy(() => import('@/pages/manager/SlaPoliciesPage'))
const SettingsPage        = lazy(() => import('@/pages/SettingsPage'))

// Shared
const NotFoundPage = lazy(() => import('@/pages/NotFoundPage'))

interface JwtPayload {
  roles: string[]
}

function RequireAuth() {
  const { token, isLoading } = useAuth()
  if (isLoading) return null
  return token ? <Outlet /> : <Navigate to="/login" replace />
}

function RequireAgent() {
  const { hasRole, isLoading } = useAuth()
  if (isLoading) return null
  return hasRole('ROLE_AGENT') || hasRole('ROLE_MANAGER')
    ? <Outlet />
    : <Navigate to="/my-tickets" replace />
}

function RequireManager() {
  const { hasRole, isLoading } = useAuth()
  if (isLoading) return null
  return hasRole('ROLE_MANAGER') ? <Outlet /> : <Navigate to="/queue" replace />
}

function RequireReporter() {
  const { hasRole, isLoading } = useAuth()
  if (isLoading) return null
  return !hasRole('ROLE_AGENT') && !hasRole('ROLE_MANAGER')
    ? <Outlet />
    : <Navigate to="/queue" replace />
}

function RoleBasedHome() {
  const token = localStorage.getItem('token')
  if (!token) return <Navigate to="/login" replace />
  try {
    const { roles } = jwtDecode<JwtPayload>(token)
    if (roles.includes('ROLE_AGENT') || roles.includes('ROLE_MANAGER')) {
      return <Navigate to="/queue" replace />
    }
  } catch {}
  return <Navigate to="/my-tickets" replace />
}

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Suspense fallback={<PageLoader />}><LoginPage /></Suspense>,
  },
  {
    path: '/register',
    element: <Suspense fallback={<PageLoader />}><RegisterPage /></Suspense>,
  },
  {
    element: <RequireAuth />,
    children: [
      {
        element: (
          <Suspense fallback={<PageLoader />}>
            <AppLayout />
          </Suspense>
        ),
        children: [
          { index: true, element: <RoleBasedHome /> },
          {
            element: <RequireReporter />,
            children: [
              { path: 'my-tickets',     element: <MyTicketsPage /> },
              { path: 'my-tickets/new', element: <CreateTicketPage /> },
              { path: 'my-tickets/:id', element: <TicketDetailPage /> },
            ],
          },
          {
            element: <RequireAgent />,
            children: [
              { path: 'queue',       element: <AgentQueuePage /> },
              { path: 'my-assigned', element: <MyAssignedPage /> },
              { path: 'tickets/:id', element: <AgentTicketDetailPage /> },
            ],
          },
          {
            element: <RequireManager />,
            children: [
              { path: 'all-tickets',  element: <AllTicketsPage /> },
              { path: 'dashboard',    element: <ManagerDashboardPage /> },
              { path: 'users',        element: <UsersPage /> },
              { path: 'categories',   element: <CategoriesPage /> },
              { path: 'sla-policies', element: <SlaPoliciesPage /> },
              { path: 'settings/*',   element: <SettingsPage /> },
            ],
          },
          { path: '*', element: <NotFoundPage /> },
        ],
      },
    ],
  },
])
