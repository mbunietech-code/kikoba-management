import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Check, X } from 'lucide-react'
import {
  Button, Card, CardBody, CardHeader, Field, Input, PageHeader, Select, useToast,
} from '@/components/ui'
import { formatMoney } from '@/lib/format'
import { activeMembers, loanProducts } from '@/mock/data'
import { memberPosition } from '@/mock/selectors'
import { useMemberId } from './useMember'
import { cn } from '@/lib/cn'

export default function MemberLoanApply() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const toast = useToast()
  const memberId = useMemberId()
  const pos = memberPosition(memberId)

  const [productId, setProductId] = useState(loanProducts[0].id)
  const [amount, setAmount] = useState(1_000_000)
  const [period, setPeriod] = useState(6)

  const product = loanProducts.find((p) => p.id === productId)!

  const calc = useMemo(() => {
    const interest = Math.round((amount * product.interestRate * period) / (100 * 12))
    const fees = Math.round((amount * product.processingFee) / 100)
    const insurance = Math.round((amount * product.insuranceFee) / 100)
    const total = amount + interest + fees + insurance
    return { interest, fees, insurance, total, installment: Math.round(total / period) }
  }, [amount, period, product])

  const checks = [
    { label: t('members.status.active'), ok: true },
    { label: `${t('products.minShares')}: ${product.minShares}`, ok: pos.shareQty >= product.minShares },
    { label: `${t('products.minSavings')}: ${formatMoney(product.minSavings, { compact: true })}`, ok: pos.savingsBalance >= product.minSavings },
    { label: t('loans.status.overdue') + ' — ' + t('common.no'), ok: !pos.loans.some((l) => l.status === 'overdue') },
    { label: `${t('loans.guarantorsRequired')}: ${product.requiredGuarantors}`, ok: true },
  ]
  const eligible = checks.every((c) => c.ok)

  return (
    <>
      <PageHeader
        breadcrumb={
          <button onClick={() => navigate('/member/loans')} className="inline-flex items-center gap-1 hover:text-neutral-700">
            <ArrowLeft className="h-3.5 w-3.5" /> {t('nav.myLoans')}
          </button>
        }
        title={t('member.applyLoan')}
        subtitle={t('loans.newApplication')}
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_360px]">
        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('loans.newApplication')} />
            <CardBody className="grid gap-4 sm:grid-cols-2">
              <Field label={t('loans.product')} className="sm:col-span-2">
                <Select value={productId} onChange={(e) => setProductId(e.target.value)}>
                  {loanProducts.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                </Select>
              </Field>
              <Field label={t('loans.principal')} hint={`${formatMoney(product.minAmount, { compact: true })}–${formatMoney(product.maxAmount, { compact: true })}`}>
                <Input type="number" value={amount} onChange={(e) => setAmount(Number(e.target.value))} min={product.minAmount} max={product.maxAmount} />
              </Field>
              <Field label={`${t('loans.period')} (${t('loans.months')})`}>
                <Input type="number" value={period} onChange={(e) => setPeriod(Number(e.target.value))} min={1} max={36} />
              </Field>
              <Field label={t('loans.purpose')} className="sm:col-span-2">
                <Input placeholder="e.g. Restock shop inventory" />
              </Field>
              <Field label={t('loans.frequency')}>
                <Select>{(['weekly', 'biweekly', 'monthly', 'quarterly'] as const).map((f) => <option key={f} value={f}>{t(`loans.freq.${f}`)}</option>)}</Select>
              </Field>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title={t('loans.guarantorsRequired')} subtitle={`${product.requiredGuarantors}`} />
            <CardBody className="grid gap-3">
              {Array.from({ length: product.requiredGuarantors }).map((_, i) => (
                <Select key={i} defaultValue="">
                  <option value="" disabled>{t('loans.addGuarantor')} #{i + 1}</option>
                  {activeMembers.filter((m) => m.id !== memberId).map((m) => <option key={m.id} value={m.id}>{m.fullName} — {m.memberNumber}</option>)}
                </Select>
              ))}
            </CardBody>
          </Card>
        </div>

        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('loans.eligibility')} />
            <CardBody>
              <ul className="space-y-2">
                {checks.map((c, i) => (
                  <li key={i} className="flex items-center gap-2 text-[13px]">
                    <span className={cn('flex h-5 w-5 items-center justify-center rounded-full', c.ok ? 'bg-tertiary-100 text-tertiary-700' : 'bg-red-100 text-danger')}>
                      {c.ok ? <Check className="h-3.5 w-3.5" /> : <X className="h-3.5 w-3.5" />}
                    </span>
                    <span className={c.ok ? 'text-neutral-600' : 'text-danger'}>{c.label}</span>
                  </li>
                ))}
              </ul>
              <div className={cn('mt-3 rounded-lg px-3 py-2 text-[13px] font-medium', eligible ? 'bg-tertiary-50 text-tertiary-700' : 'bg-red-50 text-danger')}>
                {eligible ? t('loans.eligible') : t('loans.notEligible')}
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title={t('common.summary')} />
            <CardBody className="space-y-2 text-[13px]">
              {[
                [t('loans.principal'), formatMoney(amount)],
                [t('loans.interest'), formatMoney(calc.interest)],
                [t('loans.fees'), formatMoney(calc.fees)],
                [t('loans.insuranceFee'), formatMoney(calc.insurance)],
              ].map(([k, v]) => (
                <div key={k} className="flex justify-between">
                  <span className="text-neutral-500">{k}</span>
                  <span className="font-medium tabular-nums">{v}</span>
                </div>
              ))}
              <div className="flex justify-between border-t border-neutral-100 pt-2">
                <span className="font-semibold">{t('loans.totalRepayable')}</span>
                <span className="font-bold tabular-nums">{formatMoney(calc.total)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-neutral-500">{t('loans.installment')}</span>
                <span className="font-medium tabular-nums">{formatMoney(calc.installment)}</span>
              </div>
            </CardBody>
          </Card>

          <Button
            size="lg"
            disabled={!eligible}
            onClick={() => { toast(t('common.submit') + ' ✓'); navigate('/member/loans') }}
          >
            {t('common.submit')}
          </Button>
        </div>
      </div>
    </>
  )
}
