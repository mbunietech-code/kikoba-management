import 'package:intl/intl.dart';

String currencyCode = 'TZS';

final _n0 = NumberFormat('#,##0', 'en_US');
final _n1 = NumberFormat('#,##0.0', 'en_US');

String money(num v, {bool compact = false, bool symbol = true}) {
  final n = v;
  String s;
  if (compact) {
    if (n.abs() >= 1e9) {
      s = '${_n1.format(n / 1e9)}B';
    } else if (n.abs() >= 1e6) {
      s = '${_n1.format(n / 1e6)}M';
    } else if (n.abs() >= 1e3) {
      s = '${_n0.format(n / 1e3)}K';
    } else {
      s = _n0.format(n);
    }
  } else {
    s = _n0.format(n);
  }
  return symbol ? '$currencyCode $s' : s;
}

String num0(num v) => _n0.format(v);
String pct(num v, [int d = 1]) => '${v.toStringAsFixed(d)}%';

DateTime? _parse(String s) => DateTime.tryParse(s);

String fmtDate(String s, {String style = 'medium'}) {
  final d = _parse(s);
  if (d == null) return '—';
  return switch (style) {
    'short' => DateFormat('dd/MM/yyyy').format(d),
    'long' => DateFormat('EEEE, dd MMMM yyyy').format(d),
    _ => DateFormat('dd MMM yyyy').format(d),
  };
}

String fmtDateTime(String s) {
  final d = _parse(s);
  if (d == null) return '—';
  return DateFormat('dd MMM yyyy, HH:mm').format(d);
}

String relTime(String s) {
  final d = _parse(s);
  if (d == null) return '—';
  final diff = DateTime.now().difference(d);
  if (diff.inHours.abs() < 1) return '${diff.inMinutes.abs()}m ago';
  if (diff.inDays.abs() < 1) return '${diff.inHours.abs()}h ago';
  if (diff.inDays.abs() < 30) return '${diff.inDays.abs()}d ago';
  return fmtDate(s);
}

String initials(String name) {
  final parts = name.trim().split(RegExp(r'\s+')).where((e) => e.isNotEmpty).toList();
  return parts.take(2).map((p) => p[0].toUpperCase()).join();
}
