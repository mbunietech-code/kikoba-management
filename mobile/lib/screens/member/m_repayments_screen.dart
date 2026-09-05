import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MRepaymentsScreen extends StatelessWidget {
  const MRepaymentsScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.repayments'),
      showBackButton: true,
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
