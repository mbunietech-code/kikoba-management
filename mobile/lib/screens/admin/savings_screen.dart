import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class SavingsScreen extends StatelessWidget {
  const SavingsScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('savings.title'),
      currentRoute: '/admin/savings',
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
