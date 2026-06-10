import { Outlet, NavLink } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import { Button } from '@/components/ui/button'

export default function AppLayout() {
  const { currentUser, logout, hasRole } = useAuth()

  const isAgent = hasRole('ROLE_AGENT') || hasRole('ROLE_MANAGER')

  return (
    <div className="flex h-screen bg-background">
      <aside className="w-60 shrink-0 border-r bg-sidebar flex flex-col">
        <div className="p-4 border-b">
          <span className="font-semibold text-sidebar-foreground">HelpDesk Pro</span>
        </div>
        <nav className="flex-1 p-2 space-y-1">
          {isAgent ? (
            <>
              <SidebarLink to="/queue">Ticket Queue</SidebarLink>
              <SidebarLink to="/my-assigned">My Assigned</SidebarLink>
              {hasRole('ROLE_MANAGER') && (
                <SidebarLink to="/all-tickets">All Tickets</SidebarLink>
              )}
            </>
          ) : (
            <SidebarLink to="/my-tickets">My Tickets</SidebarLink>
          )}
          {hasRole('ROLE_MANAGER') && (
            <>
              <SidebarLink to="/dashboard">Dashboard</SidebarLink>
              <SidebarLink to="/users">Users</SidebarLink>
              <SidebarLink to="/categories">Categories</SidebarLink>
              <SidebarLink to="/sla-policies">SLA Policies</SidebarLink>
              <SidebarLink to="/settings">Settings</SidebarLink>
            </>
          )}
        </nav>
        <div className="p-3 border-t">
          <p className="text-xs text-muted-foreground truncate px-1 mb-2">{currentUser?.email}</p>
        </div>
      </aside>

      <div className="flex flex-col flex-1 min-w-0">
        <header className="h-14 border-b flex items-center justify-between px-6 shrink-0 bg-background">
          <span className="text-sm font-medium text-foreground">
            {currentUser?.fullName ?? currentUser?.email}
          </span>
          <Button variant="outline" size="sm" onClick={logout}>
            Sign out
          </Button>
        </header>
        <main className="flex-1 overflow-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

function SidebarLink({ to, children }: { to: string; children: React.ReactNode }) {
  return (
    <NavLink
      to={to}
      className={({ isActive }) =>
        `block px-3 py-2 rounded-md text-sm transition-colors ${
          isActive
            ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
            : 'text-sidebar-foreground hover:bg-sidebar-accent/50'
        }`
      }
    >
      {children}
    </NavLink>
  )
}
