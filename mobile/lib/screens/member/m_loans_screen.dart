import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MLoansScreen extends StatelessWidget {
  const MLoansScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.myLoans'),
      currentRoute: '/member/loans',
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
