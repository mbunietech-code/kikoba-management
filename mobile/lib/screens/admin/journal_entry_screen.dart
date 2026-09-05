import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class JournalEntryScreen extends StatelessWidget {
  const JournalEntryScreen({super.key, required this.id});
  final String id;
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('accounting.title'),
      showBackButton: true,
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
