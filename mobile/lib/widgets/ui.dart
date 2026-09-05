import 'package:flutter/material.dart';
import '../data/format.dart';
import '../data/mock_data.dart';
import '../i18n/strings.dart';
import '../theme/tokens.dart';

/* ------------------------------- Card ------------------------------- */
class KCard extends StatelessWidget {
  const KCard({super.key, required this.child, this.padding = const EdgeInsets.all(16), this.onTap});
  final Widget child;
  final EdgeInsets padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final body = Container(
      decoration: BoxDecoration(
        color: K.surface,
        borderRadius: BorderRadius.circular(K.radius),
        border: Border.all(color: K.neutral200),
        boxShadow: K.cardShadow,
      ),
      child: Padding(padding: padding, child: child),
    );
    if (onTap == null) return body;
    return InkWell(borderRadius: BorderRadius.circular(K.radius), onTap: onTap, child: body);
  }
}

class KCardHeader extends StatelessWidget {
  const KCardHeader({super.key, required this.title, this.subtitle, this.trailing});
  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15, color: K.neutral900)),
              if (subtitle != null) ...[
                const SizedBox(height: 2),
                Text(subtitle!, style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
              ],
            ],
          ),
        ),
        if (trailing != null) trailing!,
      ],
    );
  }
}

/* ------------------------------ Section ----------------------------- */
class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {super.key, this.trailing});
  final String text;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 10, top: 4),
        child: Row(children: [
          Expanded(child: Text(text, style: Theme.of(context).textTheme.titleMedium)),
          if (trailing != null) trailing!,
        ]),
      );
}

/* ------------------------------- Badge ------------------------------ */
enum Tone { neutral, primary, success, warning, danger, info, purple }

const _toneBg = {
  Tone.neutral: K.neutral100, Tone.primary: K.primary50, Tone.success: K.tertiary50,
  Tone.warning: Color(0xFFFFFBEB), Tone.danger: Color(0xFFFEF2F2), Tone.info: K.secondary50,
  Tone.purple: Color(0xFFF5F3FF),
};
const _toneFg = {
  Tone.neutral: K.neutral600, Tone.primary: K.primary700, Tone.success: K.tertiary700,
  Tone.warning: Color(0xFFB45309), Tone.danger: Color(0xFFB91C1C), Tone.info: K.secondary700,
  Tone.purple: Color(0xFF6D28D9),
};

class KBadge extends StatelessWidget {
  const KBadge(this.label, {super.key, this.tone = Tone.neutral, this.dot = false});
  final String label;
  final Tone tone;
  final bool dot;
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(color: _toneBg[tone], borderRadius: BorderRadius.circular(999)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        if (dot) ...[
          Container(width: 6, height: 6, decoration: BoxDecoration(color: _toneFg[tone], shape: BoxShape.circle)),
          const SizedBox(width: 5),
        ],
        Text(label, style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: _toneFg[tone])),
      ]),
    );
  }
}

const Map<String, Tone> statusTones = {
  'active': Tone.success, 'successful': Tone.success, 'paid': Tone.success, 'approved': Tone.success,
  'completed': Tone.success, 'confirmed': Tone.success, 'distributed': Tone.success, 'released': Tone.success,
  'pending': Tone.warning, 'submitted': Tone.warning, 'under_review': Tone.info, 'partial': Tone.warning,
  'calculated': Tone.info, 'draft': Tone.neutral, 'inactive': Tone.neutral, 'dormant': Tone.neutral,
  'closed': Tone.neutral, 'cancelled': Tone.neutral, 'disbursed': Tone.info, 'suspended': Tone.warning,
  'expired': Tone.neutral, 'overdue': Tone.danger, 'rejected': Tone.danger, 'failed': Tone.danger,
  'defaulted': Tone.danger, 'reversed': Tone.danger, 'deceased': Tone.neutral,
};

class StatusBadge extends StatelessWidget {
  const StatusBadge(this.status, {super.key, this.label});
  final String status;
  final String? label;
  @override
  Widget build(BuildContext context) =>
      KBadge(label ?? status.replaceAll('_', ' '), tone: statusTones[status] ?? Tone.neutral, dot: true);
}

/* ----------------------------- StatCard ----------------------------- */
class StatCard extends StatelessWidget {
  const StatCard({super.key, required this.label, required this.value, this.hint, this.delta, this.deltaUp = true, this.icon, this.tone = Tone.primary});
  final String label, value;
  final String? hint, delta;
  final bool deltaUp;
  final IconData? icon;
  final Tone tone;

  @override
  Widget build(BuildContext context) {
    return KCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Expanded(child: Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w500, color: K.neutral500))),
            if (icon != null)
              Container(
                width: 34, height: 34,
                decoration: BoxDecoration(color: _toneBg[tone], borderRadius: BorderRadius.circular(10)),
                child: Icon(icon, size: 17, color: _toneFg[tone]),
              ),
          ]),
          const SizedBox(height: 10),
          Text(value, style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w800, letterSpacing: -0.5, color: K.neutral900)),
          if (delta != null || hint != null) ...[
            const SizedBox(height: 6),
            Row(children: [
              if (delta != null) ...[
                Icon(deltaUp ? Icons.arrow_outward : Icons.south_east, size: 13, color: deltaUp ? K.tertiary600 : K.danger),
                const SizedBox(width: 2),
                Text(delta!, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: deltaUp ? K.tertiary700 : K.danger)),
                const SizedBox(width: 6),
              ],
              if (hint != null) Expanded(child: Text(hint!, style: const TextStyle(fontSize: 12, color: K.neutral400))),
            ]),
          ],
        ],
      ),
    );
  }
}

/* ----------------------------- InfoRow ------------------------------ */
class InfoGrid extends StatelessWidget {
  const InfoGrid(this.items, {super.key, this.columns = 2});
  final List<(String, Widget)> items;
  final int columns;
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < items.length; i += columns)
          Padding(
            padding: const EdgeInsets.only(bottom: 14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (var j = i; j < i + columns && j < items.length; j++)
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(items[j].$1.toUpperCase(),
                            style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, letterSpacing: 0.4, color: K.neutral400)),
                        const SizedBox(height: 3),
                        DefaultTextStyle(
                          style: const TextStyle(fontSize: 13.5, color: K.neutral800),
                          child: items[j].$2,
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
      ],
    );
  }
}

/* --------------------------- Progress bar --------------------------- */
class KProgress extends StatelessWidget {
  const KProgress(this.value, {super.key, this.tone = Tone.primary, this.showLabel = false});
  final double value;
  final Tone tone;
  final bool showLabel;
  @override
  Widget build(BuildContext context) {
    final p = (value.clamp(0, 100) / 100).toDouble();
    final color = switch (tone) {
      Tone.primary => K.primary600,
      Tone.success => K.tertiary600,
      Tone.info => K.secondary600,
      Tone.danger => K.danger,
      _ => K.primary600,
    };
    return Row(children: [
      Expanded(
        child: ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: 0, end: p),
            duration: const Duration(milliseconds: 550),
            curve: Curves.easeOutCubic,
            builder: (_, v, __) => LinearProgressIndicator(
              value: v, minHeight: 7, backgroundColor: K.neutral100, valueColor: AlwaysStoppedAnimation(color),
            ),
          ),
        ),
      ),
      if (showLabel) ...[
        const SizedBox(width: 8),
        SizedBox(width: 34, child: Text('${(p * 100).round()}%', textAlign: TextAlign.right, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: K.neutral500))),
      ],
    ]);
  }
}

/* ---------------------------- EmptyState ---------------------------- */
class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.title, this.hint, this.icon, this.action});
  final String title;
  final String? hint;
  final IconData? icon;
  final Widget? action;
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 44, horizontal: 24),
      child: Column(
        children: [
          Container(
            width: 48, height: 48,
            decoration: BoxDecoration(color: K.neutral100, borderRadius: BorderRadius.circular(14)),
            child: Icon(icon ?? Icons.inbox_outlined, color: K.neutral400),
          ),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(fontWeight: FontWeight.w700, color: K.neutral800)),
          if (hint != null) ...[
            const SizedBox(height: 4),
            Text(hint!, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12.5, color: K.neutral500)),
          ],
          if (action != null) ...[const SizedBox(height: 14), action!],
        ],
      ),
    );
  }
}

/* ------------------------- Language toggle ------------------------- */
class LangToggle extends StatelessWidget {
  const LangToggle({super.key});
  @override
  Widget build(BuildContext context) {
    final loc = context.locale;
    return Container(
      decoration: BoxDecoration(color: K.neutral50, borderRadius: BorderRadius.circular(9), border: Border.all(color: K.neutral200)),
      padding: const EdgeInsets.all(2),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        for (final l in const [('en', 'EN'), ('sw', 'SW')])
          GestureDetector(
            onTap: () => loc.set(l.$1),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: loc.code == l.$1 ? Colors.white : Colors.transparent,
                borderRadius: BorderRadius.circular(7),
                boxShadow: loc.code == l.$1 ? [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 4)] : null,
              ),
              child: Text(l.$2, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: loc.code == l.$1 ? K.primary700 : K.neutral500)),
            ),
          ),
      ]),
    );
  }
}

/* --------------------------- Member tile --------------------------- */
class KAvatar extends StatelessWidget {
  const KAvatar(this.name, {super.key, this.color, this.size = 38});
  final String name;
  final Color? color;
  final double size;
  @override
  Widget build(BuildContext context) => Container(
        width: size, height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: color ?? K.primary800, shape: BoxShape.circle),
        child: Text(initials(name), style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: size * 0.36)),
      );
}

class MemberInline extends StatelessWidget {
  const MemberInline(this.memberId, {super.key, this.dense = false});
  final String memberId;
  final bool dense;
  @override
  Widget build(BuildContext context) {
    final m = mock.memberById(memberId);
    if (m == null) return const Text('—');
    return Row(mainAxisSize: MainAxisSize.min, children: [
      KAvatar(m.fullName, color: m.avatarColor, size: dense ? 30 : 36),
      const SizedBox(width: 10),
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(m.fullName, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: K.neutral800)),
          Text(m.memberNumber, style: const TextStyle(fontSize: 11, color: K.neutral400)),
        ],
      ),
    ]);
  }
}

/* ----------------------------- helpers ----------------------------- */
void toast(BuildContext context, String msg) {
  ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(content: Text(msg), duration: const Duration(seconds: 2)));
}

class ListDivider extends StatelessWidget {
  const ListDivider({super.key});
  @override
  Widget build(BuildContext context) => const Divider(height: 1, color: K.neutral100);
}

/// simple animated fade+slide for page content sections
class Reveal extends StatelessWidget {
  const Reveal({super.key, required this.child, this.delayMs = 0});
  final Widget child;
  final int delayMs;
  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 350 + delayMs),
      curve: Curves.easeOutCubic,
      builder: (_, v, c) => Opacity(
        opacity: v.clamp(0, 1),
        child: Transform.translate(offset: Offset(0, (1 - v) * 12), child: c),
      ),
      child: child,
    );
  }
}

Tone accountTypeTone(String type) => switch (type) {
      'asset' => Tone.primary,
      'liability' => Tone.info,
      'equity' => Tone.purple,
      'revenue' => Tone.success,
      _ => Tone.danger,
    };
