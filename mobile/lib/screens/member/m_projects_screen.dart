import 'package:flutter/material.dart';
import '../../i18n/strings.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/ui.dart';

class MProjectsScreen extends StatelessWidget {
  const MProjectsScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: context.t('nav.myProjects'),
      currentRoute: '/member/projects',
      body: const Center(child: EmptyState(title: 'Screen in progress')),
    );
  }
}
