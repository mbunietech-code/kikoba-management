import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MSavingsScreen extends StatelessWidget {
  const MSavingsScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.mySavings'),
      currentRoute: '/member/savings',
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
