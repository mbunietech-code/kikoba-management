import { useState } from 'react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-react'
import { Button, Menu, MenuItem, Modal } from '@/components/ui'

interface RowActionsProps {
  onEdit?: () => void
  onDelete?: () => Promise<void> | void
  deleteLabel?: string
  deleteMessage?: ReactNode
  extra?: (close: () => void) => ReactNode
}

export function RowActions({ onEdit, onDelete, deleteLabel, deleteMessage, extra }: RowActionsProps) {
  const { t } = useTranslation()
  const [confirming, setConfirming] = useState(false)
  const [busy, setBusy] = useState(false)

  async function doDelete() {
    if (!onDelete) return
    setBusy(true)
    try {
      await onDelete()
      setConfirming(false)
    } finally {
      setBusy(false)
    }
  }

  return (
    <span onClick={(e) => e.stopPropagation()}>
      <Menu
        trigger={
          <span className="flex h-8 w-8 items-center justify-center rounded-lg text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700">
            <MoreHorizontal className="h-4 w-4" />
          </span>
        }
      >
        {(close) => (
          <>
            {onEdit && (
              <MenuItem icon={<Pencil className="h-4 w-4" />} onClick={() => { close(); onEdit() }}>
                {t('common.edit')}
              </MenuItem>
            )}
            {extra?.(close)}
            {onDelete && (
              <MenuItem danger icon={<Trash2 className="h-4 w-4" />} onClick={() => { close(); setConfirming(true) }}>
                {deleteLabel ?? t('common.delete')}
              </MenuItem>
            )}
          </>
        )}
      </Menu>

      <Modal
        open={confirming}
        onClose={() => setConfirming(false)}
        title={deleteLabel ?? t('common.delete')}
        size="sm"
        footer={
          <>
            <Button variant="outlined" onClick={() => setConfirming(false)}>{t('common.cancel')}</Button>
            <Button variant="danger" loading={busy} onClick={doDelete}>{deleteLabel ?? t('common.delete')}</Button>
          </>
        }
      >
        <p className="text-sm text-neutral-600">
          {deleteMessage ?? t('common.confirmDelete')}
        </p>
      </Modal>
    </span>
  )
}
