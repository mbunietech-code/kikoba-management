import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class SharesScreen extends StatelessWidget {
  const SharesScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('shares.title'),
      currentRoute: '/admin/shares',
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
