import 'package:flutter/material.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class LoanProductsScreen extends StatelessWidget {
  const LoanProductsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return AppScaffold(
      title: t('products.title'),
      subtitle: t('products.subtitle'),
      showBackButton: true,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: K.primary800, foregroundColor: Colors.white,
        onPressed: () => toast(context, '${t('products.addProduct')} ✓'),
        icon: const Icon(Icons.add), label: Text(t('products.addProduct')),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
        children: [
          for (final p in mock.loanProducts)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(p.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    Text(p.description, style: const TextStyle(fontSize: 12, color: K.neutral500)),
                  ])),
                  KBadge(p.status, tone: p.status == 'active' ? Tone.success : Tone.neutral),
                ]),
                const SizedBox(height: 12),
                Wrap(spacing: 16, runSpacing: 10, children: [
                  _kv(t('products.minAmount'), money(p.minAmount, compact: true)),
                  _kv(t('products.maxAmount'), money(p.maxAmount, compact: true)),
                  _kv(t('products.interestRate'), '${pct(p.interestRate)} · ${t('loans.method.${p.interestMethod}')}'),
                  _kv(t('loans.period'), '${p.repaymentPeriod} ${t('loans.months')}'),
                  _kv(t('products.processingFee'), pct(p.processingFee)),
                  _kv(t('products.minSavings'), money(p.minSavings, compact: true)),
                  _kv(t('products.requiredGuarantors'), '${p.requiredGuarantors}'),
                ]),
              ])),
            ),
        ],
      ),
    );
  }

  Widget _kv(String k, String v) => SizedBox(
        width: 150,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(k.toUpperCase(), style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: K.neutral400)),
          Text(v, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral800)),
        ]),
      );
}
