import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { motion } from 'motion/react'
import { Plus, Users } from 'lucide-react'
import {
  Badge, Button, Card, CardBody, Field, Input, Modal, PageHeader, Select, useToast,
} from '@/components/ui'
import { formatMoney, formatPercent } from '@/lib/format'
import { loanProducts } from '@/mock/data'

export default function LoanProductsPage() {
  const { t } = useTranslation()
  const toast = useToast()
  const [open, setOpen] = useState(false)

  return (
    <>
      <PageHeader
        title={t('products.title')}
        subtitle={t('products.subtitle')}
        actions={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setOpen(true)}>{t('products.addProduct')}</Button>}
      />

      <div className="grid gap-4 md:grid-cols-2">
        {loanProducts.map((p, i) => (
          <motion.div key={p.id} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <Card>
              <CardBody>
                <div className="flex items-start justify-between">
                  <div>
                    <h3 className="font-display text-base font-bold text-neutral-900">{p.name}</h3>
                    <p className="mt-0.5 text-[13px] text-neutral-500">{p.description}</p>
                  </div>
                  <Badge tone={p.status === 'active' ? 'success' : 'neutral'}>{p.status}</Badge>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[13px]">
                  <Row label={t('products.minAmount')} value={formatMoney(p.minAmount, { compact: true })} />
                  <Row label={t('products.maxAmount')} value={formatMoney(p.maxAmount, { compact: true })} />
                  <Row label={t('products.interestRate')} value={`${formatPercent(p.interestRate)} · ${t(`loans.method.${p.interestMethod}`)}`} />
                  <Row label={t('loans.period')} value={`${p.repaymentPeriod} ${t('loans.months')} · ${t(`loans.freq.${p.repaymentFrequency}`)}`} />
                  <Row label={t('products.processingFee')} value={formatPercent(p.processingFee)} />
                  <Row label={t('products.penaltyRate')} value={formatPercent(p.penaltyRate)} />
                  <Row label={t('products.minSavings')} value={formatMoney(p.minSavings, { compact: true })} />
                  <Row label={t('products.minShares')} value={`${p.minShares}`} />
                </div>

                <div className="mt-4 flex items-center justify-between border-t border-neutral-100 pt-3">
                  <span className="inline-flex items-center gap-1.5 text-[13px] text-neutral-500">
                    <Users className="h-4 w-4" /> {p.requiredGuarantors} {t('products.requiredGuarantors').toLowerCase()}
                  </span>
                  <Button size="sm" variant="outlined" onClick={() => setOpen(true)}>{t('common.edit')}</Button>
                </div>
              </CardBody>
            </Card>
          </motion.div>
        ))}
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={t('products.addProduct')}
        size="lg"
        footer={
          <>
            <Button variant="outlined" onClick={() => setOpen(false)}>{t('common.cancel')}</Button>
            <Button onClick={() => { setOpen(false); toast(t('common.save') + ' ✓') }}>{t('common.save')}</Button>
          </>
        }
      >
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label={t('common.name')} className="sm:col-span-2"><Input placeholder="e.g. School Fees Loan" /></Field>
          <Field label={t('products.minAmount')}><Input type="number" defaultValue={100000} /></Field>
          <Field label={t('products.maxAmount')}><Input type="number" defaultValue={5000000} /></Field>
          <Field label={t('products.interestRate')}><Input type="number" defaultValue={10} /></Field>
          <Field label={t('products.interestMethod')}>
            <Select><option value="reducing">{t('loans.method.reducing')}</option><option value="flat">{t('loans.method.flat')}</option></Select>
          </Field>
          <Field label={t('loans.period')}><Input type="number" defaultValue={6} /></Field>
          <Field label={t('loans.frequency')}>
            <Select>{(['weekly', 'biweekly', 'monthly', 'quarterly'] as const).map((f) => <option key={f} value={f}>{t(`loans.freq.${f}`)}</option>)}</Select>
          </Field>
          <Field label={t('products.processingFee')}><Input type="number" defaultValue={1} /></Field>
          <Field label={t('products.penaltyRate')}><Input type="number" defaultValue={5} /></Field>
          <Field label={t('products.minSavings')}><Input type="number" defaultValue={100000} /></Field>
          <Field label={t('products.requiredGuarantors')}><Input type="number" defaultValue={2} /></Field>
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
