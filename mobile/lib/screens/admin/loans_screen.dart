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

const _tabFilters = <String, List<String>?>{
  'all': null,
  'applications': ['draft', 'submitted', 'under_review'],
  'active': ['approved', 'disbursed', 'active'],
  'overdue': ['overdue', 'defaulted'],
  'completed': ['completed', 'rejected', 'cancelled'],
};

class LoansScreen extends StatefulWidget {
  const LoansScreen({super.key});
  @override
  State<LoansScreen> createState() => _LoansScreenState();
}

class _LoansScreenState extends State<LoansScreen> {
  String _tab = 'all';
  String _q = '';

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final allowed = _tabFilters[_tab];
    final rows = mock.loans.where((l) {
      if (allowed != null && !allowed.contains(l.status)) return false;
      if (_q.isNotEmpty && !'${l.loanNumber} ${l.productName} ${l.purpose}'.toLowerCase().contains(_q.toLowerCase())) return false;
      return true;
    }).toList();

    final disbursed = sumI(mock.loans.where((l) => l.disbursementDate != null).map((l) => l.principal));
    final outstanding = sumI(mock.loans.map((l) => l.outstanding));
    final overdueAmt = sumI(mock.loans.where((l) => l.status == 'overdue' || l.status == 'defaulted').map((l) => l.outstanding));

    return AppScaffold(
      title: t('loans.title'),
      subtitle: t('loans.subtitle'),
      currentRoute: '/admin/loans',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Row(children: [
              Expanded(child: StatCard(label: t('dashboard.totalLoans'), value: money(disbursed, compact: true))),
              const SizedBox(width: 10),
              Expanded(child: StatCard(label: t('dashboard.outstandingLoans'), value: money(outstanding, compact: true), tone: Tone.neutral)),
              const SizedBox(width: 10),
              Expanded(child: StatCard(label: t('nav.overdue'), value: money(overdueAmt, compact: true), tone: Tone.danger)),
            ]),
          ),
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                for (final key in _tabFilters.keys)
                  Padding(
                    padding: const EdgeInsets.only(right: 8, top: 4),
                    child: ChoiceChip(
                      label: Text(key == 'all' ? t('common.all') : t('nav.$key')),
                      selected: _tab == key,
                      onSelected: (_) => setState(() => _tab = key),
                    ),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
            child: TextField(
              onChanged: (v) => setState(() => _q = v),
              decoration: InputDecoration(hintText: t('common.searchPlaceholder'), prefixIcon: const Icon(Icons.search, size: 20)),
            ),
          ),
          Expanded(
            child: rows.isEmpty
                ? EmptyState(title: t('common.noData'))
                : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                    itemCount: rows.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (_, i) => Reveal(delayMs: (i * 12).clamp(0, 180), child: _row(context, rows[i])),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _row(BuildContext context, Loan l) {
    final t = context.t;
    final progress = l.total == 0 ? 0.0 : l.amountPaid / l.total * 100;
    return KCard(
      onTap: () => context.go('/admin/loans/${l.id}'),
      padding: const EdgeInsets.all(12),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(l.loanNumber, style: const TextStyle(fontWeight: FontWeight.w700, color: K.primary700)),
              const SizedBox(height: 2),
              MemberInline(l.memberId, dense: true),
            ]),
          ),
          StatusBadge(l.status, label: t('loans.status.${l.status}')),
        ]),
        const SizedBox(height: 10),
        Row(children: [
          Expanded(child: _kv(t('loans.principal'), money(l.principal, compact: true))),
          Expanded(child: _kv(t('loans.outstanding'), money(l.outstanding, compact: true))),
          Expanded(child: _kv(t('loans.product'), l.productName)),
        ]),
        const SizedBox(height: 8),
        KProgress(progress, tone: l.status == 'overdue' ? Tone.danger : Tone.primary, showLabel: true),
      ]),
    );
  }

  Widget _kv(String k, String v) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(k, style: const TextStyle(fontSize: 10.5, color: K.neutral400)),
        Text(v, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
      ]);
}
