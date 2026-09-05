import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';

class ForgotScreen extends StatefulWidget {
  const ForgotScreen({super.key});
  @override
  State<ForgotScreen> createState() => _ForgotScreenState();
}

class _ForgotScreenState extends State<ForgotScreen> {
  bool _sent = false;
  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return Scaffold(
      appBar: AppBar(),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: _sent
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 56, height: 56,
                    decoration: BoxDecoration(color: K.tertiary50, borderRadius: BorderRadius.circular(16)),
                    child: const Icon(Icons.mark_email_read_outlined, color: K.tertiary600),
                  ),
                  const SizedBox(height: 16),
                  Text(t('auth.sendResetLink'), style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 8),
                  Text(t('auth.forgotSubtitle'), textAlign: TextAlign.center, style: const TextStyle(color: K.neutral500)),
                  const SizedBox(height: 24),
                  OutlinedButton(onPressed: () => context.go('/login'), child: Text(t('auth.backToSignIn'))),
                ],
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(t('auth.forgotTitle'), style: Theme.of(context).textTheme.headlineSmall),
                  const SizedBox(height: 8),
                  Text(t('auth.forgotSubtitle'), style: const TextStyle(color: K.neutral500)),
                  const SizedBox(height: 24),
                  Text(t('common.email'), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700)),
                  const SizedBox(height: 6),
                  const TextField(keyboardType: TextInputType.emailAddress),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: FilledButton(onPressed: () => setState(() => _sent = true), child: Text(t('auth.sendResetLink'))),
                  ),
                ],
              ),
      ),
    );
  }
}
