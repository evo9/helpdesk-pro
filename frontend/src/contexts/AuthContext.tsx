import { createContext, useContext, useState, useEffect, type ReactNode } from 'react'
import { jwtDecode } from 'jwt-decode'
import { login as apiLogin } from '@/api/auth'
import { getMe } from '@/api/users'
import type { User, Role } from '@/api/types'

interface JwtPayload {
  exp: number
  roles: string[]
}

interface AuthContextValue {
  token: string | null
  currentUser: User | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  hasRole: (role: Role) => boolean
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem('token'))
  const [currentUser, setCurrentUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(!!localStorage.getItem('token'))

  useEffect(() => {
    if (!token) {
      setCurrentUser(null)
      setIsLoading(false)
      return
    }

    try {
      const decoded = jwtDecode<JwtPayload>(token)
      if (decoded.exp * 1000 < Date.now()) {
        logout()
        return
      }
    } catch {
      logout()
      return
    }

    setIsLoading(true)
    getMe()
      .then(setCurrentUser)
      .catch(() => logout())
      .finally(() => setIsLoading(false))
  }, [token])

  async function login(email: string, password: string) {
    const { token: newToken } = await apiLogin(email, password)
    localStorage.setItem('token', newToken)
    setToken(newToken)
  }

  function logout() {
    localStorage.removeItem('token')
    setToken(null)
    setCurrentUser(null)
    window.location.href = '/login'
  }

  const ROLE_HIERARCHY: Record<Role, Role[]> = {
    ROLE_REPORTER: ['ROLE_REPORTER'],
    ROLE_AGENT:    ['ROLE_AGENT'],
    ROLE_MANAGER:  ['ROLE_MANAGER', 'ROLE_AGENT', 'ROLE_REPORTER'],
  }

  function hasRole(role: Role): boolean {
    if (!currentUser) return false
    return ROLE_HIERARCHY[currentUser.role as Role]?.includes(role) ?? false
  }

  return (
    <AuthContext.Provider value={{ token, currentUser, isLoading, login, logout, hasRole }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
