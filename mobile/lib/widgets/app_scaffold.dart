import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../app/nav.dart';
import '../app/session.dart';
import '../i18n/strings.dart';
import '../models/models.dart';
import '../theme/tokens.dart';
import 'ui.dart';

class AppScaffold extends StatelessWidget {
  const AppScaffold({
    super.key,
    required this.title,
    required this.body,
    this.subtitle,
    this.actions,
    this.currentRoute,
    this.floatingActionButton,
    this.showBackButton = false,
  });

  final String title;
  final String? subtitle;
  final Widget body;
  final List<Widget>? actions;
  final String? currentRoute;
  final Widget? floatingActionButton;
  final bool showBackButton;

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final t = context.t;
    final isStaff = session.isStaff;
    final tabs = isStaff ? adminTabs : memberTabs;

    int selectedIndex = tabs.indexWhere((d) => d.route == currentRoute);

    return Scaffold(
      appBar: AppBar(
        leading: showBackButton
            ? IconButton(icon: const Icon(Icons.arrow_back_rounded), onPressed: () => context.pop())
            : null,
        automaticallyImplyLeading: showBackButton,
        titleSpacing: showBackButton ? 0 : 16,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: K.neutral900)),
            if (subtitle != null)
              Text(subtitle!, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w400, color: K.neutral400)),
          ],
        ),
        actions: [
          ...?actions,
          if (currentRoute != null)
            IconButton(
              tooltip: t('common.refresh'),
              icon: session.hydrating
                  ? const SizedBox(
                      width: 16, height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2, color: K.neutral500))
                  : const Icon(Icons.refresh_rounded, size: 20),
              onPressed: session.hydrating ? null : () => session.refresh(),
            ),
          const Padding(padding: EdgeInsets.symmetric(horizontal: 4), child: LangToggle()),
          _ProfileButton(),
          const SizedBox(width: 6),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () => context.read<Session>().refresh(),
          child: body,
        ),
      ),
      floatingActionButton: floatingActionButton,
      bottomNavigationBar: currentRoute == null
          ? null
          : NavigationBar(
              selectedIndex: selectedIndex < 0 ? tabs.length : selectedIndex.clamp(0, tabs.length),
              onDestinationSelected: (i) {
                if (i < tabs.length) {
                  context.go(tabs[i].route);
                } else {
                  _openMore(context, isStaff);
                }
              },
              destinations: [
                for (final d in tabs) NavigationDestination(icon: Icon(d.icon), label: t(d.labelKey)),
                NavigationDestination(icon: const Icon(Icons.more_horiz_rounded), label: t('common.more')),
              ],
            ),
    );
  }

  void _openMore(BuildContext context, bool isStaff) {
    final t = context.t;
    final groups = isStaff ? adminMore : memberMore;
    final session = context.read<Session>();
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: K.surface,
      builder: (ctx) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.7,
        maxChildSize: 0.92,
        builder: (_, controller) => ListView(
          controller: controller,
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
          children: [
            for (final g in groups) ...[
              Padding(
                padding: const EdgeInsets.fromLTRB(4, 14, 4, 6),
                child: Text(t(g.$1).toUpperCase(),
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.5, color: K.neutral400)),
              ),
              for (final d in g.$2)
                ListTile(
                  dense: true,
                  leading: Icon(d.icon, size: 20, color: K.neutral500),
                  title: Text(t(d.labelKey), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
                  onTap: () {
                    Navigator.pop(ctx);
                    context.go(d.route);
                  },
                ),
            ],
            const Divider(height: 24),
            ListTile(
              leading: const Icon(Icons.logout_rounded, size: 20, color: K.danger),
              title: Text(t('common.logout'), style: const TextStyle(color: K.danger, fontWeight: FontWeight.w600)),
              onTap: () {
                Navigator.pop(ctx);
                session.signOut();
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfileButton extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final t = context.t;
    return PopupMenuButton<String>(
      offset: const Offset(0, 44),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      icon: KAvatar(session.name.isEmpty ? 'U' : session.name, color: K.neutral900, size: 30),
      onSelected: (v) {
        if (v == 'logout') {
          session.signOut();
        } else if (v == 'profile') {
          context.go(session.isStaff ? '/admin/settings' : '/member/profile');
        }
      },
      itemBuilder: (_) => [
        PopupMenuItem(
          enabled: false,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(session.name, style: const TextStyle(fontWeight: FontWeight.w600, color: K.neutral900)),
              Text(t('users.roles.${roleKey(session.role ?? Role.member)}'),
                  style: const TextStyle(fontSize: 12, color: K.neutral400)),
            ],
          ),
        ),
        const PopupMenuDivider(),
        PopupMenuItem(value: 'profile', child: Row(children: [const Icon(Icons.person_outline, size: 18), const SizedBox(width: 10), Text(t('common.profile'))])),
        PopupMenuItem(value: 'logout', child: Row(children: [const Icon(Icons.logout, size: 18, color: K.danger), const SizedBox(width: 10), Text(t('common.logout'), style: const TextStyle(color: K.danger))])),
      ],
    );
  }
}
