import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = context.t;

    Widget group(String title, List<(String, String)> fields) => Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: KCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              KCardHeader(title: title),
              const SizedBox(height: 12),
              for (final f in fields)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(f.$1, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
                    const SizedBox(height: 6),
                    TextField(controller: TextEditingController(text: f.$2)),
                  ]),
                ),
            ]),
          ),
        );

    return AppScaffold(
      title: t('settings.title'),
      subtitle: t('settings.subtitle'),
      currentRoute: null,
      showBackButton: true,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          group(t('settings.tabs.organization'), [
            (t('settings.organizationName'), 'Benja Kikoba'),
            (t('settings.registrationNumber'), 'TZ-SACCO-2021-0473'),
            (t('settings.currency'), 'TZS — Tanzanian Shilling'),
            (t('common.email'), 'info@benjakikoba.co.tz'),
          ]),
          group(t('settings.tabs.financial'), [
            (t('settings.sharePrice'), '10000'),
            (t('settings.minShares'), '10'),
            (t('settings.minSavings'), '100000'),
            (t('settings.reserveRate'), '20'),
          ]),
          group(t('settings.tabs.loans'), [
            (t('settings.loanInterestRate'), '10'),
            (t('settings.penaltyRate'), '5'),
          ]),
          group(t('settings.tabs.insurance'), [
            (t('settings.insuranceContribution'), '20000'),
          ]),
          group(t('settings.tabs.integrations'), [
            (t('settings.smsGateway'), 'Beem Africa'),
            (t('settings.paymentGateway'), 'Selcom / M-Pesa'),
          ]),
          SizedBox(
            width: double.infinity,
            child: FilledButton(onPressed: () => toast(context, t('settings.saved')), child: Text(t('common.saveChanges'))),
          ),
        ],
      ),
    );
  }
}
