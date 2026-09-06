import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Plus, Users } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, Field, Input, Modal, PageHeader, Select, useToast, EmptyState,
} from '@/components/ui'
import { RowActions } from '@/components/RowActions'
import { useApiQuery } from '@/lib/useApi'
import { createLoanProduct, deleteLoanProduct, listLoanProducts, updateLoanProduct } from '@/api'
import { formatMoney, formatPercent } from '@/lib/format'

const EMPTY = {
  name: '', description: '', minimum_amount: 100000, maximum_amount: 5000000, interest_rate: 10,
  interest_method: 'reducing', repayment_period: 6, repayment_frequency: 'monthly',
  processing_fee: 1, insurance_fee: 1, penalty_rate: 5, minimum_savings: 100000, minimum_shares: 10,
  required_guarantors: 2, status: 'active',
}

export default function LoanProductsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const { data, loading, error, refetch } = useApiQuery(() => listLoanProducts(), [])
  const products: any[] = data ?? []

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<any | null>(null)
  const [form, setForm] = useState<any>(EMPTY)
  const [busy, setBusy] = useState(false)

  function startCreate() {
    setEditing(null)
    setForm(EMPTY)
    setOpen(true)
  }
  function startEdit(p: any) {
    setEditing(p)
    setForm({
      name: p.name, description: p.description ?? '', minimum_amount: p.minAmount ?? p.minimumAmount,
      maximum_amount: p.maxAmount ?? p.maximumAmount, interest_rate: p.interestRate,
      interest_method: p.interestMethod, repayment_period: p.repaymentPeriod,
      repayment_frequency: p.repaymentFrequency, processing_fee: p.processingFee, insurance_fee: p.insuranceFee,
      penalty_rate: p.penaltyRate, minimum_savings: p.minimumSavings, minimum_shares: p.minimumShares,
      required_guarantors: p.requiredGuarantors, status: p.status,
    })
    setOpen(true)
  }

  async function save() {
    setBusy(true)
    try {
      const body = { ...form }
      for (const k of ['minimum_amount', 'maximum_amount', 'repayment_period', 'minimum_savings', 'minimum_shares', 'required_guarantors']) body[k] = Number(body[k])
      for (const k of ['interest_rate', 'processing_fee', 'insurance_fee', 'penalty_rate']) body[k] = Number(body[k])
      if (editing) await updateLoanProduct(editing.id, body)
      else await createLoanProduct(body)
      toast(t('common.save') + ' ✓')
      setOpen(false)
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    } finally {
      setBusy(false)
    }
  }

  async function remove(p: any) {
    try {
      await deleteLoanProduct(p.id)
      toast(t('common.deleted'))
      refetch()
    } catch (e: any) {
      toast(e?.message ?? t('common.error'), 'error')
    }
  }

  return (
    <>
      <PageHeader
        title={t('products.title')}
        subtitle={t('products.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={startCreate}>{t('products.addProduct')}</Button>}
      />

      {error ? (
        <Card><EmptyState title={t('common.error')} hint={error.message} action={<Button onClick={refetch}>{t('common.retry')}</Button>} /></Card>
      ) : loading ? (
        <Card><EmptyState title={t('common.loading')} /></Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {products.map((p, i) => (
            <motion.div key={p.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
              <Card>
                <CardBody>
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="font-display text-base font-bold text-neutral-900">{p.name}</h3>
                      <p className="mt-0.5 text-[13px] text-neutral-500">{p.description}</p>
                    </div>
                    <div className="flex items-center gap-1">
                      <Badge tone={p.status === 'active' ? 'success' : 'neutral'}>{p.status}</Badge>
                      <RowActions onEdit={() => startEdit(p)} onDelete={() => remove(p)} deleteMessage={t('common.confirmDelete')} />
                    </div>
                  </div>

                  <div className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[13px]">
                    <Row label={t('products.minAmount')} value={formatMoney(p.minimumAmount, { compact: true })} />
                    <Row label={t('products.maxAmount')} value={formatMoney(p.maximumAmount, { compact: true })} />
                    <Row label={t('products.interestRate')} value={`${formatPercent(p.interestRate)} · ${t(`loans.method.${p.interestMethod}`)}`} />
                    <Row label={t('loans.period')} value={`${p.repaymentPeriod} ${t('loans.months')}`} />
                    <Row label={t('products.processingFee')} value={formatPercent(p.processingFee)} />
                    <Row label={t('products.minSavings')} value={formatMoney(p.minimumSavings, { compact: true })} />
                  </div>

                  <div className="mt-4 border-t border-neutral-100 pt-3">
                    <span className="inline-flex items-center gap-1.5 text-[13px] text-neutral-500">
                      <Users className="h-4 w-4" /> {p.requiredGuarantors} {t('products.requiredGuarantors').toLowerCase()}
                    </span>
                  </div>
                </CardBody>
              </Card>
            </motion.div>
          ))}
        </div>
      )}

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? `${t('common.edit')} — ${editing.name}` : t('products.addProduct')}
        size="lg"
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button loading={busy} onClick={save}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label={t('common.name')} className="sm:col-span-2"><Input value={form.name} onChange={(e) => setForm((f: any) => ({ ...f, name: e.target.value }))} /></Field>
          <Field label={t('common.description')} className="sm:col-span-2"><Input value={form.description} onChange={(e) => setForm((f: any) => ({ ...f, description: e.target.value }))} /></Field>
          <Field label={t('products.minAmount')}><Input type="number" value={form.minimum_amount} onChange={(e) => setForm((f: any) => ({ ...f, minimum_amount: e.target.value }))} /></Field>
          <Field label={t('products.maxAmount')}><Input type="number" value={form.maximum_amount} onChange={(e) => setForm((f: any) => ({ ...f, maximum_amount: e.target.value }))} /></Field>
          <Field label={t('products.interestRate')}><Input type="number" value={form.interest_rate} onChange={(e) => setForm((f: any) => ({ ...f, interest_rate: e.target.value }))} /></Field>
          <Field label={t('products.interestMethod')}>
            <Select value={form.interest_method} onChange={(e) => setForm((f: any) => ({ ...f, interest_method: e.target.value }))}>
              <option value="reducing">{t('loans.method.reducing')}</option>
              <option value="flat">{t('loans.method.flat')}</option>
            </Select>
          </Field>
          <Field label={t('loans.period')}><Input type="number" value={form.repayment_period} onChange={(e) => setForm((f: any) => ({ ...f, repayment_period: e.target.value }))} /></Field>
          <Field label={t('products.processingFee')}><Input type="number" value={form.processing_fee} onChange={(e) => setForm((f: any) => ({ ...f, processing_fee: e.target.value }))} /></Field>
          <Field label={t('products.penaltyRate')}><Input type="number" value={form.penalty_rate} onChange={(e) => setForm((f: any) => ({ ...f, penalty_rate: e.target.value }))} /></Field>
          <Field label={t('products.minSavings')}><Input type="number" value={form.minimum_savings} onChange={(e) => setForm((f: any) => ({ ...f, minimum_savings: e.target.value }))} /></Field>
          <Field label={t('products.minShares')}><Input type="number" value={form.minimum_shares} onChange={(e) => setForm((f: any) => ({ ...f, minimum_shares: e.target.value }))} /></Field>
          <Field label={t('products.requiredGuarantors')}><Input type="number" value={form.required_guarantors} onChange={(e) => setForm((f: any) => ({ ...f, required_guarantors: e.target.value }))} /></Field>
        </div>
      </Modal>
    </>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-[11px] uppercase tracking-wide text-neutral-400">{label}</p>
      <p className="mt-0.5 font-medium text-neutral-800">{value}</p>
    </div>
  )
}
