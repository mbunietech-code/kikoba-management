import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MTransactionsScreen extends StatelessWidget {
  const MTransactionsScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.transactions'),
      showBackButton: true,
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
