import { useEffect, type ReactNode } from 'react'
import { cn } from '@/lib/utils'

interface DialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  children: ReactNode
}

interface DialogContentProps {
  title: string
  children: ReactNode
  footer?: ReactNode
}

export function Dialog({ open, onOpenChange, children }: DialogProps) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onOpenChange(false)
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onOpenChange])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div
        className="fixed inset-0 bg-black/50"
        aria-hidden="true"
        onClick={() => onOpenChange(false)}
      />
      <div role="dialog" aria-modal="true" className="relative z-10 w-full max-w-md">{children}</div>
    </div>
  )
}

export function DialogContent({ title, children, footer }: DialogContentProps) {
  return (
    <div className={cn('bg-background rounded-lg border shadow-lg p-6 space-y-4')}>
      <h2 className="text-lg font-semibold">{title}</h2>
      <div>{children}</div>
      {footer && <div className="flex justify-end gap-2 pt-2">{footer}</div>}
    </div>
  )
}
