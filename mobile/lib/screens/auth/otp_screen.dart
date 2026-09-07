import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/api_client.dart';
import '../../i18n/strings.dart';
import '../../theme/tokens.dart';

class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key});
  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final _controllers = List.generate(6, (_) => TextEditingController());
  final _nodes = List.generate(6, (_) => FocusNode());
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final n in _nodes) {
      n.dispose();
    }
    super.dispose();
  }

  String get _code => _controllers.map((c) => c.text).join();

  Future<void> _verify() async {
    if (_code.length < 6) {
      setState(() => _error = 'Enter all 6 digits.');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<Session>().verifyOtp(_code);
      // router redirects on success
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'Verification failed. Try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = context.t;
    final session = context.watch<Session>();
    final target = session.pendingOtpDestination ?? '';
    final debug = session.pendingOtpDebugCode;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () {
            session.pendingOtpDestination = null;
            context.go('/login');
          },
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(t('auth.otpTitle'), style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text(t('auth.otpSubtitle', {'target': target}),
                style: const TextStyle(color: K.neutral500)),
            const SizedBox(height: 28),
            Row(
              children: List.generate(
                6,
                (i) => Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(right: i == 5 ? 0 : 8),
                    child: TextField(
                      controller: _controllers[i],
                      focusNode: _nodes[i],
                      textAlign: TextAlign.center,
                      maxLength: 1,
                      keyboardType: TextInputType.number,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                      decoration: const InputDecoration(counterText: ''),
                      onChanged: (v) {
                        if (v.isNotEmpty && i < 5) _nodes[i + 1].requestFocus();
                        if (v.isEmpty && i > 0) _nodes[i - 1].requestFocus();
                        setState(() => _error = null);
                        if (i == 5 && _code.length == 6) _verify();
                      },
                    ),
                  ),
                ),
              ),
            ),
            if (debug != null && debug.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text('Dev code: $debug', style: const TextStyle(fontSize: 12, color: K.neutral400)),
            ],
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: K.danger, fontSize: 12.5)),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: FilledButton(
                onPressed: _loading ? null : _verify,
                child: _loading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : Text(t('auth.otpVerify')),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
