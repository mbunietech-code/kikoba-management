import 'package:flutter/widgets.dart';
import 'en.dart';
import 'sw.dart';

class AppLocale extends ChangeNotifier {
  AppLocale(this._code);
  String _code;
  String get code => _code;
  bool get isSwahili => _code == 'sw';

  void set(String code) {
    if (code == _code) return;
    _code = code;
    notifyListeners();
  }

  void toggle() => set(_code == 'en' ? 'sw' : 'en');
}

/// Dot-path translation lookup mirroring the web i18next keys.
class T {
  T(this.locale);
  final AppLocale locale;

  String call(String key, [Map<String, String>? vars]) {
    final table = locale.code == 'sw' ? swStrings : enStrings;
    dynamic node = table;
    for (final part in key.split('.')) {
      if (node is Map && node.containsKey(part)) {
        node = node[part];
      } else {
        // fall back to english
        dynamic en = enStrings;
        for (final p in key.split('.')) {
          if (en is Map && en.containsKey(p)) {
            en = en[p];
          } else {
            return key;
          }
        }
        node = en;
        break;
      }
    }
    if (node is! String) return key;
    var out = node;
    vars?.forEach((k, v) => out = out.replaceAll('{$k}', v));
    return out;
  }
}

extension TranslateContext on BuildContext {
  T get t {
    final loc = _LocaleScope.of(this);
    return T(loc);
  }

  AppLocale get locale => _LocaleScope.of(this);
}

class LocaleScope extends StatelessWidget {
  const LocaleScope({super.key, required this.notifier, required this.child});
  final AppLocale notifier;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: notifier,
      builder: (_, __) => _LocaleScope(notifier: notifier, child: child),
    );
  }
}

class _LocaleScope extends InheritedWidget {
  const _LocaleScope({required this.notifier, required super.child});
  final AppLocale notifier;

  static AppLocale of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<_LocaleScope>();
    assert(scope != null, 'LocaleScope missing');
    return scope!.notifier;
  }

  @override
  bool updateShouldNotify(_LocaleScope oldWidget) => oldWidget.notifier.code != notifier.code;
}
