import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/ui.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController(text: 'admin@kikoba.co.tz');
  final _password = TextEditingController(text: 'demo1234');
  bool _loading = false;
  bool _obscure = true;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    setState(() => _loading = true);
    Future.delayed(const Duration(milliseconds: 500), () {
      if (!mounted) return;
      context.read<Session>().signIn(_email.text.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [K.primary900, K.primary800, K.primary700],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  const Align(alignment: Alignment.centerRight, child: LangToggle()),
                  const SizedBox(height: 28),
                  _brand(),
                  const SizedBox(height: 30),
                  Reveal(
                    child: Container(
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: K.popShadow),
                      padding: const EdgeInsets.all(22),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(t('auth.emailOrPhone'), style: _label),
                          const SizedBox(height: 6),
                          TextField(controller: _email, keyboardType: TextInputType.emailAddress),
                          const SizedBox(height: 16),
                          Text(t('auth.password'), style: _label),
                          const SizedBox(height: 6),
                          TextField(
                            controller: _password,
                            obscureText: _obscure,
                            decoration: InputDecoration(
                              suffixIcon: IconButton(
                                icon: Icon(_obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 20),
                                onPressed: () => setState(() => _obscure = !_obscure),
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: () => context.push('/forgot-password'),
                              child: Text(t('auth.forgotPassword')),
                            ),
                          ),
                          const SizedBox(height: 4),
                          SizedBox(
                            height: 50,
                            child: FilledButton(
                              onPressed: _loading ? null : _submit,
                              child: _loading
                                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : Text(t('auth.signIn'), style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                            ),
                          ),
                          const SizedBox(height: 14),
                          Text(
                            t('auth.noAccount') + ' ' + t('auth.contactAdmin'),
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 12, color: K.neutral400),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Demo: admin@kikoba.co.tz → staff  ·  any other email → member',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 11, color: Colors.white54),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  static const _label = TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700);

  Widget _brand() {
    final t = context.t;
    return Column(
      children: [
        Container(
          width: 56, height: 56,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(18)),
          child: const Text('BK', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 20)),
        ),
        const SizedBox(height: 12),
        Text(t('app.name'), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 22, letterSpacing: -0.3)),
        const SizedBox(height: 4),
        Text(t('app.tagline'), style: const TextStyle(color: Colors.white70, fontSize: 13)),
      ],
    );
  }
}
