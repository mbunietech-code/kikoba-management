import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api_client.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';
import '../../widgets/ui.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final ok = await context.read<Session>().signIn(_email.text, _password.text);
      if (!mounted) return;
      if (!ok) context.go('/verify-otp');
      // on success the router redirects automatically
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'Could not sign in. Please try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final hydrating = context.select<Session, bool>((s) => s.hydrating);

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
          child: Stack(
            children: [
              Center(
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
                          decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              boxShadow: K.popShadow),
                          padding: const EdgeInsets.all(22),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(t('auth.emailOrPhone'), style: _label),
                              const SizedBox(height: 6),
                              TextField(
                                controller: _email,
                                keyboardType: TextInputType.emailAddress,
                                autocorrect: false,
                                enabled: !_loading,
                                onSubmitted: (_) => _submit(),
                              ),
                              const SizedBox(height: 16),
                              Text(t('auth.password'), style: _label),
                              const SizedBox(height: 6),
                              TextField(
                                controller: _password,
                                obscureText: _obscure,
                                enabled: !_loading,
                                onSubmitted: (_) => _submit(),
                                decoration: InputDecoration(
                                  suffixIcon: IconButton(
                                    icon: Icon(
                                        _obscure
                                            ? Icons.visibility_off_outlined
                                            : Icons.visibility_outlined,
                                        size: 20),
                                    onPressed: () => setState(() => _obscure = !_obscure),
                                  ),
                                ),
                              ),
                              if (_error != null) ...[
                                const SizedBox(height: 12),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                                  decoration: BoxDecoration(
                                    color: K.danger.withValues(alpha: 0.08),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Row(children: [
                                    const Icon(Icons.error_outline_rounded, size: 16, color: K.danger),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(_error!,
                                          style: const TextStyle(fontSize: 12.5, color: K.danger)),
                                    ),
                                  ]),
                                ),
                              ],
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
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                              strokeWidth: 2, color: Colors.white))
                                      : Text(t('auth.signIn'),
                                          style: const TextStyle(
                                              fontSize: 15, fontWeight: FontWeight.w700)),
                                ),
                              ),
                              const SizedBox(height: 14),
                              Text(
                                '${t('auth.noAccount')} ${t('auth.contactAdmin')}',
                                textAlign: TextAlign.center,
                                style: const TextStyle(fontSize: 12, color: K.neutral400),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (hydrating)
                Positioned.fill(
                  child: Container(
                    color: K.primary900.withValues(alpha: 0.55),
                    child: const Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)),
                          SizedBox(height: 12),
                          Text('Loading your data…',
                              style: TextStyle(color: Colors.white, fontSize: 13)),
                        ],
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  static const _label =
      TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: K.neutral700);

  Widget _brand() {
    final t = context.t;
    return Column(
      children: [
        Container(
          width: 56,
          height: 56,
          alignment: Alignment.center,
          decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(18)),
          child: const Text('BK',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 20)),
        ),
        const SizedBox(height: 12),
        Text(t('app.name'),
            style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 22,
                letterSpacing: -0.3)),
        const SizedBox(height: 4),
        Text(t('app.tagline'), style: const TextStyle(color: Colors.white70, fontSize: 13)),
      ],
    );
  }
}
