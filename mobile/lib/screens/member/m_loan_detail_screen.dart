import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MLoanDetailScreen extends StatelessWidget {
  const MLoanDetailScreen({super.key, required this.id});
  final String id;
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.myLoans'),
      showBackButton: true,
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
