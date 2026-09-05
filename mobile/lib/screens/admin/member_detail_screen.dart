import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MemberDetailScreen extends StatelessWidget {
  const MemberDetailScreen({super.key, required this.id});
  final String id;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final m = mock.memberById(id);
    if (m == null) {
      return AppScaffold(title: t('errors.notFound'), showBackButton: true, body: EmptyState(title: t('errors.notFound')));
    }
    final pos = memberPosition(id);
    final acc = mock.savingsAccounts.where((a) => a.memberId == id).firstOrNull;
    final shares = mock.shares.where((s) => s.memberId == id).toList();
    final loans = mock.loans.where((l) => l.memberId == id).toList();
    final ins = mock.insuranceAccounts.where((a) => a.memberId == id).firstOrNull;

    return AppScaffold(
      title: m.fullName,
      subtitle: m.memberNumber,
      showBackButton: true,
      actions: [
        IconButton(onPressed: () => context.go('/admin/members/$id/edit'), icon: const Icon(Icons.edit_outlined)),
      ],
      body: DefaultTabController(
        length: 4,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: KCard(
                child: Row(children: [
                  KAvatar(m.fullName, color: m.avatarColor, size: 52),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(m.fullName, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                      const SizedBox(height: 4),
                      StatusBadge(m.status, label: t('members.status.${m.status}')),
                      const SizedBox(height: 6),
                      Text(m.phone, style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
                    ]),
                  ),
                ]),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: Row(children: [
                Expanded(child: StatCard(label: t('member.shareValue'), value: money(pos.shareValue, compact: true))),
                const SizedBox(width: 10),
                Expanded(child: StatCard(label: t('member.savingsBalance'), value: money(pos.savingsBalance, compact: true), tone: Tone.info)),
                const SizedBox(width: 10),
                Expanded(child: StatCard(label: t('member.outstanding'), value: money(pos.loanOutstanding, compact: true), tone: Tone.neutral)),
              ]),
            ),
            TabBar(
              isScrollable: true,
              tabAlignment: TabAlignment.start,
              labelColor: K.primary700,
              unselectedLabelColor: K.neutral500,
              indicatorColor: K.primary600,
              tabs: [
                Tab(text: t('members.tabs.profile')),
                Tab(text: '${t('members.tabs.shares')} (${shares.length})'),
                Tab(text: '${t('members.tabs.loans')} (${loans.length})'),
                Tab(text: t('members.tabs.insurance')),
              ],
            ),
            Expanded(
              child: TabBarView(children: [
                ListView(padding: const EdgeInsets.all(16), children: [
                  KCard(
                    child: InfoGrid([
                      (t('members.memberNumber'), Text(m.memberNumber)),
                      (t('members.gender'), Text(t('members.${m.gender}'))),
                      (t('members.dob'), Text(fmtDate(m.dateOfBirth))),
                      (t('members.registrationDate'), Text(fmtDate(m.registrationDate))),
                      (t('members.address'), Text(m.address)),
                      (t('members.nextOfKin'), Text('${m.nextOfKin} · ${m.nextOfKinPhone}')),
                      (t('savings.accountNumber'), Text(acc?.accountNumber ?? '—')),
                      (t('common.email'), Text(m.email)),
                    ]),
                  ),
                ]),
                _list(shares.isEmpty
                    ? [EmptyState(title: t('common.noData'))]
                    : shares
                        .map((s) => KCard(
                              padding: const EdgeInsets.all(12),
                              child: Row(children: [
                                Expanded(child: Text(s.transactionRef, style: const TextStyle(fontSize: 12, color: K.neutral500))),
                                Text('${s.quantity} × ${money(s.pricePerShare, symbol: false)}', style: const TextStyle(fontSize: 12.5)),
                                const SizedBox(width: 10),
                                Text(money(s.totalValue), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                              ]),
                            ))
                        .toList()),
                _list(loans.isEmpty
                    ? [EmptyState(title: t('common.noData'))]
                    : loans
                        .map((l) => KCard(
                              onTap: () => context.go('/admin/loans/${l.id}'),
                              padding: const EdgeInsets.all(12),
                              child: Row(children: [
                                Expanded(
                                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                    Text(l.loanNumber, style: const TextStyle(fontWeight: FontWeight.w600, color: K.primary700)),
                                    Text(l.productName, style: const TextStyle(fontSize: 11.5, color: K.neutral400)),
                                  ]),
                                ),
                                Text(money(l.outstanding, compact: true), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                                const SizedBox(width: 8),
                                StatusBadge(l.status, label: t('loans.status.${l.status}')),
                              ]),
                            ))
                        .toList()),
                _list([
                  if (ins == null)
                    EmptyState(title: t('common.noData'))
                  else
                    KCard(
                      child: InfoGrid([
                        (t('insurance.planName'), Text(ins.planName)),
                        (t('insurance.monthlyContribution'), Text(money(ins.monthlyContribution))),
                        (t('insurance.coverageAmount'), Text(money(ins.coverageAmount))),
                        (t('insurance.totalContributions'), Text(money(ins.totalContributed))),
                        (t('insurance.endDate'), Text(fmtDate(ins.endDate))),
                        (t('common.status'), StatusBadge(ins.status, label: t('insurance.status.${ins.status}'))),
                      ]),
                    ),
                ]),
              ]),
            ),
          ],
        ),
      ),
    );
  }

  Widget _list(List<Widget> children) => ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: children.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) => children[i],
      );
}
