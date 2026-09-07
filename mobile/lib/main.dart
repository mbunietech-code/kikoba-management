import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'app/router.dart';
import 'app/session.dart';
import 'i18n/strings.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(statusBarColor: Colors.transparent, statusBarIconBrightness: Brightness.dark),
  );
  final session = Session();
  final locale = AppLocale('en');
  final app = BenjaKikobaApp(session: session, locale: locale);
  runApp(app);
  // Restore any saved session (token → /auth/me → hydrate) after first frame.
  session.bootstrap();
}

class BenjaKikobaApp extends StatelessWidget {
  const BenjaKikobaApp({super.key, required this.session, required this.locale});
  final Session session;
  final AppLocale locale;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider.value(
      value: session,
      child: LocaleScope(
        notifier: locale,
        child: Builder(
          builder: (context) {
            final router = buildRouter(session);
            return MaterialApp.router(
              title: 'Benja Kikoba',
              debugShowCheckedModeBanner: false,
              theme: buildTheme(),
              routerConfig: router,
            );
          },
        ),
      ),
    );
  }
}
