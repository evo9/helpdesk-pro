import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom'
import { jwtDecode } from 'jwt-decode'
import { useAuth } from '@/contexts/AuthContext'
import AppLayout from '@/layouts/AppLayout'
import LoginPage from '@/pages/LoginPage'
import RegisterPage from '@/pages/RegisterPage'
import DashboardPage from '@/pages/DashboardPage'
import SettingsPage from '@/pages/SettingsPage'
import NotFoundPage from '@/pages/NotFoundPage'
import MyTicketsPage from '@/pages/reporter/MyTicketsPage'
import CreateTicketPage from '@/pages/reporter/CreateTicketPage'
import TicketDetailPage from '@/pages/reporter/TicketDetailPage'
import AgentQueuePage from '@/pages/agent/QueuePage'
import MyAssignedPage from '@/pages/agent/MyAssignedPage'
import AgentTicketDetailPage from '@/pages/agent/TicketDetailPage'
import AllTicketsPage from '@/pages/manager/AllTicketsPage'

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
    element: <LoginPage />,
  },
  {
    path: '/register',
    element: <RegisterPage />,
  },
  {
    element: <RequireAuth />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { index: true, element: <RoleBasedHome /> },
          {
            element: <RequireReporter />,
            children: [
              { path: 'my-tickets', element: <MyTicketsPage /> },
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
              { path: 'all-tickets', element: <AllTicketsPage /> },
              { path: 'dashboard',   element: <DashboardPage /> },
              { path: 'settings/*',  element: <SettingsPage /> },
            ],
          },
          { path: '*', element: <NotFoundPage /> },
        ],
      },
    ],
  },
])
