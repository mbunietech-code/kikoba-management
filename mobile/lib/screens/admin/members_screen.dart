import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MembersScreen extends StatefulWidget {
  const MembersScreen({super.key});
  @override
  State<MembersScreen> createState() => _MembersScreenState();
}

class _MembersScreenState extends State<MembersScreen> {
  String _q = '';
  String _status = 'all';

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    var rows = mock.members.where((m) {
      if (_status != 'all' && m.status != _status) return false;
      if (_q.isNotEmpty && !'${m.fullName} ${m.memberNumber} ${m.phone}'.toLowerCase().contains(_q.toLowerCase())) return false;
      return true;
    }).toList();

    return AppScaffold(
      title: t('members.title'),
      subtitle: t('members.subtitle'),
      currentRoute: '/admin/members',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/admin/members/new'),
        backgroundColor: K.primary800,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.person_add_alt_1_rounded),
        label: Text(t('members.addMember')),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Column(children: [
              TextField(
                onChanged: (v) => setState(() => _q = v),
                decoration: InputDecoration(hintText: t('common.searchPlaceholder'), prefixIcon: const Icon(Icons.search, size: 20)),
              ),
              const SizedBox(height: 8),
              SizedBox(
                height: 34,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    for (final s in ['all', 'active', 'pending', 'suspended', 'inactive', 'deceased'])
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(
                          label: Text(s == 'all' ? t('common.all') : t('members.status.$s')),
                          selected: _status == s,
                          onSelected: (_) => setState(() => _status = s),
                        ),
                      ),
                  ],
                ),
              ),
            ]),
          ),
          Expanded(
            child: rows.isEmpty
                ? EmptyState(title: t('common.noData'), hint: t('common.noDataHint'))
                : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 90),
                    itemCount: rows.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) => Reveal(delayMs: (i * 12).clamp(0, 200), child: _row(context, rows[i])),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _row(BuildContext context, Member m) {
    final shareVal = sumI(mock.shares.where((s) => s.memberId == m.id).map((s) => s.totalValue));
    final sav = mock.savingsAccounts.firstWhere((a) => a.memberId == m.id, orElse: () => SavingsAccount(id: '', memberId: '', accountNumber: '', balance: 0, status: '', openedAt: '')).balance;
    return KCard(
      onTap: () => context.go('/admin/members/${m.id}'),
      padding: const EdgeInsets.all(12),
      child: Row(children: [
        KAvatar(m.fullName, color: m.avatarColor, size: 42),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(m.fullName, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
            const SizedBox(height: 2),
            Text('${m.memberNumber} · ${m.phone}', style: const TextStyle(fontSize: 11.5, color: K.neutral400)),
            const SizedBox(height: 6),
            Row(children: [
              _chip(context.t('nav.shares'), money(shareVal, compact: true)),
              const SizedBox(width: 6),
              _chip(context.t('nav.savings'), money(sav, compact: true)),
            ]),
          ]),
        ),
        const SizedBox(width: 8),
        StatusBadge(m.status, label: context.t('members.status.${m.status}')),
      ]),
    );
  }

  Widget _chip(String k, String v) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(color: K.neutral100, borderRadius: BorderRadius.circular(6)),
        child: Text('$k $v', style: const TextStyle(fontSize: 10.5, color: K.neutral600, fontWeight: FontWeight.w500)),
      );
}
