import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/format.dart';
import '../../data/mock_data.dart';
import '../../data/selectors.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';
import '_helpers.dart';

class MLoanApplyScreen extends StatefulWidget {
  const MLoanApplyScreen({super.key});
  @override
  State<MLoanApplyScreen> createState() => _MLoanApplyScreenState();
}

class _MLoanApplyScreenState extends State<MLoanApplyScreen> {
  String _productId = mock.loanProducts.first.id;
  double _amount = 1000000;
  double _period = 6;

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final id = currentMemberId(context);
    final pos = memberPosition(id);
    final product = mock.loanProducts.firstWhere((p) => p.id == _productId);

    final interest = (_amount * product.interestRate * _period / (100 * 12)).round();
    final fees = (_amount * product.processingFee / 100).round();
    final insurance = (_amount * product.insuranceFee / 100).round();
    final total = _amount.round() + interest + fees + insurance;
    final installment = (total / _period).round();

    final checks = <(String, bool)>[
      (t('members.status.active'), true),
      ('${t('products.minShares')}: ${product.minShares}', pos.shareQty >= product.minShares),
      ('${t('products.minSavings')}: ${money(product.minSavings, compact: true)}', pos.savingsBalance >= product.minSavings),
      ('${t('loans.status.overdue')} — ${t('common.no')}', !pos.loans.any((l) => l.status == 'overdue')),
      ('${t('loans.guarantorsRequired')}: ${product.requiredGuarantors}', true),
    ];
    final eligible = checks.every((c) => c.$2);

    return AppScaffold(
      title: t('member.applyLoan'),
      subtitle: t('loans.newApplication'),
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('loans.newApplication')),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _productId,
              decoration: InputDecoration(labelText: t('loans.product')),
              items: mock.loanProducts.map((p) => DropdownMenuItem(value: p.id, child: Text(p.name))).toList(),
              onChanged: (v) => setState(() => _productId = v!),
            ),
            const SizedBox(height: 16),
            Text('${t('loans.principal')}: ${money(_amount)}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            Slider(
              value: _amount.clamp(product.minAmount.toDouble(), product.maxAmount.toDouble()),
              min: product.minAmount.toDouble(),
              max: product.maxAmount.toDouble().clamp(product.minAmount.toDouble() + 1, 20000000),
              onChanged: (v) => setState(() => _amount = v),
            ),
            Text('${t('loans.period')}: ${_period.round()} ${t('loans.months')}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            Slider(value: _period, min: 1, max: 24, divisions: 23, onChanged: (v) => setState(() => _period = v)),
          ])),
          const SizedBox(height: 12),
          KCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            KCardHeader(title: t('loans.eligibility')),
            const SizedBox(height: 10),
            for (final c in checks)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(children: [
                  Icon(c.$2 ? Icons.check_circle : Icons.cancel, size: 17, color: c.$2 ? K.tertiary600 : K.danger),
                  const SizedBox(width: 8),
                  Expanded(child: Text(c.$1, style: TextStyle(fontSize: 12.5, color: c.$2 ? K.neutral600 : K.danger))),
                ]),
              ),
            const SizedBox(height: 4),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(color: eligible ? K.tertiary50 : const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
              child: Text(eligible ? t('loans.eligible') : t('loans.notEligible'),
                  style: TextStyle(fontWeight: FontWeight.w700, color: eligible ? K.tertiary700 : K.danger)),
            ),
          ])),
          const SizedBox(height: 12),
          KCard(child: Column(children: [
            KCardHeader(title: t('common.summary')),
            const SizedBox(height: 10),
            _row(t('loans.principal'), money(_amount)),
            _row(t('loans.interest'), money(interest)),
            _row(t('loans.fees'), money(fees + insurance)),
            const Divider(),
            _row(t('loans.totalRepayable'), money(total), bold: true),
            _row(t('loans.installment'), money(installment)),
          ])),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: FilledButton(
              onPressed: eligible ? () { toast(context, '${t('common.submit')} ✓'); context.go('/member/loans'); } : null,
              child: Text(t('common.submit')),
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(String k, String v, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(children: [
          Expanded(child: Text(k, style: TextStyle(fontSize: 12.5, color: bold ? K.neutral900 : K.neutral500, fontWeight: bold ? FontWeight.w700 : FontWeight.w400))),
          Text(v, style: TextStyle(fontSize: 12.5, fontWeight: bold ? FontWeight.w800 : FontWeight.w600)),
        ]),
      );
}
