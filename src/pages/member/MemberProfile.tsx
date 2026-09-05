import { useTranslation } from 'react-i18next'
import { Mail, MapPin, Phone } from 'lucide-react'
import {
  Avatar, Button, Card, CardBody, CardHeader, Field, Input, PageHeader, StatusBadge, useToast,
} from '@/components/ui'
import { LanguageToggle } from '@/components/LanguageToggle'
import { formatDate } from '@/lib/format'
import { savingsAccounts } from '@/mock/data'
import { useCurrentMember, useMemberId } from './useMember'

export default function MemberProfile() {
  const { t } = useTranslation()
  const toast = useToast()
  const memberId = useMemberId()
  const member = useCurrentMember()
  const account = savingsAccounts.find((a) => a.memberId === memberId)

  return (
    <>
      <PageHeader title={t('common.profile')} subtitle={member.memberNumber} />

      <div className="grid gap-4 lg:grid-cols-[300px_1fr]">
        <Card>
          <CardBody className="flex flex-col items-center text-center">
            <Avatar name={member.fullName} color={member.avatarColor} size="lg" />
            <p className="mt-3 font-display text-lg font-bold text-neutral-900">{member.fullName}</p>
            <StatusBadge status={member.status} label={t(`members.status.${member.status}`)} />
            <div className="mt-4 w-full space-y-2 text-left text-[13px] text-neutral-600">
              <p className="flex items-center gap-2"><Phone className="h-4 w-4 text-neutral-400" /> {member.phone}</p>
              <p className="flex items-center gap-2"><Mail className="h-4 w-4 text-neutral-400" /> {member.email}</p>
              <p className="flex items-center gap-2"><MapPin className="h-4 w-4 text-neutral-400" /> {member.address}</p>
            </div>
          </CardBody>
        </Card>

        <div className="flex flex-col gap-4">
          <Card>
            <CardHeader title={t('members.tabs.profile')} />
            <CardBody className="grid gap-4 sm:grid-cols-2">
              <Field label={t('members.fullName')}><Input defaultValue={member.fullName} /></Field>
              <Field label={t('common.phone')}><Input defaultValue={member.phone} /></Field>
              <Field label={t('common.email')}><Input defaultValue={member.email} /></Field>
              <Field label={t('members.dob')}><Input type="date" defaultValue={member.dateOfBirth} /></Field>
              <Field label={t('members.address')} className="sm:col-span-2"><Input defaultValue={member.address} /></Field>
              <Field label={t('members.nextOfKin')}><Input defaultValue={member.nextOfKin} /></Field>
              <Field label={t('members.nextOfKinPhone')}><Input defaultValue={member.nextOfKinPhone} /></Field>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title={t('common.details')} />
            <CardBody className="grid gap-4 sm:grid-cols-2 text-[13px]">
              <div><p className="text-[11px] uppercase text-neutral-400">{t('members.memberNumber')}</p><p className="font-medium">{member.memberNumber}</p></div>
              <div><p className="text-[11px] uppercase text-neutral-400">{t('savings.accountNumber')}</p><p className="font-medium">{account?.accountNumber ?? '—'}</p></div>
              <div><p className="text-[11px] uppercase text-neutral-400">{t('members.registrationDate')}</p><p className="font-medium">{formatDate(member.registrationDate)}</p></div>
              <div>
                <p className="text-[11px] uppercase text-neutral-400">{t('common.language')}</p>
                <div className="mt-1"><LanguageToggle /></div>
              </div>
            </CardBody>
          </Card>

          <div className="flex justify-end">
            <Button onClick={() => toast(t('common.saveChanges') + ' ✓')}>{t('common.saveChanges')}</Button>
          </div>
        </div>
      </div>
    </>
  )
}
